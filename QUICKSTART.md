# 🚀 Démarrage Rapide - Aéroport Minecraft

Guide ultra-rapide pour lancer votre site en 5 minutes !

---

## ⚡ Installation Express (Local)

```bash
# 1. Décompresser le projet
unzip minecraft-airport.zip
cd minecraft-airport

# 2. Créer la base de données
mysql -u root -p
> CREATE DATABASE minecraft_airport;
> quit;

# Importer le schéma
mysql -u root -p minecraft_airport < config/database.sql

# 3. Lancer le serveur
php -S localhost:8000 -t public

# 4. Ouvrir le navigateur
# http://localhost:8000

# 5. Se connecter avec le compte admin
# Pseudo: Admin
# Mot de passe: password
```

**C'est tout ! Votre site est opérationnel** ✅

---

## ☁️ Déploiement Railway (5 minutes)

1. **Créer un compte** sur [railway.app](https://railway.app)

2. **Nouveau projet** → "Empty Project"

3. **Ajouter MySQL** → "+ New" → "Database" → "MySQL"

4. **Déployer le code** :
   - Option A : Push sur GitHub puis connecter le repo
   - Option B : Railway CLI : `railway up`

5. **Initialiser la BDD** :
   ```bash
   railway connect mysql
   # Copier-coller le contenu de config/database.sql
   ```

6. **Générer un domaine** :
   - Cliquer sur votre service
   - Settings → Generate Domain

**Votre site est en ligne !** 🎉

---

## 🎯 Première Configuration

### 1. Changez le mot de passe admin !

Via MySQL :
```sql
UPDATE users 
SET password = '$2y$10$[NOUVEAU_HASH]' 
WHERE pseudo_minecraft = 'Admin';
```

Générer le hash avec :
```php
<?php echo password_hash('votre_nouveau_mdp', PASSWORD_DEFAULT); ?>
```

### 2. Ajoutez vos images

Placez dans `assets/images/` :
- `banner.png` (1920×150px)
- `background.jpg` (optionnel)

### 3. Testez les fonctionnalités

- ✅ Créer un compte utilisateur
- ✅ Approuver le compte (en tant qu'admin)
- ✅ Réserver un vol
- ✅ Commander un taxi
- ✅ Envoyer un message à l'admin

---

## 📱 Accès Rapide

### URLs Importantes
- `/` - Page d'accueil
- `/login.php` - Connexion
- `/register.php` - Inscription
- `/horaire.php` - Horaires publics
- `/member/dashboard.php` - Espace membre
- `/admin/dashboard.php` - Espace admin

### Comptes par Défaut
**Admin** :
- Pseudo Minecraft: `Admin`
- Mot de passe: `password` (à changer !)

---

## 🛠️ Commandes Utiles

```bash
# Lancer le serveur local
php -S localhost:8000 -t public

# Voir les logs MySQL (Railway)
railway logs mysql

# Redéployer sur Railway
railway up

# Se connecter à MySQL (Railway)
railway connect mysql

# Voir les variables d'environnement
railway variables
```

---

## 📊 Structure Rapide

```
minecraft-airport/
├── public/          → Pages web (index.php, login.php, etc.)
├── config/          → Configuration BDD
├── includes/        → Header, footer, fonctions
├── assets/          → CSS, JS, images
├── member/          → Espace membre
├── vip/             → Espace VIP
└── admin/           → Espace admin
```

---

## 🔧 Problèmes Fréquents

### Erreur de connexion BDD ?
→ Vérifiez `config/database.php`

### Erreur 500 ?
→ Vérifiez les logs PHP et que MySQL fonctionne

### Images ne s'affichent pas ?
→ Vérifiez qu'elles sont bien dans `assets/images/`

### Sur Railway, rien ne fonctionne ?
→ Vérifiez que :
1. MySQL est démarré
2. Le schéma SQL a été importé
3. Les variables d'environnement sont correctes

---

## 📚 Documentation Complète

- `README.md` - Documentation complète du projet
- `INSTALLATION.md` - Guide d'installation détaillé
- `TODO.md` - Fichiers restants à créer

---

## ✨ Prochaines Étapes

1. **Personnalisez le design** (couleurs dans `assets/css/style.css`)
2. **Ajoutez vos images**
3. **Créez des comptes de test**
4. **Invitez vos joueurs !**

---

**Besoin d'aide ?** Consultez `INSTALLATION.md` pour plus de détails !

Bon vol ! ✈️
