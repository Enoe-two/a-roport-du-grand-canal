<?php
$pageTitle = 'Réservation - Aéroport Minecraft';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$currentUser = getCurrentUser();
$isVIP = $currentUser['role'] === 'vip' || $currentUser['role'] === 'admin';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $classe = $_POST['classe'] ?? '';
    $quantite = intval($_POST['quantite'] ?? 0);
    $date_vol = $_POST['date_vol'] ?? '';
    $heure_vol = $_POST['heure_vol'] ?? '';
    $vol_masque = isset($_POST['vol_masque']) ? 1 : 0;
    
    // Validation
    if (empty($type) || empty($classe) || $quantite <= 0 || empty($date_vol) || empty($heure_vol)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } elseif (strtotime($date_vol) < strtotime(date('Y-m-d'))) {
        $error = 'La date du vol doit être dans le futur';
    } else {
        // Calculer le prix
        if ($type === 'vol_simple') {
            $tarif = calculerPrixVolSimple($classe, $quantite, $isVIP);
        } else {
            $tarif = calculerPrixCargaison($quantite);
            $classe = '1'; // Par défaut pour cargaison
        }
        
        if ($tarif === null) {
            $error = 'Configuration de tarif invalide';
        } else {
            $prix_total = $tarif['prix'];
            $devise = $tarif['devise'];
            
            // Ajouter le coût du vol masqué
            if ($vol_masque) {
                $prix_total += 10;
            }
            
            // Créer la réservation
            $pdo = getDB();
            $stmt = $pdo->prepare("
                INSERT INTO reservations (user_id, type, classe, quantite, prix_total, devise, date_vol, heure_vol, vol_masque, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')
            ");
            
            if ($stmt->execute([$currentUser['id'], $type, $classe, $quantite, $prix_total, $devise, $date_vol, $heure_vol, $vol_masque])) {
                // Mettre à jour la carte
                updateCarte($currentUser['id'], 'vol', $quantite);
                
                $success = "Réservation créée avec succès ! Prix total : {$prix_total} {$devise}";
            } else {
                $error = 'Une erreur est survenue lors de la création de la réservation';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="hero">
    <h1>✈️ Réservation de Vol</h1>
    <p>Réservez votre vol ou expédiez votre cargaison</p>
</div>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="flash flash-success">
                <?= htmlspecialchars($success) ?>
                <div style="margin-top: 1rem;">
                    <a href="/member/mes-reservations.php" class="btn btn-primary">Voir mes réservations</a>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="reservationForm">
            <div class="form-group">
                <label for="type">Type de réservation *</label>
                <select id="type" name="type" class="form-control" required onchange="updateForm()">
                    <option value="">Sélectionnez le type</option>
                    <option value="vol_simple">Vol Simple (Passager)</option>
                    <option value="cargaison">Cargaison (Fret)</option>
                </select>
            </div>
            
            <div class="form-group" id="classe_group" style="display: none;">
                <label for="classe">Classe *</label>
                <select id="classe" name="classe" class="form-control" onchange="updatePriceEstimate()">
                    <option value="">Sélectionnez la classe</option>
                    <?php if (!$isVIP): ?>
                        <option value="2">2ème Classe</option>
                    <?php endif; ?>
                    <option value="1">1ère Classe</option>
                    <?php if ($isVIP): ?>
                        <option value="vip">Classe VIP ⭐</option>
                    <?php endif; ?>
                </select>
                <?php if ($isVIP): ?>
                    <small style="color: var(--primary);">✨ En tant que VIP, vous bénéficiez de -20% sur les classes 1 et réduction VIP</small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="quantite" id="quantite_label">Quantité *</label>
                <input type="number" id="quantite" name="quantite" class="form-control" min="1" required onchange="updatePriceEstimate()">
                <small id="quantite_help" style="color: var(--gray);"></small>
            </div>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="date_vol">Date du vol *</label>
                    <input type="date" id="date_vol" name="date_vol" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label for="heure_vol">Heure du vol *</label>
                    <input type="time" id="heure_vol" name="heure_vol" class="form-control" required>
                </div>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="vol_masque" name="vol_masque" value="1">
                <label for="vol_masque">
                    🔒 Vol masqué (+10 diamants) - Votre réservation n'apparaîtra pas dans les horaires publics
                </label>
            </div>
            
            <div id="price_estimate" style="display: none; background: rgba(0, 217, 255, 0.1); padding: 1.5rem; border-radius: 10px; margin: 1.5rem 0; border: 1px solid rgba(0, 217, 255, 0.3);">
                <h4 style="color: var(--primary);">💰 Estimation du prix</h4>
                <p id="price_text" style="font-size: 1.3rem; font-weight: 700; margin-top: 0.5rem;"></p>
                <p id="vol_masque_cost" style="color: var(--gray); font-size: 0.9rem; display: none;">+ 10 diamants pour vol masqué</p>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Confirmer la réservation</button>
        </form>
    </div>
</div>

<script>
const isVIP = <?= $isVIP ? 'true' : 'false' ?>;

const tarifsVolSimple = {
    'vip': {
        1: { prix: 20, devise: 'or' },
        2: { prix: 5, devise: 'diamants' },
        5: { prix: 15, devise: 'diamants' },
        10: { prix: 25, devise: 'diamants' },
        20: { prix: 40, devise: 'diamants' },
        25: { prix: 1, devise: 'netherite' }
    },
    '1': {
        1: { prix: 10, devise: 'or' },
        2: { prix: 1, devise: 'diamants' },
        5: { prix: 3, devise: 'diamants' },
        10: { prix: 7, devise: 'diamants' },
        15: { prix: 12, devise: 'diamants' },
        20: { prix: 16, devise: 'diamants' },
        25: { prix: 24, devise: 'diamants' }
    },
    '2': {
        1: { prix: 5, devise: 'or/améthyste' },
        2: { prix: 10, devise: 'or/améthyste' },
        5: { prix: 20, devise: 'or/améthyste' },
        10: { prix: 5, devise: 'diamants' }
    }
};

function updateForm() {
    const type = document.getElementById('type').value;
    const classeGroup = document.getElementById('classe_group');
    const quantiteLabel = document.getElementById('quantite_label');
    const quantiteHelp = document.getElementById('quantite_help');
    
    if (type === 'vol_simple') {
        classeGroup.style.display = 'block';
        quantiteLabel.textContent = 'Nombre de vols *';
        quantiteHelp.textContent = 'Combien de vols souhaitez-vous réserver ?';
    } else if (type === 'cargaison') {
        classeGroup.style.display = 'none';
        quantiteLabel.textContent = 'Nombre de stacks *';
        quantiteHelp.textContent = '1 stack = 1 slot';
    } else {
        classeGroup.style.display = 'none';
        quantiteHelp.textContent = '';
    }
    
    updatePriceEstimate();
}

function updatePriceEstimate() {
    const type = document.getElementById('type').value;
    const classe = document.getElementById('classe').value;
    const quantite = parseInt(document.getElementById('quantite').value) || 0;
    const volMasque = document.getElementById('vol_masque').checked;
    const priceEstimate = document.getElementById('price_estimate');
    const priceText = document.getElementById('price_text');
    const volMasqueCost = document.getElementById('vol_masque_cost');
    
    if (!type || quantite <= 0) {
        priceEstimate.style.display = 'none';
        return;
    }
    
    let prix = 0;
    let devise = 'diamants';
    
    if (type === 'vol_simple' && classe) {
        const tarifsClasse = tarifsVolSimple[classe];
        const seuils = Object.keys(tarifsClasse).map(Number).sort((a, b) => a - b);
        
        let tarif = null;
        for (const seuil of seuils) {
            if (quantite <= seuil) {
                tarif = tarifsClasse[seuil];
                break;
            }
        }
        
        if (!tarif) {
            tarif = tarifsClasse[seuils[seuils.length - 1]];
        }
        
        prix = tarif.prix;
        devise = tarif.devise;
        
        // Réduction VIP (-20%) sauf pour classe VIP
        if (isVIP && classe !== 'vip') {
            prix *= 0.8;
        }
    } else if (type === 'cargaison') {
        if (quantite <= 1) {
            prix = 1;
        } else if (quantite <= 5) {
            prix = 3;
        } else if (quantite <= 10) {
            prix = 5;
        } else if (quantite <= 20) {
            prix = 7;
        } else if (quantite <= 50) {
            prix = 10;
        } else if (quantite <= 100) {
            prix = 15;
        } else {
            const stacksSupp = quantite - 100;
            prix = 15 + Math.ceil(stacksSupp / 5);
        }
        devise = 'diamants';
    }
    
    if (prix > 0) {
        priceText.textContent = `${prix.toFixed(2)} ${devise}`;
        priceEstimate.style.display = 'block';
        
        if (volMasque) {
            volMasqueCost.style.display = 'block';
        } else {
            volMasqueCost.style.display = 'none';
        }
    } else {
        priceEstimate.style.display = 'none';
    }
}

document.getElementById('vol_masque').addEventListener('change', updatePriceEstimate);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
