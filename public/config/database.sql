CREATE DATABASE IF NOT EXISTS railway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE railway;

CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reservations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS taxis (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cartes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    vols_achetes INT DEFAULT 0,
    vols_utilises INT DEFAULT 0,
    taxis_achetes INT DEFAULT 0,
    taxis_utilises INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des tarifs (modifiable depuis l'admin)
CREATE TABLE IF NOT EXISTS tarifs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie VARCHAR(50) NOT NULL,
    cle VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(200) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    devise VARCHAR(50) NOT NULL DEFAULT 'diamants',
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tarifs par défaut
INSERT IGNORE INTO tarifs (categorie, cle, label, prix, devise, description) VALUES
('vol_classe2', 'vol_2_1',  '2ème classe - 1 vol',   5,  'or/améthyste', NULL),
('vol_classe2', 'vol_2_2',  '2ème classe - 2 vols',  10, 'or/améthyste', NULL),
('vol_classe2', 'vol_2_5',  '2ème classe - 5 vols',  20, 'or/améthyste', NULL),
('vol_classe2', 'vol_2_10', '2ème classe - 10 vols',  5, 'diamants',     NULL),
('vol_classe1', 'vol_1_1',  '1ère classe - 1 vol',   10, 'or',       NULL),
('vol_classe1', 'vol_1_2',  '1ère classe - 2 vols',   1, 'diamants', NULL),
('vol_classe1', 'vol_1_5',  '1ère classe - 5 vols',   3, 'diamants', NULL),
('vol_classe1', 'vol_1_10', '1ère classe - 10 vols',  7, 'diamants', NULL),
('vol_classe1', 'vol_1_15', '1ère classe - 15 vols', 12, 'diamants', NULL),
('vol_classe1', 'vol_1_20', '1ère classe - 20 vols', 16, 'diamants', NULL),
('vol_classe1', 'vol_1_25', '1ère classe - 25 vols', 24, 'diamants', NULL),
('vol_vip',     'vol_v_1',  'Classe VIP - 1 vol',    20, 'or',        '(ou gratuit/48h)'),
('vol_vip',     'vol_v_2',  'Classe VIP - 2 vols',    5, 'diamants',  NULL),
('vol_vip',     'vol_v_5',  'Classe VIP - 5 vols',   15, 'diamants',  NULL),
('vol_vip',     'vol_v_10', 'Classe VIP - 10 vols',  25, 'diamants',  NULL),
('vol_vip',     'vol_v_20', 'Classe VIP - 20 vols',  40, 'diamants',  NULL),
('vol_vip',     'vol_v_25', 'Classe VIP - 25 vols',   1, 'netherite', NULL),
('cargaison',   'cargo_1',  'Cargaison - 1 stack',    1, 'diamants', NULL),
('cargaison',   'cargo_5',  'Cargaison - 5 stacks',   3, 'diamants', NULL),
('cargaison',   'cargo_10', 'Cargaison - 10 stacks',  5, 'diamants', NULL),
('cargaison',   'cargo_20', 'Cargaison - 20 stacks',  7, 'diamants', NULL),
('cargaison',   'cargo_50', 'Cargaison - 50 stacks', 10, 'diamants', NULL),
('cargaison',   'cargo_100','Cargaison - 100 stacks',15, 'diamants', NULL),
('taxi',        'taxi_aller',       'Taxi - Multiplicateur aller',       1.15, 'x', 'Prix base × ce facteur'),
('taxi',        'taxi_retour',      'Taxi - Multiplicateur retour',      1.20, 'x', 'Prix base × ce facteur'),
('taxi',        'taxi_aller_retour','Taxi - Multiplicateur aller-retour',2.00, 'x', 'Prix base × ce facteur'),
('options',     'vol_masque_prix',  'Option vol masqué',                10,   'diamants', '+X diamants au total'),
('options',     'vip_reduction',    'Réduction VIP (%)',                20,   '%',  'Réduction sur classes 1 et 2'),
('options',     'vip_vol_gratuit_h','Vol gratuit VIP (heures)',         48,   'h',  'Délai entre vols gratuits VIP');

-- Compte admin par défaut (mot de passe: password)
INSERT IGNORE INTO users (pseudo_discord, pseudo_minecraft, password, faction, role, status)
VALUES ('Admin', 'Admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'autre', 'admin', 'approved');
