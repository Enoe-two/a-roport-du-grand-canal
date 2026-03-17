<?php
$pageTitle = 'Horaires - Aéroport Minecraft';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();

// Récupérer le mois et l'année (par défaut le mois actuel)
$mois = isset($_GET['mois']) ? intval($_GET['mois']) : date('n');
$annee = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');

// Validation
if ($mois < 1 || $mois > 12) $mois = date('n');
if ($annee < 2020 || $annee > 2099) $annee = date('Y');

// Récupérer toutes les réservations NON MASQUÉES du mois
$stmt = $pdo->prepare("
    SELECT r.*, u.pseudo_minecraft, u.role
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    WHERE MONTH(r.date_vol) = ? 
    AND YEAR(r.date_vol) = ?
    AND r.vol_masque = 0
    AND r.status != 'annule'
    ORDER BY r.date_vol ASC, r.heure_vol ASC
");
$stmt->execute([$mois, $annee]);
$reservations = $stmt->fetchAll();

// Récupérer les taxis NON MASQUÉS du mois
$stmt = $pdo->prepare("
    SELECT t.*, u.pseudo_minecraft, u.role
    FROM taxis t
    JOIN users u ON t.user_id = u.id
    WHERE MONTH(t.date_depart) = ? 
    AND YEAR(t.date_depart) = ?
    AND t.vol_masque = 0
    AND t.status != 'annule'
    ORDER BY t.date_depart ASC, t.heure_depart ASC
");
$stmt->execute([$mois, $annee]);
$taxis = $stmt->fetchAll();

// Noms des mois
$moisNoms = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="hero">
    <h1>📅 Horaires des Vols</h1>
    <p>Consultez tous les vols et taxis prévus pour le mois de <?= $moisNoms[$mois] ?> <?= $annee ?></p>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h2>Calendrier</h2>
        
        <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
            <select name="mois" class="form-control" style="width: auto;">
                <?php foreach ($moisNoms as $num => $nom): ?>
                    <option value="<?= $num ?>" <?= $num === $mois ? 'selected' : '' ?>><?= $nom ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="annee" class="form-control" style="width: auto;">
                <?php for ($a = date('Y') - 1; $a <= date('Y') + 2; $a++): ?>
                    <option value="<?= $a ?>" <?= $a === $annee ? 'selected' : '' ?>><?= $a ?></option>
                <?php endfor; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">Afficher</button>
        </form>
    </div>
    
    <?php if (empty($reservations) && empty($taxis)): ?>
        <p style="text-align: center; color: var(--gray); padding: 3rem;">
            Aucun vol ou taxi prévu pour ce mois.
        </p>
    <?php else: ?>
        <?php if (!empty($reservations)): ?>
            <h3 style="color: var(--secondary); margin-top: 2rem;">✈️ Vols Passagers & Cargaisons</h3>
            <div class="table-container" style="margin-top: 1rem;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Type</th>
                            <th>Classe</th>
                            <th>Quantité</th>
                            <th>Passager</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($res['date_vol'])) ?></td>
                                <td><?= date('H:i', strtotime($res['heure_vol'])) ?></td>
                                <td>
                                    <?php if ($res['type'] === 'vol_simple'): ?>
                                        <span style="color: var(--primary);">🛫 Vol Simple</span>
                                    <?php else: ?>
                                        <span style="color: var(--secondary);">📦 Cargaison</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($res['classe'] === 'vip') {
                                        echo '<span class="badge badge-vip">VIP ⭐</span>';
                                    } elseif ($res['classe'] === '1') {
                                        echo '1ère Classe';
                                    } else {
                                        echo '2ème Classe';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($res['type'] === 'vol_simple'): ?>
                                        <?= $res['quantite'] ?> vol(s)
                                    <?php else: ?>
                                        <?= $res['quantite'] ?> stack(s)
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($res['pseudo_minecraft']) ?>
                                    <?php if ($res['role'] === 'vip'): ?>
                                        <span class="badge badge-vip">VIP</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($res['status'] === 'en_attente'): ?>
                                        <span class="badge badge-pending">En attente</span>
                                    <?php elseif ($res['status'] === 'confirme'): ?>
                                        <span class="badge badge-approved">Confirmé</span>
                                    <?php elseif ($res['status'] === 'complete'): ?>
                                        <span style="color: var(--success);">✓ Complété</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($taxis)): ?>
            <h3 style="color: var(--secondary); margin-top: 3rem;">🚕 Service Taxi</h3>
            <div class="table-container" style="margin-top: 1rem;">
                <table>
                    <thead>
                        <tr>
                            <th>Date Départ</th>
                            <th>Heure</th>
                            <th>Type</th>
                            <th>Classe</th>
                            <th>Destination</th>
                            <th>Client</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taxis as $taxi): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($taxi['date_depart'])) ?></td>
                                <td><?= date('H:i', strtotime($taxi['heure_depart'])) ?></td>
                                <td>
                                    <?php 
                                    $types = [
                                        'aller' => '→ Aller',
                                        'retour' => '← Retour',
                                        'aller_retour' => '↔ Aller-Retour'
                                    ];
                                    echo $types[$taxi['type']] ?? $taxi['type'];
                                    ?>
                                </td>
                                <td><?= $taxi['classe'] === '1' ? '1ère Classe' : '2ème Classe' ?></td>
                                <td><?= htmlspecialchars($taxi['coordonnees_arrivee']) ?></td>
                                <td>
                                    <?= htmlspecialchars($taxi['pseudo_minecraft']) ?>
                                    <?php if ($taxi['role'] === 'vip'): ?>
                                        <span class="badge badge-vip">VIP</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($taxi['status'] === 'en_attente'): ?>
                                        <span class="badge badge-pending">En attente</span>
                                    <?php elseif ($taxi['status'] === 'confirme'): ?>
                                        <span class="badge badge-approved">Confirmé</span>
                                    <?php elseif ($taxi['status'] === 'complete'): ?>
                                        <span style="color: var(--success);">✓ Complété</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div style="margin-top: 2rem; padding: 1rem; background: rgba(0, 217, 255, 0.1); border-radius: 10px; border: 1px solid rgba(0, 217, 255, 0.3);">
        <p style="margin: 0; color: var(--gray);">
            ℹ️ Les vols et taxis marqués comme "masqués" ne sont pas affichés dans ce calendrier public.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
