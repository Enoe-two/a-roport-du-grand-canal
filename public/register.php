<?php
$pageTitle = 'Inscription - Aéroport Minecraft';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discord = sanitize($_POST['pseudo_discord'] ?? '');
    $minecraft = sanitize($_POST['pseudo_minecraft'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $faction = $_POST['faction'] ?? '';
    $faction_autre = sanitize($_POST['faction_autre'] ?? '');
    
    // Validation
    if (empty($discord) || empty($minecraft) || empty($password) || empty($faction)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } elseif ($password !== $password_confirm) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } elseif ($faction === 'autre' && empty($faction_autre)) {
        $error = 'Veuillez préciser votre faction';
    } else {
        $pdo = getDB();
        
        // Vérifier si le pseudo Minecraft existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE pseudo_minecraft = ?");
        $stmt->execute([$minecraft]);
        if ($stmt->fetch()) {
            $error = 'Ce pseudo Minecraft est déjà utilisé';
        } else {
            // Créer le compte
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (pseudo_discord, pseudo_minecraft, password, faction, faction_autre, role, status) 
                VALUES (?, ?, ?, ?, ?, 'member', 'pending')
            ");
            
            if ($stmt->execute([$discord, $minecraft, $hashed_password, $faction, $faction_autre])) {
                $success = 'Votre compte a été créé avec succès ! Il sera activé après validation par un administrateur.';
            } else {
                $error = 'Une erreur est survenue lors de la création du compte';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="hero">
    <h1>📝 Inscription</h1>
    <p>Rejoignez la communauté de l'Aéroport Minecraft</p>
</div>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="flash flash-success">
                <?= htmlspecialchars($success) ?>
                <div style="margin-top: 1rem;">
                    <a href="/login.php" class="btn btn-primary">Aller à la connexion</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="pseudo_discord">Pseudo Discord *</label>
                    <input type="text" id="pseudo_discord" name="pseudo_discord" class="form-control" required
                           value="<?= htmlspecialchars($_POST['pseudo_discord'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="pseudo_minecraft">Pseudo Minecraft *</label>
                    <input type="text" id="pseudo_minecraft" name="pseudo_minecraft" class="form-control" required
                           value="<?= htmlspecialchars($_POST['pseudo_minecraft'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="6">
                    <small style="color: var(--gray);">Minimum 6 caractères</small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirmer le mot de passe *</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="faction">Faction *</label>
                    <select id="faction" name="faction" class="form-control" required onchange="toggleAutreFaction(this.value)">
                        <option value="">Sélectionnez votre faction</option>
                        <option value="scooby_empire" <?= ($_POST['faction'] ?? '') === 'scooby_empire' ? 'selected' : '' ?>>Scooby Empire</option>
                        <option value="blocaria" <?= ($_POST['faction'] ?? '') === 'blocaria' ? 'selected' : '' ?>>Blocaria</option>
                        <option value="gamelon_III" <?= ($_POST['faction'] ?? '') === 'gamelon_III' ? 'selected' : '' ?>>Gamelon III</option>
                        <option value="autre" <?= ($_POST['faction'] ?? '') === 'autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
                
                <div class="form-group" id="faction_autre_group" style="display: <?= ($_POST['faction'] ?? '') === 'autre' ? 'block' : 'none' ?>;">
                    <label for="faction_autre">Précisez votre faction</label>
                    <input type="text" id="faction_autre" name="faction_autre" class="form-control"
                           value="<?= htmlspecialchars($_POST['faction_autre'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">S'inscrire</button>
            </form>
            
            <div style="text-align: center; margin-top: 2rem;">
                <p style="color: var(--gray);">Vous avez déjà un compte ?</p>
                <a href="/login.php" class="btn btn-secondary">Se connecter</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleAutreFaction(value) {
    const autreFactionGroup = document.getElementById('faction_autre_group');
    if (value === 'autre') {
        autreFactionGroup.style.display = 'block';
        document.getElementById('faction_autre').required = true;
    } else {
        autreFactionGroup.style.display = 'none';
        document.getElementById('faction_autre').required = false;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
