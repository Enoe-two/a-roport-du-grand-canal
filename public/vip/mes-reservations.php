<?php
$pageTitle = 'Mes Réservations - Aéroport Minecraft';
require_once __DIR__ . '/../../includes/functions.php';

requireVIP();

$currentUser = getCurrentUser();
$pdo = getDB();
$success = '';
$error = '';

// Traitement des actions (modifier, reporter, annuler)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reservationId = intval($_POST['reservation_id'] ?? 0);
    
    // Vérifier que la réservation appartient bien à l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ?");
    $stmt->execute([$reservationId, $currentUser['id']]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        $error = "Réservation introuvable";
    } else {
        if ($action === 'annuler') {
            // Vérifier le délai (2 jours avant)
            if (canModifyReservation($reservation['date_vol'], 2)) {
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'annule' WHERE id = ?");
                if ($stmt->execute([$reservationId])) {
                    $success = "Réservation annulée avec succès (remboursée)";
                } else {
                    $error = "Erreur lors de l'annulation";
                }
            } else {
                $error = "Annulation impossible : doit être fait au moins 2 jours avant le vol (non remboursable maintenant)";
            }
        } elseif ($action === 'modifier') {
            $nouvelle_date = $_POST['nouvelle_date'] ?? '';
            $nouvelle_heure = $_POST['nouvelle_heure'] ?? '';
            
            if (canModifyReservation($reservation['date_vol'], 1)) {
                if (!empty($nouvelle_date) && !empty($nouvelle_heure)) {
                    $stmt = $pdo->prepare("UPDATE reservations SET date_vol = ?, heure_vol = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$nouvelle_date, $nouvelle_heure, $reservationId])) {
                        $success = "Réservation modifiée avec succès";
                    } else {
                        $error = "Erreur lors de la modification";
                    }
                } else {
                    $error = "Veuillez renseigner la nouvelle date et heure";
                }
            } else {
                $error = "Modification impossible : doit être fait au moins 1 jour avant le vol";
            }
        }
    }
}

// Récupérer toutes les réservations
$filtre = $_GET['filtre'] ?? 'tous';
$sql = "SELECT * FROM reservations WHERE user_id = ?";
$params = [$currentUser['id']];

if ($filtre === 'futures') {
    $sql .= " AND date_vol >= CURDATE() AND status != 'annule'";
} elseif ($filtre === 'passees') {
    $sql .= " AND (date_vol < CURDATE() OR status = 'complete')";
} elseif ($filtre === 'annulees') {
    $sql .= " AND status = 'annule'";
}

$sql .= " ORDER BY date_vol DESC, heure_vol DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <h1>📋 Mes Réservations</h1>
    <p>Gérez toutes vos réservations de vols et cargaisons</p>
</div>

<?php if ($success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h2>Filtres</h2>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="?filtre=tous" class="btn <?= $filtre === 'tous' ? 'btn-primary' : 'btn-secondary' ?>">Tous</a>
            <a href="?filtre=futures" class="btn <?= $filtre === 'futures' ? 'btn-primary' : 'btn-secondary' ?>">À venir</a>
            <a href="?filtre=passees" class="btn <?= $filtre === 'passees' ? 'btn-primary' : 'btn-secondary' ?>">Passées</a>
            <a href="?filtre=annulees" class="btn <?= $filtre === 'annulees' ? 'btn-primary' : 'btn-secondary' ?>">Annulées</a>
        </div>
    </div>
    
    <?php if (empty($reservations)): ?>
        <p style="text-align: center; color: var(--gray); padding: 3rem;">
            Aucune réservation trouvée.
        </p>
        <div style="text-align: center;">
            <a href="/reservation.php" class="btn btn-primary">Faire une réservation</a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Type</th>
                        <th>Classe</th>
                        <th>Quantité</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
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
                                <?php if ($res['vol_masque']): ?>
                                    <span style="color: var(--warning); font-size: 0.9rem;">🔒</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($res['classe'] === 'vip') {
                                    echo '<span class="badge badge-vip">VIP</span>';
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
                            <td style="font-weight: 700; color: var(--primary);">
                                <?= formaterPrix($res['prix_total'], $res['devise']) ?>
                            </td>
                            <td>
                                <?php if ($res['status'] === 'en_attente'): ?>
                                    <span class="badge badge-pending">En attente</span>
                                <?php elseif ($res['status'] === 'confirme'): ?>
                                    <span class="badge badge-approved">Confirmé</span>
                                <?php elseif ($res['status'] === 'annule'): ?>
                                    <span class="badge badge-cancelled">Annulé</span>
                                <?php elseif ($res['status'] === 'complete'): ?>
                                    <span style="color: var(--success);">✓ Complété</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($res['status'] !== 'annule' && $res['status'] !== 'complete' && strtotime($res['date_vol']) >= strtotime(date('Y-m-d'))): ?>
                                    <button onclick="modifierReservation(<?= $res['id'] ?>, '<?= $res['date_vol'] ?>', '<?= $res['heure_vol'] ?>')" 
                                            class="btn btn-secondary" style="padding: 0.4rem 1rem; font-size: 0.9rem; margin: 0.2rem;">
                                        ✏️ Modifier
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                        <input type="hidden" name="action" value="annuler">
                                        <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.4rem 1rem; font-size: 0.9rem; margin: 0.2rem;">
                                            ❌ Annuler
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--gray); font-size: 0.9rem;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card" style="margin-top: 2rem; background: rgba(0, 217, 255, 0.05);">
    <h3 style="color: var(--primary);">ℹ️ Règles de modification et annulation</h3>
    <ul style="list-style: none; padding: 0; margin-top: 1rem;">
        <li style="margin: 0.8rem 0;">
            ✏️ <strong>Modification</strong> : Possible jusqu'à <strong>1 jour avant</strong> le vol
        </li>
        <li style="margin: 0.8rem 0;">
            💰 <strong>Annulation avec remboursement</strong> : Jusqu'à <strong>2 jours avant</strong> le vol
        </li>
        <li style="margin: 0.8rem 0;">
            ⚠️ <strong>Annulation tardive</strong> : Moins de 2 jours avant = <strong>Non remboursée</strong>
        </li>
    </ul>
</div>

<!-- Modal de modification -->
<div id="modalModifier" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 500px; margin: 2rem;">
        <h3 style="color: var(--primary);">Modifier la réservation</h3>
        <form method="POST">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="reservation_id" id="modal_reservation_id">
            
            <div class="form-group">
                <label for="modal_nouvelle_date">Nouvelle date</label>
                <input type="date" id="modal_nouvelle_date" name="nouvelle_date" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label for="modal_nouvelle_heure">Nouvelle heure</label>
                <input type="time" id="modal_nouvelle_heure" name="nouvelle_heure" class="form-control" required>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">Confirmer</button>
                <button type="button" onclick="fermerModal()" class="btn btn-secondary">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
function modifierReservation(id, date, heure) {
    document.getElementById('modal_reservation_id').value = id;
    document.getElementById('modal_nouvelle_date').value = date;
    document.getElementById('modal_nouvelle_heure').value = heure;
    document.getElementById('modalModifier').style.display = 'flex';
}

function fermerModal() {
    document.getElementById('modalModifier').style.display = 'none';
}

// Fermer le modal en cliquant en dehors
document.getElementById('modalModifier').addEventListener('click', function(e) {
    if (e.target === this) {
        fermerModal();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
