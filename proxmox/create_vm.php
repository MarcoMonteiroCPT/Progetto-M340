<?php
function proxmox_api_call($endpoint, $method = 'GET', $data = null) {
    require __DIR__ . "/../config.php";
    
    $url = "https://$PROXMOX_HOST:8006/api2/json$endpoint";
    $ch = curl_init($url);
    
    $headers = [
        "Authorization: PVEAPIToken=$PROXMOX_TOKEN_ID=$PROXMOX_TOKEN_SECRET"
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $VERIFY_SSL);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $VERIFY_SSL ? 2 : 0);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ["success" => false, "error" => "Errore curl: $error"];
    }
    
    if ($http_code >= 400) {
        return ["success" => false, "error" => "Errore HTTP $http_code: $response"];
    }
    
    $result = json_decode($response, true);
    return ["success" => true, "data" => $result];
}

function generate_ssh_key() {
    // Genera una chiave SSH (solo privata per semplicità)
    // In produzione sarebbe meglio generare anche la pubblica e installarla
    $config = [
        "digest_alg" => "sha512",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];
    
    $res = openssl_pkey_new($config);
    if (!$res) {
        return null;
    }
    
    openssl_pkey_export($res, $private_key);
    $public_key = openssl_pkey_get_details($res)["key"];
    
    return [
        "private" => $private_key,
        "public" => $public_key
    ];
}

function get_available_storage() {
    require __DIR__ . "/../config.php";
    
    // Recupera la lista degli storage disponibili
    $result = proxmox_api_call("/nodes/$PROX_NODE/storage");
    
    if (!$result['success']) {
        return "local-lvm"; // Fallback
    }
    
    $storages = $result['data']['data'] ?? [];
    
    // Cerca uno storage che supporta container (content include 'rootdir' o 'images')
    foreach ($storages as $storage) {
        $content = $storage['content'] ?? '';
        $type = $storage['type'] ?? '';
        $storage_name = $storage['storage'] ?? '';
        
        // Storage che supportano container LXC
        // 'dir' type con 'rootdir' content, o 'lvm' type
        if ($type === 'dir' && strpos($content, 'rootdir') !== false) {
            return $storage_name;
        }
        if ($type === 'lvm' || $type === 'lvmthin') {
            return $storage_name;
        }
        if ($type === 'zfspool') {
            return $storage_name;
        }
    }
    
    // Fallback: prova local-lvm, poi local
    return "local-lvm";
}

function create_vm($req) {
    require __DIR__ . "/../config.php";

    $type = $req['type'];

    // Verifica che il tipo sia valido
    if (!isset($VM_TEMPLATES[$type])) {
        return ["success" => false, "error" => "Tipo VM non valido: $type"];
    }

    // Ottieni il VMID del template da clonare
    $template_vmid = $VM_TEMPLATES[$type];

    // Verifica che il template esista
    $check_result = proxmox_api_call("/nodes/$PROX_NODE/lxc/$template_vmid/config");
    if (!$check_result['success']) {
        return [
            "success" => false, 
            "error" => "Template VM $template_vmid (tipo $type) non trovato. Assicurati di aver creato i template su Proxmox."
        ];
    }

    // Ottieni un VMID disponibile per la nuova VM
    $result = proxmox_api_call("/cluster/nextid");
    if (!$result['success']) {
        return $result;
    }
    
    $vmid = isset($result['data']['data']) ? $result['data']['data'] : rand(1000, 9999);

    // Genera password e chiave SSH per la nuova VM
    $password = bin2hex(random_bytes(8));
    $ssh_key = generate_ssh_key();

    // Clona la VM template
    // Per LXC, usiamo la clonazione
    $clone_data = [
        "newid" => $vmid,
        "hostname" => $req['vm_name'],
        "target" => $PROX_NODE,  // Nodo di destinazione
        "full" => 0  // 0 = clone veloce (linked), 1 = clone completo
    ];

    $clone_result = proxmox_api_call("/nodes/$PROX_NODE/lxc/$template_vmid/clone", 'POST', $clone_data);
    
    if (!$clone_result['success']) {
        return $clone_result;
    }

    // Attendi che la clonazione sia completata
    sleep(5);

    // Aggiorna hostname e password della VM clonata
    $config_data = [
        "hostname" => $req['vm_name'],
        "password" => $password
    ];

    $config_result = proxmox_api_call("/nodes/$PROX_NODE/lxc/$vmid/config", 'POST', $config_data);
    
    // Avvia la VM
    $start_result = proxmox_api_call("/nodes/$PROX_NODE/lxc/$vmid/status/start", 'POST');
    
    // Attendi qualche secondo per l'avvio
    sleep(3);

    // Prova a recuperare l'IP della VM
    $ip = "DHCP - Controllare Proxmox";
    $ip_result = proxmox_api_call("/nodes/$PROX_NODE/lxc/$vmid/status/current");
    
    if ($ip_result['success'] && isset($ip_result['data']['data']['ip'])) {
        $ip = $ip_result['data']['data']['ip'];
    } else {
        // Prova a recuperare dalla configurazione di rete
        $config_check = proxmox_api_call("/nodes/$PROX_NODE/lxc/$vmid/config");
        if ($config_check['success'] && isset($config_check['data']['data']['net0'])) {
            preg_match('/ip=([\d.]+)/', $config_check['data']['data']['net0'], $matches);
            if (isset($matches[1])) {
                $ip = $matches[1];
            }
        }
    }

    // Prepara le credenziali
    $credentials = [
        "user" => "root",
        "password" => $password,
        "hostname" => $req['vm_name'],
        "vmid" => $vmid
    ];

    if ($ssh_key) {
        $credentials["ssh_private_key"] = $ssh_key['private'];
        $credentials["ssh_public_key"] = $ssh_key['public'];
    }

    return [
        "success" => true,
        "vmid" => $vmid,
        "ip" => $ip,
        "hostname" => $req['vm_name'],
        "credentials" => $credentials
    ];
}
?>