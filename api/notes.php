<?php
// =====================================================================
//  /notes.php — Gestion des notes
//      GET    /notes.php?idCours=1            -> carnet de notes d'un cours (enseignant du cours / admin)
//      GET    /notes.php?idEtudiant=9         -> résultats d'un étudiant (admin / l'étudiant lui-même)
//      GET    /notes.php                      -> ses propres résultats (étudiant connecté)
//      POST   /notes.php                      -> saisir une note (enseignant du cours / admin)
//      POST   /notes.php?action=valider       -> valider+verrouiller les notes d'un cours
//      PUT    /notes.php?id=5                 -> modifier une note (si non verrouillée)
//      DELETE /notes.php?id=5                 -> supprimer une note (si non verrouillée)
// =====================================================================

require_once __DIR__ . '/config/init.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        lireNotes();
        break;
    case 'POST':
        (($_GET['action'] ?? '') === 'valider') ? validerNotesCours() : creerNote();
        break;
    case 'PUT':
        if ($id === null) erreurJson('Identifiant de note manquant.');
        modifierNote($id);
        break;
    case 'DELETE':
        if ($id === null) erreurJson('Identifiant de note manquant.');
        supprimerNote($id);
        break;
    default:
        erreurJson('Méthode non autorisée.', 405);
}

// ---------------------------------------------------------------------
// Vérifie que l'utilisateur a le droit de gérer les notes d'un cours :
// soit l'admin, soit l'enseignant responsable de CE cours. Renvoie le cours.
function exigerAccesCours(int $idCours): array
{
    $user = exigerConnexion();
    $req = db()->prepare("SELECT * FROM Cours WHERE idCours = ?");
    $req->execute([$idCours]);
    $cours = $req->fetch();
    if (!$cours) erreurJson('Cours introuvable.', 404);

    if ($user['role'] === 'admin') return $cours;
    if ($user['role'] === 'enseignant' && (int) $cours['idEnseignant'] === (int) $user['idUser']) return $cours;

    erreurJson('Vous ne pouvez gérer que les notes de vos propres cours.', 403);
}

// Un cours est "verrouillé" dès qu'au moins une de ses notes est validée.
function coursEstVerrouille(int $idCours): bool
{
    $req = db()->prepare("SELECT COUNT(*) AS n FROM Note WHERE idCours = ? AND verrouille = 1");
    $req->execute([$idCours]);
    return (int) $req->fetch()['n'] > 0;
}

// ---------------------------------------------------------------------
function creerNote(): void
{
    $data       = corpsJson();
    $idCours    = (int) ($data['idCours'] ?? 0);
    $idEtudiant = (int) ($data['idEtudiant'] ?? 0);
    $type       = trim($data['typeEvaluation'] ?? '');
    $valeur     = $data['valeur'] ?? null;
    $coefficient = $data['coefficient'] ?? 1;

    if ($idCours <= 0 || $idEtudiant <= 0) erreurJson('idCours et idEtudiant requis.');

    exigerAccesCours($idCours);   // existence du cours + droits (enseignant du cours / admin)

    if (!in_array($type, ['CC1', 'CC2', 'TP', 'Projet', 'Examen'], true)) {
        erreurJson('Type d\'évaluation invalide.');
    }
    if (!is_numeric($valeur) || $valeur < 0 || $valeur > 20) {
        erreurJson('La note doit être un nombre entre 0 et 20.');
    }
    if (!is_numeric($coefficient) || $coefficient <= 0) {
        erreurJson('Coefficient invalide.');
    }

    // L'étudiant doit être inscrit au cours.
    $req = db()->prepare("SELECT 1 FROM Inscription WHERE idEtudiant = ? AND idCours = ?");
    $req->execute([$idEtudiant, $idCours]);
    if (!$req->fetch()) erreurJson('Cet étudiant n\'est pas inscrit à ce cours.');

    // Si le cours est déjà validé, on ne saisit plus de notes.
    if (coursEstVerrouille($idCours)) {
        erreurJson('Les notes de ce cours sont validées : la saisie est verrouillée.', 409);
    }

    $req = db()->prepare(
        "INSERT INTO Note (idEtudiant, idCours, typeEvaluation, valeur, coefficient)
         VALUES (?, ?, ?, ?, ?)"
    );
    $req->execute([$idEtudiant, $idCours, $type, $valeur, $coefficient]);

    repondreJson(['success' => true, 'idNote' => (int) db()->lastInsertId()], 201);
}

