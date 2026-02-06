<?php
$pageTitle = 'Connexion - Aéroport Minecraft';
require_once __DIR__ . '/../includes/functions.php';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin') {
        header('Location: /admin/dashboard.php');
    } elseif ($role === 'vip') {
        header('Location: /vip/dashboard.php');
    } else {
        header('Location: /member/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $minecraft = sanitize($_POST['pseudo_minecraft'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($minecraft) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE pseudo_minecraft = ?");
        $stmt->execute([$minecraft]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'approved') {
                $error = 'Votre compte est en attente de validation par un administrateur';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['pseudo_minecraft'] = $user['pseudo_minecraft'];
                
                // Rediriger selon le rôle
                if ($user['role'] === 'admin') {
                    header('Location: /admin/dashboard.php');
                } elseif ($user['role'] === 'vip') {
                    header('Location: /vip/dashboard.php');
                } else {
                    header('Location: /member/dashboard.php');
                }
                exit;
            }
        } else {
            $error = 'Pseudo Minecraft ou mot de passe incorrect';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="hero">
    <h1>🔐 Connexion</h1>
    <p>Accédez à votre espace personnel</p>
</div>

<div style="max-width: 500px; margin: 0 auto;">
    <div class="card">
        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="pseudo_minecraft">Pseudo Minecraft</label>
                <input type="text" id="pseudo_minecraft" name="pseudo_minecraft" class="form-control" required 
                       value="<?= htmlspecialchars($_POST['pseudo_minecraft'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
        
        <div style="text-align: center; margin-top: 2rem;">
            <p style="color: var(--gray);">Pas encore de compte ?</p>
            <a href="/register.php" class="btn btn-secondary">S'inscrire</a>
        </div>
        
        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(0, 217, 255, 0.2);">
            <p style="color: var(--gray); font-size: 0.9rem;">Vous êtes VIP ?</p>
            <p style="color: var(--primary); font-size: 0.9rem;">Utilisez vos identifiants normaux, votre statut VIP sera automatiquement reconnu.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
