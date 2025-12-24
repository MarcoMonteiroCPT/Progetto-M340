<?php
/**
 * Script per verificare le caratteristiche di una VM creata
 * Apri nel browser: http://localhost/verify_vm.php?id=1
 * Oppure: http://localhost/verify_vm.php?vmid=100
 */

session_start();
require "db.php";
require "config.php";
require "proxmox/create_vm.php";

$vm_request = null;
$vm_info = null;
$error = "";

// Recupera ID richiesta o VMID
$request_id = $_GET['id'] ?? null;
$vmid = $_GET['vmid'] ?? null;

if ($request_id) {
    // Se è un utente normale, può vedere solo le sue VM
    if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
        $stmt = $pdo->prepare("SELECT * FROM vm_requests WHERE id = ? AND user_id = ?");
        $stmt->execute([$request_id, $_SESSION["user"]["id"] ?? 0]);
    } else {
        // Admin può vedere tutte le VM
        $stmt = $pdo->prepare("SELECT * FROM vm_requests WHERE id = ?");
        $stmt->execute([$request_id]);
    }
    $vm_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vm_request && $vm_request['vmid']) {
        $vmid = $vm_request['vmid'];
    }
}

if ($vmid) {
    // Recupera informazioni dalla VM su Proxmox
    $result = proxmox_api_call("/nodes/$PROX_NODE/lxc/$vmid/config");
    
    if ($result['success'] && isset($result['data']['data'])) {
        $vm_info = $result['data']['data'];
        
        // Recupera anche lo status
        $status_result = proxmox_api_call("/nodes/$PROX_NODE/lxc/$vmid/status/current");
        if ($status_result['success'] && isset($status_result['data']['data'])) {
            $vm_info['status'] = $status_result['data']['data'];
        }
    } else {
        $error = "VM non trovata su Proxmox o errore di connessione";
    }
} else {
    $error = "Specifica un ID richiesta (?id=1) o un VMID (?vmid=100)";
}

include "templates/header.php";
?>