// ---------------------------------------------------------------------
function modifierNote(int $id): void
{
    $req = db()->prepare("SELECT * FROM Note WHERE idNote = ?");
    $req->execute([$id]);
    $note = $req->fetch();
    if (!$note) erreurJson('Note introuvable.', 404);

    exigerAccesCours((int) $note['idCours']);

    // Règle : une note validée ne peut plus être modifiée.
    if ((int) $note['verrouille'] === 1) {
        erreurJson('Cette note est validée : elle ne peut plus être modifiée.', 409);
    }

    $data        = corpsJson();
    $valeur      = $data['valeur'] ?? $note['valeur'];
    $type        = trim($data['typeEvaluation'] ?? $note['typeEvaluation']);
    $coefficient = $data['coefficient'] ?? $note['coefficient'];

    if (!is_numeric($valeur) || $valeur < 0 || $valeur > 20) erreurJson('La note doit être entre 0 et 20.');
    if (!in_array($type, ['CC1', 'CC2', 'TP', 'Projet', 'Examen'], true)) erreurJson('Type d\'évaluation invalide.');
    if (!is_numeric($coefficient) || $coefficient <= 0) erreurJson('Coefficient invalide.');

    $req = db()->prepare("UPDATE Note SET valeur = ?, typeEvaluation = ?, coefficient = ? WHERE idNote = ?");
    $req->execute([$valeur, $type, $coefficient, $id]);

    repondreJson(['success' => true]);
}

// ---------------------------------------------------------------------
function supprimerNote(int $id): void
{
    $req = db()->prepare("SELECT idCours, verrouille FROM Note WHERE idNote = ?");
    $req->execute([$id]);
    $note = $req->fetch();
    if (!$note) erreurJson('Note introuvable.', 404);

    exigerAccesCours((int) $note['idCours']);

    if ((int) $note['verrouille'] === 1) erreurJson('Cette note est validée : suppression impossible.', 409);

    $req = db()->prepare("DELETE FROM Note WHERE idNote = ?");
    $req->execute([$id]);
    repondreJson(['success' => true]);
}

// ---------------------------------------------------------------------
// Validation finale : verrouille toutes les notes d'un cours.
function validerNotesCours(): void
{
    $data = corpsJson();
    $idCours = (int) ($data['idCours'] ?? 0);
    if ($idCours <= 0) erreurJson('idCours requis.');

    exigerAccesCours($idCours);

    $req = db()->prepare("UPDATE Note SET verrouille = 1 WHERE idCours = ?");
    $req->execute([$idCours]);

    repondreJson(['success' => true, 'notesVerrouillees' => $req->rowCount()]);
}

// ---------------------------------------------------------------------
function lireNotes(): void
{
    $user       = exigerConnexion();
    $idCours    = isset($_GET['idCours'])    ? (int) $_GET['idCours']    : null;
    $idEtudiant = isset($_GET['idEtudiant']) ? (int) $_GET['idEtudiant'] : null;

    if ($idCours !== null) {
        exigerAccesCours($idCours);          // enseignant du cours / admin
        carnetDeCours($idCours);
    } elseif ($idEtudiant !== null) {
        if ($user['role'] === 'etudiant' && (int) $user['idUser'] !== $idEtudiant) erreurJson('Accès interdit.', 403);
        if ($user['role'] === 'enseignant') erreurJson('Accès interdit.', 403);
        resultatsDEtudiant($idEtudiant);
    } else {
        if ($user['role'] !== 'etudiant') erreurJson('Préciser idCours ou idEtudiant.');
        resultatsDEtudiant((int) $user['idUser']);
    }
}

