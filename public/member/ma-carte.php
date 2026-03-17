<?php
$pageTitle = 'Ma Carte - Aéroport Minecraft';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$currentUser = getCurrentUser();
$pdo = getDB();

// Récupérer les statistiques de la carte
$carte = getCarteStats($currentUser['id']);

// Historique des réservations récentes
$stmt = $pdo->prepare("
    SELECT * FROM reservations 
    WHERE user_id = ? AND status != 'annule'
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$currentUser['id']]);
$historiqueVols = $stmt->fetchAll();

// Historique des taxis récents
$stmt = $pdo->prepare("
    SELECT * FROM taxis 
    WHERE user_id = ? AND status != 'annule'
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$currentUser['id']]);
$historiqueTaxis = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <h1>🎫 Ma Carte de Membre</h1>
    <p>Toutes vos informations et statistiques</p>
</div>

<!-- Carte de membre visuelle -->
<div class="card" style="background: linear-gradient(135deg, rgba(0, 217, 255, 0.2) 0%, rgba(255, 107, 53, 0.2) 100%); border: 2px solid rgba(0, 217, 255, 0.5); box-shadow: 0 10px 40px rgba(0, 217, 255, 0.3);">
    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem;">
        <div>
            <p style="font-size: 0.9rem; color: var(--gray); margin-bottom: 0.5rem;">MEMBRE DEPUIS</p>
            <p style="font-size: 1.8rem; font-weight: 900; color: var(--primary); margin: 0; font-family: var(--font-display);">
                AÉROPORT MINECRAFT
            </p>
        </div>
        <?php if ($currentUser['role'] === 'vip'): ?>
            <span class="badge badge-vip" style="font-size: 1.2rem; padding: 0.5rem 1rem;">VIP ⭐</span>
        <?php else: ?>
            <span class="badge" style="background: rgba(0, 217, 255, 0.3); color: var(--primary); font-size: 1rem; padding: 0.5rem 1rem;">MEMBRE</span>
        <?php endif; ?>
    </div>
    
    <div style="background: rgba(0, 0, 0, 0.3); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.5rem;">PSEUDO MINECRAFT</p>
                <p style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin: 0;">
                    <?= htmlspecialchars($currentUser['pseudo_minecraft']) ?>
                </p>
            </div>
            <div>
                <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.5rem;">DISCORD</p>
                <p style="font-size: 1.2rem; font-weight: 700; color: var(--light); margin: 0;">
                    <?= htmlspecialchars($currentUser['pseudo_discord']) ?>
                </p>
            </div>
        </div>
    </div>
    
    <div style="background: rgba(0, 0, 0, 0.3); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
        <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.5rem;">FACTION</p>
        <p style="font-size: 1.3rem; font-weight: 700; color: var(--secondary); margin: 0;">
            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $currentUser['faction']))) ?>
            <?php if ($currentUser['faction'] === 'autre' && $currentUser['faction_autre']): ?>
                - <?= htmlspecialchars($currentUser['faction_autre']) ?>
            <?php endif; ?>
        </p>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        <div style="text-align: center; background: rgba(0, 217, 255, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(0, 217, 255, 0.3);">
            <p style="font-size: 2rem; font-weight: 900; color: var(--primary); margin: 0; font-family: var(--font-display);">
                <?= max(0, $carte['vols_achetes'] - $carte['vols_utilises']) ?>
            </p>
            <p style="font-size: 0.85rem; color: var(--gray); margin: 0.5rem 0 0 0;">Vols Restants</p>
        </div>
        
        <div style="text-align: center; background: rgba(255, 107, 53, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255, 107, 53, 0.3);">
            <p style="font-size: 2rem; font-weight: 900; color: var(--secondary); margin: 0; font-family: var(--font-display);">
                <?= max(0, $carte['taxis_achetes'] - $carte['taxis_utilises']) ?>
            </p>
            <p style="font-size: 0.85rem; color: var(--gray); margin: 0.5rem 0 0 0;">Taxis Restants</p>
        </div>
    </div>
    
    <p style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: var(--gray);">
        Membre depuis le <?= date('d/m/Y', strtotime($currentUser['created_at'])) ?>
    </p>
