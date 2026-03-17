<?php
$pageTitle = 'Gestion des Comptes - Admin';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$pdo = getDB();
$success = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "Utilisateur introuvable";
    } elseif ($user['role'] === 'admin') {
        $error = "Impossible de modifier le compte administrateur";
    } else {
        if ($action === 'approuver') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $success = "Compte approuvé avec succès";
            }
        } elseif ($action === 'rejeter') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $success = "Compte rejeté";
            }
        } elseif ($action === 'vip') {
            $stmt = $pdo->prepare("UPDATE users SET role = 'vip' WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $success = "Utilisateur promu VIP";
            }
        } elseif ($action === 'member') {
            $stmt = $pdo->prepare("UPDATE users SET role = 'member' WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $success = "Utilisateur rétrogradé en membre";
            }
        } elseif ($action === 'supprimer') {
            // Vérifier qu'il y a d'autres admins avant de supprimer
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $success = "Compte supprimé";
            }
        }
    }
}

// Filtres
$filtre = $_GET['filtre'] ?? 'tous';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM users WHERE role != 'admin'";
$params = [];

if ($filtre === 'pending') {
    $sql .= " AND status = 'pending'";
} elseif ($filtre === 'approved') {
    $sql .= " AND status = 'approved'";
} elseif ($filtre === 'vip') {
    $sql .= " AND role = 'vip'";
} elseif ($filtre === 'rejected') {
    $sql .= " AND status = 'rejected'";
}

if (!empty($search)) {
    $sql .= " AND (pseudo_minecraft LIKE ? OR pseudo_discord LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Statistiques
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
$pendingCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'approved'");
$approvedCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'vip'");
$vipCount = $stmt->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <h1>👥 Gestion des Comptes</h1>
    <p>Validez, gérez et promouvez les utilisateurs</p>
</div>

<?php if ($success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card" style="<?= $pendingCount > 0 ? 'border-color: var(--warning);' : '' ?>">
        <span class="stat-value" style="<?= $pendingCount > 0 ? 'color: var(--warning);' : '' ?>"><?= $pendingCount ?></span>
        <span class="stat-label">En Attente</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $approvedCount ?></span>
        <span class="stat-label">Approuvés</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $vipCount ?></span>
        <span class="stat-label">VIP</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= count($users) ?></span>
        <span class="stat-label">Total Utilisateurs</span>
    </div>
</div>

<div class="card">
    <h2>🔍 Filtres et Recherche</h2>
    
    <form method="GET" style="margin-top: 1.5rem;">
        <div class="grid grid-2">
            <div class="form-group">
                <label for="filtre">Statut</label>
                <select name="filtre" id="filtre" class="form-control">
                    <option value="tous" <?= $filtre === 'tous' ? 'selected' : '' ?>>Tous</option>
                    <option value="pending" <?= $filtre === 'pending' ? 'selected' : '' ?>>En attente</option>
                    <option value="approved" <?= $filtre === 'approved' ? 'selected' : '' ?>>Approuvés</option>
                    <option value="vip" <?= $filtre === 'vip' ? 'selected' : '' ?>>VIP seulement</option>
                    <option value="rejected" <?= $filtre === 'rejected' ? 'selected' : '' ?>>Rejetés</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="search">Rechercher</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Pseudo Minecraft ou Discord" value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Appliquer</button>
        <a href="/admin/comptes.php" class="btn btn-secondary">Réinitialiser</a>
    </form>
</div>

<?php if (empty($users)): ?>
    <div class="card">
        <p style="text-align: center; color: var(--gray); padding: 3rem;">
            Aucun utilisateur trouvé.
        </p>
    </div>
<?php else: ?>
    <div class="card">
        <h2>📊 <?= count($users) ?> Utilisateur(s)</h2>
        
        <div style="margin-top: 1.5rem;">
            <?php foreach ($users as $user): ?>
                <div class="card" style="margin-bottom: 1rem; background: rgba(0, 217, 255, 0.05); border: 1px solid rgba(0, 217, 255, 0.2);">
                    <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1rem;">
                        <div style="flex: 1; min-width: 200px;">
                            <h3 style="color: var(--primary); margin: 0 0 0.5rem 0;">
                                <?= htmlspecialchars($user['pseudo_minecraft']) ?>
                                <?php if ($user['role'] === 'vip'): ?>
                                    <span class="badge badge-vip">VIP</span>
                                <?php endif; ?>
                            </h3>
                            
                            <div style="display: grid; gap: 0.5rem; font-size: 0.95rem;">
                                <div>
                                    <span style="color: var(--gray);">Discord:</span>
                                    <span style="color: var(--light);"><?= htmlspecialchars($user['pseudo_discord']) ?></span>
                                </div>
                                
                                <div>
                                    <span style="color: var(--gray);">Faction:</span>
                                    <span style="color: var(--light);">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user['faction']))) ?>
                                        <?php if ($user['faction'] === 'autre' && $user['faction_autre']): ?>
                                            - <?= htmlspecialchars($user['faction_autre']) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div>
                                    <span style="color: var(--gray);">Inscrit le:</span>
                                    <span style="color: var(--light);"><?= date('d/m/Y à H:i', strtotime($user['created_at'])) ?></span>
                                </div>
                                
                                <div>
                                    <span style="color: var(--gray);">Statut:</span>
                                    <?php if ($user['status'] === 'pending'): ?>
                                        <span class="badge badge-pending">En attente</span>
                                    <?php elseif ($user['status'] === 'approved'): ?>
                                        <span class="badge badge-approved">Approuvé</span>
                                    <?php elseif ($user['status'] === 'rejected'): ?>
                                        <span class="badge badge-cancelled">Rejeté</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; min-width: 200px;">
                            <?php if ($user['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="approuver">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-success btn-block">✅ Approuver</button>
                                </form>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Rejeter ce compte ?');">
                                    <input type="hidden" name="action" value="rejeter">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-block">❌ Rejeter</button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($user['status'] === 'approved'): ?>
                                <?php if ($user['role'] !== 'vip'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="vip">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-block">⭐ Promouvoir VIP</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="member">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-block">⬇️ Rétrograder</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('SUPPRIMER ce compte ? Toutes ses données seront perdues !');">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-block" style="background: linear-gradient(135deg, #c53030 0%, #742a2a 100%);">
                                    🗑️ Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card" style="background: rgba(0, 217, 255, 0.05);">
    <h3 style="color: var(--primary);">ℹ️ Guide de gestion des comptes</h3>
    <ul style="list-style: none; padding: 0; margin-top: 1rem;">
        <li style="margin: 0.8rem 0;">
            ✅ <strong>Approuver</strong> : Permet à l'utilisateur de se connecter et d'utiliser tous les services
        </li>
        <li style="margin: 0.8rem 0;">
            ⭐ <strong>Promouvoir VIP</strong> : Donne accès aux avantages VIP (-20%, classe VIP, vol gratuit tous les 48h)
        </li>
        <li style="margin: 0.8rem 0;">
            ⬇️ <strong>Rétrograder</strong> : Retire le statut VIP et revient au statut de membre normal
        </li>
        <li style="margin: 0.8rem 0;">
            ❌ <strong>Rejeter</strong> : Refuse l'inscription (le compte reste mais ne peut pas se connecter)
        </li>
        <li style="margin: 0.8rem 0;">
            🗑️ <strong>Supprimer</strong> : Supprime définitivement le compte et toutes ses données
        </li>
    </ul>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
