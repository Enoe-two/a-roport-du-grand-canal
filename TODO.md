# 📋 Fichiers Restants à Créer

Ce document liste les fichiers qui n'ont pas encore été créés dans le projet. Ils suivent tous la même logique que les fichiers existants.

## ✅ Fichiers Déjà Créés

### Pages publiques
- ✅ `/public/index.php` - Page d'accueil
- ✅ `/public/login.php` - Connexion
- ✅ `/public/register.php` - Inscription
- ✅ `/public/logout.php` - Déconnexion
- ✅ `/public/reservation.php` - Réservation de vols
- ✅ `/public/taxis.php` - Commande de taxis
- ✅ `/public/horaire.php` - Horaires publics

### Espace Membre
- ✅ `/public/member/dashboard.php` - Dashboard membre

### Configuration
- ✅ `/config/database.php` - Configuration BDD
- ✅ `/config/database.sql` - Script SQL d'initialisation
- ✅ `/includes/functions.php` - Fonctions utilitaires
- ✅ `/includes/header.php` - En-tête HTML
- ✅ `/includes/footer.php` - Pied de page HTML

### Assets
- ✅ `/assets/css/style.css` - Styles CSS
- ✅ `/assets/js/main.js` - JavaScript

### Documentation
- ✅ `README.md` - Documentation principale
- ✅ `INSTALLATION.md` - Guide d'installation
- ✅ `railway.toml` - Configuration Railway

---

## 📝 Fichiers à Créer

### Espace Membre (`/public/member/`)

#### 1. `mes-reservations.php`
**Fonction** : Afficher toutes les réservations de l'utilisateur
**Contenu** :
- Liste des réservations (vols + cargaisons)
- Filtres par date, statut
- Actions : Modifier, Reporter, Annuler
- Respect des délais (1 jour pour modification, 2 jours pour annulation)

**Structure similaire à** : `dashboard.php` avec tableau des réservations

#### 2. `messagerie.php`
**Fonction** : Messagerie avec l'admin
**Contenu** :
- Liste des conversations
- Envoi de nouveaux messages
- Lecture des messages reçus
- Indication messages non lus

**SQL requis** :
```sql
SELECT * FROM messages 
WHERE (from_user_id = ? OR to_user_id = ?) 
ORDER BY created_at DESC
```

#### 3. `mes-cargaisons.php`
**Fonction** : Liste des cargaisons uniquement
**Contenu** :
- Filtre sur `type = 'cargaison'`
- Détails : nombre de stacks, prix, date
- Statut de livraison

#### 4. `ma-carte.php`
**Fonction** : Carte de membre détaillée
**Contenu** :
- Statistiques complètes
- Historique des achats
- Graphique d'utilisation (optionnel)

#### 5. `mes-taxis.php`
**Fonction** : Liste des commandes de taxis
**Contenu** :
- Tous les taxis commandés
- Détails : coordonnées, dates, prix
- Actions : Modifier, Annuler

---

### Espace VIP (`/public/vip/`)

Les fichiers VIP sont identiques aux fichiers member, avec :
- Vérification du rôle VIP : `requireVIP()`
- Badge VIP visible partout
- Mention des réductions (-20%)

**Fichiers à créer** :
1. `dashboard.php` - Copie de `/member/dashboard.php` avec `requireVIP()`
2. `mes-reservations.php`
3. `messagerie.php`
4. `mes-cargaisons.php`
5. `ma-carte.php`
6. `mes-taxis.php`

---

### Espace Admin (`/public/admin/`)

#### 1. `dashboard.php`
**Fonction** : Vue d'ensemble administrative
**Contenu** :
- Statistiques globales
- Nouveaux comptes en attente
- Réservations récentes
- Messages non lus

**SQL exemples** :
```sql
-- Comptes en attente
SELECT COUNT(*) FROM users WHERE status = 'pending'

-- Réservations du jour
SELECT COUNT(*) FROM reservations WHERE date_vol = CURDATE()

-- Messages non lus
SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND is_read = 0
```

#### 2. `reservations.php`
**Fonction** : Gestion de toutes les réservations
**Contenu** :
- Liste complète (vols + taxis + cargaisons)
- Filtres : date, statut, utilisateur
- Actions : Voir détails, Modifier, Confirmer, Annuler, Reporter
- Voir les vols masqués

**SQL** :
```sql
SELECT r.*, u.pseudo_minecraft 
FROM reservations r
JOIN users u ON r.user_id = u.id
ORDER BY r.date_vol DESC
```

#### 3. `comptes.php`
**Fonction** : Gestion des utilisateurs
**Contenu** :
- Liste de tous les comptes
- Filtres : rôle, statut, faction
- Actions :
  - Approuver comptes en attente
  - Promouvoir au rang VIP
  - Rétrograder VIP → Member
  - Supprimer compte
  - Voir l'historique

