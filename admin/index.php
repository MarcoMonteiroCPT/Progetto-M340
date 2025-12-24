<?php
session_start();
require "../db.php";
include "../templates/header.php";

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

// Richieste in attesa
$stmt = $pdo->prepare("SELECT vm_requests.*, users.username, users.email 
                       FROM vm_requests 
                       JOIN users ON users.id = vm_requests.user_id 
                       WHERE status='pending' 
                       ORDER BY requested_at ASC");
$stmt->execute();
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tutte le richieste
$stmt = $pdo->prepare("SELECT vm_requests.*, users.username 
                       FROM vm_requests 
                       JOIN users ON users.id = vm_requests.user_id 
                       ORDER BY requested_at DESC 
                       LIMIT 50");
$stmt->execute();
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">In attesa</span>',
        'approved' => '<span class="badge bg-info">Approvata</span>',
        'creating' => '<span class="badge bg-primary">Creazione...</span>',
        'done' => '<span class="badge bg-success">Completata</span>',
        'error' => '<span class="badge bg-danger">Errore</span>',
        'rejected' => '<span class="badge bg-secondary">Rifiutata</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
}
?>

<div class="container">
    <h2 class="mb-4">Pannello Amministratore</h2>

    <?php if (isset($_SESSION["success"])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION["success"]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION["success"]); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION["error"])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION["error"]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION["error"]); ?>
    <?php endif; ?>

    <h3 class="mt-4 mb-3">Richieste in attesa di approvazione</h3>
    <?php if (empty($pending)): ?>
        <div class="alert alert-info">Nessuna richiesta in attesa.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Utente</th>
                        <th>Nome VM</th>
                        <th>Tipo</th>
                        <th>Template</th>
                        <th>Data richiesta</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['username']) ?></td>
                        <td><strong><?= htmlspecialchars($r['vm_name']) ?></strong></td>
                        <td><span class="badge bg-secondary"><?= strtoupper($r['type']) ?></span></td>
                        <td><small><?= htmlspecialchars(basename($r['template'])) ?></small></td>
                        <td><?= date("d/m/Y H:i", strtotime($r['requested_at'])) ?></td>
                        <td>
                            <form action="approve.php" method="POST" class="d-inline">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">Approva</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $r['id'] ?>">Rifiuta</button>
                            
                            <!-- Modal per rifiuto -->
                            <div class="modal fade" id="rejectModal<?= $r['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Rifiuta richiesta</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="reject.php" method="POST">
                                            <div class="modal-body">
                                                <p>Sei sicuro di voler rifiutare la richiesta per <strong><?= htmlspecialchars($r['vm_name']) ?></strong>?</p>
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                <div class="mb-3">
                                                    <label for="notes<?= $r['id'] ?>" class="form-label">Motivo del rifiuto (opzionale):</label>
                                                    <textarea class="form-control" id="notes<?= $r['id'] ?>" name="notes" rows="3"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                                <button type="submit" class="btn btn-danger">Conferma rifiuto</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h3 class="mt-5 mb-3">Tutte le richieste</h3>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Utente</th>
                    <th>Nome VM</th>
                    <th>Tipo</th>
                    <th>Stato</th>
                    <th>Data richiesta</th>
                    <th>VMID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['username']) ?></td>
                    <td><?= htmlspecialchars($r['vm_name']) ?></td>
                    <td><span class="badge bg-secondary"><?= strtoupper($r['type']) ?></span></td>
                    <td><?= getStatusBadge($r['status']) ?></td>
                    <td><?= date("d/m/Y H:i", strtotime($r['requested_at'])) ?></td>
                    <td><?= $r['vmid'] ?: '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../templates/footer.php"; ?>