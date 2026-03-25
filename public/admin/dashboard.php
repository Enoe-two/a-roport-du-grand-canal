<?php
$pageTitle = 'Dashboard Admin - Aéroport Minecraft';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$currentUser = getCurrentUser();
$pdo = getDB();

// Statistiques générales
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'approved'");
$totalUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
$pendingUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'vip'");
$vipUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status != 'annule'");
$totalReservations = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM reservations WHERE date_vol = CURDATE() AND status != 'annule'");
$reservationsToday = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM taxis WHERE status != 'annule'");
$totalTaxis = $stmt->fetchColumn();

// Messages non lus
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND is_read = 0");
$stmt->execute([$currentUser['id']]);
$messagesNonLus = $stmt->fetchColumn();

// Réservations récentes (tous types)
$stmt = $pdo->query("
    SELECT r.*, u.pseudo_minecraft, u.role
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    WHERE r.date_vol >= CURDATE()
    ORDER BY r.created_at DESC
    LIMIT 10
");
$recentReservations = $stmt->fetchAll();

// Comptes en attente
$stmt = $pdo->query("
    SELECT * FROM users 
    WHERE status = 'pending' 
    ORDER BY created_at DESC 
    LIMIT 5
");
$pendingAccounts = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="hero">
    <h1>👨‍💼 Dashboard Administrateur</h1>
    <p>Vue d'ensemble de l'aéroport</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= $totalUsers ?></span>
        <span class="stat-label">Membres Actifs</span>
    </div>
    
    <div class="stat-card" style="<?= $pendingUsers > 0 ? 'border-color: var(--warning);' : '' ?>">
        <span class="stat-value" style="<?= $pendingUsers > 0 ? 'color: var(--warning);' : '' ?>"><?= $pendingUsers ?></span>
        <span class="stat-label">Comptes en Attente</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $vipUsers ?></span>
        <span class="stat-label">Membres VIP</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $totalReservations ?></span>
        <span class="stat-label">Réservations Totales</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $reservationsToday ?></span>
        <span class="stat-label">Vols Aujourd'hui</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $totalTaxis ?></span>
        <span class="stat-label">Taxis Commandés</span>
    </div>
    
    <div class="stat-card" style="<?= $messagesNonLus > 0 ? 'border-color: var(--danger);' : '' ?>">
        <span class="stat-value" style="<?= $messagesNonLus > 0 ? 'color: var(--danger);' : '' ?>"><?= $messagesNonLus ?></span>
        <span class="stat-label">Messages Non Lus</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $totalUsers + $pendingUsers ?></span>
        <span class="stat-label">Total Comptes</span>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2>🚀 Actions Rapides</h2>
        <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
            <a href="/admin/tarifs.php" class="btn btn-primary" style="background:linear-gradient(135deg,var(--gold),#cc9900);color:var(--dark);">💰 Gérer les tarifs</a>
            <a href="/admin/comptes.php" class="btn btn-primary">
                👥 Gérer les comptes
                <?php if ($pendingUsers > 0): ?>
                    <span class="badge" style="background: var(--warning); margin-left: 0.5rem;"><?= $pendingUsers ?></span>
                <?php endif; ?>
            </a>
            <a href="/admin/reservations.php" class="btn btn-primary">📋 Voir les réservations</a>
            <a href="/admin/messagerie.php" class="btn btn-secondary">
                💬 Messagerie
                <?php if ($messagesNonLus > 0): ?>
                    <span class="badge" style="background: var(--danger); margin-left: 0.5rem;"><?= $messagesNonLus ?></span>
                <?php endif; ?>
            </a>
            <a href="/horaire.php" class="btn btn-secondary">📅 Horaires publics</a>
        </div>
    </div>
    
    <?php if (!empty($pendingAccounts)): ?>
    <div class="card" style="background: rgba(237, 137, 54, 0.1); border-color: var(--warning);">
        <h2 style="color: var(--warning);">⚠️ Comptes en Attente</h2>
        <div style="margin-top: 1rem;">
            <?php foreach ($pendingAccounts as $account): ?>
                <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px; margin-bottom: 0.8rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <p style="font-weight: 700; color: var(--light); margin: 0;">
                                <?= htmlspecialchars($account['pseudo_minecraft']) ?>
                            </p>
                            <p style="font-size: 0.9rem; color: var(--gray); margin: 0.2rem 0;">
                                Discord: <?= htmlspecialchars($account['pseudo_discord']) ?>
                            </p>
                            <p style="font-size: 0.85rem; color: var(--gray); margin: 0.2rem 0;">
                                <?= date('d/m/Y à H:i', strtotime($account['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <a href="/admin/comptes.php" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                Gérer tous les comptes
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="card" style="background: rgba(72, 187, 120, 0.1); border-color: var(--success);">
        <h2 style="color: var(--success);">✅ Aucun Compte en Attente</h2>
        <p style="color: var(--gray); margin-top: 1rem;">
            Tous les comptes ont été traités.
        </p>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($recentReservations)): ?>
<div class="card">
    <h2>📅 Réservations Récentes</h2>
    <div class="table-container" style="margin-top: 1rem;">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Classe</th>
                    <th>Quantité</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentReservations as $res): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($res['date_vol'] . ' ' . $res['heure_vol'])) ?></td>
                        <td>
                            <?= htmlspecialchars($res['pseudo_minecraft']) ?>
                            <?php if ($res['role'] === 'vip'): ?>
                                <span class="badge badge-vip">VIP</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($res['type'] === 'vol_simple'): ?>
                                🛫 Vol
                            <?php else: ?>
                                📦 Cargo
                            <?php endif; ?>
                            <?php if ($res['vol_masque']): ?>
                                <span style="color: var(--warning);">🔒</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if ($res['classe'] === 'vip') {
                                echo '<span class="badge badge-vip">VIP</span>';
                            } else {
                                echo $res['classe'] === '1' ? '1ère' : '2ème';
                            }
                            ?>
                        </td>
                        <td><?= $res['quantite'] ?></td>
                        <td>
                            <?php if ($res['status'] === 'en_attente'): ?>
                                <span class="badge badge-pending">En attente</span>
                            <?php elseif ($res['status'] === 'confirme'): ?>
                                <span class="badge badge-approved">Confirmé</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/admin/reservations.php" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.9rem;">
                                Gérer
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="/admin/reservations.php" class="btn btn-secondary btn-block" style="margin-top: 1rem;">
        Voir toutes les réservations
    </a>
</div>
<?php endif; ?>

<div class="grid grid-3">
    <div class="card" style="background: rgba(0, 217, 255, 0.05);">
        <h3 style="color: var(--primary);">💡 Astuce</h3>
        <p>Utilisez les filtres dans la page Réservations pour trouver rapidement les vols masqués ou en attente.</p>
    </div>
    
    <div class="card" style="background: rgba(255, 107, 53, 0.05);">
        <h3 style="color: var(--secondary);">📊 Statistiques</h3>
        <p>Consultez les cartes des membres pour voir leur historique d'achats et d'utilisation.</p>
    </div>
    
    <div class="card" style="background: rgba(72, 187, 120, 0.05);">
        <h3 style="color: var(--success);">✅ Gestion</h3>
        <p>N'oubliez pas de valider les nouveaux comptes et de répondre aux messages des membres.</p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
