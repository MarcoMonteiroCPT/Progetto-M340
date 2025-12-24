<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

require "db.php";
include "templates/header.php";

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM vm_requests WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION["user"]["id"]]);
$vm = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vm) {
    echo "<div class='container alert alert-danger mt-3'>Richiesta non trovata o accesso negato.</div>";
    include "templates/footer.php";
    exit;
}

if ($vm['status'] !== 'done') {
    echo "<div class='container alert alert-warning mt-3'>La VM non è ancora stata creata. Stato: " . htmlspecialchars($vm['status']) . "</div>";
    include "templates/footer.php";
    exit;
}

$credentials = json_decode($vm['credentials'], true);
?>

<div class="container">
    <h2 class="mb-4">Dettagli VM: <?= htmlspecialchars($vm['vm_name']) ?></h2>

    <div class="alert alert-success">
        <strong>✓ VM creata con successo!</strong>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Informazioni VM</h5>
        </div>
        <div class="card-body">
            <table class="table table-borderless">
                <tr>
                    <th width="200">Nome VM:</th>
                    <td><strong><?= htmlspecialchars($vm['vm_name']) ?></strong></td>
                </tr>
                <tr>
                    <th>VMID:</th>
                    <td><?= htmlspecialchars($vm['vmid']) ?></td>
                </tr>
                <tr>
                    <th>Hostname:</th>
                    <td><?= htmlspecialchars($vm['hostname'] ?? $vm['vm_name']) ?></td>
                </tr>
                <tr>
                    <th>Indirizzo IP:</th>
                    <td><code><?= htmlspecialchars($vm['ip']) ?></code></td>
                </tr>
                <tr>
                    <th>Tipo:</th>
                    <td><span class="badge bg-secondary"><?= strtoupper($vm['type']) ?></span></td>
                </tr>
                <tr>
                    <th>Data creazione:</th>
                    <td><?= date("d/m/Y H:i:s", strtotime($vm['approved_at'])) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">⚠ Credenziali di accesso</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Importante:</strong> Salva queste credenziali in un luogo sicuro. Non verranno più mostrate.
            </div>

            <table class="table">
                <tr>
                    <th width="200">Username:</th>
                    <td><code><?= htmlspecialchars($credentials['user'] ?? 'root') ?></code></td>
                </tr>
                <tr>
                    <th>Password:</th>
                    <td>
                        <code id="password-display"><?= htmlspecialchars($credentials['password'] ?? 'N/A') ?></code>
                        <button class="btn btn-sm btn-secondary ms-2" onclick="copyToClipboard('<?= htmlspecialchars($credentials['password'] ?? '') ?>')">Copia</button>
                    </td>
                </tr>
                <tr>
                    <th>Comando SSH:</th>
                    <td>
                        <code id="ssh-command">ssh <?= htmlspecialchars($credentials['user'] ?? 'root') ?>@<?= htmlspecialchars($vm['ip']) ?></code>
                        <button class="btn btn-sm btn-secondary ms-2" onclick="copyToClipboard(document.getElementById('ssh-command').textContent)">Copia</button>
                    </td>
                </tr>
            </table>

            <?php if (isset($credentials['ssh_private_key']) && !empty($credentials['ssh_private_key'])): ?>
            <div class="mt-4">
                <h6>Chiave SSH Privata:</h6>
                <textarea class="form-control font-monospace" rows="10" readonly><?= htmlspecialchars($credentials['ssh_private_key']) ?></textarea>
                <small class="text-muted">Salva questa chiave in un file (es: ~/.ssh/vm_<?= $vm['vmid'] ?>_key) e usa: ssh -i ~/.ssh/vm_<?= $vm['vmid'] ?>_key root@<?= htmlspecialchars($vm['ip']) ?></small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-4">
        <a href="index.php" class="btn btn-primary">Torna alle richieste</a>
        <button class="btn btn-success" onclick="window.print()">Stampa credenziali</button>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copiato negli appunti!');
    }, function(err) {
        // Fallback per browser più vecchi
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Copiato negli appunti!');
    });
}
</script>

<?php include "templates/footer.php"; ?>

