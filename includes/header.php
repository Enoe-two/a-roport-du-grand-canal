<?php
// header.php — inclure functions.php UNE seule fois
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/functions.php';
}
$currentUser     = getCurrentUser();
$_unreadMessages = ($currentUser) ? unreadCount((int)$currentUser['id']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Aéroport du Grand Canal') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <div class="header-banner">
            <img src="/assets/images/banner.png" alt="Aéroport du Grand Canal" class="banner-img" onerror="this.style.display='none'">
        </div>
        <nav class="main-nav">
            <div class="nav-container">
                <a href="/index.php" class="nav-logo">✈️ Grand Canal Air</a>
                <button class="nav-toggle" id="navToggle" aria-label="Menu">☰</button>
                <ul class="nav-menu" id="navMenu">
                    <li><a href="/index.php">Accueil</a></li>
                    <li><a href="/horaire.php">Horaires</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="/reservation.php">Réservation</a></li>
                        <li><a href="/taxis.php">Taxis</a></li>
                        <?php if (hasRole('admin')): ?>
                            <li><a href="/admin/dashboard.php">⚙️ Admin</a></li>
                        <?php elseif (hasRole('vip')): ?>
                            <li><a href="/vip/dashboard.php">⭐ Espace VIP</a></li>
                        <?php else: ?>
                            <li><a href="/member/dashboard.php">Mon Espace</a></li>
                        <?php endif; ?>
                        <?php if ($_unreadMessages > 0): ?>
                            <?php
                            $msgLink = hasRole('admin') ? '/admin/messagerie.php'
                                     : (hasRole('vip') ? '/vip/messagerie.php' : '/member/messagerie.php');
                            ?>
                            <li><a href="<?= $msgLink ?>">💬 <span class="badge badge-danger"><?= $_unreadMessages ?></span></a></li>
                        <?php endif; ?>
                        <li><a href="/logout.php" class="btn-logout">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="/reservation.php">Réservation</a></li>
                        <li><a href="/taxis.php">Taxis</a></li>
                        <li><a href="/login.php" class="btn-login">Connexion</a></li>
                        <li><a href="/register.php" class="btn-register">S'inscrire</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <?php displayFlash(); ?>
