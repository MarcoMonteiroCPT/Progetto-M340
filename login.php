<?php
session_start();
require "db.php";

$error = "";
$success = "";

// Controlla se provieni da registrazione
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Registrazione effettuata con successo! Ora puoi accedere.";
}

// Login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {
        $error = "Compila tutti i campi.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION["user"] = $user;
            header("Location: index.php");
            exit;
        } else {
            $error = "Username o password errati.";
        }
    }
}

include "templates/header.php";
?>

<div class="container">
    <h2>Login</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-3">
        <label>Username:</label>
        <input type="text" name="username" class="form-control mb-2" required>

        <label>Password:</label>
        <input type="password" name="password" class="form-control mb-3" required>

        <button class="btn btn-primary">Accedi</button>
    </form>

    <p class="mt-3">Non hai un account? <a href="register.php">Registrati qui</a>.</p>
</div>

<?php include "templates/footer.php"; ?>
