<?php

if (($_GET['action'] ?? '') === 'dns') {
    header('Content-Type: application/json; charset=utf-8');
    $host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
    $port = getenv('MYSQLPORT') ?: '3306';
    
    $result = [];
    
    // Test DNS
    $ip = gethostbyname($host);
    $result['hostname'] = $host;
    $result['resolved_ip'] = $ip;
    $result['dns_ok'] = ($ip !== $host);
    
    // Test connexion TCP raw
    $errno = 0; $errstr = '';
    $sock = @fsockopen($host, (int)$port, $errno, $errstr, 5);
    if ($sock) {
        $result['tcp_connect'] = 'OK';
        fclose($sock);
    } else {
        $result['tcp_connect'] = "ECHEC: $errstr (code $errno)";
    }
    
    // Test avec IP directe si DNS a résolu
    if ($result['dns_ok'] && $ip !== $host) {
        $sock2 = @fsockopen($ip, (int)$port, $errno2, $errstr2, 5);
        if ($sock2) {
            $result['tcp_via_ip'] = 'OK';
            fclose($sock2);
        } else {
            $result['tcp_via_ip'] = "ECHEC: $errstr2 (code $errno2)";
        }
    }
    
    // PDO avec host= (tcp forcé)
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            getenv('MYSQLUSER') ?: 'root',
            getenv('MYSQLPASSWORD') ?: '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        $result['pdo_without_dbname'] = 'OK';
    } catch (Exception $e) {
        $result['pdo_without_dbname'] = $e->getMessage();
    }
    
    // PDO avec dbname
    $name = getenv('MYSQLDATABASE') ?: 'railway';
    try {
        $pdo2 = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            getenv('MYSQLUSER') ?: 'root',
            getenv('MYSQLPASSWORD') ?: '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        $result['pdo_with_dbname'] = 'OK - connecté à ' . $name;
    } catch (Exception $e) {
        $result['pdo_with_dbname'] = $e->getMessage();
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// ==============================================
//  DEBUG - affiche les variables env Railway
// ==============================================
if (($_GET["action"] ?? "") === "debug") {
    header("Content-Type: application/json; charset=utf-8");
    $keys = ["MYSQLHOST","MYSQLUSER","MYSQLPASSWORD","MYSQLDATABASE","MYSQLPORT",
             "MYSQL_URL","DATABASE_URL","DB_HOST","DB_USER","DB_NAME","RAILWAY_ENVIRONMENT"];
    $out = [];
    foreach ($keys as $k) {
        $val = getenv($k);
        if ($val !== false && in_array($k, ["MYSQLPASSWORD","DB_PASS","MYSQL_URL","DATABASE_URL"])) {
            $val = "***MASQUE*** (" . strlen($val) . " chars)";
        }
        $out[$k] = $val !== false ? $val : "NON DEFINI";
    }
    echo json_encode(["env" => $out, "php_version" => phpversion()], JSON_PRETTY_PRINT);
    exit;
}
// ══════════════════════════════════════════════════════════════
//  API JSON — DOIT être avant tout output HTML
// ══════════════════════════════════════════════════════════════
if (($_GET['action'] ?? '') === 'run') {
    header('Content-Type: application/json; charset=utf-8');
    ini_set('display_errors', '0'); // évite que PHP pollue le JSON avec des warnings

    $host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: '';
    $user = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS')     ?: '';
    $name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';
    $port = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';

    // Fallback MYSQL_URL si les variables individuelles sont absentes
    if (!$host) {
        $url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';
        if ($url) {
            $p    = parse_url($url);
            $host = $p['host'] ?? '127.0.0.1';
            $user = $p['user'] ?? 'root';
            $pass = $p['pass'] ?? '';
            $name = ltrim($p['path'] ?? '/railway', '/');
            $port = $p['port'] ?? '3306';
        } else {
            $host = '127.0.0.1';
        }
    }

    $results = [];
    $tablesCreated = $tablesExisting = 0;

    // 1. Connexion
    try {
        // Forcer TCP/IP (pas de socket Unix) + timeout court
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 10,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ];
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, $options);
        $results['connexion'] = ['ok' => true, 'msg' => "Connecté à {$host}:{$port}"];
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error'   => 'Connexion impossible : ' . $e->getMessage(),
            'results' => ['connexion' => ['ok' => false, 'msg' => $e->getMessage()]],
            'dbname' => $name, 'tables_created' => 0, 'tables_existing' => 0,
        ]);
        exit;
    }

    // Créer / sélectionner la BDD
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (Exception $e) { /* déjà existante sur Railway, on ignore */ }
    $pdo->exec("USE `{$name}`");

    // Helper
    $make = function(string $key, string $tbl, string $sql)
            use ($pdo, &$results, &$tablesCreated, &$tablesExisting) {
        try {
            if ($pdo->query("SHOW TABLES LIKE '{$tbl}'")->rowCount() > 0) {
                $results[$key] = ['skip' => true, 'msg' => 'Déjà existante — conservée'];
                $tablesExisting++;
            } else {
                $pdo->exec($sql);
                $results[$key] = ['ok' => true, 'msg' => 'Créée avec succès ✓'];
                $tablesCreated++;
            }
        } catch (Exception $e) {
            $results[$key] = ['ok' => false, 'msg' => $e->getMessage()];
        }
    };

    // 2. users
    $make('users', 'users', "CREATE TABLE users (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 3. reservations
    $make('reservations', 'reservations', "CREATE TABLE reservations (
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
        INDEX idx_user (user_id), INDEX idx_date (date_vol), INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 4. taxis
    $make('taxis', 'taxis', "CREATE TABLE taxis (
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
        INDEX idx_user (user_id), INDEX idx_date (date_depart)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 5. messages
    $make('messages', 'messages', "CREATE TABLE messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT NOT NULL,
        to_user_id INT NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_to_user (to_user_id), INDEX idx_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 6. cartes
    $make('cartes', 'cartes', "CREATE TABLE cartes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        vols_achetes INT DEFAULT 0,
        vols_utilises INT DEFAULT 0,
        taxis_achetes INT DEFAULT 0,
        taxis_utilises INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");


    // 6b. Table tarifs (nouvelle)
    $make('tarifs', 'tarifs', "CREATE TABLE tarifs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        categorie VARCHAR(50) NOT NULL,
        cle VARCHAR(100) NOT NULL UNIQUE,
        label VARCHAR(200) NOT NULL,
        prix DECIMAL(10,2) NOT NULL,
        devise VARCHAR(50) NOT NULL DEFAULT 'diamants',
        description VARCHAR(255) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Insérer les tarifs par défaut
    if (isset($results['tarifs']) && ($results['tarifs']['ok'] ?? false)) {
        $tarifsData = [
            ['vol_classe2','vol_2_1','2ème classe - 1 vol',5,'or/améthyste',null],
            ['vol_classe2','vol_2_2','2ème classe - 2 vols',10,'or/améthyste',null],
            ['vol_classe2','vol_2_5','2ème classe - 5 vols',20,'or/améthyste',null],
            ['vol_classe2','vol_2_10','2ème classe - 10 vols',5,'diamants',null],
            ['vol_classe1','vol_1_1','1ère classe - 1 vol',10,'or',null],
            ['vol_classe1','vol_1_2','1ère classe - 2 vols',1,'diamants',null],
            ['vol_classe1','vol_1_5','1ère classe - 5 vols',3,'diamants',null],
            ['vol_classe1','vol_1_10','1ère classe - 10 vols',7,'diamants',null],
            ['vol_classe1','vol_1_15','1ère classe - 15 vols',12,'diamants',null],
            ['vol_classe1','vol_1_20','1ère classe - 20 vols',16,'diamants',null],
            ['vol_classe1','vol_1_25','1ère classe - 25 vols',24,'diamants',null],
            ['vol_vip','vol_v_1','Classe VIP - 1 vol',20,'or','(ou gratuit/48h)'],
            ['vol_vip','vol_v_2','Classe VIP - 2 vols',5,'diamants',null],
            ['vol_vip','vol_v_5','Classe VIP - 5 vols',15,'diamants',null],
            ['vol_vip','vol_v_10','Classe VIP - 10 vols',25,'diamants',null],
            ['vol_vip','vol_v_20','Classe VIP - 20 vols',40,'diamants',null],
            ['vol_vip','vol_v_25','Classe VIP - 25 vols',1,'netherite',null],
            ['cargaison','cargo_1','Cargaison - 1 stack',1,'diamants',null],
            ['cargaison','cargo_5','Cargaison - 5 stacks',3,'diamants',null],
            ['cargaison','cargo_10','Cargaison - 10 stacks',5,'diamants',null],
            ['cargaison','cargo_20','Cargaison - 20 stacks',7,'diamants',null],
            ['cargaison','cargo_50','Cargaison - 50 stacks',10,'diamants',null],
            ['cargaison','cargo_100','Cargaison - 100 stacks',15,'diamants',null],
            ['taxi','taxi_aller','Taxi - Multiplicateur aller',1.15,'x','Prix base × ce facteur'],
            ['taxi','taxi_retour','Taxi - Multiplicateur retour',1.20,'x','Prix base × ce facteur'],
            ['taxi','taxi_aller_retour','Taxi - Aller-retour',2.00,'x','Prix base × ce facteur'],
            ['options','vol_masque_prix','Option vol masqué',10,'diamants','+X diamants'],
            ['options','vip_reduction','Réduction VIP (%)',20,'%','Réduction classes 1 et 2'],
            ['options','vip_vol_gratuit_h','Vol gratuit VIP (heures)',48,'h','Délai vols gratuits'],
        ];
        $ins = $pdo->prepare("INSERT IGNORE INTO tarifs (categorie,cle,label,prix,devise,description) VALUES (?,?,?,?,?,?)");
        foreach ($tarifsData as $row) $ins->execute($row);
    }

    // 7. Admin
    try {
        if ($pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->rowCount() > 0) {
            $results['admin'] = ['skip' => true, 'msg' => 'Compte admin déjà existant — conservé'];
        } else {
            $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            $pdo->prepare("INSERT INTO users (pseudo_discord,pseudo_minecraft,password,faction,role,status) VALUES (?,?,?,'autre','admin','approved')")
                ->execute(['Admin','Admin',$hash]);
            $results['admin'] = ['ok' => true, 'msg' => 'Créé : Admin / password'];
        }
    } catch (Exception $e) {
        $results['admin'] = ['ok' => false, 'msg' => $e->getMessage()];
    }

    // 8. Vérification
    try {
        $missing = [];
        foreach (['users','reservations','taxis','messages','cartes','tarifs'] as $t) {
            if ($pdo->query("SHOW TABLES LIKE '{$t}'")->rowCount() === 0) $missing[] = $t;
        }
        $results['verify'] = empty($missing)
            ? ['ok' => true,  'msg' => '6 / 6 tables vérifiées ✓']
            : ['ok' => false, 'msg' => 'Manquantes : '.implode(', ', $missing)];
    } catch (Exception $e) {
        $results['verify'] = ['ok' => false, 'msg' => $e->getMessage()];
    }

    $hasError = count(array_filter($results, fn($r) => isset($r['ok']) && !$r['ok'])) > 0;
    echo json_encode([
        'success'         => !$hasError,
        'dbname'          => $name,
        'tables_created'  => $tablesCreated,
        'tables_existing' => $tablesExisting,
        'results'         => $results,
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>⚙️ Installation — Aéroport du Grand Canal</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: #0a0e27; color: #e2e8f0;
  font-family: 'Segoe UI', sans-serif;
  min-height: 100vh; display: flex;
  align-items: center; justify-content: center; padding: 2rem;
}
.container { width: 100%; max-width: 680px; }
h1 { font-size: 1.8rem; color: #00d9ff; margin-bottom: .4rem; }
.subtitle { color: #718096; margin-bottom: 2rem; font-size: .95rem; }

.progress-wrap {
  background: #1a2040; border-radius: 999px; height: 24px;
  overflow: hidden; margin-bottom: .6rem;
  border: 1px solid rgba(0,217,255,.2);
}
.progress-bar {
  height: 100%; width: 0%;
  background: linear-gradient(90deg, #00d9ff, #0099cc);
  border-radius: 999px; transition: width .5s ease;
  display: flex; align-items: center; justify-content: flex-end;
  padding-right: 10px; font-size: .78rem; font-weight: 700;
  color: #0a0e27; white-space: nowrap; min-width: 40px;
}
.progress-label { text-align: right; font-size: .85rem; color: #a0aec0; margin-bottom: 1.5rem; }

.steps { display: flex; flex-direction: column; gap: .65rem; }
.step {
  background: #111827; border: 1px solid rgba(255,255,255,.07);
  border-radius: 10px; padding: .9rem 1.2rem;
  display: flex; align-items: center; gap: 1rem;
  transition: border-color .3s, background .3s;
}
.step.running { border-color: #00d9ff; }
.step.ok      { border-color: #48bb78; background: rgba(72,187,120,.04); }
.step.error   { border-color: #fc8181; background: rgba(252,129,129,.05); }
.step.skip    { border-color: #f6ad55; background: rgba(246,173,85,.04); }
.step-icon { font-size: 1.3rem; width: 2rem; text-align: center; flex-shrink: 0; }
.step-text { flex: 1; }
.step-title { font-weight: 600; font-size: .93rem; }
.step-detail { font-size: .82rem; color: #718096; margin-top: .2rem; }
.step-detail.ok    { color: #68d391; }
.step-detail.error { color: #fc8181; }
.step-detail.skip  { color: #f6ad55; }
.step-badge {
  font-size: .75rem; padding: .15rem .55rem;
  border-radius: 999px; font-weight: 700; flex-shrink: 0;
}
.bw { background: rgba(255,255,255,.08); color: #718096; }
.br { background: rgba(0,217,255,.2);    color: #00d9ff; }
.bo { background: rgba(72,187,120,.2);   color: #68d391; }
.be { background: rgba(252,129,129,.2);  color: #fc8181; }
.bs { background: rgba(246,173,85,.2);   color: #f6ad55; }

.summary {
  display: none; margin-top: 2rem; background: #111827;
  border-radius: 12px; padding: 1.5rem;
  border: 1px solid rgba(72,187,120,.4);
}
.summary.err { border-color: rgba(252,129,129,.4); }
.summary h2 { font-size: 1.15rem; margin-bottom: 1rem; }
.info { font-size: .9rem; color: #a0aec0; margin-bottom: .4rem; }
.info strong { color: #00d9ff; }
.warn {
  margin-top: 1rem; padding: .9rem 1rem;
  background: rgba(246,173,85,.1); border: 1px solid rgba(246,173,85,.35);
  border-radius: 8px; font-size: .85rem; color: #f6ad55; line-height: 1.6;
}
.warn code { background: rgba(0,0,0,.3); padding: .1rem .35rem; border-radius: 4px; }
.btn {
  display: inline-block; margin-top: 1.2rem; margin-right: .5rem;
  padding: .65rem 1.4rem;
  background: linear-gradient(135deg,#00d9ff,#0099cc);
  color: #0a0e27; font-weight: 700; border-radius: 8px;
  text-decoration: none; font-size: .9rem;
}
.err-box {
  display: none; margin-top: 1.5rem;
  background: rgba(252,129,129,.07);
  border: 1px solid rgba(252,129,129,.4);
  border-radius: 10px; padding: 1.2rem 1.4rem;
}
.err-box h3 { color: #fc8181; margin-bottom: .6rem; }
.err-box pre {
  font-size: .8rem; color: #a0aec0; white-space: pre-wrap;
  word-break: break-all; background: rgba(0,0,0,.3);
  padding: .8rem; border-radius: 6px; margin-top: .5rem;
}
</style>
</head>
<body>
<div class="container">
  <h1>⚙️ Installation de la base de données</h1>
  <p class="subtitle">Création automatique des tables et du compte administrateur</p>

  <div class="progress-wrap"><div class="progress-bar" id="bar">0%</div></div>
  <div class="progress-label" id="plabel">Démarrage...</div>

  <div class="steps" id="steps"></div>

  <div class="err-box" id="errBox">
    <h3>❌ Erreur de communication</h3>
    <p style="color:#a0aec0;font-size:.88rem;">Le serveur n'a pas renvoyé du JSON valide. Détails :</p>
    <pre id="errDetail"></pre>
  </div>

  <div class="summary" id="summary"></div>
</div>

<script>
const STEPS = [
  { id:'connexion',    icon:'🔌', label:'Connexion à la base de données' },
  { id:'users',        icon:'👥', label:'Table users (comptes membres)' },
  { id:'reservations', icon:'✈️',  label:'Table reservations (vols)' },
  { id:'taxis',        icon:'🚕', label:'Table taxis' },
  { id:'messages',     icon:'💬', label:'Table messages (messagerie)' },
  { id:'cartes',       icon:'🎫', label:'Table cartes (statistiques)' },
  { id:'admin',        icon:'👨‍💼', label:'Compte administrateur par défaut' },
  { id:'verify',       icon:'🔍', label:'Vérification finale' },
];

// Rendu initial
const container = document.getElementById('steps');
STEPS.forEach(s => {
  container.insertAdjacentHTML('beforeend',`
    <div class="step" id="s-${s.id}">
      <div class="step-icon">${s.icon}</div>
      <div class="step-text">
        <div class="step-title">${s.label}</div>
        <div class="step-detail" id="d-${s.id}">En attente...</div>
      </div>
      <span class="step-badge bw" id="b-${s.id}">⏳</span>
    </div>`);
});

const setBar = (pct, txt) => {
  document.getElementById('bar').style.width = pct+'%';
  document.getElementById('bar').textContent = pct+'%';
  document.getElementById('plabel').textContent = txt;
};

const setStep = (id, state, detail) => {
  document.getElementById('s-'+id).className = 'step '+state;
  const d = document.getElementById('d-'+id);
  d.className = 'step-detail '+state; d.textContent = detail;
  const b = document.getElementById('b-'+id);
  const map = {running:['br','⏳'],ok:['bo','✅'],error:['be','❌'],skip:['bs','⚠️']};
  b.className = 'step-badge '+(map[state]?.[0]||'bw');
  b.textContent = map[state]?.[1]||'⏳';
};

const sleep = ms => new Promise(r => setTimeout(r, ms));

async function run() {
  setBar(5, 'Connexion au serveur...');

  let data;
  try {
    const res = await fetch('install.php?action=run');
    const text = await res.text();         // lire en texte brut d'abord
    try {
      data = JSON.parse(text);             // essayer de parser en JSON
    } catch(_) {
      // Pas du JSON → afficher l'erreur HTML brute
      document.getElementById('errBox').style.display = 'block';
      document.getElementById('errDetail').textContent = text.substring(0, 600);
      setBar(0, '❌ Le serveur a renvoyé une réponse invalide');
      return;
    }
  } catch(err) {
    document.getElementById('errBox').style.display = 'block';
    document.getElementById('errDetail').textContent = err.message;
    setBar(0, '❌ Impossible de joindre le serveur');
    return;
  }

  const inc = Math.floor(88 / STEPS.length);
  let pct = 10;

  for (const s of STEPS) {
    setStep(s.id, 'running', 'Traitement...');
    setBar(Math.min(pct, 95), s.label+'...');
    await sleep(380);

    const r = data.results?.[s.id];
    if (!r)       setStep(s.id, 'skip',  'Non exécuté');
    else if(r.ok) setStep(s.id, 'ok',    r.msg);
    else if(r.skip) setStep(s.id, 'skip', r.msg);
    else          setStep(s.id, 'error', r.msg);

    pct += inc;
    await sleep(180);
  }

  setBar(100, data.success ? '✅ Installation terminée !' : '⚠️ Terminé avec des erreurs');

  // Résumé
  const el = document.getElementById('summary');
  el.style.display = 'block';

  if (data.success) {
    el.innerHTML = `
      <h2 style="color:#68d391">✅ Installation réussie !</h2>
      <div class="info">Base de données : <strong>${data.dbname}</strong></div>
      <div class="info">Tables créées : <strong>${data.tables_created}</strong></div>
      <div class="info">Tables déjà existantes : <strong>${data.tables_existing}</strong></div>
      <div class="info">Compte admin : <strong>Admin</strong> / mot de passe : <strong>password</strong></div>
      <div class="warn">
        ⚠️ <strong>Supprime ce fichier maintenant !</strong><br><br>
        Dans ton repo, supprime <code>public/install.php</code> puis <code>git push</code>.<br>
        Ce fichier laissé en ligne permet à n'importe qui d'accéder à ta BDD.
      </div>
      <a href="/index.php" class="btn">🏠 Aller au site</a>
      <a href="/login.php" class="btn">🔐 Se connecter (Admin / password)</a>`;
  } else {
    el.className = 'summary err';
    el.innerHTML = `
      <h2 style="color:#fc8181">❌ Erreur durant l'installation</h2>
      <div class="info" style="color:#fc8181">${data.error||'Une ou plusieurs étapes ont échoué.'}</div>
      <ul style="margin:.8rem 0 0 1.2rem;font-size:.88rem;color:#a0aec0;line-height:1.9;">
        <li>Le service <strong>MySQL</strong> Railway est-il bien ajouté ?</li>
        <li>Est-il bien <strong>lié</strong> au service PHP dans Railway ?</li>
        <li>Les variables <code>MYSQLHOST</code> etc. apparaissent-elles dans l'onglet <em>Variables</em> ?</li>
      </ul>`;
  }
}

window.addEventListener('DOMContentLoaded', run);
</script>
</body>
</html>
