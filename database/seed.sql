
USE smartcampus;

INSERT INTO Utilisateur (idUser, nom, prenom, email, mot_de_passe, role, promotion) VALUES
(1, 'SMARTCAMPUS', 'Admin', 'admin@smartcampus.fr', '$2y$10$fmA3cj6xg.n4sp.mWYrkreNEyCTwoNHZ07dJTohWVmTcIXjym2A2e', 'admin', NULL);

INSERT INTO Utilisateur (idUser, nom, prenom, email, mot_de_passe, role, promotion) VALUES
(2, 'CURIE',    'Marie', 'm.curie@smartcampus.fr',    '$2y$10$b4AIa7rEvRixrE7qOF1PL.rIhFINWV82eUP2f8VrMDhp2ps3IzSCe', 'enseignant', NULL),
(3, 'TURING',   'Alan',  'a.turing@smartcampus.fr',   '$2y$10$b4AIa7rEvRixrE7qOF1PL.rIhFINWV82eUP2f8VrMDhp2ps3IzSCe', 'enseignant', NULL),
(4, 'LOVELACE', 'Ada',   'a.lovelace@smartcampus.fr', '$2y$10$b4AIa7rEvRixrE7qOF1PL.rIhFINWV82eUP2f8VrMDhp2ps3IzSCe', 'enseignant', NULL),
(5, 'RITCHIE',  'Denis', 'd.ritchie@smartcampus.fr',  '$2y$10$b4AIa7rEvRixrE7qOF1PL.rIhFINWV82eUP2f8VrMDhp2ps3IzSCe', 'enseignant', NULL);

INSERT INTO Utilisateur (idUser, nom, prenom, email, mot_de_passe, role, promotion) VALUES
(6,  'MARTIN',  'Lucas',  'lucas.martin@smartcampus.fr',  '$2y$10$d5KGUrTKON7tSP75wWzkiecpIVnBrR8r8DiGOvtaZtUmrskiPweTu', 'etudiant', 'ING1'),
(7,  'BERNARD', 'Emma',   'emma.bernard@smartcampus.fr',  '$2y$10$d5KGUrTKON7tSP75wWzkiecpIVnBrR8r8DiGOvtaZtUmrskiPweTu', 'etudiant', 'ING1'),
(8,  'PETIT',   'Hugo',   'hugo.petit@smartcampus.fr',    '$2y$10$d5KGUrTKON7tSP75wWzkiecpIVnBrR8r8DiGOvtaZtUmrskiPweTu', 'etudiant', 'ING1'),
(9,  'DURAND',  'Lea',    'lea.durand@smartcampus.fr',    '$2y$10$d5KGUrTKON7tSP75wWzkiecpIVnBrR8r8DiGOvtaZtUmrskiPweTu', 'etudiant', 'ING2'),
(10, 'MOREAU',  'Nathan', 'nathan.moreau@smartcampus.fr', '$2y$10$d5KGUrTKON7tSP75wWzkiecpIVnBrR8r8DiGOvtaZtUmrskiPweTu', 'etudiant', 'ING2'),
(11, 'LAURENT', 'Chloe',  'chloe.laurent@smartcampus.fr', '$2y$10$d5KGUrTKON7tSP75wWzkiecpIVnBrR8r8DiGOvtaZtUmrskiPweTu', 'etudiant', 'ING2'),
(12, 'SIMON',   'Louis',  'louis.simon@smartcampus.fr',   '$2y$10$d5KGUrTKON7tSP75wWzkiecpIVnBrR8r8DiGOvtaZtUmrskiPweTu', 'etudiant', 'ING3'),
(13, 'MICHEL',  'Jade',   'jade.michel@smartcampus.fr',   '$2y$10$d5KGUrTKON7tSP75wWzkiecpIVnBrR8r8DiGOvtaZtUmrskiPweTu', 'etudiant', 'ING3'),
(14, 'GARCIA',  'Gabriel','gabriel.garcia@smartcampus.fr','$2y$10$d5KGUrTKON7tSP75wWzkiecpIVnBrR8r8DiGOvtaZtUmrskiPweTu', 'etudiant', 'ING3'),
(15, 'ROUX',    'Manon',  'manon.roux@smartcampus.fr',    '$2y$10$d5KGUrTKON7tSP75wWzkiecpIVnBrR8r8DiGOvtaZtUmrskiPweTu', 'etudiant', 'ING2');

