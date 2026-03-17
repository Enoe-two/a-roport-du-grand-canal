<?php
$pageTitle = 'Mes Taxis - Aéroport Minecraft';
require_once __DIR__ . '/../../includes/functions.php';

requireVIP();

$currentUser = getCurrentUser();
$pdo = getDB();

// Récupérer tous les taxis
$filtre = $_GET['filtre'] ?? 'tous';
$sql = "SELECT * FROM taxis WHERE user_id = ?";
$params = [$currentUser['id']];

if ($filtre === 'futures') {
    $sql .= " AND date_depart >= CURDATE() AND status != 'annule'";
} elseif ($filtre === 'passes') {
    $sql .= " AND (date_depart < CURDATE() OR status = 'complete')";
} elseif ($filtre === 'annules') {
    $sql .= " AND status = 'annule'";
}

$sql .= " ORDER BY date_depart DESC, heure_depart DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$taxis = $stmt->fetchAll();

// Statistiques
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) as completes,
        SUM(prix_total) as total_depense
    FROM taxis 
    WHERE user_id = ? AND status != 'annule'
");
$stmt->execute([$currentUser['id']]);
$stats = $stmt->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <h1>🚕 Mes Taxis</h1>
    <p>Gérez toutes vos commandes de taxi</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= $stats['total'] ?? 0 ?></span>
        <span class="stat-label">Taxis Commandés</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $stats['completes'] ?? 0 ?></span>
        <span class="stat-label">Trajets Effectués</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= number_format($stats['total_depense'] ?? 0, 0) ?></span>
        <span class="stat-label">Diamants Dépensés</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= max(0, ($stats['total'] ?? 0) - ($stats['completes'] ?? 0)) ?></span>
        <span class="stat-label">Trajets En Attente</span>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h2>Filtres</h2>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="?filtre=tous" class="btn <?= $filtre === 'tous' ? 'btn-primary' : 'btn-secondary' ?>">Tous</a>
            <a href="?filtre=futures" class="btn <?= $filtre === 'futures' ? 'btn-primary' : 'btn-secondary' ?>">À venir</a>
            <a href="?filtre=passes" class="btn <?= $filtre === 'passes' ? 'btn-primary' : 'btn-secondary' ?>">Passés</a>
            <a href="?filtre=annules" class="btn <?= $filtre === 'annules' ? 'btn-primary' : 'btn-secondary' ?>">Annulés</a>
        </div>
    </div>
    
    <?php if (empty($taxis)): ?>
        <p style="text-align: center; color: var(--gray); padding: 3rem;">
            Aucun taxi trouvé.
        </p>
        <div style="text-align: center;">
            <a href="/taxis.php" class="btn btn-primary">Commander un taxi</a>
        </div>
    <?php else: ?>
        <div class="grid grid-2" style="margin-top: 1.5rem;">
            <?php foreach ($taxis as $taxi): ?>
                <div class="card" style="background: rgba(0, 217, 255, 0.05); border: 1px solid rgba(0, 217, 255, 0.2);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <h3 style="color: var(--primary); margin: 0;">
                            🚕 Taxi #<?= $taxi['id'] ?>
                        </h3>
                        <?php if ($taxi['status'] === 'en_attente'): ?>
                            <span class="badge badge-pending">En attente</span>
                        <?php elseif ($taxi['status'] === 'confirme'): ?>
                            <span class="badge badge-approved">Confirmé</span>
                        <?php elseif ($taxi['status'] === 'complete'): ?>
                            <span style="color: var(--success); font-weight: 700;">✓ Effectué</span>
                        <?php elseif ($taxi['status'] === 'annule'): ?>
                            <span class="badge badge-cancelled">Annulé</span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <div style="display: grid; gap: 0.8rem; font-size: 0.95rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--gray);">Type:</span>
                                <span style="color: var(--light); font-weight: 700;">
                                    <?php 
                                    $types = [
                                        'aller' => '→ Aller Simple',
                                        'retour' => '← Retour',
                                        'aller_retour' => '↔ Aller-Retour'
                                    ];
                                    echo $types[$taxi['type']] ?? $taxi['type'];
                                    ?>
                                </span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--gray);">Classe:</span>
                                <span style="color: var(--light); font-weight: 700;">
                                    <?= $taxi['classe'] === '1' ? '1ère Classe' : '2ème Classe' ?>
                                </span>
                            </div>
                            
                            <?php if ($taxi['coordonnees_depart']): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--gray);">Départ:</span>
                                <span style="color: var(--light);"><?= htmlspecialchars($taxi['coordonnees_depart']) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--gray);">Destination:</span>
                                <span style="color: var(--secondary); font-weight: 700;">
                                    <?= htmlspecialchars($taxi['coordonnees_arrivee']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.3rem;">📅 Départ</p>
                                <p style="font-weight: 700; color: var(--light); margin: 0;">
                                    <?= date('d/m/Y', strtotime($taxi['date_depart'])) ?>
                                </p>
                                <p style="color: var(--gray); margin: 0; font-size: 0.9rem;">
                                    <?= date('H:i', strtotime($taxi['heure_depart'])) ?>
                                </p>
                            </div>
                            
                            <?php if ($taxi['type'] === 'retour' || $taxi['type'] === 'aller_retour'): ?>
                            <div>
                                <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.3rem;">🔄 Retour</p>
                                <p style="font-weight: 700; color: var(--light); margin: 0;">
                                    <?= $taxi['date_retour'] ? date('d/m/Y', strtotime($taxi['date_retour'])) : '-' ?>
                                </p>
                                <p style="color: var(--gray); margin: 0; font-size: 0.9rem;">
                                    <?= $taxi['heure_retour'] ? date('H:i', strtotime($taxi['heure_retour'])) : '' ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($taxi['temps_attente'] && $taxi['type'] === 'aller_retour'): ?>
                        <p style="margin-top: 0.8rem; color: var(--gray); font-size: 0.9rem;">
                            ⏱️ Temps d'attente: <?= $taxi['temps_attente'] ?> minutes
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="text-align: center; padding: 1rem; background: rgba(0, 217, 255, 0.1); border-radius: 8px;">
                        <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.3rem;">Prix Total</p>
                        <p style="font-size: 1.8rem; font-weight: 900; color: var(--primary); margin: 0; font-family: var(--font-display);">
                            <?= number_format($taxi['prix_total'], 2) ?> 💎
                        </p>
                        <?php if ($taxi['vol_masque']): ?>
                        <p style="font-size: 0.85rem; color: var(--warning); margin: 0.5rem 0 0 0;">
                            🔒 Trajet masqué
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <p style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: var(--gray);">
                        Commandé le <?= date('d/m/Y à H:i', strtotime($taxi['created_at'])) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card" style="background: rgba(0, 217, 255, 0.05);">
    <h3 style="color: var(--primary);">ℹ️ Tarification des Taxis</h3>
    <div style="margin-top: 1rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px;">
                <p style="font-size: 1.5rem; margin-bottom: 0.5rem;">→</p>
                <p style="font-weight: 700; color: var(--light); margin-bottom: 0.3rem;">Aller Simple</p>
                <p style="color: var(--primary);">Prix de base × 1.15</p>
            </div>
            
            <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px;">
                <p style="font-size: 1.5rem; margin-bottom: 0.5rem;">←</p>
                <p style="font-weight: 700; color: var(--light); margin-bottom: 0.3rem;">Retour</p>
                <p style="color: var(--primary);">Prix de base × 1.20</p>
            </div>
            
            <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px;">
                <p style="font-size: 1.5rem; margin-bottom: 0.5rem;">↔</p>
                <p style="font-weight: 700; color: var(--light); margin-bottom: 0.3rem;">Aller-Retour</p>
                <p style="color: var(--primary);">Prix de base × 2</p>
            </div>
        </div>
        
        <p style="color: var(--gray); margin-top: 1rem; font-size: 0.9rem;">
            Le prix de base est déterminé en fonction de la distance et de la complexité du trajet.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
