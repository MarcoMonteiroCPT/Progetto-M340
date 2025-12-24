<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=vm_portal;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Errore connessione DB: " . $e->getMessage());
}
?>
