# 📦 Guide d'Installation - Aéroport Minecraft

Ce guide vous accompagne pas à pas dans l'installation et le déploiement de votre site d'aéroport Minecraft.

## 🎯 Table des matières
1. [Installation Locale](#installation-locale)
2. [Déploiement sur Railway](#déploiement-sur-railway)
3. [Configuration des Images](#configuration-des-images)
4. [Premier Compte Admin](#premier-compte-admin)
5. [Dépannage](#dépannage)

---

## 🏠 Installation Locale

### Prérequis
- PHP 7.4 ou supérieur
- MySQL/MariaDB 5.7 ou supérieur
- Serveur web (Apache/Nginx) OU utiliser le serveur PHP intégré

### Étape 1 : Télécharger le projet
```bash
# Si vous avez le projet en ZIP, décompressez-le
unzip minecraft-airport.zip
cd minecraft-airport
```

### Étape 2 : Configuration de la base de données

#### Option A : Ligne de commande MySQL
```bash
# Connectez-vous à MySQL
mysql -u root -p

# Créez la base de données et importez le schéma
mysql -u root -p < config/database.sql
```

#### Option B : phpMyAdmin
1. Ouvrez phpMyAdmin dans votre navigateur
2. Créez une nouvelle base de données nommée `minecraft_airport`
3. Sélectionnez la base de données
4. Cliquez sur "Importer"
5. Sélectionnez le fichier `config/database.sql`
6. Cliquez sur "Exécuter"

### Étape 3 : Configuration des identifiants

Modifiez le fichier `config/database.php` si nécessaire :

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'votre_mot_de_passe');
define('DB_NAME', 'minecraft_airport');
```

### Étape 4 : Lancer le serveur

#### Option A : Serveur PHP intégré (recommandé pour le développement)
```bash
php -S localhost:8000 -t public
```
Accédez au site : http://localhost:8000

#### Option B : Apache/Nginx
1. Placez le projet dans votre dossier web (ex: `/var/www/html/`)
2. Configurez le VirtualHost pour pointer vers le dossier `public/`
3. Assurez-vous que mod_rewrite est activé (Apache)

### Étape 5 : Connexion admin
- **Pseudo Minecraft** : Admin
- **Mot de passe** : password

⚠️ **IMPORTANT** : Changez ce mot de passe immédiatement après la première connexion !

---

## ☁️ Déploiement sur Railway

### Étape 1 : Créer un compte Railway
1. Allez sur [railway.app](https://railway.app)
2. Inscrivez-vous (gratuit avec GitHub)

### Étape 2 : Créer un nouveau projet
1. Cliquez sur "New Project"
2. Sélectionnez "Empty Project"

### Étape 3 : Ajouter MySQL
1. Dans votre projet, cliquez sur "+ New"
2. Sélectionnez "Database" → "Add MySQL"
3. Railway va automatiquement créer une base de données

### Étape 4 : Déployer votre code

#### Option A : Via GitHub (recommandé)
1. Créez un repository GitHub avec votre code
2. Dans Railway, cliquez sur "+ New"
3. Sélectionnez "GitHub Repo"
4. Choisissez votre repository
5. Railway détecte automatiquement la configuration grâce à `railway.toml`

#### Option B : Via Railway CLI
```bash
# Installez Railway CLI
npm install -g @railway/cli

# Connectez-vous
railway login

# Initialisez le projet
railway init

# Déployez
railway up
```

### Étape 5 : Initialiser la base de données

1. Dans Railway, cliquez sur votre service MySQL
2. Copiez les identifiants de connexion (Host, Port, User, Password, Database)
3. Utilisez un client MySQL (comme MySQL Workbench, TablePlus, ou DBeaver)
4. Connectez-vous avec ces identifiants
5. Exécutez le contenu du fichier `config/database.sql`

Ou via Railway CLI :
```bash
# Connectez-vous à MySQL
railway connect mysql

# Copiez-collez le contenu de config/database.sql
```

### Étape 6 : Vérifier les variables d'environnement

Railway configure automatiquement :
- `MYSQL_HOST` → devient `DB_HOST`
- `MYSQL_USER` → devient `DB_USER`
- `MYSQL_PASSWORD` → devient `DB_PASS`
- `MYSQL_DATABASE` → devient `DB_NAME`

Ces conversions sont gérées dans `config/database.php` :
```php
define('DB_HOST', getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: 'localhost');
```

### Étape 7 : Accéder à votre site

1. Railway va générer une URL publique (ex: `votre-projet.railway.app`)
2. Cliquez sur le service de votre application
3. Sous "Settings", activez "Generate Domain"
4. Votre site est maintenant accessible !

---

## 🎨 Configuration des Images

### Bannière (header)
- **Nom** : `banner.png`
- **Emplacement** : `assets/images/banner.png`
- **Dimensions recommandées** : 1920×150px
- **Format** : PNG ou JPG

### Image de fond (optionnel)
- **Nom** : `background.jpg`
- **Emplacement** : `assets/images/background.jpg`
- **Dimensions** : Selon votre préférence (Full HD recommandé)
- **Format** : JPG

### Comment ajouter vos images ?

1. Créez vos images avec les dimensions recommandées
2. Placez-les dans le dossier `assets/images/`
3. Le site les chargera automatiquement

**Sur Railway** :
```bash
# Ajoutez vos images au repository Git
git add assets/images/banner.png
git add assets/images/background.jpg
git commit -m "Ajout des images"
git push

# Railway redéploiera automatiquement
```

---

## 👤 Premier Compte Admin

### Compte par défaut
- **Pseudo Discord** : Admin
- **Pseudo Minecraft** : Admin
- **Mot de passe** : password

### Changer le mot de passe admin

#### Via l'interface web :
1. Connectez-vous avec le compte admin
2. Allez dans votre profil
3. Changez le mot de passe

#### Via MySQL :
```sql
-- Générer un nouveau hash de mot de passe
-- Remplacez 'nouveau_mot_de_passe' par votre mot de passe souhaité

UPDATE users 
SET password = '$2y$10$VOTRE_NOUVEAU_HASH' 
WHERE pseudo_minecraft = 'Admin';
```

Pour générer le hash, utilisez ce script PHP :
```php
<?php
echo password_hash('nouveau_mot_de_passe', PASSWORD_DEFAULT);
?>
```

---

## 🔧 Dépannage

### Erreur "Connection refused" (base de données)

**Cause** : Les identifiants de base de données sont incorrects

**Solution** :
1. Vérifiez `config/database.php`
2. Sur Railway, vérifiez que MySQL est bien démarré
3. Vérifiez les variables d'environnement

### Erreur 500 (Internal Server Error)

**Cause** : Erreur PHP ou problème de configuration

**Solutions** :
1. Activez l'affichage des erreurs PHP (développement uniquement) :
   ```php
   // Au début de config/database.php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
2. Vérifiez les logs PHP
3. Sur Railway, consultez les logs : cliquez sur votre service → "Logs"

### Les images ne s'affichent pas

**Causes possibles** :
1. Chemin incorrect
2. Permissions incorrectes
3. Images non uploadées sur Railway

**Solutions** :
```bash
# Vérifier les permissions (local)
chmod 755 assets/images/
chmod 644 assets/images/*

# Sur Railway, vérifier que les images sont dans Git
git status
git add assets/images/
git commit -m "Ajout images"
git push
```

### Redirection en boucle

**Cause** : Problème avec .htaccess

**Solution** :
1. Vérifiez que mod_rewrite est activé (Apache)
2. Utilisez le serveur PHP intégré pour tester :
   ```bash
   php -S localhost:8000 -t public
   ```

### Erreur "PDO driver not found"

**Cause** : Extension PDO MySQL non installée

**Solution** :
```bash
# Ubuntu/Debian
sudo apt-get install php-mysql

# CentOS/RHEL
sudo yum install php-mysqlnd

# macOS (via Homebrew)
brew install php
```

Redémarrez ensuite votre serveur web.

### Les réservations ne s'enregistrent pas

**Vérifications** :
1. Base de données bien initialisée ?
   ```sql
   SHOW TABLES;
   -- Doit afficher : users, reservations, taxis, messages, cartes
   ```
2. L'utilisateur est bien connecté ?
3. Vérifier les logs d'erreurs

---

## 📞 Support

Si vous rencontrez un problème non listé ici :

1. **Vérifiez les logs** (Railway Dashboard ou logs PHP locaux)
2. **Vérifiez la base de données** (tables créées, données présentes)
3. **Testez en local** avant de déployer sur Railway
4. **Consultez le README.md** pour plus d'informations

---

## ✅ Checklist de déploiement

- [ ] Base de données créée et initialisée
- [ ] Compte admin créé et mot de passe changé
- [ ] Images (banner.png) ajoutées
- [ ] Site accessible via l'URL
- [ ] Test de connexion réussi
- [ ] Test de réservation réussi
- [ ] Messagerie fonctionnelle
- [ ] Horaires s'affichent correctement

Félicitations ! Votre aéroport Minecraft est opérationnel ! ✈️
