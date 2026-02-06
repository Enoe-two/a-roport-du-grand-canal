<?php
$pageTitle = 'Mon Dashboard - Aéroport Minecraft';
require_once __DIR__ . '/../../includes/functions.php';

requireVIP();

$currentUser = getCurrentUser();
$pdo = getDB();

// Récupérer les statistiques
$carte = getCarteStats($currentUser['id']);

// Compter les réservations
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status != 'annule'");
$stmt->execute([$currentUser['id']]);
$totalReservations = $stmt->fetchColumn();

// Compter les taxis
$stmt = $pdo->prepare("SELECT COUNT(*) FROM taxis WHERE user_id = ? AND status != 'annule'");
$stmt->execute([$currentUser['id']]);
$totalTaxis = $stmt->fetchColumn();

// Récupérer les messages non lus
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND is_read = 0");
$stmt->execute([$currentUser['id']]);
$messagesNonLus = $stmt->fetchColumn();

// Prochaines réservations
$stmt = $pdo->prepare("
    SELECT * FROM reservations 
    WHERE user_id = ? AND date_vol >= CURDATE() AND status != 'annule'
    ORDER BY date_vol ASC, heure_vol ASC
    LIMIT 5
");
$stmt->execute([$currentUser['id']]);
$prochaines = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <h1>👋 Bienvenue, <?= htmlspecialchars($currentUser['pseudo_minecraft']) ?> !</h1>
    <p>
        <?php if ($currentUser['role'] === 'vip'): ?>
            <span class="badge badge-vip">Membre VIP ⭐</span>
        <?php else: ?>
            Membre depuis <?= date('d/m/Y', strtotime($currentUser['created_at'])) ?>
        <?php endif; ?>
    </p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= $carte['vols_achetes'] ?></span>
        <span class="stat-label">Vols Achetés</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $carte['vols_utilises'] ?></span>
        <span class="stat-label">Vols Effectués</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $carte['taxis_achetes'] ?></span>
        <span class="stat-label">Taxis Commandés</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $messagesNonLus ?></span>
        <span class="stat-label">Messages Non Lus</span>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <h2>🚀 Actions Rapides</h2>
        <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
            <a href="/reservation.php" class="btn btn-primary">✈️ Réserver un vol</a>
            <a href="/taxis.php" class="btn btn-primary">🚕 Commander un taxi</a>
            <a href="/member/mes-reservations.php" class="btn btn-secondary">📋 Mes réservations</a>
            <a href="/member/messagerie.php" class="btn btn-secondary">
                💬 Messagerie
                <?php if ($messagesNonLus > 0): ?>
                    <span class="badge" style="background: var(--danger); margin-left: 0.5rem;"><?= $messagesNonLus ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
    
    <div class="card">
        <h2>🎫 Ma Carte de Membre</h2>
        <div style="background: linear-gradient(135deg, rgba(0, 217, 255, 0.2) 0%, rgba(255, 107, 53, 0.2) 100%); padding: 1.5rem; border-radius: 10px; margin-top: 1rem; border: 2px solid rgba(0, 217, 255, 0.3);">
            <p style="font-size: 1.1rem; font-weight: 700; color: var(--primary);">
                <?= htmlspecialchars($currentUser['pseudo_minecraft']) ?>
            </p>
            <p style="color: var(--gray); font-size: 0.9rem; margin: 0.5rem 0;">
                <?= htmlspecialchars($currentUser['pseudo_discord']) ?>
            </p>
            <p style="color: var(--gray); font-size: 0.9rem;">
                Faction: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $currentUser['faction']))) ?>
                <?php if ($currentUser['faction'] === 'autre' && $currentUser['faction_autre']): ?>
                    (<?= htmlspecialchars($currentUser['faction_autre']) ?>)
                <?php endif; ?>
            </p>
            
            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(0, 217, 255, 0.3);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.3rem;">Vols restants</p>
                        <p style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">
                            <?= max(0, $carte['vols_achetes'] - $carte['vols_utilises']) ?>
                        </p>
                    </div>
                    <div>
                        <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.3rem;">Taxis restants</p>
                        <p style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);">
                            <?= max(0, $carte['taxis_achetes'] - $carte['taxis_utilises']) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <a href="/member/ma-carte.php" class="btn btn-secondary btn-block" style="margin-top: 1rem;">
            Voir ma carte détaillée
        </a>
    </div>
</div>

<?php if (!empty($prochaines)): ?>
    <div class="card">
        <h2>📅 Prochaines Réservations</h2>
        <div class="table-container" style="margin-top: 1rem;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Type</th>
                        <th>Classe</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prochaines as $res): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($res['date_vol'])) ?></td>
                            <td><?= date('H:i', strtotime($res['heure_vol'])) ?></td>
                            <td>
                                <?php if ($res['type'] === 'vol_simple'): ?>
                                    🛫 Vol
                                <?php else: ?>
                                    📦 Cargaison
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
                            <td>
                                <?php if ($res['status'] === 'en_attente'): ?>
                                    <span class="badge badge-pending">En attente</span>
                                <?php elseif ($res['status'] === 'confirme'): ?>
                                    <span class="badge badge-approved">Confirmé</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/member/mes-reservations.php" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.9rem;">
                                    Détails
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="text-align: center;">
        <p style="color: var(--gray); font-size: 1.2rem;">
            Aucune réservation à venir
        </p>
        <a href="/reservation.php" class="btn btn-primary" style="margin-top: 1rem;">
            Réserver maintenant
        </a>
    </div>
<?php endif; ?>

<?php if ($currentUser['role'] !== 'vip'): ?>
    <div class="card" style="background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 107, 53, 0.1) 100%); border-color: #ffd700;">
        <h2 style="color: #ffd700;">⭐ Devenir VIP</h2>
        <p>Profitez de réductions exclusives et d'avantages premium !</p>
        <ul style="list-style: none; padding: 0; margin: 1rem 0;">
            <li>✓ -20% sur tous les vols</li>
            <li>✓ Accès à la classe VIP exclusive</li>
            <li>✓ 1 vol gratuit tous les 48h</li>
            <li>✓ Support prioritaire</li>
        </ul>
        <p style="color: var(--gray); font-size: 0.9rem;">
            Contactez un administrateur pour devenir VIP
        </p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
