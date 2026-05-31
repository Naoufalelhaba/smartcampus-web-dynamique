-- =====================================================================
--  SmartCampus — Schéma de la base de données (MySQL 8)
--  Projet Web Dynamique 2026 — ING2 Groupe 5
--  Reprend fidèlement le MLD du Livrable 1 : 5 tables
--      Utilisateur, Cours, Inscription, Note, Seance
--
--  Les règles métier sont en partie portées par le schéma lui-même :
--      R1 (anti double-inscription) -> clé primaire composée de Inscription
--      R2 (capacité d'un cours)     -> colonne capaciteMax (+ contrôle PHP)
--      Verrouillage des notes       -> colonne verrouille (+ contrôle PHP)
--      Cohérence après suppression  -> clés étrangères ON DELETE CASCADE / SET NULL
-- =====================================================================

-- On repart d'une base propre : pratique pour ré-importer pendant les tests.
DROP DATABASE IF EXISTS smartcampus;
CREATE DATABASE smartcampus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartcampus;

-- ---------------------------------------------------------------------
-- Table centrale : un compte = un acteur (admin / enseignant / etudiant)
-- ---------------------------------------------------------------------
CREATE TABLE Utilisateur (
    idUser        INT AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(50)  NOT NULL,
    prenom        VARCHAR(50)  NOT NULL,
    email         VARCHAR(120) NOT NULL,
    mot_de_passe  VARCHAR(255) NOT NULL,                    -- haché avec password_hash()
    role          ENUM('admin','enseignant','etudiant') NOT NULL,
    promotion     ENUM('ING1','ING2','ING3') DEFAULT NULL,  -- renseigné uniquement pour les étudiants
    dateCreation  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_utilisateur_email UNIQUE (email)          -- deux comptes ne peuvent pas partager un email
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Cours : porté par un enseignant, ciblé à une promotion et un semestre.
--   promotion / semestre / departement / credits servent au filtrage
--   et au tri (critère « Filtrage / tri des cours » de la grille).
-- ---------------------------------------------------------------------
CREATE TABLE Cours (
    idCours       INT AUTO_INCREMENT PRIMARY KEY,
    nomCours      VARCHAR(120) NOT NULL,
    description   TEXT DEFAULT NULL,
    promotion     ENUM('ING1','ING2','ING3') NOT NULL,
    semestre      ENUM('S1','S2','S3','S4','S5','S6') NOT NULL,
    departement   VARCHAR(60)  NOT NULL,
    credits       INT NOT NULL DEFAULT 3,
    capaciteMax   INT NOT NULL DEFAULT 30,                  -- Règle R2 : nombre de places
    idEnseignant  INT DEFAULT NULL,                         -- FK -> Utilisateur (un enseignant)
    dateCreation  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cours_enseignant
        FOREIGN KEY (idEnseignant) REFERENCES Utilisateur(idUser)
        ON DELETE SET NULL ON UPDATE CASCADE                -- si l'enseignant est supprimé, le cours reste (sans prof)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Inscription : association N–N entre étudiants et cours.
--   La clé primaire composée (idEtudiant, idCours) interdit nativement
--   qu'un étudiant s'inscrive deux fois au même cours  ->  Règle R1.
-- ---------------------------------------------------------------------
CREATE TABLE Inscription (
    idEtudiant       INT NOT NULL,
    idCours          INT NOT NULL,
    dateInscription  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idEtudiant, idCours),                      -- R1 : couple unique
    CONSTRAINT fk_insc_etudiant
        FOREIGN KEY (idEtudiant) REFERENCES Utilisateur(idUser)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_insc_cours
        FOREIGN KEY (idCours) REFERENCES Cours(idCours)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Note : une évaluation d'un étudiant dans un cours.
--   verrouille = 1  ->  note validée définitivement, non modifiable
--   (critère « Validation finale des notes » / « Blocage modification note validée »).
--   coefficient permet une moyenne pondérée (1.00 par défaut = moyenne simple).
-- ---------------------------------------------------------------------
CREATE TABLE Note (
    idNote          INT AUTO_INCREMENT PRIMARY KEY,
    idEtudiant      INT NOT NULL,
    idCours         INT NOT NULL,
    typeEvaluation  ENUM('CC1','CC2','TP','Projet','Examen') NOT NULL,
    valeur          DECIMAL(4,2) NOT NULL,
    coefficient     DECIMAL(3,2) NOT NULL DEFAULT 1.00,
    verrouille      TINYINT(1)   NOT NULL DEFAULT 0,
    dateSaisie      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_note_etudiant
        FOREIGN KEY (idEtudiant) REFERENCES Utilisateur(idUser)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_note_cours
        FOREIGN KEY (idCours) REFERENCES Cours(idCours)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_note_valeur CHECK (valeur >= 0 AND valeur <= 20)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Seance : un créneau de l'emploi du temps, rattaché à un cours.
--   Sert à l'affichage de l'EDT et à la détection des conflits horaires
--   (même salle, même promotion ou même enseignant sur un créneau qui se chevauche).
-- ---------------------------------------------------------------------
CREATE TABLE Seance (
    idSeance     INT AUTO_INCREMENT PRIMARY KEY,
    idCours      INT NOT NULL,
    jour         ENUM('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
    heureDebut   TIME NOT NULL,
    heureFin     TIME NOT NULL,
    salle        VARCHAR(30) NOT NULL,
    CONSTRAINT fk_seance_cours
        FOREIGN KEY (idCours) REFERENCES Cours(idCours)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_seance_horaire CHECK (heureFin > heureDebut)
) ENGINE=InnoDB;