</div>

<!-- Statistiques détaillées -->
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
        <span class="stat-value"><?= $carte['taxis_utilises'] ?></span>
        <span class="stat-label">Taxis Utilisés</span>
    </div>
</div>

<!-- Historique des vols -->
<?php if (!empty($historiqueVols)): ?>
<div class="card">
    <h2>✈️ Historique des Vols</h2>
    <div class="table-container" style="margin-top: 1rem;">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Quantité</th>
                    <th>Prix</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historiqueVols as $vol): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($vol['date_vol'])) ?></td>
                        <td>
                            <?php if ($vol['type'] === 'vol_simple'): ?>
                                🛫 Vol Simple
                            <?php else: ?>
                                📦 Cargaison
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($vol['type'] === 'vol_simple'): ?>
                                <?= $vol['quantite'] ?> vol(s)
                            <?php else: ?>
                                <?= $vol['quantite'] ?> stack(s)
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 700; color: var(--primary);">
                            <?= formaterPrix($vol['prix_total'], $vol['devise']) ?>
                        </td>
                        <td>
                            <?php if ($vol['status'] === 'complete'): ?>
                                <span style="color: var(--success);">✓ Complété</span>
                            <?php elseif ($vol['status'] === 'confirme'): ?>
                                <span class="badge badge-approved">Confirmé</span>
                            <?php else: ?>
                                <span class="badge badge-pending">En attente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Historique des taxis -->
<?php if (!empty($historiqueTaxis)): ?>
<div class="card">
    <h2>🚕 Historique des Taxis</h2>
    <div class="table-container" style="margin-top: 1rem;">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Destination</th>
                    <th>Prix</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historiqueTaxis as $taxi): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($taxi['date_depart'])) ?></td>
                        <td>
                            <?php 
                            $types = [
                                'aller' => '→ Aller',
                                'retour' => '← Retour',
                                'aller_retour' => '↔ A/R'
                            ];
                            echo $types[$taxi['type']] ?? $taxi['type'];
                            ?>
                        </td>
                        <td><?= htmlspecialchars($taxi['coordonnees_arrivee']) ?></td>
                        <td style="font-weight: 700; color: var(--secondary);">
                            <?= number_format($taxi['prix_total'], 2) ?> diamants
                        </td>
                        <td>
                            <?php if ($taxi['status'] === 'complete'): ?>
                                <span style="color: var(--success);">✓ Complété</span>
                            <?php elseif ($taxi['status'] === 'confirme'): ?>
                                <span class="badge badge-approved">Confirmé</span>
                            <?php else: ?>
                                <span class="badge badge-pending">En attente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($currentUser['role'] !== 'vip'): ?>
<div class="card" style="background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 107, 53, 0.1) 100%); border-color: #ffd700;">
    <h2 style="color: #ffd700;">⭐ Passez VIP !</h2>
    <p>Profitez d'avantages exclusifs :</p>
    <div class="grid grid-2" style="margin-top: 1rem;">
        <ul style="list-style: none; padding: 0;">
            <li style="margin: 0.5rem 0;">✓ <strong>-20%</strong> sur tous les vols</li>
            <li style="margin: 0.5rem 0;">✓ Accès à la <strong>classe VIP</strong></li>
        </ul>
        <ul style="list-style: none; padding: 0;">
            <li style="margin: 0.5rem 0;">✓ <strong>1 vol gratuit</strong> tous les 48h</li>
            <li style="margin: 0.5rem 0;">✓ Support <strong>prioritaire</strong></li>
        </ul>
    </div>
    <a href="/member/messagerie.php" class="btn btn-primary btn-block" style="margin-top: 1rem; background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: var(--dark);">
        Contactez l'admin pour devenir VIP
    </a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
