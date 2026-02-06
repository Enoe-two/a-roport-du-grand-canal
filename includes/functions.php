<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier le rôle de l'utilisateur
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

// Rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

// Rediriger si non admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        header('Location: /index.php');
        exit;
    }
}

// Rediriger si non VIP
function requireVIP() {
    requireLogin();
    if (!hasRole('vip') && !hasRole('admin')) {
        header('Location: /index.php');
        exit;
    }
}

// Obtenir l'utilisateur actuel
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Calculer le prix d'un vol simple
function calculerPrixVolSimple($classe, $quantite, $isVIP = false) {
    $tarifs = [
        'vip' => [
            1 => ['prix' => 20, 'devise' => 'or'],
            2 => ['prix' => 5, 'devise' => 'diamants'],
            5 => ['prix' => 15, 'devise' => 'diamants'],
            10 => ['prix' => 25, 'devise' => 'diamants'],
            20 => ['prix' => 40, 'devise' => 'diamants'],
            25 => ['prix' => 1, 'devise' => 'netherite']
        ],
        '1' => [
            1 => ['prix' => 10, 'devise' => 'or'],
            2 => ['prix' => 1, 'devise' => 'diamants'],
            5 => ['prix' => 3, 'devise' => 'diamants'],
            10 => ['prix' => 7, 'devise' => 'diamants'],
            15 => ['prix' => 12, 'devise' => 'diamants'],
            20 => ['prix' => 16, 'devise' => 'diamants'],
            25 => ['prix' => 24, 'devise' => 'diamants']
        ],
        '2' => [
            1 => ['prix' => 5, 'devise' => 'or/améthyste'],
            2 => ['prix' => 10, 'devise' => 'or/améthyste'],
            5 => ['prix' => 20, 'devise' => 'or/améthyste'],
            10 => ['prix' => 5, 'devise' => 'diamants']
        ]
    ];
    
    if ($classe === 'vip' && !$isVIP) {
        return null; // Les non-VIP ne peuvent pas réserver en classe VIP
    }
    
    if ($classe === '2' && $isVIP) {
        return null; // Les VIP n'ont pas accès à la 2e classe
    }
    
    $tarifsClasse = $tarifs[$classe] ?? [];
    
    // Trouver le tarif correspondant
    $tarifsOrdonnes = array_keys($tarifsClasse);
    sort($tarifsOrdonnes);
    
    $tarif = null;
    foreach ($tarifsOrdonnes as $seuil) {
        if ($quantite <= $seuil) {
            $tarif = $tarifsClasse[$seuil];
            break;
        }
    }
    
    // Si quantité > tous les seuils, prendre le dernier
    if ($tarif === null) {
        $dernierSeuil = end($tarifsOrdonnes);
        $tarif = $tarifsClasse[$dernierSeuil];
    }
    
    // Appliquer réduction VIP (-20%)
    if ($isVIP && $classe !== 'vip') {
        $tarif['prix'] *= 0.8;
    }
    
    return $tarif;
}

// Calculer le prix de cargaison
function calculerPrixCargaison($stacks) {
    if ($stacks <= 1) return ['prix' => 1, 'devise' => 'diamants'];
    if ($stacks <= 5) return ['prix' => 3, 'devise' => 'diamants'];
    if ($stacks <= 10) return ['prix' => 5, 'devise' => 'diamants'];
    if ($stacks <= 20) return ['prix' => 7, 'devise' => 'diamants'];
    if ($stacks <= 50) return ['prix' => 10, 'devise' => 'diamants'];
    if ($stacks <= 100) return ['prix' => 15, 'devise' => 'diamants'];
    
    // Au-dessus de 100 stacks : 15d + 1d par 5 stacks supplémentaires
    $stacksSupp = $stacks - 100;
    $prixSupp = ceil($stacksSupp / 5);
    return ['prix' => 15 + $prixSupp, 'devise' => 'diamants'];
}

// Calculer le prix d'un taxi
function calculerPrixTaxi($classe, $type, $prixBase) {
    $multiplicateurs = [
        'aller' => 0.15,
        'retour' => 0.20,
        'aller_retour' => 2
    ];
    
    return $prixBase * $multiplicateurs[$type];
}

// Formater le prix
function formaterPrix($prix, $devise) {
    return number_format($prix, 2) . ' ' . $devise;
}

// Sécuriser les entrées
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Générer un message flash
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Afficher un message flash
function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $class = $flash['type'] === 'success' ? 'flash-success' : 'flash-error';
        echo "<div class='flash {$class}'>{$flash['message']}</div>";
        unset($_SESSION['flash']);
    }
}

// Mettre à jour la carte d'un utilisateur
function updateCarte($userId, $type, $quantite) {
    $pdo = getDB();
    
    // Vérifier si la carte existe
    $stmt = $pdo->prepare("SELECT * FROM cartes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $carte = $stmt->fetch();
    
    if (!$carte) {
        // Créer la carte
        $stmt = $pdo->prepare("INSERT INTO cartes (user_id) VALUES (?)");
        $stmt->execute([$userId]);
    }
    
    // Mettre à jour selon le type
    if ($type === 'vol') {
        $stmt = $pdo->prepare("UPDATE cartes SET vols_achetes = vols_achetes + ? WHERE user_id = ?");
        $stmt->execute([$quantite, $userId]);
    } elseif ($type === 'taxi') {
        $stmt = $pdo->prepare("UPDATE cartes SET taxis_achetes = taxis_achetes + ? WHERE user_id = ?");
        $stmt->execute([$quantite, $userId]);
    }
}

// Obtenir les statistiques de carte
function getCarteStats($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM cartes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $carte = $stmt->fetch();
    
    if (!$carte) {
        return [
            'vols_achetes' => 0,
            'vols_utilises' => 0,
            'taxis_achetes' => 0,
            'taxis_utilises' => 0
        ];
    }
    
    return $carte;
}

// Vérifier si un vol peut être modifié/annulé
function canModifyReservation($dateVol, $joursAvant) {
    $dateVol = new DateTime($dateVol);
    $maintenant = new DateTime();
    $diff = $maintenant->diff($dateVol);
    
    return $diff->days >= $joursAvant && $diff->invert === 0;
}
?>
