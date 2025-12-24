<?php
/**
 * Script per vedere i template disponibili su Proxmox
 * Apri nel browser: http://localhost/list_templates.php
 */

require "config.php";
require "proxmox/create_vm.php";

$templates = [];
$error = "";

// Recupera i template disponibili
$result = proxmox_api_call("/nodes/$PROX_NODE/storage/local/content");

if ($result['success'] && isset($result['data']['data'])) {
    $all_content = $result['data']['data'];
    
    // Filtra solo i template (vztmpl)
    foreach ($all_content as $item) {
        if (isset($item['content']) && $item['content'] === 'vztmpl') {
            $templates[] = [
                'volid' => $item['volid'],
                'size' => isset($item['size']) ? number_format($item['size'] / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A',
                'format' => $item['format'] ?? 'N/A'
            ];
        }
    }
} else {
    $error = "Errore nel recupero template: " . ($result['error'] ?? 'Errore sconosciuto');
}

include "templates/header.php";
?>

<div class="container">
    <h2 class="mb-4">Template Disponibili su Proxmox</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <strong>Errore:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif (empty($templates)): ?>
        <div class="alert alert-warning">
            <strong>Nessun template trovato!</strong>
            <p>Devi scaricare dei template su Proxmox:</p>
            <ol>
                <li>Vai su Proxmox Web UI → <strong>Datacenter</strong> → <strong>local</strong> → <strong>CT Templates</strong></li>
                <li>Clicca su <strong>Download</strong> o <strong>Templates</strong></li>
                <li>Scegli un template (es. Ubuntu, Debian, CentOS)</li>
                <li>Scarica e attendi il completamento</li>
                <li>Ricarica questa pagina</li>
            </ol>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Template LXC Disponibili</h5>
            </div>
            <div class="card-body">
                <p>Ecco i template disponibili. Usa il <strong>volid</strong> completo in <code>request_vm.php</code>:</p>
                
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>VolID (da usare nel codice)</th>
                            <th>Dimensione</th>
                            <th>Formato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $tpl): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($tpl['volid']) ?></code></td>
                            <td><?= htmlspecialchars($tpl['size']) ?></td>
                            <td><?= htmlspecialchars($tpl['format']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="alert alert-info mt-3">
                    <strong>Come aggiornare request_vm.php:</strong>
                    <ol class="mb-0">
                        <li>Apri <code>request_vm.php</code></li>
                        <li>Trova l'array <code>$templates</code></li>
                        <li>Sostituisci i template con quelli disponibili sopra</li>
                        <li>Usa il <strong>volid</strong> completo (es. <code>local:vztmpl/ubuntu-22.04-standard_22.04-1_amd64.tar.zst</code>)</li>
                    </ol>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="request_vm.php" class="btn btn-primary">Vai a Richiedi VM</a>
        <a href="index.php" class="btn btn-secondary">Dashboard</a>
    </div>
</div>

<?php include "templates/footer.php"; ?>






