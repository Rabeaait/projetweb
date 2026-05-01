

CREATE DATABASE IF NOT EXISTS hadj_lottery
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE hadj_lottery;

-- ── Table des utilisateurs ──────────────────────────────────
CREATE TABLE IF NOT EXISTS utilisateurs (
  id              INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nin             VARCHAR(18)  NOT NULL UNIQUE,
  nom             VARCHAR(100) NOT NULL,
  prenom          VARCHAR(100) NOT NULL,
  ddn             DATE         NOT NULL,
  nom_pere        VARCHAR(100) NOT NULL,
  nom_grand_pere  VARCHAR(100) NOT NULL,
  prenom_mere     VARCHAR(100) NOT NULL,
  nom_mere        VARCHAR(100) NOT NULL,
  adresse         TEXT         NOT NULL,
  email           VARCHAR(255) NOT NULL UNIQUE,
  telephone       VARCHAR(20)  NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  statut          ENUM('attente','actif','bloque') NOT NULL DEFAULT 'attente',
  date_soumission DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_validation DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Table des tirages ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS tirages (
  id              INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nom             VARCHAR(200) NOT NULL,
  annee           INT          NOT NULL,
  date_tirage     DATE         NOT NULL,
  date_ouverture  DATE         NOT NULL,
  date_cloture    DATE         NOT NULL,
  nb_gagnants     INT          NOT NULL DEFAULT 500,
  statut          ENUM('ouvert','cloture','termine') NOT NULL DEFAULT 'ouvert',
  date_creation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Table des participations ────────────────────────────────
CREATE TABLE IF NOT EXISTS participations (
  id               INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id          INT NOT NULL,
  tirage_id        INT NOT NULL,
  date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resultat         ENUM('gagnant','perdu') NULL DEFAULT NULL,
  UNIQUE KEY uk_participation (user_id, tirage_id),
  CONSTRAINT fk_part_user   FOREIGN KEY (user_id)   REFERENCES utilisateurs(id) ON DELETE CASCADE,
  CONSTRAINT fk_part_tirage FOREIGN KEY (tirage_id) REFERENCES tirages(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Table des notifications ─────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id            INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id       INT          NOT NULL,
  titre         VARCHAR(255) NOT NULL,
  message       TEXT         NOT NULL,
  lu            TINYINT(1)   NOT NULL DEFAULT 0,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tirage par défaut (Hadj 2026) ───────────────────────────
INSERT IGNORE INTO tirages (nom, annee, date_tirage, date_ouverture, date_cloture, nb_gagnants, statut)
VALUES ('Tirage Hadj 2026', 2026, '2026-06-15', '2026-04-01', '2026-04-30', 500, 'ouvert');
