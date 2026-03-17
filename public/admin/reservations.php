<?php
$pageTitle = 'Gestion des Réservations - Admin';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

$pdo = getDB();
$success = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reservationId = intval($_POST['reservation_id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$reservationId]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        $error = "Réservation introuvable";
    } else {
        if ($action === 'confirmer') {
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirme' WHERE id = ?");
            if ($stmt->execute([$reservationId])) {
                $success = "Réservation confirmée";
            }
        } elseif ($action === 'annuler') {
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'annule' WHERE id = ?");
            if ($stmt->execute([$reservationId])) {
                $success = "Réservation annulée";
            }
        } elseif ($action === 'completer') {
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'complete' WHERE id = ?");
            if ($stmt->execute([$reservationId])) {
                // Mettre à jour les vols utilisés
                $stmt = $pdo->prepare("UPDATE cartes SET vols_utilises = vols_utilises + ? WHERE user_id = ?");
                $stmt->execute([$reservation['quantite'], $reservation['user_id']]);
                $success = "Réservation marquée comme complétée";
            }
        } elseif ($action === 'modifier') {
            $nouvelle_date = $_POST['nouvelle_date'] ?? '';
            $nouvelle_heure = $_POST['nouvelle_heure'] ?? '';
            
            if (!empty($nouvelle_date) && !empty($nouvelle_heure)) {
                $stmt = $pdo->prepare("UPDATE reservations SET date_vol = ?, heure_vol = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$nouvelle_date, $nouvelle_heure, $reservationId])) {
                    $success = "Réservation modifiée";
                }
            }
        } elseif ($action === 'supprimer') {
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
            if ($stmt->execute([$reservationId])) {
                $success = "Réservation supprimée";
            }
        }
    }
}

// Filtres
$filtre = $_GET['filtre'] ?? 'tous';
$type = $_GET['type'] ?? 'tous';
$search = $_GET['search'] ?? '';

$sql = "SELECT r.*, u.pseudo_minecraft, u.role FROM reservations r JOIN users u ON r.user_id = u.id WHERE 1=1";
$params = [];

if ($filtre === 'en_attente') {
    $sql .= " AND r.status = 'en_attente'";
} elseif ($filtre === 'confirmes') {
    $sql .= " AND r.status = 'confirme'";
} elseif ($filtre === 'masques') {
    $sql .= " AND r.vol_masque = 1";
} elseif ($filtre === 'aujourdhui') {
    $sql .= " AND r.date_vol = CURDATE()";
}

if ($type === 'vols') {
    $sql .= " AND r.type = 'vol_simple'";
} elseif ($type === 'cargaisons') {
    $sql .= " AND r.type = 'cargaison'";
}

