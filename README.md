# ✈️ Aéroport du Grand Canal — Site Web PHP

Site de gestion d'un aéroport Minecraft : réservations de vols, cargaisons, taxis, messagerie et espaces membre / VIP / admin.

## 🚀 Déploiement sur Railway (étapes complètes)

### 1. Préparer GitHub
```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/TON_USER/TON_REPO.git
git push -u origin main
```

### 2. Créer le projet Railway
1. Aller sur [railway.app](https://railway.app) → New Project
2. **Add a service** → MySQL (depuis le Marketplace)
3. **Add a service** → GitHub Repo → sélectionner ton repo

### 3. Connecter la BDD automatiquement
Railway injecte automatiquement les variables MySQL dans ton service PHP :
- `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`

Le code les lit déjà — **rien à configurer manuellement**.

### 4. Initialiser la base de données
Une fois déployé, ouvrir un terminal Railway CLI ou via l'onglet "Shell" :
```bash
php init.php
```
Cela crée toutes les tables et le compte admin par défaut.

Ou manuellement via un client MySQL avec les credentials Railway :
```
mysql -h $MYSQLHOST -u $MYSQLUSER -p$MYSQLPASSWORD $MYSQLDATABASE < config/database.sql
```

### 5. Compte admin par défaut
- **Pseudo Minecraft** : `Admin`
- **Mot de passe** : `password`
- ⚠️ **CHANGER LE MOT DE PASSE IMMÉDIATEMENT** après la première connexion !

---

## 🏗️ Structure du projet

```
├── config/
│   ├── database.php      # Config BDD (Railway + local)
│   └── database.sql      # Script d'init BDD
├── includes/
│   ├── functions.php     # Toutes les fonctions utilitaires
│   ├── header.php        # En-tête HTML commun
│   └── footer.php        # Pied de page HTML commun
├── public/               # Racine web (servie par Railway)
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── reservation.php
│   ├── taxis.php
│   ├── horaire.php
│   ├── logout.php
│   ├── member/           # Espace membre
│   ├── vip/              # Espace VIP
│   └── admin/            # Espace admin
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── images/           # Mettre banner.png ici (1920×150px)
├── init.php              # Script d'initialisation BDD
└── railway.toml          # Config Railway
```

---

## 🎨 Personnalisation

### Bannière
Placer une image `banner.png` (1920×150px recommandé) dans `assets/images/`.

### Couleurs
Modifier les variables CSS dans `assets/css/style.css` :
```css
:root {
    --primary:   #00d9ff;
    --secondary: #ff6b35;
    --dark:      #0a0e27;
}
```

---

## 🐛 Bugs corrigés (v2)

| Bug | Fichier | Correction |
|-----|---------|------------|
| Prix taxis ×10 trop bas | `functions.php` | `0.15 / 0.20` → `1.15 / 1.20` |
| Double `session_start()` crash | `header.php` + `functions.php` | Session unique avec `session_status()` |
| Connexion BDD impossible sur Railway | `config/database.php` | Support `MYSQLHOST` etc. |
| `logout.php` erreur session | `public/logout.php` | Suppression du `session_start()` redondant |
| Liens VIP pointaient vers `/member/` | `public/vip/*.php` | Tous les liens corrigés vers `/vip/` |
| `index.php` chemin includes cassé | `public/index.php` | Chemin absolu `__DIR__ . '/../includes/'` |
| Injection SQL dans messagerie admin | `admin/messagerie.php` | `pdo->query()` → `prepare()->execute()` |
| Table `cartes` absente du SQL | `config/database.sql` | Table ajoutée |
| Mobile : pas de menu hamburger | CSS + JS | Toggle mobile ajouté |
