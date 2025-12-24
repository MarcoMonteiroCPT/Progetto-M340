<?php
session_start();
require "../db.php";
require "../config.php";

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

// Aggiorna lo stato a "creating"
$stmt = $pdo->prepare("UPDATE vm_requests SET status='creating', approved_by=?, approved_at=NOW() WHERE id=?");
$stmt->execute([$_SESSION["user"]["id"], $id]);

// ===== API PROXMOX ===== //
require "../proxmox/create_vm.php";

$result = create_vm($req);

if ($result['success']) {
    $ip = $result['ip'];
    $vmid = $result['vmid'];
    $hostname = $result['hostname'] ?? $req['vm_name'];
    $cred = json_encode($result['credentials']);

    $stmt = $pdo->prepare("UPDATE vm_requests SET status='done', ip=?, vmid=?, hostname=?, credentials=? WHERE id=?");
    $stmt->execute([$ip, $vmid, $hostname, $cred, $id]);
    
    $_SESSION["success"] = "VM creata con successo! VMID: $vmid";
} else {
    $error_msg = $result['error'] ?? 'Errore sconosciuto';
    $stmt = $pdo->prepare("UPDATE vm_requests SET status='error', notes=? WHERE id=?");
    $stmt->execute([$error_msg, $id]);
    
    $_SESSION["error"] = "Errore nella creazione della VM: " . htmlspecialchars($error_msg);
}

header("Location: index.php");
exit;