if (!empty($search)) {
    $sql .= " AND u.pseudo_minecraft LIKE ?";
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY r.date_vol DESC, r.heure_vol DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <h1>📋 Gestion des Réservations</h1>
    <p>Toutes les réservations de vols et cargaisons</p>
</div>

<?php if ($success): ?>
    <div class="flash flash-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2>🔍 Filtres et Recherche</h2>
    
    <form method="GET" style="margin-top: 1.5rem;">
        <div class="grid grid-3">
            <div class="form-group">
                <label for="filtre">Statut</label>
                <select name="filtre" id="filtre" class="form-control">
                    <option value="tous" <?= $filtre === 'tous' ? 'selected' : '' ?>>Tous</option>
                    <option value="en_attente" <?= $filtre === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="confirmes" <?= $filtre === 'confirmes' ? 'selected' : '' ?>>Confirmés</option>
                    <option value="masques" <?= $filtre === 'masques' ? 'selected' : '' ?>>Vols masqués</option>
                    <option value="aujourdhui" <?= $filtre === 'aujourdhui' ? 'selected' : '' ?>>Aujourd'hui</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="type">Type</label>
                <select name="type" id="type" class="form-control">
                    <option value="tous" <?= $type === 'tous' ? 'selected' : '' ?>>Tous</option>
                    <option value="vols" <?= $type === 'vols' ? 'selected' : '' ?>>Vols seulement</option>
                    <option value="cargaisons" <?= $type === 'cargaisons' ? 'selected' : '' ?>>Cargaisons seulement</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="search">Rechercher un client</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Pseudo Minecraft" value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Appliquer les filtres</button>
        <a href="/admin/reservations.php" class="btn btn-secondary">Réinitialiser</a>
    </form>
</div>

<?php if (empty($reservations)): ?>
    <div class="card">
        <p style="text-align: center; color: var(--gray); padding: 3rem;">
            Aucune réservation trouvée avec ces critères.
        </p>
    </div>
<?php else: ?>
    <div class="card">
        <h2>📊 <?= count($reservations) ?> Réservation(s)</h2>
        
        <div class="table-container" style="margin-top: 1.5rem;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Classe</th>
                        <th>Qté</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $res): ?>
                        <tr style="<?= $res['vol_masque'] ? 'background: rgba(237, 137, 54, 0.05);' : '' ?>">
                            <td>#<?= $res['id'] ?></td>
                            <td><?= date('d/m/Y', strtotime($res['date_vol'])) ?></td>
                            <td><?= date('H:i', strtotime($res['heure_vol'])) ?></td>
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
                                    <span style="color: var(--warning);" title="Vol masqué">🔒</span>
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
                            <td style="font-size: 0.9rem;"><?= formaterPrix($res['prix_total'], $res['devise']) ?></td>
                            <td>
                                <?php if ($res['status'] === 'en_attente'): ?>
                                    <span class="badge badge-pending">Attente</span>
                                <?php elseif ($res['status'] === 'confirme'): ?>
                                    <span class="badge badge-approved">Confirmé</span>
                                <?php elseif ($res['status'] === 'complete'): ?>
                                    <span style="color: var(--success);">✓ OK</span>
                                <?php elseif ($res['status'] === 'annule'): ?>
                                    <span class="badge badge-cancelled">Annulé</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="gererReservation(<?= htmlspecialchars(json_encode($res)) ?>)" 
                                        class="btn btn-primary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">
                                    Gérer
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de gestion -->
<div id="modalGestion" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center; overflow-y: auto;">
    <div class="card" style="max-width: 600px; margin: 2rem; max-height: 90vh; overflow-y: auto;">
        <h3 style="color: var(--primary);" id="modal_title">Gérer la réservation</h3>
        
        <div id="modal_details" style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 8px; margin: 1.5rem 0;">
            <!-- Détails remplis dynamiquement -->
        </div>
        
        <h4 style="margin-top: 2rem; color: var(--secondary);">Actions</h4>
        
        <div style="display: grid; gap: 1rem; margin-top: 1rem;">
            <!-- Confirmer -->
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="confirmer">
                <input type="hidden" name="reservation_id" id="modal_id_confirmer">
                <button type="submit" class="btn btn-success btn-block">✅ Confirmer</button>
            </form>
            
            <!-- Modifier -->
            <div style="background: rgba(0, 217, 255, 0.1); padding: 1rem; border-radius: 8px;">
                <h5 style="color: var(--primary); margin-bottom: 1rem;">Modifier la date/heure</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="reservation_id" id="modal_id_modifier">
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label>Nouvelle date</label>
                            <input type="date" name="nouvelle_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Nouvelle heure</label>
                            <input type="time" name="nouvelle_heure" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">✏️ Modifier</button>
                </form>
            </div>
            
            <!-- Marquer comme complété -->
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="completer">
                <input type="hidden" name="reservation_id" id="modal_id_completer">
                <button type="submit" class="btn btn-secondary btn-block">✓ Marquer comme complété</button>
            </form>
            
            <!-- Annuler -->
            <form method="POST" style="display: inline;" onsubmit="return confirm('Annuler cette réservation ?');">
                <input type="hidden" name="action" value="annuler">
                <input type="hidden" name="reservation_id" id="modal_id_annuler">
                <button type="submit" class="btn btn-danger btn-block">❌ Annuler</button>
            </form>
            
            <!-- Supprimer -->
            <form method="POST" style="display: inline;" onsubmit="return confirm('SUPPRIMER définitivement cette réservation ? Cette action est irréversible !');">
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="reservation_id" id="modal_id_supprimer">
                <button type="submit" class="btn btn-danger btn-block" style="background: linear-gradient(135deg, #c53030 0%, #742a2a 100%);">
                    🗑️ Supprimer définitivement
                </button>
            </form>
        </div>
        
        <button onclick="fermerModal()" class="btn btn-secondary btn-block" style="margin-top: 1.5rem;">Fermer</button>
    </div>
</div>

<script>
function gererReservation(reservation) {
    document.getElementById('modal_title').textContent = `Réservation #${reservation.id}`;
    
    const typeText = reservation.type === 'vol_simple' ? '🛫 Vol Simple' : '📦 Cargaison';
    const classeText = reservation.classe === 'vip' ? 'VIP' : (reservation.classe === '1' ? '1ère' : '2ème');
    const statusText = {
        'en_attente': 'En attente',
        'confirme': 'Confirmé',
        'complete': 'Complété',
        'annule': 'Annulé'
    }[reservation.status] || reservation.status;
    
    document.getElementById('modal_details').innerHTML = `
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 0.8rem; font-size: 0.95rem;">
            <span style="color: var(--gray);">Client:</span>
            <span style="color: var(--light); font-weight: 700;">${reservation.pseudo_minecraft}</span>
            
            <span style="color: var(--gray);">Type:</span>
            <span style="color: var(--light);">${typeText}</span>
            
            <span style="color: var(--gray);">Classe:</span>
            <span style="color: var(--light);">${classeText}</span>
            
            <span style="color: var(--gray);">Quantité:</span>
            <span style="color: var(--light);">${reservation.quantite}</span>
            
            <span style="color: var(--gray);">Prix:</span>
            <span style="color: var(--primary); font-weight: 700;">${reservation.prix_total} ${reservation.devise}</span>
            
            <span style="color: var(--gray);">Date:</span>
            <span style="color: var(--light);">${new Date(reservation.date_vol).toLocaleDateString('fr-FR')}</span>
            
            <span style="color: var(--gray);">Heure:</span>
            <span style="color: var(--light);">${reservation.heure_vol}</span>
            
            <span style="color: var(--gray);">Statut:</span>
            <span style="color: var(--light);">${statusText}</span>
            
            ${reservation.vol_masque ? '<span style="color: var(--gray);">Vol masqué:</span><span style="color: var(--warning);">🔒 Oui</span>' : ''}
        </div>
    `;
    
    document.getElementById('modal_id_confirmer').value = reservation.id;
    document.getElementById('modal_id_modifier').value = reservation.id;
    document.getElementById('modal_id_completer').value = reservation.id;
    document.getElementById('modal_id_annuler').value = reservation.id;
    document.getElementById('modal_id_supprimer').value = reservation.id;
    
    document.getElementById('modalGestion').style.display = 'flex';
}

function fermerModal() {
    document.getElementById('modalGestion').style.display = 'none';
}

document.getElementById('modalGestion').addEventListener('click', function(e) {
    if (e.target === this) fermerModal();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
