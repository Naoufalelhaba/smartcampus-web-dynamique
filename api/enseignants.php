<?php
// =====================================================================
//  /enseignants.php — Gestion des enseignants (réservé à l'administrateur)
//      GET    /enseignants.php          -> liste (avec nb de cours) + recherche
//      GET    /enseignants.php?id=3     -> un enseignant + ses cours
//      POST   /enseignants.php          -> créer un enseignant
//      PUT    /enseignants.php?id=3     -> modifier un enseignant
//      DELETE /enseignants.php?id=3     -> supprimer un enseignant
// =====================================================================

require_once __DIR__ . '/config/init.php';

exigerRole('admin');

$methode = $_SERVER['REQUEST_METHOD'];
$id      = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($methode) {
    case 'GET':
        $id !== null ? voirEnseignant($id) : listerEnseignants();
        break;
    case 'POST':
        creerEnseignant();
        break;
    case 'PUT':
        if ($id === null) erreurJson('Identifiant manquant.');
        modifierEnseignant($id);
        break;
    case 'DELETE':
        if ($id === null) erreurJson('Identifiant manquant.');
        supprimerEnseignant($id);
        break;
    default:
        erreurJson('Méthode non autorisée.', 405);
}

// ---------------------------------------------------------------------
function listerEnseignants(): void
{
    $recherche = trim($_GET['recherche'] ?? '');

    // On compte au passage le nombre de cours dont chaque enseignant est responsable.
    $sql = "SELECT u.idUser, u.nom, u.prenom, u.email, COUNT(c.idCours) AS nbCours
            FROM Utilisateur u
            LEFT JOIN Cours c ON c.idEnseignant = u.idUser
            WHERE u.role = 'enseignant'";
    $params = [];

    if ($recherche !== '') {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $motif = '%' . $recherche . '%';
        array_push($params, $motif, $motif, $motif);
    }
    $sql .= " GROUP BY u.idUser ORDER BY u.nom, u.prenom";

    $req = db()->prepare($sql);
    $req->execute($params);
    repondreJson($req->fetchAll());
}

// ---------------------------------------------------------------------
function voirEnseignant(int $id): void
{
    $req = db()->prepare(
        "SELECT idUser, nom, prenom, email FROM Utilisateur
         WHERE idUser = ? AND role = 'enseignant'"
    );
    $req->execute([$id]);
    $enseignant = $req->fetch();
    if (!$enseignant) erreurJson('Enseignant introuvable.', 404);

    // Cours dont il est responsable (+ nombre d'inscrits).
    $req = db()->prepare(
        "SELECT c.idCours, c.nomCours, c.promotion, c.semestre, c.capaciteMax,
                COUNT(i.idEtudiant) AS nbInscrits
         FROM Cours c
         LEFT JOIN Inscription i ON i.idCours = c.idCours
         WHERE c.idEnseignant = ?
         GROUP BY c.idCours
         ORDER BY c.promotion, c.nomCours"
    );
    $req->execute([$id]);
    $cours = $req->fetchAll();

    repondreJson(['enseignant' => $enseignant, 'cours' => $cours]);
}

// ---------------------------------------------------------------------
function creerEnseignant(): void
{
    $data       = corpsJson();
    $nom        = trim($data['nom'] ?? '');
    $prenom     = trim($data['prenom'] ?? '');
    $email      = trim($data['email'] ?? '');
    $motDePasse = $data['motDePasse'] ?? '';

    if ($nom === '' || $prenom === '' || $email === '' || $motDePasse === '') {
        erreurJson('Nom, prénom, email et mot de passe sont obligatoires.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) erreurJson('Email invalide.');
    if (strlen($motDePasse) < 6) erreurJson('Le mot de passe doit faire au moins 6 caractères.');

    $req = db()->prepare("SELECT idUser FROM Utilisateur WHERE email = ?");
    $req->execute([$email]);
    if ($req->fetch()) erreurJson('Cet email est déjà utilisé.', 409);

    $hache = password_hash($motDePasse, PASSWORD_DEFAULT);
    $req = db()->prepare(
        "INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, role, promotion)
         VALUES (?, ?, ?, ?, 'enseignant', NULL)"
    );
    $req->execute([$nom, $prenom, $email, $hache]);

    repondreJson(['success' => true, 'idUser' => (int) db()->lastInsertId()], 201);
}

// ---------------------------------------------------------------------
function modifierEnseignant(int $id): void
{
    $data = corpsJson();

    $req = db()->prepare("SELECT idUser FROM Utilisateur WHERE idUser = ? AND role = 'enseignant'");
    $req->execute([$id]);
    if (!$req->fetch()) erreurJson('Enseignant introuvable.', 404);

    $nom    = trim($data['nom'] ?? '');
    $prenom = trim($data['prenom'] ?? '');
    $email  = trim($data['email'] ?? '');

    if ($nom === '' || $prenom === '' || $email === '') erreurJson('Nom, prénom et email sont obligatoires.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) erreurJson('Email invalide.');

    $req = db()->prepare("SELECT idUser FROM Utilisateur WHERE email = ? AND idUser <> ?");
    $req->execute([$email, $id]);
    if ($req->fetch()) erreurJson('Cet email est déjà utilisé par un autre compte.', 409);

    if (!empty($data['motDePasse'])) {
        $hache = password_hash($data['motDePasse'], PASSWORD_DEFAULT);
        $req = db()->prepare("UPDATE Utilisateur SET nom = ?, prenom = ?, email = ?, mot_de_passe = ? WHERE idUser = ?");
        $req->execute([$nom, $prenom, $email, $hache, $id]);
    } else {
        $req = db()->prepare("UPDATE Utilisateur SET nom = ?, prenom = ?, email = ? WHERE idUser = ?");
        $req->execute([$nom, $prenom, $email, $id]);
    }

    repondreJson(['success' => true]);
}

// ---------------------------------------------------------------------
function supprimerEnseignant(int $id): void
{
    $req = db()->prepare("SELECT idUser FROM Utilisateur WHERE idUser = ? AND role = 'enseignant'");
    $req->execute([$id]);
    if (!$req->fetch()) erreurJson('Enseignant introuvable.', 404);

    // Les cours de cet enseignant ne sont PAS supprimés : idEnseignant passe à NULL
    // (clause ON DELETE SET NULL) -> le cours reste, simplement sans enseignant.
    $req = db()->prepare("DELETE FROM Utilisateur WHERE idUser = ?");
    $req->execute([$id]);

    repondreJson(['success' => true]);
}