// ---------------------------------------------------------------------
// Carnet de notes d'un cours : étudiants inscrits, leurs notes, leur moyenne.
function carnetDeCours(int $idCours): void
{
    $req = db()->prepare("SELECT idCours, nomCours, promotion, semestre FROM Cours WHERE idCours = ?");
    $req->execute([$idCours]);
    $cours = $req->fetch();

    $req = db()->prepare(
        "SELECT u.idUser, u.nom, u.prenom
         FROM Inscription i JOIN Utilisateur u ON u.idUser = i.idEtudiant
         WHERE i.idCours = ? ORDER BY u.nom, u.prenom"
    );
    $req->execute([$idCours]);
    $etudiants = $req->fetchAll();

    $req = db()->prepare(
        "SELECT idNote, idEtudiant, typeEvaluation, valeur, coefficient, verrouille
         FROM Note WHERE idCours = ? ORDER BY typeEvaluation"
    );
    $req->execute([$idCours]);
    $notes = $req->fetchAll();

    // On regroupe les notes par étudiant et on calcule la moyenne pondérée.
    $parEtudiant = [];
    foreach ($etudiants as $e) {
        $parEtudiant[$e['idUser']] = [
            'idUser' => (int) $e['idUser'], 'nom' => $e['nom'], 'prenom' => $e['prenom'],
            'notes' => [], 'moyenne' => null,
        ];
    }
    $somme = []; $sommeCoef = [];
    foreach ($notes as $n) {
        $eid = (int) $n['idEtudiant'];
        if (!isset($parEtudiant[$eid])) continue;
        $parEtudiant[$eid]['notes'][] = $n;
        $somme[$eid]     = ($somme[$eid] ?? 0) + (float) $n['valeur'] * (float) $n['coefficient'];
        $sommeCoef[$eid] = ($sommeCoef[$eid] ?? 0) + (float) $n['coefficient'];
    }
    foreach ($parEtudiant as $eid => &$row) {
        if (!empty($sommeCoef[$eid])) $row['moyenne'] = round($somme[$eid] / $sommeCoef[$eid], 2);
    }
    unset($row);

    repondreJson([
        'cours'      => $cours,
        'verrouille' => coursEstVerrouille($idCours),
        'etudiants'  => array_values($parEtudiant),
    ]);
}

// ---------------------------------------------------------------------
// Résultats d'un étudiant : cours suivis, notes par cours, moyennes, moyenne générale.
function resultatsDEtudiant(int $idEtudiant): void
{
    $req = db()->prepare("SELECT idUser, nom, prenom, email, promotion FROM Utilisateur WHERE idUser = ? AND role = 'etudiant'");
    $req->execute([$idEtudiant]);
    $etudiant = $req->fetch();
    if (!$etudiant) erreurJson('Étudiant introuvable.', 404);

    $req = db()->prepare(
        "SELECT c.idCours, c.nomCours, c.semestre, c.credits
         FROM Inscription i JOIN Cours c ON c.idCours = i.idCours
         WHERE i.idEtudiant = ? ORDER BY c.semestre, c.nomCours"
    );
    $req->execute([$idEtudiant]);
    $coursList = $req->fetchAll();

    $req = db()->prepare(
        "SELECT idNote, idCours, typeEvaluation, valeur, coefficient, verrouille
         FROM Note WHERE idEtudiant = ? ORDER BY typeEvaluation"
    );
    $req->execute([$idEtudiant]);
    $notes = $req->fetchAll();

    $parCours = [];
    foreach ($coursList as $c) {
        $parCours[(int) $c['idCours']] = $c + ['notes' => [], 'moyenne' => null];
    }
    $somme = []; $coef = [];
    foreach ($notes as $n) {
        $cid = (int) $n['idCours'];
        if (!isset($parCours[$cid])) continue;
        $parCours[$cid]['notes'][] = $n;
        $somme[$cid] = ($somme[$cid] ?? 0) + (float) $n['valeur'] * (float) $n['coefficient'];
        $coef[$cid]  = ($coef[$cid] ?? 0) + (float) $n['coefficient'];
    }

    // Moyenne par cours + moyenne générale pondérée par les crédits.
    $sommeGen = 0.0; $creditsGen = 0;
    foreach ($parCours as $cid => &$c) {
        if (!empty($coef[$cid])) {
            $c['moyenne'] = round($somme[$cid] / $coef[$cid], 2);
            $sommeGen   += $c['moyenne'] * (int) $c['credits'];
            $creditsGen += (int) $c['credits'];
        }
    }
    unset($c);
    $moyenneGenerale = $creditsGen > 0 ? round($sommeGen / $creditsGen, 2) : null;

    repondreJson([
        'etudiant'        => $etudiant,
        'cours'           => array_values($parCours),
        'moyenneGenerale' => $moyenneGenerale,
    ]);
}
