# 🛫 Aéroport Minecraft - Site Web PHP

Site web complet pour gérer un aéroport Minecraft avec système de réservations, taxis, cargaisons et gestion des utilisateurs.

## 🎯 Fonctionnalités

### Pour les utilisateurs
- ✅ Inscription et connexion sécurisées
- ✈️ Réservation de vols (3 classes : 2ème, 1ère, VIP)
- 📦 Expédition de cargaisons (tarifs par stacks)
- 🚕 Commande de taxis (aller, retour, aller-retour)
- 🔒 Option "vol masqué" pour la confidentialité
- 📊 Dashboard personnel avec statistiques
- 💬 Messagerie avec l'administration
- 🎫 Carte de membre avec historique

### Pour les VIP
- ⭐ -20% sur tous les vols (sauf classe VIP)
- 🎖️ Accès à la classe VIP exclusive
- 🚫 Pas d'accès à la 2ème classe
- 1 vol gratuit tous les 48h en classe VIP

### Pour les administrateurs
- 👥 Gestion des comptes (validation, promotion VIP)
- 📋 Gestion des réservations (modification, annulation)
- 💬 Messagerie avec tous les utilisateurs
- 📊 Vue complète des horaires et statistiques

## 🏗️ Structure du projet

```
minecraft-airport/
├── config/
│   ├── database.php          # Configuration BDD
│   └── database.sql           # Script d'initialisation
├── includes/
│   ├── functions.php          # Fonctions utilitaires
│   ├── header.php             # En-tête commun
│   └── footer.php             # Pied de page commun
├── public/                    # Dossier public (racine web)
│   ├── index.php              # Page d'accueil
│   ├── login.php              # Connexion
│   ├── register.php           # Inscription
│   ├── reservation.php        # Réservation de vols
│   ├── taxis.php              # Commande de taxis
│   ├── horaire.php            # Horaires publics
│   └── logout.php             # Déconnexion
├── member/                    # Espace membre
│   ├── dashboard.php
│   ├── mes-reservations.php
│   ├── messagerie.php
│   ├── mes-cargaisons.php
│   ├── ma-carte.php
│   └── mes-taxis.php
├── vip/                       # Espace VIP
│   └── (mêmes pages que member)
├── admin/                     # Espace admin
│   ├── dashboard.php
│   ├── reservations.php
│   ├── comptes.php
│   └── messagerie.php
├── assets/
│   ├── css/
│   │   └── style.css          # Styles modernes
│   ├── js/
│   │   └── main.js            # JavaScript
│   └── images/                # Images (banner, background)
├── railway.toml               # Config Railway
└── .htaccess                  # Configuration Apache

```

## 📊 Base de données

### Tables principales :
- **users** : Comptes utilisateurs (member, vip, admin)
- **reservations** : Vols et cargaisons
- **taxis** : Commandes de taxis
- **messages** : Messagerie interne
- **cartes** : Statistiques des membres

## 🚀 Installation

### 1. Prérequis
- PHP 7.4+ avec PDO MySQL
- MySQL/MariaDB 5.7+
- Apache (avec mod_rewrite) ou serveur PHP intégré

### 2. Configuration locale

```bash
# Cloner ou télécharger le projet
cd minecraft-airport

# Créer la base de données
mysql -u root -p < config/database.sql

# Configurer les variables d'environnement (ou modifier config/database.php)
# export DB_HOST=localhost
# export DB_USER=root
# export DB_PASS=votre_mot_de_passe
# export DB_NAME=minecraft_airport

# Lancer le serveur PHP
php -S localhost:8000 -t public
```

Accédez au site : `http://localhost:8000`

### 3. Compte admin par défaut
- **Pseudo Minecraft** : Admin
- **Mot de passe** : password
- ⚠️ **IMPORTANT** : Changez ce mot de passe immédiatement !

## ☁️ Déploiement sur Railway

