# SmartCampus — Plateforme de gestion académique

**Projet Web Dynamique 2026 — ING2 Groupe 5**
Naoufal EL HABA · Othmane HASSIB · Nahel EL HADDAOUI ALAMI

SmartCampus est une application web de gestion académique pour une école d'ingénieurs
(filière informatique, promotions ING1 / ING2 / ING3). Elle gère les étudiants, les
enseignants, les cours, les inscriptions, les notes et l'emploi du temps, avec trois
rôles d'utilisateurs : **administrateur**, **enseignant** et **étudiant**.

---

## 🧱 Stack technique

| Couche | Technologie |
|---|---|
| Frontend | **React** (SPA) avec **Vite** |
| Backend | **PHP** (API REST, PDO) — sans framework |
| Base de données | **MySQL 8** |
| Environnement | **WAMP** (Apache + MySQL) + **Node.js** |

L'application suit une architecture **client–serveur découplée** : le frontend React
appelle une API REST en PHP, qui dialogue avec la base MySQL via PDO (requêtes préparées).

```
Navigateur (React)  ──fetch JSON──►  API REST (PHP/PDO)  ──SQL──►  MySQL
```

---

## 📂 Arborescence

```
SmartCampus/
├── api/                      Backend PHP (API REST)
│   ├── config/               Connexion BDD, session, helpers, auth
│   ├── auth/                 login, logout, me
│   ├── etudiants.php  enseignants.php  cours.php
│   └── inscriptions.php  notes.php  seances.php
├── frontend/                 Application React (Vite)
│   └── src/                  pages (admin / enseignant / etudiant), composants, contexte d'auth
├── database/
│   ├── schema.sql            Création de la base et des 5 tables
│   └── seed.sql              Jeu de données de démonstration
├── installer-base-de-donnees.bat   Importe la base (1 clic)
├── demarrer.bat                     Lance l'API + le frontend (1 clic)
└── README.md
```

---

## ⚙️ Prérequis

- **WAMP** (avec MySQL) — [https://www.wampserver.com](https://www.wampserver.com)
- **Node.js** (version 18 ou supérieure) — [https://nodejs.org](https://nodejs.org)

---

## 🚀 Installation et lancement

### 1. Démarrer WAMP
Lancez WAMP et attendez que l'icône passe au **vert** (Apache + MySQL démarrés).

### 2. Créer la base de données
Double-cliquez sur **`installer-base-de-donnees.bat`**.

> Alternative manuelle (phpMyAdmin) : importez `database/schema.sql` puis `database/seed.sql`.
> Alternative ligne de commande :
> ```bash
> mysql -u root < database/schema.sql
> mysql -u root < database/seed.sql
> ```

### 3. Lancer l'application
Double-cliquez sur **`demarrer.bat`** (il installe les dépendances la première fois,
puis démarre l'API PHP et le frontend React).

> Alternative manuelle, dans deux terminaux :
> ```bash
> # Terminal 1 — API PHP (depuis le dossier du projet)
> php -S localhost:8000 -t api
>
> # Terminal 2 — Frontend (depuis le dossier frontend)
> cd frontend
> npm install      # uniquement la première fois
> npm run dev
> ```

### 4. Ouvrir l'application
👉 **http://localhost:5173**

---

## 👤 Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@smartcampus.fr` | `admin123` |
| Enseignant | `a.turing@smartcampus.fr` | `prof123` |
| Étudiant | `lea.durand@smartcampus.fr` | `etudiant123` |

*(Tous les enseignants utilisent `prof123`, tous les étudiants `etudiant123`.)*

---

## 🗄️ Modèle de données (5 tables)

`Utilisateur` (admin/enseignant/étudiant) · `Cours` · `Inscription` · `Note` · `Seance`.

### Règles métier implémentées
- **Anti double-inscription** : clé primaire composée `(idEtudiant, idCours)` dans `Inscription`.
- **Capacité maximale** : contrôle du nombre d'inscrits par rapport à `capaciteMax`.
- **Verrouillage des notes** : une note validée (`verrouille = 1`) ne peut plus être modifiée.
- **Conflits d'emploi du temps** : refus de deux séances qui se chevauchent sur la même
  salle, la même promotion ou le même enseignant.
- **Cohérence** : suppressions en cascade (`ON DELETE CASCADE` / `SET NULL`).

### Sécurité
Mots de passe hachés (`password_hash`), requêtes préparées (anti-injection SQL),
sessions PHP, cookies `httpOnly`, et contrôle des accès par rôle côté serveur.
