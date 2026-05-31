<?php


require_once __DIR__ . '/config/init.php';

$methode = $_SERVER['REQUEST_METHOD'];
$id      = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($methode) {
    case 'GET':
        exigerConnexion();
        $id !== null ? voirCours($id) : listerCours();
        break;
    case 'POST':
        exigerRole('admin');
        creerCours();
        break;
    case 'PUT':
        exigerRole('admin');
        if ($id === null) erreurJson('Identifiant manquant.');
        modifierCours($id);
        break;
    case 'DELETE':
        exigerRole('admin');
        if ($id === null) erreurJson('Identifiant manquant.');
        supprimerCours($id);
        break;
    default:
        erreurJson('Méthode non autorisée.', 405);
}


function listerCours(): void
{
    $recherche   = trim($_GET['recherche'] ?? '');
    $promotion   = trim($_GET['promotion'] ?? '');
    $semestre    = trim($_GET['semestre'] ?? '');
    $departement = trim($_GET['departement'] ?? '');

  
    $colonnesTri = [
        'nom'         => 'c.nomCours',
        'promotion'   => 'c.promotion',
        'semestre'    => 'c.semestre',
        'departement' => 'c.departement',
        'credits'     => 'c.credits',
        'capacite'    => 'c.capaciteMax',
        'inscrits'    => 'nbInscrits',
    ];
    $tri   = $colonnesTri[$_GET['tri'] ?? 'nom'] ?? 'c.nomCours';
    $ordre = strtoupper($_GET['ordre'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

    $sql = "SELECT c.idCours, c.nomCours, c.description, c.promotion, c.semestre,
                   c.departement, c.credits, c.capaciteMax, c.idEnseignant,
                   CONCAT(u.prenom, ' ', u.nom) AS enseignant,
                   (SELECT COUNT(*) FROM Inscription i WHERE i.idCours = c.idCours) AS nbInscrits
            FROM Cours c
            LEFT JOIN Utilisateur u ON u.idUser = c.idEnseignant
            WHERE 1 = 1";
    $params = [];

    if ($recherche !== '')   { $sql .= " AND c.nomCours LIKE ?";   $params[] = '%' . $recherche . '%'; }
    if ($promotion !== '')   { $sql .= " AND c.promotion = ?";     $params[] = $promotion; }
    if ($semestre !== '')    { $sql .= " AND c.semestre = ?";      $params[] = $semestre; }
    if ($departement !== '') { $sql .= " AND c.departement = ?";   $params[] = $departement; }
    if (isset($_GET['idEnseignant']) && $_GET['idEnseignant'] !== '') {
        $sql .= " AND c.idEnseignant = ?"; $params[] = (int) $_GET['idEnseignant'];
    }

    $sql .= " ORDER BY $tri $ordre";

    $req = db()->prepare($sql);
    $req->execute($params);
    repondreJson($req->fetchAll());
}


function voirCours(int $id): void
{
    $req = db()->prepare(
        "SELECT c.idCours, c.nomCours, c.description, c.promotion, c.semestre,
                c.departement, c.credits, c.capaciteMax, c.idEnseignant,
                CONCAT(u.prenom, ' ', u.nom) AS enseignant,
                (SELECT COUNT(*) FROM Inscription i WHERE i.idCours = c.idCours) AS nbInscrits
         FROM Cours c
         LEFT JOIN Utilisateur u ON u.idUser = c.idEnseignant
         WHERE c.idCours = ?"
    );
    $req->execute([$id]);
    $cours = $req->fetch();
    if (!$cours) erreurJson('Cours introuvable.', 404);
    repondreJson($cours);
}


function creerCours(): void
{
    $champs = validerCours(corpsJson());

    $req = db()->prepare(
        "INSERT INTO Cours (nomCours, description, promotion, semestre, departement, credits, capaciteMax, idEnseignant)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $req->execute([
        $champs['nomCours'], $champs['description'], $champs['promotion'], $champs['semestre'],
        $champs['departement'], $champs['credits'], $champs['capaciteMax'], $champs['idEnseignant'],
    ]);

    repondreJson(['success' => true, 'idCours' => (int) db()->lastInsertId()], 201);
}

function modifierCours(int $id): void
{
    $req = db()->prepare("SELECT idCours FROM Cours WHERE idCours = ?");
    $req->execute([$id]);
    if (!$req->fetch()) erreurJson('Cours introuvable.', 404);

    $champs = validerCours(corpsJson());

    $req = db()->prepare(
        "UPDATE Cours
         SET nomCours = ?, description = ?, promotion = ?, semestre = ?,
             departement = ?, credits = ?, capaciteMax = ?, idEnseignant = ?
         WHERE idCours = ?"
    );
    $req->execute([
        $champs['nomCours'], $champs['description'], $champs['promotion'], $champs['semestre'],
        $champs['departement'], $champs['credits'], $champs['capaciteMax'], $champs['idEnseignant'], $id,
    ]);

    repondreJson(['success' => true]);
}

function supprimerCours(int $id): void
{
    $req = db()->prepare("SELECT idCours FROM Cours WHERE idCours = ?");
    $req->execute([$id]);
    if (!$req->fetch()) erreurJson('Cours introuvable.', 404);

    $req = db()->prepare("DELETE FROM Cours WHERE idCours = ?");
    $req->execute([$id]);

    repondreJson(['success' => true]);
}

function validerCours(array $data): array
{
    $nomCours    = trim($data['nomCours'] ?? '');
    $description = trim($data['description'] ?? '');
    $promotion   = trim($data['promotion'] ?? '');
    $semestre    = trim($data['semestre'] ?? '');
    $departement = trim($data['departement'] ?? '');
    $credits     = $data['credits'] ?? null;
    $capaciteMax = $data['capaciteMax'] ?? null;
    $idEnseignant = $data['idEnseignant'] ?? null;

    if ($nomCours === '' || $departement === '') {
        erreurJson('Le nom du cours et le département sont obligatoires.');
    }
    if (!in_array($promotion, ['ING1', 'ING2', 'ING3'], true)) erreurJson('Promotion invalide.');
    if (!in_array($semestre, ['S1', 'S2', 'S3', 'S4', 'S5', 'S6'], true)) erreurJson('Semestre invalide.');
    if (!is_numeric($credits) || (int) $credits < 1 || (int) $credits > 30) erreurJson('Crédits invalides (1 à 30).');
    if (!is_numeric($capaciteMax) || (int) $capaciteMax < 1) erreurJson('Capacité invalide (au moins 1).');

    if ($idEnseignant === null || $idEnseignant === '') {
        $idEnseignant = null;
    } else {
        $idEnseignant = (int) $idEnseignant;
        $req = db()->prepare("SELECT idUser FROM Utilisateur WHERE idUser = ? AND role = 'enseignant'");
        $req->execute([$idEnseignant]);
        if (!$req->fetch()) erreurJson('Enseignant invalide.');
    }

    return [
        'nomCours'     => $nomCours,
        'description'  => ($description === '' ? null : $description),
        'promotion'    => $promotion,
        'semestre'     => $semestre,
        'departement'  => $departement,
        'credits'      => (int) $credits,
        'capaciteMax'  => (int) $capaciteMax,
        'idEnseignant' => $idEnseignant,
    ];
}