### 1. Préparation
1. Créez un compte sur [Railway.app](https://railway.app)
2. Installez Railway CLI (optionnel)

### 2. Déploiement

#### Via Railway Dashboard (recommandé)
1. Créez un nouveau projet Railway
2. Ajoutez un service MySQL depuis le marketplace
3. Ajoutez un nouveau service "GitHub Repo" ou "Empty Service"
4. Uploadez votre code
5. Les variables d'environnement seront automatiquement détectées

#### Via GitHub
1. Poussez le code sur GitHub
2. Connectez Railway à votre repo
3. Railway détectera automatiquement railway.toml
4. Ajoutez un service MySQL
5. Déployez !

### 3. Configuration de la base de données Railway

Une fois MySQL déployé sur Railway :
1. Récupérez les variables de connexion (disponibles dans les variables d'environnement)
2. Railway les injectera automatiquement :
   - `MYSQL_HOST` → `DB_HOST`
   - `MYSQL_USER` → `DB_USER`
   - `MYSQL_PASSWORD` → `DB_PASS`
   - `MYSQL_DATABASE` → `DB_NAME`

3. Connectez-vous à MySQL et exécutez le script d'initialisation :
```bash
# Via Railway CLI
railway connect mysql
# Puis copiez-collez le contenu de config/database.sql
```

Ou utilisez un client MySQL externe avec les credentials fournis par Railway.

### 4. Variables d'environnement Railway

Railway configure automatiquement :
- `PORT` : Port d'écoute (géré automatiquement)
- `MYSQL_*` : Variables de base de données

## 🎨 Personnalisation

### Images
Ajoutez vos images dans `assets/images/` :
- `banner.png` : Bannière en haut du site (1920x150px recommandé)
- `background.jpg` : Image de fond (optionnel)

### Couleurs
Modifiez les variables CSS dans `assets/css/style.css` :
```css
:root {
    --primary: #00d9ff;      /* Couleur principale */
    --secondary: #ff6b35;    /* Couleur secondaire */
    --dark: #0a0e27;         /* Fond sombre */
    /* ... */
}
```

## 💰 Système de tarification

### Vols Simple
- **2ème Classe** : De 5 or à 5 diamants
- **1ère Classe** : De 10 or à 24 diamants
- **Classe VIP** : De 20 or à 1 netherite
- **Réduction VIP** : -20% sur classes 1 et 2

### Cargaison
- 1 stack = 1 diamant
- 100 stacks = 15 diamants
- +100 stacks : +1 diamant par 5 stacks

### Taxis
- Aller : Prix base × 1.15
- Retour : Prix base × 1.20
- Aller-retour : Prix base × 2

### Option Vol Masqué
- +10 diamants pour cacher le vol des horaires publics

## 🔒 Sécurité

- ✅ Mots de passe hashés avec bcrypt
- ✅ Protection CSRF
- ✅ Validation des entrées
- ✅ Protection SQL injection (PDO)
- ✅ Sessions sécurisées
- ✅ Protection des fichiers sensibles (.htaccess)

## 📝 Modifications et reportages

### Règles de modification/annulation
- **Modification** : Possible jusqu'à 1 jour avant le vol
- **Annulation avec remboursement** : Jusqu'à 2 jours avant
- **Annulation tardive** : Non remboursée

## 🐛 Dépannage

### Erreur de connexion à la base de données
- Vérifiez les credentials dans `config/database.php`
- Assurez-vous que MySQL est démarré
- Sur Railway, vérifiez les variables d'environnement

### Erreur 500
- Vérifiez les logs PHP
- Assurez-vous que PDO MySQL est installé : `php -m | grep pdo_mysql`
- Vérifiez les permissions des fichiers

### Images ne s'affichent pas
- Vérifiez que les images sont dans `assets/images/`
- Vérifiez les permissions des dossiers
- Sur Railway, utilisez des URLs absolues pour les images

### Redirection en boucle
- Vérifiez la configuration .htaccess
- Désactivez mod_rewrite si nécessaire
- Utilisez le serveur PHP intégré pour tester

## 📞 Support

Pour toute question ou problème :
1. Vérifiez la documentation ci-dessus
2. Consultez les logs (Railway Dashboard ou `tail -f /var/log/php/error.log`)
3. Vérifiez que toutes les dépendances sont installées

## 📄 Licence

Ce projet est libre d'utilisation pour votre serveur Minecraft.

---

**Bon vol ! ✈️**
