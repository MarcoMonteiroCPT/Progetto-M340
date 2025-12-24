<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

include "db.php";
include "templates/header.php";

$stmt = $pdo->prepare("SELECT * FROM vm_requests WHERE user_id=? ORDER BY requested_at DESC");
$stmt->execute([$_SESSION["user"]["id"]]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <h2 class="mb-4">Le tue richieste VM</h2>

    <?php if (empty($requests)): ?>
        <div class="alert alert-info">
            Non hai ancora inviato nessuna richiesta. 
            <a href="request_vm.php" class="alert-link">Crea una nuova richiesta</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome VM</th>
                        <th>Tipo</th>
                        <th>Template</th>
                        <th>Stato</th>
                        <th>Data richiesta</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= $r["id"] ?></td>
                        <td><strong><?= htmlspecialchars($r["vm_name"]) ?></strong></td>
                        <td>
                            <span class="badge bg-secondary">
                                <?= strtoupper(htmlspecialchars($r["type"])) ?>
                            </span>
                        </td>
                        <td><small><?= htmlspecialchars(basename($r["template"])) ?></small></td>
                        <td><?= getStatusBadge($r["status"]) ?></td>
                        <td><?= date("d/m/Y H:i", strtotime($r["requested_at"])) ?></td>
                        <td>
                            <?php if ($r["status"] === "done"): ?>
                                <a href="vm_details.php?id=<?= $r["id"] ?>" class="btn btn-sm btn-primary">Credenziali</a>
                                <a href="verify_vm.php?id=<?= $r["id"] ?>" class="btn btn-sm btn-info">Verifica</a>
                            <?php elseif ($r["status"] === "error" || $r["status"] === "rejected"): ?>
                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?= $r["id"] ?>">Dettagli</button>
                                <!-- Modal per dettagli errore/rifiuto -->
                                <div class="modal fade" id="detailsModal<?= $r["id"] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Dettagli richiesta #<?= $r["id"] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Stato:</strong> <?= getStatusBadge($r["status"]) ?></p>
                                                <?php if (!empty($r["notes"])): ?>
                                                    <p><strong>Note:</strong> <?= htmlspecialchars($r["notes"]) ?></p>
                                                <?php endif; ?>
                                                <?php if ($r["approved_at"]): ?>
                                                    <p><strong>Processata il:</strong> <?= date("d/m/Y H:i", strtotime($r["approved_at"])) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <a href="request_vm.php" class="btn btn-success">Nuova richiesta</a>
        </div>
    <?php endif; ?>
</div>

<?php include "templates/footer.php"; ?>
