<?php
session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

require "db.php";
include "templates/header.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $vm_name = trim($_POST["vm_name"]);
    $type = $_POST["type"];

    if (empty($vm_name) || empty($type)) {
        $error = "Compila tutti i campi.";
    } else {
        // Verifica che il nome VM non sia già usato
        $stmt = $pdo->prepare("SELECT id FROM vm_requests WHERE vm_name = ?");
        $stmt->execute([$vm_name]);
        if ($stmt->rowCount() > 0) {
            $error = "Nome VM già utilizzato. Scegli un altro nome.";
        } else {
            // Il template viene determinato automaticamente dal tipo
            $stmt = $pdo->prepare("INSERT INTO vm_requests (user_id, vm_name, type, template, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION["user"]["id"], $vm_name, $type, $type]); // template = type
            $success = "Richiesta inviata con successo! Attendi l'approvazione dell'amministratore.";
        }
    }
}
?>

<div class="container">
    <h2>Richiedi una nuova VM</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-3">
        <div class="mb-3">
            <label class="form-label">Nome della VM:</label>
            <input type="text" name="vm_name" class="form-control" required 
                   pattern="[a-zA-Z0-9\-_]+" 
                   title="Solo lettere, numeri, trattini e underscore">
            <small class="form-text text-muted">Solo lettere, numeri, trattini e underscore</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Tipo di macchina:</label>
            <select name="type" class="form-select" required>
                <option value="">-- Seleziona tipo --</option>
                <option value="bronze">Bronze (1 core, 1GB RAM, 20GB disco)</option>
                <option value="silver">Silver (2 core, 4GB RAM, 40GB disco)</option>
                <option value="gold">Gold (4 core, 8GB RAM, 80GB disco)</option>
            </select>
        </div>


        <button type="submit" class="btn btn-success">Invia richiesta</button>
        <a href="index.php" class="btn btn-secondary">Annulla</a>
    </form>
</div>

<?php include "templates/footer.php"; ?>