INSERT INTO Cours (idCours, nomCours, description, promotion, semestre, departement, credits, capaciteMax, idEnseignant) VALUES
(1, 'Programmation Web',        'HTML, CSS, JavaScript, React, PHP et MySQL.',          'ING2', 'S4', 'Informatique',  4, 30, 3),
(2, 'Bases de Donnees',         'Modele relationnel, SQL, normalisation.',              'ING2', 'S3', 'Informatique',  5, 30, 4),
(3, 'Algorithmique Avancee',    'Complexite, graphes, programmation dynamique.',        'ING2', 'S3', 'Informatique',  4,  2, 3),
(4, 'Mathematiques Discretes',  'Logique, ensembles, combinatoire.',                    'ING1', 'S1', 'Mathematiques', 3, 40, 2),
(5, 'Systemes d''Exploitation', 'Processus, memoire, systemes de fichiers.',            'ING3', 'S5', 'Informatique',  5, 25, 5),
(6, 'Reseaux',                  'Modele OSI, TCP/IP, routage.',                         'ING3', 'S6', 'Informatique',  4, 25, 5),
(7, 'Physique Quantique',       'Mecanique quantique appliquee a l''ingenierie.',       'ING1', 'S2', 'Physique',      3, 35, 2),
(8, 'Intelligence Artificielle','Apprentissage automatique, reseaux de neurones.',      'ING2', 'S4', 'Informatique',  5, 30, 3);


INSERT INTO Inscription (idEtudiant, idCours) VALUES

(9, 1), (10, 1), (11, 1), (15, 1),    
(9, 2), (10, 2), (11, 2),             
(9, 3), (10, 3),                     
(9, 8), (15, 8),      
(6, 4), (7, 4), (8, 4),               
(6, 7), (7, 7),                        

(12, 5), (13, 5), (14, 5),            
(12, 6), (13, 6);                    

INSERT INTO Note (idEtudiant, idCours, typeEvaluation, valeur, coefficient, verrouille) VALUES
(9, 1, 'CC1',    14.00, 1.00, 0),
(9, 1, 'CC2',    15.50, 1.00, 0),
(9, 1, 'Examen', 12.00, 2.00, 0),
(9, 2, 'CC1',    16.00, 1.00, 1),
(9, 2, 'Examen', 13.00, 2.00, 1),
(10, 1, 'CC1',    8.00, 1.00, 0),
(10, 1, 'CC2',   11.00, 1.00, 0),
(10, 1, 'Examen', 9.50, 2.00, 0),

(11, 1, 'CC1',   17.00, 1.00, 0),
(11, 1, 'CC2',   16.00, 1.00, 0);


INSERT INTO Seance (idCours, jour, heureDebut, heureFin, salle) VALUES
(1, 'Lundi',    '08:00:00', '10:00:00', 'A101'), 
(2, 'Lundi',    '10:15:00', '12:15:00', 'A102'),   
(3, 'Mardi',    '08:00:00', '10:00:00', 'A101'),  
(8, 'Mercredi', '14:00:00', '16:00:00', 'B201'),  
(4, 'Lundi',    '08:00:00', '10:00:00', 'C301'), 
(7, 'Jeudi',    '10:00:00', '12:00:00', 'C302'),  
(5, 'Jeudi',    '08:00:00', '10:00:00', 'D401'),   
(6, 'Vendredi', '10:00:00', '12:00:00', 'D401');   