<div class="container">
    <h2 class="mb-4">Verifica Caratteristiche VM</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <strong>Errore:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif ($vm_request && $vm_info): ?>
        
        <!-- Informazioni dalla richiesta -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Richiesta Originale</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="200">Nome VM:</th>
                        <td><strong><?= htmlspecialchars($vm_request['vm_name']) ?></strong></td>
                    </tr>
                    <tr>
                        <th>VMID:</th>
                        <td><?= htmlspecialchars($vm_request['vmid']) ?></td>
                    </tr>
                    <tr>
                        <th>Tipo richiesto:</th>
                        <td>
                            <span class="badge bg-secondary"><?= strtoupper($vm_request['type']) ?></span>
                            <?php
                            $expected = [
                                "bronze" => ["cores" => 1, "memory" => 1024, "disk" => 20],
                                "silver" => ["cores" => 2, "memory" => 4096, "disk" => 40],
                                "gold" => ["cores" => 4, "memory" => 8192, "disk" => 80]
                            ];
                            $expected_spec = $expected[$vm_request['type']] ?? null;
                            if ($expected_spec):
                            ?>
                            <small class="text-muted ms-2">
                                (Atteso: <?= $expected_spec['cores'] ?> core, 
                                <?= $expected_spec['memory'] ?>MB RAM, 
                                <?= $expected_spec['disk'] ?>GB disco)
                            </small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Template:</th>
                        <td><code><?= htmlspecialchars(basename($vm_request['template'])) ?></code></td>
                    </tr>
                    <tr>
                        <th>Stato:</th>
                        <td>
                            <?php
                            $status_badges = [
                                'pending' => '<span class="badge bg-warning">In attesa</span>',
                                'done' => '<span class="badge bg-success">Completata</span>',
                                'error' => '<span class="badge bg-danger">Errore</span>'
                            ];
                            echo $status_badges[$vm_request['status']] ?? $vm_request['status'];
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Informazioni dalla VM su Proxmox -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Caratteristiche Reali della VM (da Proxmox)</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Caratteristica</th>
                            <th>Valore Reale</th>
                            <th>Valore Atteso</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // CPU Cores
                        $real_cores = $vm_info['cores'] ?? 'N/A';
                        $expected_cores = $expected_spec['cores'] ?? 'N/A';
                        $cores_ok = $real_cores == $expected_cores;
                        ?>
                        <tr>
                            <td><strong>CPU Cores</strong></td>
                            <td><?= htmlspecialchars($real_cores) ?></td>
                            <td><?= htmlspecialchars($expected_cores) ?></td>
                            <td>
                                <?php if ($cores_ok): ?>
                                    <span class="badge bg-success">✓ OK</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✗ Diverso</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php
                        // Memory
                        $real_memory = isset($vm_info['memory']) ? intval($vm_info['memory']) : 0;
                        $expected_memory = $expected_spec['memory'] ?? 0;
                        $memory_ok = abs($real_memory - $expected_memory) < 100; // Tolleranza 100MB
                        ?>
                        <tr>
                            <td><strong>RAM (Memory)</strong></td>
                            <td><?= number_format($real_memory / 1024, 1) ?> GB (<?= number_format($real_memory) ?> MB)</td>
                            <td><?= number_format($expected_memory / 1024, 1) ?> GB (<?= number_format($expected_memory) ?> MB)</td>
                            <td>
                                <?php if ($memory_ok): ?>
                                    <span class="badge bg-success">✓ OK</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">✗ Diverso</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php
                        // Disk
                        $real_disk = 'N/A';
                        $expected_disk = $expected_spec['disk'] ?? 'N/A';
                        if (isset($vm_info['rootfs'])) {
                            // Estrai la dimensione da rootfs (es. "local-lvm:20" o "local-lvm:vm-100-disk-0,size=20G")
                            if (preg_match('/size=(\d+)G/i', $vm_info['rootfs'], $matches)) {
                                $real_disk = intval($matches[1]);
                            } elseif (preg_match('/:(\d+)$/', $vm_info['rootfs'], $matches)) {
                                $real_disk = intval($matches[1]);
                            } else {
                                $real_disk = $vm_info['rootfs'];
                            }
                        }
                        $disk_ok = is_numeric($real_disk) && is_numeric($expected_disk) && abs($real_disk - $expected_disk) < 1;
                        ?>
                        <tr>
                            <td><strong>Disco (RootFS)</strong></td>
                            <td>
                                <?php if (is_numeric($real_disk)): ?>
                                    <?= $real_disk ?> GB
                                <?php else: ?>
                                    <code><?= htmlspecialchars($real_disk) ?></code>
                                <?php endif; ?>
                            </td>
                            <td><?= $expected_disk ?> GB</td>
                            <td>
                                <?php if ($disk_ok): ?>
                                    <span class="badge bg-success">✓ OK</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">⚠ Verifica manuale</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php
                        // Hostname
                        $real_hostname = $vm_info['hostname'] ?? 'N/A';
                        $expected_hostname = $vm_request['vm_name'];
                        $hostname_ok = $real_hostname === $expected_hostname;
                        ?>
                        <tr>
                            <td><strong>Hostname</strong></td>
                            <td><?= htmlspecialchars($real_hostname) ?></td>
                            <td><?= htmlspecialchars($expected_hostname) ?></td>
                            <td>
                                <?php if ($hostname_ok): ?>
                                    <span class="badge bg-success">✓ OK</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">⚠ Diverso</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php
                        // Status
                        $vm_status = $vm_info['status']['status'] ?? 'unknown';
                        $vm_status_text = ucfirst($vm_status);
                        ?>
                        <tr>
                            <td><strong>Stato VM</strong></td>
                            <td colspan="3">
                                <span class="badge bg-<?= $vm_status === 'running' ? 'success' : ($vm_status === 'stopped' ? 'secondary' : 'warning') ?>">
                                    <?= htmlspecialchars($vm_status_text) ?>
                                </span>
                                <?php if (isset($vm_info['status']['uptime'])): ?>
                                    <small class="text-muted ms-2">
                                        Uptime: <?= gmdate("H:i:s", $vm_info['status']['uptime']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php if (isset($vm_info['status']['ip'])): ?>
                        <tr>
                            <td><strong>Indirizzo IP</strong></td>
                            <td colspan="3">
                                <code><?= htmlspecialchars($vm_info['status']['ip']) ?></code>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Configurazione completa -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Configurazione Completa (Raw)</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3" style="max-height: 400px; overflow: auto;"><code><?= htmlspecialchars(json_encode($vm_info, JSON_PRETTY_PRINT)) ?></code></pre>
            </div>
        </div>

    <?php elseif ($vm_request && !$vm_info): ?>
        <div class="alert alert-warning">
            <strong>VM non trovata su Proxmox</strong><br>
            La richiesta esiste ma la VM potrebbe non essere stata creata o è stata eliminata.
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <strong>Come usare questo script:</strong>
            <ul>
                <li>Con ID richiesta: <code>verify_vm.php?id=1</code></li>
                <li>Con VMID: <code>verify_vm.php?vmid=100</code></li>
            </ul>
        </div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="index.php" class="btn btn-primary">Torna alla Dashboard</a>
        <?php if ($vm_request): ?>
            <a href="vm_details.php?id=<?= $vm_request['id'] ?>" class="btn btn-secondary">Vedi Credenziali</a>
        <?php endif; ?>
    </div>
</div>

<?php include "templates/footer.php"; ?>






