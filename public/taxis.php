<?php
$pageTitle = 'Commander un Taxi - Aéroport Minecraft';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$currentUser = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classe = $_POST['classe'] ?? '';
    $type = $_POST['type'] ?? '';
    $coordonnees_depart = sanitize($_POST['coordonnees_depart'] ?? '');
    $coordonnees_arrivee = sanitize($_POST['coordonnees_arrivee'] ?? '');
    $date_depart = $_POST['date_depart'] ?? '';
    $heure_depart = $_POST['heure_depart'] ?? '';
    $date_retour = $_POST['date_retour'] ?? '';
    $heure_retour = $_POST['heure_retour'] ?? '';
    $temps_attente = intval($_POST['temps_attente'] ?? 0);
    $prix_base = floatval($_POST['prix_base'] ?? 0);
    $vol_masque = isset($_POST['vol_masque']) ? 1 : 0;
    
    // Validation
    if (empty($classe) || empty($type) || empty($coordonnees_arrivee) || empty($date_depart) || empty($heure_depart) || $prix_base <= 0) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } elseif (strtotime($date_depart) < strtotime(date('Y-m-d'))) {
        $error = 'La date de départ doit être dans le futur';
    } elseif (($type === 'retour' || $type === 'aller_retour') && (empty($date_retour) || empty($heure_retour))) {
        $error = 'Veuillez renseigner la date et l\'heure de retour';
    } else {
        // Calculer le prix total
        $prix_total = calculerPrixTaxi($classe, $type, $prix_base);
        
        // Ajouter le coût du vol masqué
        if ($vol_masque) {
            $prix_total += 10;
        }
        
        // Créer la commande taxi
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO taxis (user_id, classe, type, coordonnees_depart, coordonnees_arrivee, date_depart, heure_depart, date_retour, heure_retour, temps_attente, prix_total, vol_masque, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')
        ");
        
        if ($stmt->execute([$currentUser['id'], $classe, $type, $coordonnees_depart, $coordonnees_arrivee, $date_depart, $heure_depart, $date_retour ?: null, $heure_retour ?: null, $temps_attente, $prix_total, $vol_masque])) {
            // Mettre à jour la carte
            updateCarte($currentUser['id'], 'taxi', 1);
            
            $success = "Commande de taxi créée avec succès ! Prix total : " . number_format($prix_total, 2) . " diamants";
        } else {
            $error = 'Une erreur est survenue lors de la création de la commande';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <h1>🚕 Commander un Taxi</h1>
    <p>Service de transport personnalisé pour tous vos déplacements</p>
</div>

<div style="max-width: 900px; margin: 0 auto;">
    <div class="card">
        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="flash flash-success">
                <?= htmlspecialchars($success) ?>
                <div style="margin-top: 1rem;">
                    <a href="/member/mes-taxis.php" class="btn btn-primary">Voir mes taxis</a>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="taxiForm">
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="classe">Classe *</label>
                    <select id="classe" name="classe" class="form-control" required>
                        <option value="">Sélectionnez la classe</option>
                        <option value="2">2ème Classe</option>
                        <option value="1">1ère Classe</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="type">Type de trajet *</label>
                    <select id="type" name="type" class="form-control" required onchange="updateFormFields()">
                        <option value="">Sélectionnez le type</option>
                        <option value="aller">Aller simple (+15%)</option>
                        <option value="retour">Retour (+20%)</option>
                        <option value="aller_retour">Aller-Retour (×2)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="coordonnees_depart">Coordonnées de départ (optionnel)</label>
                <input type="text" id="coordonnees_depart" name="coordonnees_depart" class="form-control" 
                       placeholder="Ex: X: 100, Y: 64, Z: -250">
                <small style="color: var(--gray);">Si non renseigné, départ depuis l'aéroport</small>
            </div>
            
            <div class="form-group">
                <label for="coordonnees_arrivee">Coordonnées d'arrivée *</label>
                <input type="text" id="coordonnees_arrivee" name="coordonnees_arrivee" class="form-control" required
                       placeholder="Ex: X: 500, Y: 70, Z: 1000">
            </div>
            
            <h3 style="color: var(--secondary); margin-top: 2rem;">📅 Départ</h3>
            
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="date_depart">Date de départ *</label>
                    <input type="date" id="date_depart" name="date_depart" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label for="heure_depart">Heure de départ *</label>
                    <input type="time" id="heure_depart" name="heure_depart" class="form-control" required>
                </div>
            </div>
            
            <div id="retour_section" style="display: none;">
                <h3 style="color: var(--secondary); margin-top: 2rem;">🔄 Retour</h3>
                
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="date_retour">Date de retour</label>
                        <input type="date" id="date_retour" name="date_retour" class="form-control" min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="heure_retour">Heure de retour</label>
                        <input type="time" id="heure_retour" name="heure_retour" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="temps_attente">Temps d'attente entre aller et retour (minutes)</label>
                    <input type="number" id="temps_attente" name="temps_attente" class="form-control" min="0" placeholder="Ex: 60">
                </div>
            </div>
            
            <div class="form-group">
                <label for="prix_base">Prix de base (en diamants) *</label>
                <input type="number" id="prix_base" name="prix_base" class="form-control" min="1" step="0.1" required 
                       placeholder="Estimez la distance et complexité du trajet">
                <small style="color: var(--gray);">Le prix final sera calculé selon le type de trajet</small>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="vol_masque" name="vol_masque" value="1" onchange="updatePriceEstimate()">
                <label for="vol_masque">
                    🔒 Trajet masqué (+10 diamants) - Ne sera pas affiché dans les horaires publics
                </label>
            </div>
            
            <div id="price_estimate" style="display: none; background: rgba(0, 217, 255, 0.1); padding: 1.5rem; border-radius: 10px; margin: 1.5rem 0; border: 1px solid rgba(0, 217, 255, 0.3);">
                <h4 style="color: var(--primary);">💰 Estimation du prix total</h4>
                <p id="price_calculation" style="font-size: 1.1rem; margin-top: 0.5rem; color: var(--gray);"></p>
                <p id="price_text" style="font-size: 1.5rem; font-weight: 700; margin-top: 0.5rem; color: var(--primary);"></p>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Commander le taxi</button>
        </form>
    </div>
    
    <div class="card" style="margin-top: 2rem; background: rgba(0, 217, 255, 0.05);">
        <h3 style="color: var(--primary);">ℹ️ Comment fonctionne la tarification ?</h3>
        <ul style="list-style: none; padding: 0; margin-top: 1rem;">
            <li style="margin: 0.8rem 0;">
                <strong style="color: var(--secondary);">→ Aller simple :</strong> Prix de base × 1.15
            </li>
            <li style="margin: 0.8rem 0;">
                <strong style="color: var(--secondary);">← Retour :</strong> Prix de base × 1.20
            </li>
            <li style="margin: 0.8rem 0;">
                <strong style="color: var(--secondary);">↔ Aller-Retour :</strong> Prix de base × 2
            </li>
        </ul>
        <p style="color: var(--gray); margin-top: 1rem;">
            Le prix de base dépend de la distance et de la complexité du trajet. Estimez-le en fonction de vos besoins.
        </p>
    </div>
</div>

<script>
function updateFormFields() {
    const type = document.getElementById('type').value;
    const retourSection = document.getElementById('retour_section');
    const dateRetour = document.getElementById('date_retour');
    const heureRetour = document.getElementById('heure_retour');
    
    if (type === 'retour' || type === 'aller_retour') {
        retourSection.style.display = 'block';
        dateRetour.required = true;
        heureRetour.required = true;
    } else {
        retourSection.style.display = 'none';
        dateRetour.required = false;
        heureRetour.required = false;
    }
    
    updatePriceEstimate();
}

function updatePriceEstimate() {
    const type = document.getElementById('type').value;
    const prixBase = parseFloat(document.getElementById('prix_base').value) || 0;
    const volMasque = document.getElementById('vol_masque').checked;
    const priceEstimate = document.getElementById('price_estimate');
    const priceCalculation = document.getElementById('price_calculation');
    const priceText = document.getElementById('price_text');
    
    if (!type || prixBase <= 0) {
        priceEstimate.style.display = 'none';
        return;
    }
    
    let multiplicateur = 0;
    let explication = '';
    
    switch(type) {
        case 'aller':
            multiplicateur = 1.15;
            explication = `${prixBase} × 1.15`;
            break;
        case 'retour':
            multiplicateur = 1.20;
            explication = `${prixBase} × 1.20`;
            break;
        case 'aller_retour':
            multiplicateur = 2;
            explication = `${prixBase} × 2`;
            break;
    }
    
    let prixTotal = prixBase * multiplicateur;
    
    if (volMasque) {
        explication += ` + 10 (vol masqué)`;
        prixTotal += 10;
    }
    
    priceCalculation.textContent = explication;
    priceText.textContent = `${prixTotal.toFixed(2)} diamants`;
    priceEstimate.style.display = 'block';
}

document.getElementById('prix_base').addEventListener('input', updatePriceEstimate);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
