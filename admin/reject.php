<?php
session_start();
require "../db.php";

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = $_POST['id'];

$stmt = $pdo->prepare("SELECT * FROM vm_requests WHERE id = ?");
$stmt->execute([$id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    $_SESSION["error"] = "Richiesta non trovata";
    header("Location: index.php");
    exit;
}

if ($req['status'] !== 'pending') {
    $_SESSION["error"] = "Questa richiesta è già stata processata";
    header("Location: index.php");
    exit;
}

$notes = $_POST['notes'] ?? 'Richiesta rifiutata dall\'amministratore';

$stmt = $pdo->prepare("UPDATE vm_requests SET status='rejected', approved_by=?, approved_at=NOW(), notes=? WHERE id=?");
$stmt->execute([$_SESSION["user"]["id"], $notes, $id]);

$_SESSION["success"] = "Richiesta rifiutata con successo";
header("Location: index.php");
exit;

