<?php


require_once __DIR__ . '/config/init.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        listerInscriptions();
        break;
    case 'POST':
        inscrire();
        break;
    case 'DELETE':
        desinscrire();
        break;
    default:
        erreurJson('Méthode non autorisée.', 405);
}


function inscrire(): void
{
    $user = exigerConnexion();
    $data = corpsJson();
    $idCours = (int) ($data['idCours'] ?? 0);
    if ($idCours <= 0) erreurJson('idCours requis.');


    if ($user['role'] === 'etudiant') {
        $idEtudiant = (int) $user['idUser'];
    } elseif ($user['role'] === 'admin') {
        $idEtudiant = (int) ($data['idEtudiant'] ?? 0);
        if ($idEtudiant <= 0) erreurJson('idEtudiant requis pour une inscription par l\'admin.');
    } else {
        erreurJson('Action non autorisée pour votre rôle.', 403);
    }

    $req = db()->prepare("SELECT capaciteMax FROM Cours WHERE idCours = ?");
    $req->execute([$idCours]);
    $cours = $req->fetch();
    if (!$cours) erreurJson('Cours introuvable.', 404);

   
    $req = db()->prepare("SELECT idUser FROM Utilisateur WHERE idUser = ? AND role = 'etudiant'");
    $req->execute([$idEtudiant]);
    if (!$req->fetch()) erreurJson('Étudiant introuvable.', 404);

   
    $req = db()->prepare("SELECT 1 FROM Inscription WHERE idEtudiant = ? AND idCours = ?");
    $req->execute([$idEtudiant, $idCours]);
    if ($req->fetch()) {
        erreurJson('Cet étudiant est déjà inscrit à ce cours.', 409);
    }

    $req = db()->prepare("SELECT COUNT(*) AS n FROM Inscription WHERE idCours = ?");
    $req->execute([$idCours]);
    $nbInscrits = (int) $req->fetch()['n'];
    if ($nbInscrits >= (int) $cours['capaciteMax']) {
        erreurJson('Cours complet : la capacité maximale est atteinte.', 409);
    }

    $req = db()->prepare("INSERT INTO Inscription (idEtudiant, idCours) VALUES (?, ?)");
    $req->execute([$idEtudiant, $idCours]);

    repondreJson(['success' => true], 201);
}


function desinscrire(): void
{
    $user = exigerConnexion();
    $idCours = (int) ($_GET['idCours'] ?? 0);
    if ($idCours <= 0) erreurJson('idCours requis.');

    if ($user['role'] === 'etudiant') {
        $idEtudiant = (int) $user['idUser'];
    } elseif ($user['role'] === 'admin') {
        $idEtudiant = (int) ($_GET['idEtudiant'] ?? 0);
        if ($idEtudiant <= 0) erreurJson('idEtudiant requis pour une désinscription par l\'admin.');
    } else {
        erreurJson('Action non autorisée pour votre rôle.', 403);
    }


    $req = db()->prepare("SELECT 1 FROM Inscription WHERE idEtudiant = ? AND idCours = ?");
    $req->execute([$idEtudiant, $idCours]);
    if (!$req->fetch()) erreurJson('Inscription introuvable.', 404);


    if ($user['role'] === 'etudiant') {
        $req = db()->prepare("SELECT COUNT(*) AS n FROM Note WHERE idEtudiant = ? AND idCours = ?");
        $req->execute([$idEtudiant, $idCours]);
        if ((int) $req->fetch()['n'] > 0) {
            erreurJson('Impossible de se désinscrire : des notes existent déjà pour ce cours.', 409);
        }
    }

    $req = db()->prepare("DELETE FROM Inscription WHERE idEtudiant = ? AND idCours = ?");
    $req->execute([$idEtudiant, $idCours]);

    repondreJson(['success' => true]);
}

function listerInscriptions(): void
{
    $user = exigerConnexion();
    $idCours    = isset($_GET['idCours'])    ? (int) $_GET['idCours']    : null;
    $idEtudiant = isset($_GET['idEtudiant']) ? (int) $_GET['idEtudiant'] : null;

    if ($idCours !== null) {
        if ($user['role'] === 'enseignant') {
            $req = db()->prepare("SELECT idEnseignant FROM Cours WHERE idCours = ?");
            $req->execute([$idCours]);
            $c = $req->fetch();
            if (!$c) erreurJson('Cours introuvable.', 404);
            if ((int) $c['idEnseignant'] !== (int) $user['idUser']) {
                erreurJson('Ce cours ne fait pas partie de vos enseignements.', 403);
            }
        } elseif ($user['role'] !== 'admin') {
            erreurJson('Accès interdit.', 403);
        }
        etudiantsDUnCours($idCours);

    } elseif ($idEtudiant !== null) {
      
        if ($user['role'] === 'etudiant' && (int) $user['idUser'] !== $idEtudiant) {
            erreurJson('Accès interdit.', 403);
        }
        if ($user['role'] === 'enseignant') erreurJson('Accès interdit.', 403);
        coursDUnEtudiant($idEtudiant);

    } else {
        if ($user['role'] !== 'etudiant') erreurJson('Préciser idCours ou idEtudiant.');
        coursDUnEtudiant((int) $user['idUser']);
    }
}

function etudiantsDUnCours(int $idCours): void
{
    $req = db()->prepare(
        "SELECT u.idUser, u.nom, u.prenom, u.email, u.promotion, i.dateInscription,
                (SELECT ROUND(SUM(n.valeur * n.coefficient) / NULLIF(SUM(n.coefficient), 0), 2)
                 FROM Note n WHERE n.idCours = i.idCours AND n.idEtudiant = u.idUser) AS moyenne
         FROM Inscription i
         JOIN Utilisateur u ON u.idUser = i.idEtudiant
         WHERE i.idCours = ?
         ORDER BY u.nom, u.prenom"
    );
    $req->execute([$idCours]);
    repondreJson($req->fetchAll());
}
function coursDUnEtudiant(int $idEtudiant): void
{
    $req = db()->prepare(
        "SELECT c.idCours, c.nomCours, c.promotion, c.semestre, c.credits, c.capaciteMax,
                CONCAT(u.prenom, ' ', u.nom) AS enseignant, i.dateInscription,
                (SELECT ROUND(SUM(n.valeur * n.coefficient) / NULLIF(SUM(n.coefficient), 0), 2)
                 FROM Note n WHERE n.idCours = c.idCours AND n.idEtudiant = i.idEtudiant) AS moyenne
         FROM Inscription i
         JOIN Cours c        ON c.idCours = i.idCours
         LEFT JOIN Utilisateur u ON u.idUser = c.idEnseignant
         WHERE i.idEtudiant = ?
         ORDER BY c.semestre, c.nomCours"
    );
    $req->execute([$idEtudiant]);
    repondreJson($req->fetchAll());
}
