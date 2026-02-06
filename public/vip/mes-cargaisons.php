<?php
$pageTitle = 'Mes Cargaisons - Aéroport Minecraft';
require_once __DIR__ . '/../../includes/functions.php';

requireVIP();

$currentUser = getCurrentUser();
$pdo = getDB();

// Récupérer toutes les cargaisons (réservations de type cargaison)
$filtre = $_GET['filtre'] ?? 'tous';
$sql = "SELECT * FROM reservations WHERE user_id = ? AND type = 'cargaison'";
$params = [$currentUser['id']];

if ($filtre === 'futures') {
    $sql .= " AND date_vol >= CURDATE() AND status != 'annule'";
} elseif ($filtre === 'livrees') {
    $sql .= " AND status = 'complete'";
} elseif ($filtre === 'en_cours') {
    $sql .= " AND status IN ('en_attente', 'confirme') AND date_vol >= CURDATE()";
}

$sql .= " ORDER BY date_vol DESC, heure_vol DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cargaisons = $stmt->fetchAll();

// Calculer les statistiques
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(quantite) as total_stacks,
        SUM(CASE WHEN status = 'complete' THEN quantite ELSE 0 END) as stacks_livres
    FROM reservations 
    WHERE user_id = ? AND type = 'cargaison' AND status != 'annule'
");
$stmt->execute([$currentUser['id']]);
$stats = $stmt->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <h1>📦 Mes Cargaisons</h1>
    <p>Suivez toutes vos expéditions de fret</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= $stats['total'] ?? 0 ?></span>
        <span class="stat-label">Cargaisons Totales</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $stats['total_stacks'] ?? 0 ?></span>
        <span class="stat-label">Stacks Expédiés</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= $stats['stacks_livres'] ?? 0 ?></span>
        <span class="stat-label">Stacks Livrés</span>
    </div>
    
    <div class="stat-card">
        <span class="stat-value"><?= max(0, ($stats['total_stacks'] ?? 0) - ($stats['stacks_livres'] ?? 0)) ?></span>
        <span class="stat-label">Stacks En Transit</span>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h2>Filtres</h2>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="?filtre=tous" class="btn <?= $filtre === 'tous' ? 'btn-primary' : 'btn-secondary' ?>">Toutes</a>
            <a href="?filtre=en_cours" class="btn <?= $filtre === 'en_cours' ? 'btn-primary' : 'btn-secondary' ?>">En cours</a>
            <a href="?filtre=futures" class="btn <?= $filtre === 'futures' ? 'btn-primary' : 'btn-secondary' ?>">À venir</a>
            <a href="?filtre=livrees" class="btn <?= $filtre === 'livrees' ? 'btn-primary' : 'btn-secondary' ?>">Livrées</a>
        </div>
    </div>
    
    <?php if (empty($cargaisons)): ?>
        <p style="text-align: center; color: var(--gray); padding: 3rem;">
            Aucune cargaison trouvée.
        </p>
        <div style="text-align: center;">
            <a href="/reservation.php" class="btn btn-primary">Expédier une cargaison</a>
        </div>
    <?php else: ?>
        <div class="grid grid-2" style="margin-top: 1.5rem;">
            <?php foreach ($cargaisons as $cargo): ?>
                <div class="card" style="background: rgba(255, 107, 53, 0.05); border: 1px solid rgba(255, 107, 53, 0.2);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <h3 style="color: var(--secondary); margin: 0;">
                            📦 Cargaison #<?= $cargo['id'] ?>
                        </h3>
                        <?php if ($cargo['status'] === 'en_attente'): ?>
                            <span class="badge badge-pending">En attente</span>
                        <?php elseif ($cargo['status'] === 'confirme'): ?>
                            <span class="badge badge-approved">Confirmée</span>
                        <?php elseif ($cargo['status'] === 'complete'): ?>
                            <span style="color: var(--success); font-weight: 700;">✓ Livrée</span>
                        <?php elseif ($cargo['status'] === 'annule'): ?>
                            <span class="badge badge-cancelled">Annulée</span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.3rem;">Nombre de stacks</p>
                                <p style="font-size: 1.5rem; font-weight: 700; color: var(--secondary); margin: 0;">
                                    <?= $cargo['quantite'] ?> 📦
                                </p>
                            </div>
                            <div>
                                <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.3rem;">Prix total</p>
                                <p style="font-size: 1.3rem; font-weight: 700; color: var(--primary); margin: 0;">
                                    <?= formaterPrix($cargo['prix_total'], $cargo['devise']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: auto 1fr; gap: 0.8rem; font-size: 0.95rem;">
                        <span style="color: var(--gray);">📅 Date:</span>
                        <span style="color: var(--light);"><?= date('d/m/Y', strtotime($cargo['date_vol'])) ?></span>
                        
                        <span style="color: var(--gray);">⏰ Heure:</span>
                        <span style="color: var(--light);"><?= date('H:i', strtotime($cargo['heure_vol'])) ?></span>
                        
                        <?php if ($cargo['vol_masque']): ?>
                            <span style="color: var(--gray);">🔒 Statut:</span>
                            <span style="color: var(--warning);">Vol masqué</span>
                        <?php endif; ?>
                        
                        <span style="color: var(--gray);">🕐 Créée le:</span>
                        <span style="color: var(--light);"><?= date('d/m/Y', strtotime($cargo['created_at'])) ?></span>
                    </div>
                    
                    <?php if ($cargo['status'] !== 'annule' && $cargo['status'] !== 'complete' && strtotime($cargo['date_vol']) >= strtotime(date('Y-m-d'))): ?>
                        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(255, 107, 53, 0.2);">
                            <a href="/member/mes-reservations.php" class="btn btn-secondary btn-block">
                                Gérer cette cargaison
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card" style="background: rgba(255, 107, 53, 0.05);">
    <h3 style="color: var(--secondary);">📦 Tarifs Cargaison</h3>
    <div style="margin-top: 1rem;">
        <p style="color: var(--gray); margin-bottom: 1rem;">1 stack = 1 slot de stockage</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px;">
                <p style="font-weight: 700; color: var(--light);">1 stack</p>
                <p style="color: var(--primary);">1 diamant</p>
            </div>
            <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px;">
                <p style="font-weight: 700; color: var(--light);">5 stacks</p>
                <p style="color: var(--primary);">3 diamants</p>
            </div>
            <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px;">
                <p style="font-weight: 700; color: var(--light);">10 stacks</p>
                <p style="color: var(--primary);">5 diamants</p>
            </div>
            <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px;">
                <p style="font-weight: 700; color: var(--light);">20 stacks</p>
                <p style="color: var(--primary);">7 diamants</p>
            </div>
            <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px;">
                <p style="font-weight: 700; color: var(--light);">50 stacks</p>
                <p style="color: var(--primary);">10 diamants</p>
            </div>
            <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px;">
                <p style="font-weight: 700; color: var(--light);">100 stacks</p>
                <p style="color: var(--primary);">15 diamants</p>
            </div>
        </div>
        <p style="color: var(--gray); margin-top: 1rem; font-size: 0.9rem;">
            Au-delà de 100 stacks : +1 diamant par 5 stacks supplémentaires
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
