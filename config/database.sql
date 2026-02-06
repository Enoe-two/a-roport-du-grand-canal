-- Base de données pour l'aéroport Minecraft

CREATE DATABASE IF NOT EXISTS minecraft_airport;
USE minecraft_airport;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pseudo_discord VARCHAR(100) NOT NULL,
    pseudo_minecraft VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    faction ENUM('scooby_empire', 'blocaria', 'gamelon_III', 'autre') NOT NULL,
    faction_autre VARCHAR(100),
    role ENUM('member', 'vip', 'admin') DEFAULT 'member',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_minecraft (pseudo_minecraft),
    INDEX idx_status (status),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des réservations de vols
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('vol_simple', 'cargaison') NOT NULL,
    classe ENUM('2', '1', 'vip') NOT NULL,
    quantite INT NOT NULL COMMENT 'Nombre de vols ou stacks',
    prix_total DECIMAL(10,2) NOT NULL,
    devise VARCHAR(50) NOT NULL,
    date_vol DATE NOT NULL,
    heure_vol TIME NOT NULL,
    vol_masque BOOLEAN DEFAULT FALSE,
    status ENUM('en_attente', 'confirme', 'annule', 'complete') DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_date (date_vol),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des taxis
CREATE TABLE IF NOT EXISTS taxis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    classe ENUM('2', '1') NOT NULL,
    type ENUM('aller', 'retour', 'aller_retour') NOT NULL,
    coordonnees_depart VARCHAR(100),
    coordonnees_arrivee VARCHAR(100) NOT NULL,
    date_depart DATE NOT NULL,
    heure_depart TIME NOT NULL,
    date_retour DATE,
    heure_retour TIME,
    temps_attente INT COMMENT 'En minutes',
    prix_total DECIMAL(10,2) NOT NULL,
    vol_masque BOOLEAN DEFAULT FALSE,
    status ENUM('en_attente', 'confirme', 'annule', 'complete') DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_date (date_depart)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table de messagerie
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_to_user (to_user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des cartes (pour les membres)
CREATE TABLE IF NOT EXISTS cartes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    vols_achetes INT DEFAULT 0,
    vols_utilises INT DEFAULT 0,
    taxis_achetes INT DEFAULT 0,
    taxis_utilises INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Créer un compte admin par défaut
INSERT INTO users (pseudo_discord, pseudo_minecraft, password, faction, role, status) 
VALUES ('Admin', 'Admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'autre', 'admin', 'approved');
-- Mot de passe par défaut : password (À CHANGER IMMÉDIATEMENT)