**SQL exemples** :
```sql
-- Approuver un compte
UPDATE users SET status = 'approved' WHERE id = ?

-- Promouvoir en VIP
UPDATE users SET role = 'vip' WHERE id = ?

-- Liste des comptes en attente
SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC
```

#### 4. `messagerie.php`
**Fonction** : Messagerie admin avec tous les utilisateurs
**Contenu** :
- Liste de tous les utilisateurs
- Conversations avec chaque utilisateur
- Envoi de messages
- Indication des messages non lus par utilisateur

**SQL** :
```sql
-- Messages avec un utilisateur spécifique
SELECT * FROM messages 
WHERE (from_user_id = ? AND to_user_id = ?) 
   OR (from_user_id = ? AND to_user_id = ?)
ORDER BY created_at ASC
```

---

## 🔧 Fonctions Utilitaires Supplémentaires

Ces fonctions peuvent être ajoutées à `/includes/functions.php` :

```php
// Envoyer un message
function sendMessage($fromUserId, $toUserId, $subject, $message) {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO messages (from_user_id, to_user_id, subject, message) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$fromUserId, $toUserId, $subject, $message]);
}

// Marquer un message comme lu
function markMessageAsRead($messageId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    return $stmt->execute([$messageId]);
}

// Obtenir l'ID admin
function getAdminId() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch();
    return $admin ? $admin['id'] : null;
}

// Mettre à jour le statut d'une réservation
function updateReservationStatus($reservationId, $newStatus) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    return $stmt->execute([$newStatus, $reservationId]);
}

// Promouvoir un utilisateur en VIP
function promoteToVIP($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET role = 'vip' WHERE id = ?");
    return $stmt->execute([$userId]);
}

// Approuver un compte
function approveAccount($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
    return $stmt->execute([$userId]);
}
```

---

## 🎨 Structure HTML Standard

Tous les fichiers PHP suivent cette structure :

```php
<?php
$pageTitle = 'Titre de la Page - Aéroport Minecraft';
require_once __DIR__ . '/../../includes/functions.php';

// Protection si nécessaire
requireLogin(); // ou requireVIP() ou requireAdmin()

// Logique PHP ici
$currentUser = getCurrentUser();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation et traitement
}

// Requêtes SQL pour récupérer les données
$pdo = getDB();
// ...

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="hero">
    <h1>Titre Principal</h1>
    <p>Sous-titre</p>
</div>

<!-- Contenu de la page -->
<div class="card">
    <!-- Votre contenu -->
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```

---

## 📊 Exemples de Requêtes SQL Utiles

### Pour les réservations
```sql
-- Réservations d'un utilisateur
SELECT * FROM reservations WHERE user_id = ? ORDER BY date_vol DESC;

-- Réservations futures
SELECT * FROM reservations 
WHERE user_id = ? AND date_vol >= CURDATE() 
ORDER BY date_vol ASC;

-- Modifier une réservation
UPDATE reservations 
SET date_vol = ?, heure_vol = ?, updated_at = NOW() 
WHERE id = ? AND user_id = ?;

-- Annuler une réservation
UPDATE reservations SET status = 'annule' WHERE id = ?;
```

### Pour les messages
```sql
-- Messages entre admin et utilisateur
SELECT m.*, u_from.pseudo_minecraft as from_pseudo, u_to.pseudo_minecraft as to_pseudo
FROM messages m
JOIN users u_from ON m.from_user_id = u_from.id
JOIN users u_to ON m.to_user_id = u_to.id
WHERE (m.from_user_id = ? AND m.to_user_id = ?)
   OR (m.from_user_id = ? AND m.to_user_id = ?)
ORDER BY m.created_at DESC;

-- Compter messages non lus
SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND is_read = 0;
```

### Pour l'admin
```sql
-- Comptes en attente de validation
SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC;

-- Toutes les réservations (avec utilisateur)
SELECT r.*, u.pseudo_minecraft, u.role
FROM reservations r
JOIN users u ON r.user_id = u.id
ORDER BY r.date_vol DESC;

-- Statistiques globales
SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'vip' THEN 1 ELSE 0 END) as vip_users,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_users
FROM users;
```

---

## 🚀 Prochaines Étapes

1. **Créer les fichiers manquants** en suivant la structure ci-dessus
2. **Tester chaque fonctionnalité** localement
3. **Ajouter les images** dans `assets/images/`
4. **Déployer sur Railway**
5. **Créer des comptes de test**
6. **Tester en production**

---

## 💡 Conseils

- **Copier-coller intelligemment** : Les fichiers VIP sont identiques aux fichiers member
- **Réutiliser le code** : Les tableaux de réservations sont similaires partout
- **Sécurité** : Toujours valider les entrées avec `sanitize()`
- **Design cohérent** : Utiliser les classes CSS existantes
- **Messages flash** : Utiliser `setFlash()` pour les confirmations

---

Bon courage ! Le plus gros est déjà fait ✨
