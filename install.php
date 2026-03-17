<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>⚙️ Installation — Aéroport du Grand Canal</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: #0a0e27;
    color: #e2e8f0;
    font-family: 'Segoe UI', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
  }
  .container {
    width: 100%;
    max-width: 680px;
  }
  h1 {
    font-size: 1.8rem;
    color: #00d9ff;
    margin-bottom: .4rem;
  }
  .subtitle { color: #718096; margin-bottom: 2rem; font-size: .95rem; }

  /* Barre de progression globale */
  .progress-wrap {
    background: #1a2040;
    border-radius: 999px;
    height: 22px;
    overflow: hidden;
    margin-bottom: .6rem;
    border: 1px solid rgba(0,217,255,.2);
  }
  .progress-bar {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #00d9ff, #0099cc);
    border-radius: 999px;
    transition: width .4s ease;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
    font-size: .75rem;
    font-weight: 700;
    color: #0a0e27;
    white-space: nowrap;
  }
  .progress-label {
    text-align: right;
    font-size: .85rem;
    color: #a0aec0;
    margin-bottom: 1.5rem;
  }

  /* Étapes */
  .steps { display: flex; flex-direction: column; gap: .7rem; }
  .step {
    background: #111827;
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px;
    padding: 1rem 1.2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: border-color .3s;
  }
  .step.running { border-color: #00d9ff; }
  .step.ok      { border-color: #48bb78; }
  .step.error   { border-color: #fc8181; background: rgba(252,129,129,.05); }
  .step.skip    { border-color: #f6ad55; background: rgba(246,173,85,.05); }

  .step-icon {
    font-size: 1.4rem;
    width: 2rem;
    text-align: center;
    flex-shrink: 0;
  }
  .step-text { flex: 1; }
  .step-title { font-weight: 600; font-size: .95rem; }
  .step-detail { font-size: .82rem; color: #718096; margin-top: .2rem; }
  .step-detail.ok    { color: #68d391; }
  .step-detail.error { color: #fc8181; }
  .step-detail.skip  { color: #f6ad55; }

  .spinner {
    width: 18px; height: 18px;
    border: 2px solid rgba(0,217,255,.3);
    border-top-color: #00d9ff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    flex-shrink: 0;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* Résumé final */
  .summary {
    display: none;
    margin-top: 2rem;
    background: #111827;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid rgba(72,187,120,.4);
  }
  .summary.error-summary { border-color: rgba(252,129,129,.4); }
  .summary h2 { font-size: 1.2rem; margin-bottom: 1rem; }
  .summary .info { font-size: .9rem; color: #a0aec0; margin-bottom: .5rem; }
  .summary .info span { color: #00d9ff; font-weight: 700; }
  .summary .warn {
    margin-top: 1rem;
    background: rgba(246,173,85,.1);
    border: 1px solid rgba(246,173,85,.4);
    border-radius: 8px;
    padding: .8rem 1rem;
    font-size: .85rem;
    color: #f6ad55;
  }
  .btn {
    display: inline-block;
    margin-top: 1.2rem;
    padding: .7rem 1.5rem;
    background: linear-gradient(135deg, #00d9ff, #0099cc);
    color: #0a0e27;
    font-weight: 700;
    border-radius: 8px;
    text-decoration: none;
    font-size: .95rem;
    margin-right: .6rem;
  }
  .btn-danger {
    background: linear-gradient(135deg, #fc8181, #c53030);
    color: #fff;
  }
</style>
</head>
<body>
<div class="container">
  <h1>⚙️ Installation de la base de données</h1>
  <p class="subtitle">Création des tables et du compte administrateur...</p>

  <div class="progress-wrap">
    <div class="progress-bar" id="progressBar">0%</div>
  </div>
  <div class="progress-label" id="progressLabel">Démarrage...</div>

  <div class="steps" id="steps"></div>
  <div class="summary" id="summary"></div>
</div>

<script>
// ── Définition des étapes ─────────────────────────────────────────────────
const steps = [
  { id: 'connexion',    label: 'Connexion à la base de données',   icon: '🔌' },
  { id: 'users',        label: 'Table users (comptes)',             icon: '👥' },
  { id: 'reservations', label: 'Table reservations (vols)',        icon: '✈️' },
  { id: 'taxis',        label: 'Table taxis',                      icon: '🚕' },
  { id: 'messages',     label: 'Table messages (messagerie)',       icon: '💬' },
  { id: 'cartes',       label: 'Table cartes (statistiques)',       icon: '🎫' },
  { id: 'admin',        label: 'Compte administrateur par défaut', icon: '👨‍💼' },
  { id: 'verify',       label: 'Vérification finale',              icon: '✅' },
];

// ── Rendu initial ──────────────────────────────────────────────────────────
const stepsEl = document.getElementById('steps');
steps.forEach(s => {
  stepsEl.innerHTML += `
    <div class="step" id="step-${s.id}">
      <div class="step-icon">${s.icon}</div>
      <div class="step-text">
        <div class="step-title">${s.label}</div>
        <div class="step-detail" id="detail-${s.id}">En attente...</div>
      </div>
      <div id="loader-${s.id}" style="opacity:0"><div class="spinner"></div></div>
    </div>`;
});

// ── Helpers UI ─────────────────────────────────────────────────────────────
function setProgress(pct, label) {
  const bar = document.getElementById('progressBar');
  bar.style.width = pct + '%';
  bar.textContent = pct + '%';
  document.getElementById('progressLabel').textContent = label;
}

function setStep(id, state, detail) {
  const el     = document.getElementById('step-' + id);
  const detEl  = document.getElementById('detail-' + id);
  const loader = document.getElementById('loader-' + id);
  el.className = 'step ' + state;
  detEl.className = 'step-detail ' + state;
  detEl.textContent = detail;
  loader.style.opacity = (state === 'running') ? '1' : '0';
}

// ── Appel AJAX vers le backend ─────────────────────────────────────────────
async function runInstall() {
  const res  = await fetch('?action=run');
  const data = await res.json();

  let pct = 0;
  const inc = Math.floor(100 / steps.length);

  for (const step of steps) {
    setStep(step.id, 'running', 'Traitement...');
    setProgress(Math.min(pct + Math.floor(inc / 2), 99), step.label + '...');
    await sleep(300);

    const r = data.results[step.id];
    if (!r) { setStep(step.id, 'skip', 'Non exécuté'); continue; }

    if (r.ok) {
      setStep(step.id, 'ok', r.msg);
    } else if (r.skip) {
      setStep(step.id, 'skip', r.msg);
    } else {
      setStep(step.id, 'error', r.msg);
    }

    pct = Math.min(pct + inc, 100);
    setProgress(pct, step.label);
    await sleep(250);
  }

  setProgress(100, data.success ? '✅ Installation terminée !' : '⚠️ Terminé avec des erreurs');
  showSummary(data);
}

function showSummary(data) {
  const el = document.getElementById('summary');
  el.style.display = 'block';

  if (data.success) {
    el.innerHTML = `
      <h2 style="color:#68d391">✅ Installation réussie !</h2>
      <div class="info">Base de données : <span>${data.dbname}</span></div>
      <div class="info">Tables créées : <span>${data.tables_created}</span></div>
      <div class="info">Tables déjà existantes : <span>${data.tables_existing}</span></div>
      <div class="info">Compte admin : <span>Admin / password</span></div>
      <div class="warn">
        ⚠️ <strong>Supprime ce fichier immédiatement après l'installation !</strong><br>
        Laisse ce fichier accessible = n'importe qui peut réinitialiser ta BDD.
        <br><br>Sur Railway : supprime <code>public/install.php</code> de ton repo et re-push.
      </div>
      <a href="/index.php" class="btn">🏠 Aller au site</a>
      <a href="/login.php" class="btn">🔐 Se connecter (Admin / password)</a>`;
  } else {
    el.className = 'summary error-summary';
    el.innerHTML = `
      <h2 style="color:#fc8181">❌ Erreur lors de l'installation</h2>
      <div class="info" style="color:#fc8181">${data.error}</div>
      <div class="info" style="margin-top:.8rem;">Vérifie que le service MySQL Railway est bien connecté à ce projet et que les variables d'environnement sont injectées.</div>`;
  }
}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

// Lancer dès le chargement
window.addEventListener('DOMContentLoaded', runInstall);
</script>

<?php
// ══════════════════════════════════════════════════════════════
//  BACKEND PHP — réponse JSON à ?action=run
// ══════════════════════════════════════════════════════════════
if (($_GET['action'] ?? '') === 'run') {
    header('Content-Type: application/json');

    // Charger les variables Railway / locales
    $host = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: 'localhost';
    $user = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS')     ?: '';
    $name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'minecraft_airport';
    $port = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';

    $results = [];
    $tablesCreated  = 0;
    $tablesExisting = 0;

    // ── 1. Connexion ──────────────────────────────────────────
    try {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $results['connexion'] = ['ok' => true, 'msg' => "Connecté à {$host}:{$port}"];
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error'   => 'Impossible de se connecter : ' . $e->getMessage(),
            'results' => ['connexion' => ['ok' => false, 'msg' => $e->getMessage()]],
        ]);
        exit;
    }

    // ── Créer / sélectionner la BDD ───────────────────────────
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$name}`");
    } catch (Exception $e) {
        // Sur Railway la BDD existe déjà, juste USE
        try { $pdo->exec("USE `{$name}`"); } catch (Exception $e2) {
            echo json_encode(['success' => false, 'error' => $e2->getMessage(), 'results' => $results]);
            exit;
        }
    }

    // ── Helper : créer une table ──────────────────────────────
    $createTable = function(string $key, string $sql) use ($pdo, &$results, &$tablesCreated, &$tablesExisting) {
        try {
            // Vérifier si elle existe déjà
            $check = $pdo->query("SHOW TABLES LIKE '" . str_replace("'", '', $key) . "'")->rowCount();
            if ($check > 0) {
                $results[$key] = ['skip' => true, 'msg' => 'Table déjà existante — ignorée'];
                $tablesExisting++;
            } else {
                $pdo->exec($sql);
                $results[$key] = ['ok' => true, 'msg' => 'Table créée avec succès'];
                $tablesCreated++;
            }
        } catch (Exception $e) {
            $results[$key] = ['ok' => false, 'msg' => $e->getMessage()];
        }
    };

    // ── 2. Table users ────────────────────────────────────────
    $createTable('users', "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pseudo_discord VARCHAR(100) NOT NULL,
            pseudo_minecraft VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            faction ENUM('scooby_empire','blocaria','gamelon_III','autre') NOT NULL,
            faction_autre VARCHAR(100) DEFAULT NULL,
            role ENUM('member','vip','admin') DEFAULT 'member',
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_minecraft (pseudo_minecraft),
            INDEX idx_status (status),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── 3. Table reservations ─────────────────────────────────
    $createTable('reservations', "
        CREATE TABLE reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('vol_simple','cargaison') NOT NULL,
            classe ENUM('2','1','vip') NOT NULL,
            quantite INT NOT NULL,
            prix_total DECIMAL(10,2) NOT NULL,
            devise VARCHAR(50) NOT NULL,
            date_vol DATE NOT NULL,
            heure_vol TIME NOT NULL,
            vol_masque TINYINT(1) DEFAULT 0,
            status ENUM('en_attente','confirme','annule','complete') DEFAULT 'en_attente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id),
            INDEX idx_date (date_vol),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── 4. Table taxis ────────────────────────────────────────
    $createTable('taxis', "
        CREATE TABLE taxis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            classe ENUM('2','1') NOT NULL,
            type ENUM('aller','retour','aller_retour') NOT NULL,
            coordonnees_depart VARCHAR(100) DEFAULT NULL,
            coordonnees_arrivee VARCHAR(100) NOT NULL,
            date_depart DATE NOT NULL,
            heure_depart TIME NOT NULL,
            date_retour DATE DEFAULT NULL,
            heure_retour TIME DEFAULT NULL,
            temps_attente INT DEFAULT NULL,
            prix_total DECIMAL(10,2) NOT NULL,
            vol_masque TINYINT(1) DEFAULT 0,
            status ENUM('en_attente','confirme','annule','complete') DEFAULT 'en_attente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user (user_id),
            INDEX idx_date (date_depart)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── 5. Table messages ─────────────────────────────────────
    $createTable('messages', "
        CREATE TABLE messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_user_id INT NOT NULL,
            to_user_id INT NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_to_user (to_user_id),
            INDEX idx_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── 6. Table cartes ───────────────────────────────────────
    $createTable('cartes', "
        CREATE TABLE cartes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            vols_achetes INT DEFAULT 0,
            vols_utilises INT DEFAULT 0,
            taxis_achetes INT DEFAULT 0,
            taxis_utilises INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── 7. Compte admin ───────────────────────────────────────
    try {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        if ($stmt->rowCount() > 0) {
            $results['admin'] = ['skip' => true, 'msg' => 'Compte admin déjà existant — ignoré'];
        } else {
            // hash de "password"
            $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            $pdo->prepare("
                INSERT INTO users (pseudo_discord, pseudo_minecraft, password, faction, role, status)
                VALUES (?, ?, ?, 'autre', 'admin', 'approved')
            ")->execute(['Admin', 'Admin', $hash]);
            $results['admin'] = ['ok' => true, 'msg' => 'Admin créé (pseudo: Admin / mdp: password)'];
        }
    } catch (Exception $e) {
        $results['admin'] = ['ok' => false, 'msg' => $e->getMessage()];
    }

    // ── 8. Vérification finale ────────────────────────────────
    try {
        $tables = ['users','reservations','taxis','messages','cartes'];
        $missing = [];
        foreach ($tables as $t) {
            if ($pdo->query("SHOW TABLES LIKE '{$t}'")->rowCount() === 0) $missing[] = $t;
        }
        if (empty($missing)) {
            $results['verify'] = ['ok' => true, 'msg' => '5/5 tables présentes et vérifiées'];
        } else {
            $results['verify'] = ['ok' => false, 'msg' => 'Tables manquantes : ' . implode(', ', $missing)];
        }
    } catch (Exception $e) {
        $results['verify'] = ['ok' => false, 'msg' => $e->getMessage()];
    }

    // ── Réponse ───────────────────────────────────────────────
    $hasError = array_filter($results, fn($r) => isset($r['ok']) && !$r['ok']);

    echo json_encode([
        'success'         => empty($hasError),
        'dbname'          => $name,
        'tables_created'  => $tablesCreated,
        'tables_existing' => $tablesExisting,
        'results'         => $results,
    ]);
    exit;
}
?>
</body>
</html>
