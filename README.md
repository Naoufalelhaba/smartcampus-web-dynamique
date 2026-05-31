# SmartCampus

Application web de gestion académique pour une école d'ingénieurs.
Elle réunit trois acteurs (administrateur, enseignant et étudiant) autour d'un
système centralisé : inscriptions aux cours, saisie et calcul des notes, emploi du
temps avec détection de conflits, et tableaux de bord adaptés à chaque rôle.

Projet Web Dynamique 2026, ING2, Groupe 5.
Othmane HASSIB, Naoufal EL HABA, Nahel EL HADDAOUI ALAMI.

## Technologies

- Frontend : React et React Router, build avec Vite.
- Backend : PHP, une API REST avec un fichier par ressource, accès aux données via PDO.
- Base de données : MySQL.

Le frontend (port 5173) parle à l'API (port 8000) à travers un proxy Vite qui préfixe
toutes les requêtes par `/api`. Vu du navigateur, tout est sur la même adresse, donc le
cookie de session passe sans souci.

```
Navigateur  ->  React (Vite, 5173)  ->  /api  ->  API PHP (8000)  ->  PDO  ->  MySQL (3306)
```

## Prérequis

- WAMP démarré, avec PHP 8 ou plus et MySQL 8 ou plus.
- Node.js 18 ou plus, et npm.

Sous WAMP, les deux scripts `.bat` retrouvent tout seuls PHP et MySQL dans `C:\wamp64`.
Si votre installation est ailleurs, ajustez le chemin dans les scripts.

## Installation et lancement

### Méthode simple, sous Windows avec WAMP

1. Démarrer WAMP et attendre que l'icône passe au vert.
2. Double-cliquer sur `installer-base-de-donnees.bat`. Le script crée la base, puis importe
   les tables (`database/schema.sql`) et les données de démonstration (`database/seed.sql`).
3. Double-cliquer sur `demarrer.bat`. Le script démarre l'API PHP sur le port 8000, installe
   les dépendances au premier lancement, puis démarre le frontend sur le port 5173.
4. Ouvrir le navigateur sur http://localhost:5173

### Méthode manuelle, sur n'importe quel système

```bash
# 1. Créer la base de données (depuis la racine du projet)
mysql -u root < database/schema.sql
mysql -u root < database/seed.sql

# 2. Démarrer l'API PHP (laisser ce terminal ouvert)
php -S localhost:8000 -t api

# 3. Démarrer le frontend (dans un second terminal)
cd frontend
npm install
npm run dev
```

Puis ouvrir http://localhost:5173

Si MySQL a un mot de passe, renseignez vos identifiants dans `api/config/database.php`
(hôte, port, utilisateur, mot de passe).

## Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | admin@smartcampus.fr | admin123 |
| Enseignant | a.turing@smartcampus.fr | prof123 |
| Étudiant | lea.durand@smartcampus.fr | etudiant123 |

La page de connexion propose aussi des boutons qui remplissent ces comptes en un clic.
Tous les enseignants utilisent prof123, tous les étudiants etudiant123.

## Arborescence du projet

```
smartcampus-web-dynamique/
  api/                       Backend PHP (API REST)
    config/                  Connexion à la base (PDO), session, authentification
    auth/                    login, logout, me
    etudiants.php            Gestion des étudiants et profil académique
    enseignants.php          Gestion des enseignants et de leurs cours
    cours.php                Gestion des cours, recherche, filtres, tri
    inscriptions.php         Inscription et désinscription
    notes.php                Saisie, modification, moyennes, validation
    seances.php              Emploi du temps et détection de conflits
  database/
    schema.sql               Les cinq tables
    seed.sql                 Jeu de données de démonstration
  frontend/                  Application React (Vite)
    src/
      pages/                 Pages par rôle : admin, enseignant, etudiant
      components/            Layout, Modale, EmploiDuTemps, ProtectedRoute
      auth/                  Contexte d'authentification
      api.js                 Client fetch centralisé
  docs/                      Rapport de compromis, journal IA, scénario de démo
  installer-base-de-donnees.bat
  demarrer.bat
```

## Règles métier

- Pas de double inscription : la clé primaire (idEtudiant, idCours) et un contrôle côté
  serveur empêchent un étudiant de s'inscrire deux fois au même cours.
- Capacité d'un cours : on compte les inscrits avant l'ajout, un cours plein est refusé.
- Conflits d'emploi du temps : deux séances qui se chevauchent sur la même salle, la même
  promotion ou le même enseignant sont bloquées.
- Notes verrouillées : une fois validées, les notes ne se modifient plus.
- Contrôle des rôles : chaque action sensible vérifie le rôle côté serveur.
- Suppressions propres : les clés étrangères en cascade évitent les données orphelines.

Pour la démonstration, le cours Algorithmique Avancée a une capacité de deux places déjà
remplies, ce qui permet de montrer le blocage. Léa Durand a aussi des notes validées en
Bases de Données, ce qui permet de montrer le verrouillage.

## Sécurité

- Mots de passe hachés avec bcrypt, jamais en clair.
- Requêtes préparées (PDO) partout, contre l'injection SQL.
- Tri des cours validé par une liste blanche de colonnes.
- Session régénérée à la connexion, cookie httpOnly.
- Validation des données côté serveur, en plus du côté client.
- Espaces protégés par rôle côté frontend, et vérifiés côté backend.

Les limites qu'on assume (HTTPS, CSRF avancé, limitation de débit) sont détaillées dans
`docs/rapport-compromis.md`.

## Documentation

- Rapport de compromis techniques : `docs/rapport-compromis.md`
- Journal d'assistance par IA : `docs/journal-assistance-ia.md`
- Scénario de démonstration : `docs/scenario-demonstration.md`
