-- ============================================
-- WK Poule Database
-- ============================================

DROP DATABASE IF EXISTS wk_poule;
CREATE DATABASE wk_poule CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wk_poule;

-- Gebruikers
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Poules
CREATE TABLE pools (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(500),
  access_code VARCHAR(8) NOT NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT NOW(),
  UNIQUE (access_code)
);

-- Koppeltabel: gebruikers in poules
CREATE TABLE pool_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pool_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member (pool_id, user_id),
    FOREIGN KEY (pool_id) REFERENCES pools(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Wedstrijden
CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    home_team VARCHAR(50) NOT NULL,
    away_team VARCHAR(50) NOT NULL,
    match_date DATETIME NOT NULL,
    stage VARCHAR(50) DEFAULT 'Groepsfase',
    home_score INT DEFAULT NULL,
    away_score INT DEFAULT NULL
);

-- Voorspellingen
CREATE TABLE predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    predicted_home INT NOT NULL,
    predicted_away INT NOT NULL,
    points TINYINT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_prediction (user_id, match_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);

-- ============================================
-- Testdata: wedstrijden
-- ============================================
INSERT INTO matches (home_team, away_team, match_date, stage) VALUES
('Nederland', 'Argentinië',    '2026-06-11 18:00:00', 'Groepsfase'),
('Brazilië',  'Duitsland',     '2026-06-11 21:00:00', 'Groepsfase'),
('Frankrijk', 'Spanje',        '2026-06-12 15:00:00', 'Groepsfase'),
('Engeland',  'Portugal',      '2026-06-12 18:00:00', 'Groepsfase'),
('België',    'Kroatië',       '2026-06-13 15:00:00', 'Groepsfase'),
('Italië',    'Uruguay',       '2026-06-13 18:00:00', 'Groepsfase'),
('Japan',     'Marokko',       '2026-06-14 15:00:00', 'Groepsfase'),
('Verenigde Staten', 'Mexico', '2026-06-14 18:00:00', 'Groepsfase');
