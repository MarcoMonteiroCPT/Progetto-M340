<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$USER = $_SESSION["user"] ?? null;

// Funzione helper per i percorsi
function url($path = '') {
    $current_dir = dirname($_SERVER['SCRIPT_NAME']);
    
    // Se siamo nella root
    if ($current_dir === '/' || $current_dir === '\\' || $current_dir === '.') {
        return '/' . ltrim($path, '/\\');
    }
    
    // Conta i livelli di profondità (es. /admin/ = 1 livello)
    $levels = substr_count(rtrim($current_dir, '/\\'), '/');
    
    if ($levels > 0) {
        // Torna indietro di N livelli
        return str_repeat('../', $levels) . ltrim($path, '/\\');
    }
    
    return '/' . ltrim($path, '/\\');
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Portal VM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1;
        }
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body>
<main>

<?php if ($USER): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= url('index.php') ?>">Portal VM</a>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('index.php') ?>">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('request_vm.php') ?>">Richiedi VM</a>
                </li>
                <?php if ($USER["role"] === "admin"): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('admin/index.php') ?>">Admin</a>
                </li>
                <?php endif; ?>
            </ul>

            <span class="navbar-text text-white me-3">
                <?= htmlspecialchars($USER["username"]) ?>
            </span>

            <a class="btn btn-outline-light" href="<?= url('logout.php') ?>">Logout</a>
        </div>
    </div>
</nav>
<?php endif; ?>
