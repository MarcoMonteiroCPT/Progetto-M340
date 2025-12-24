<?php
session_start();
require "db.php";

$error = "";

// Se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm"];

    // Controlli di base
    if (empty($username) || empty($password) || empty($confirm)) {
        $error = "Compila tutti i campi richiesti.";
    } elseif ($password !== $confirm) {
        $error = "Le password non coincidono.";
    } else {
        // Controlla se l'username esiste già
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $error = "Username già in uso.";
        } else {
            // Inserimento nel DB con password hashata
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'user', ?)");
            $stmt->execute([$username, $hash, $email]);

            // Redirect alla pagina di login
            header("Location: login.php?registered=1");
	    exit;
        }
    }
}

include "templates/header.php";
?>

<div class="container">
    <h2>Registrazione Utente</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-3">
        <label>Username:</label>
        <input type="text" name="username" class="form-control mb-2" required>

        <label>Email:</label>
        <input type="email" name="email" class="form-control mb-2">

        <label>Password:</label>
        <input type="password" name="password" class="form-control mb-2" required>

        <label>Conferma Password:</label>
        <input type="password" name="confirm" class="form-control mb-3" required>

        <button class="btn btn-primary">Registrati</button>
    </form>

    <p class="mt-3">Hai già un account? <a href="login.php">Accedi qui</a>.</p>
</div>

<?php include "templates/footer.php"; ?>
