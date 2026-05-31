
DROP DATABASE IF EXISTS smartcampus;
CREATE DATABASE smartcampus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartcampus;

CREATE TABLE Utilisateur (
    idUser        INT AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(50)  NOT NULL,
    prenom        VARCHAR(50)  NOT NULL,
    email         VARCHAR(120) NOT NULL,
    mot_de_passe  VARCHAR(255) NOT NULL,                    
    role          ENUM('admin','enseignant','etudiant') NOT NULL,
    promotion     ENUM('ING1','ING2','ING3') DEFAULT NULL, 
    dateCreation  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_utilisateur_email UNIQUE (email)        
) ENGINE=InnoDB;

CREATE TABLE Cours (
    idCours       INT AUTO_INCREMENT PRIMARY KEY,
    nomCours      VARCHAR(120) NOT NULL,
    description   TEXT DEFAULT NULL,
    promotion     ENUM('ING1','ING2','ING3') NOT NULL,
    semestre      ENUM('S1','S2','S3','S4','S5','S6') NOT NULL,
    departement   VARCHAR(60)  NOT NULL,
    credits       INT NOT NULL DEFAULT 3,
    capaciteMax   INT NOT NULL DEFAULT 30,                 
    idEnseignant  INT DEFAULT NULL,                      
    dateCreation  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cours_enseignant
        FOREIGN KEY (idEnseignant) REFERENCES Utilisateur(idUser)
        ON DELETE SET NULL ON UPDATE CASCADE              
) ENGINE=InnoDB;


CREATE TABLE Inscription (
    idEtudiant       INT NOT NULL,
    idCours          INT NOT NULL,
    dateInscription  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idEtudiant, idCours),                   
    CONSTRAINT fk_insc_etudiant
        FOREIGN KEY (idEtudiant) REFERENCES Utilisateur(idUser)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_insc_cours
        FOREIGN KEY (idCours) REFERENCES Cours(idCours)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


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
