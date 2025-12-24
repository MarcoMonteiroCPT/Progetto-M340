<?php
/**
 * Script per creare un utente amministratore
 * 
 * Uso: php create_admin.php
 * Oppure apri nel browser e compila il form
 */

require "db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm"] ?? "";

    if (empty($username) || empty($password)) {
        $error = "Username e password sono obbligatori.";
    } elseif ($password !== $confirm) {
        $error = "Le password non coincidono.";
    } elseif (strlen($password) < 6) {
        $error = "La password deve essere di almeno 6 caratteri.";
    } else {
        // Verifica se l'utente esiste già
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $error = "Username già esistente.";
        } else {
            // Crea l'hash della password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Inserisci l'utente admin
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$username, $hash, $email]);
            
            $success = "Utente amministratore creato con successo! Username: " . htmlspecialchars($username);
        }
    }
}

// Se eseguito da CLI
if (php_sapi_name() === 'cli') {
    if (isset($argv[1]) && $argv[1] === '--help') {
        echo "Uso: php create_admin.php\n";
        echo "Oppure apri nel browser per usare il form interattivo.\n";
        exit(0);
    }
    
    echo "=== Creazione Utente Amministratore ===\n";
    echo "Inserisci i dati:\n\n";
    
    $username = readline("Username: ");
    $email = readline("Email (opzionale): ");
    $password = readline("Password: ");
    $confirm = readline("Conferma password: ");
    
    if (empty($username) || empty($password)) {
        echo "Errore: Username e password sono obbligatori.\n";
        exit(1);
    }
    
    if ($password !== $confirm) {
        echo "Errore: Le password non coincidono.\n";
        exit(1);
    }
    
    if (strlen($password) < 6) {
        echo "Errore: La password deve essere di almeno 6 caratteri.\n";
        exit(1);
    }
    
    // Verifica se esiste già
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        echo "Errore: Username già esistente.\n";
        exit(1);
    }
    
    // Crea l'hash e inserisci
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin')");
    $stmt->execute([$username, $hash, $email]);
    
    echo "\n✓ Utente amministratore creato con successo!\n";
    echo "Username: $username\n";
    echo "Ruolo: admin\n";
    exit(0);
}

// Se eseguito dal browser, mostra il form
include "templates/header.php";
?>

<div class="container">
    <h2 class="mb-4">Crea Utente Amministratore</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <a href="login.php" class="btn btn-primary">Vai al login</a>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username:</label>
                        <input type="text" name="username" class="form-control" required 
                               value="<?= htmlspecialchars($_POST["username"] ?? "") ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email:</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($_POST["email"] ?? "") ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password:</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <small class="form-text text-muted">Minimo 6 caratteri</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Conferma Password:</label>
                        <input type="password" name="confirm" class="form-control" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary">Crea Admin</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include "templates/footer.php"; ?>

