<?php



require_once __DIR__ . '/config/init.php';

exigerRole('admin');   

$methode = $_SERVER['REQUEST_METHOD'];
$id      = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($methode) {
    case 'GET':
        $id !== null ? voirEtudiant($id) : listerEtudiants();
        break;
    case 'POST':
        creerEtudiant();
        break;
    case 'PUT':
        if ($id === null) erreurJson('Identifiant manquant.');
        modifierEtudiant($id);
        break;
    case 'DELETE':
        if ($id === null) erreurJson('Identifiant manquant.');
        supprimerEtudiant($id);
        break;
    default:
        erreurJson('Méthode non autorisée.', 405);
}


function listerEtudiants(): void
{

    $recherche = trim($_GET['recherche'] ?? '');
    $promotion = trim($_GET['promotion'] ?? '');

    $sql = "SELECT idUser, nom, prenom, email, promotion
            FROM Utilisateur WHERE role = 'etudiant'";
    $params = [];

    if ($recherche !== '') {
        $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
        $motif = '%' . $recherche . '%';
        array_push($params, $motif, $motif, $motif);
    }
    if ($promotion !== '') {
        $sql .= " AND promotion = ?";
        $params[] = $promotion;
    }
    $sql .= " ORDER BY nom, prenom";

    $req = db()->prepare($sql);
    $req->execute($params);
    repondreJson($req->fetchAll());
}
function voirEtudiant(int $id): void
{
    $req = db()->prepare(
        "SELECT idUser, nom, prenom, email, promotion
         FROM Utilisateur WHERE idUser = ? AND role = 'etudiant'"
    );
    $req->execute([$id]);
    $etudiant = $req->fetch();
    if (!$etudiant) erreurJson('Étudiant introuvable.', 404);

    $req = db()->prepare(
        "SELECT c.idCours, c.nomCours, c.semestre, c.credits,
                ROUND(SUM(n.valeur * n.coefficient) / NULLIF(SUM(n.coefficient), 0), 2) AS moyenne
         FROM Inscription i
         JOIN Cours c       ON c.idCours = i.idCours
         LEFT JOIN Note n   ON n.idCours = c.idCours AND n.idEtudiant = i.idEtudiant
         WHERE i.idEtudiant = ?
         GROUP BY c.idCours
         ORDER BY c.semestre, c.nomCours"
    );
    $req->execute([$id]);
    $cours = $req->fetchAll();
    $sommePonderee = 0.0;
    $sommeCredits  = 0;
    foreach ($cours as $c) {
        if ($c['moyenne'] !== null) {
            $sommePonderee += (float) $c['moyenne'] * (int) $c['credits'];
            $sommeCredits  += (int) $c['credits'];
        }
    }
    $moyenneGenerale = $sommeCredits > 0 ? round($sommePonderee / $sommeCredits, 2) : null;

    repondreJson([
        'etudiant'        => $etudiant,
        'cours'           => $cours,
        'moyenneGenerale' => $moyenneGenerale,
    ]);
}
function creerEtudiant(): void
{
    $data       = corpsJson();
    $nom        = trim($data['nom'] ?? '');
    $prenom     = trim($data['prenom'] ?? '');
    $email      = trim($data['email'] ?? '');
    $motDePasse = $data['motDePasse'] ?? '';
    $promotion  = trim($data['promotion'] ?? '');

  
    if ($nom === '' || $prenom === '' || $email === '' || $motDePasse === '') {
        erreurJson('Nom, prénom, email et mot de passe sont obligatoires.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        erreurJson('Email invalide.');
    }
    if (!in_array($promotion, ['ING1', 'ING2', 'ING3'], true)) {
        erreurJson('Promotion invalide (ING1, ING2 ou ING3).');
    }
    if (strlen($motDePasse) < 6) {
        erreurJson('Le mot de passe doit faire au moins 6 caractères.');
    }

    $req = db()->prepare("SELECT idUser FROM Utilisateur WHERE email = ?");
    $req->execute([$email]);
    if ($req->fetch()) erreurJson('Cet email est déjà utilisé.', 409);

    $hache = password_hash($motDePasse, PASSWORD_DEFAULT);
    $req = db()->prepare(
        "INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, role, promotion)
         VALUES (?, ?, ?, ?, 'etudiant', ?)"
    );
    $req->execute([$nom, $prenom, $email, $hache, $promotion]);

    repondreJson(['success' => true, 'idUser' => (int) db()->lastInsertId()], 201);
}

function modifierEtudiant(int $id): void
{
    $data = corpsJson();

    $req = db()->prepare("SELECT idUser FROM Utilisateur WHERE idUser = ? AND role = 'etudiant'");
    $req->execute([$id]);
    if (!$req->fetch()) erreurJson('Étudiant introuvable.', 404);

    $nom       = trim($data['nom'] ?? '');
    $prenom    = trim($data['prenom'] ?? '');
    $email     = trim($data['email'] ?? '');
    $promotion = trim($data['promotion'] ?? '');

    if ($nom === '' || $prenom === '' || $email === '') {
        erreurJson('Nom, prénom et email sont obligatoires.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) erreurJson('Email invalide.');
    if (!in_array($promotion, ['ING1', 'ING2', 'ING3'], true)) erreurJson('Promotion invalide.');

    $req = db()->prepare("SELECT idUser FROM Utilisateur WHERE email = ? AND idUser <> ?");
    $req->execute([$email, $id]);
    if ($req->fetch()) erreurJson('Cet email est déjà utilisé par un autre compte.', 409);


    if (!empty($data['motDePasse'])) {
        $hache = password_hash($data['motDePasse'], PASSWORD_DEFAULT);
        $req = db()->prepare(
            "UPDATE Utilisateur SET nom = ?, prenom = ?, email = ?, promotion = ?, mot_de_passe = ?
             WHERE idUser = ?"
        );
        $req->execute([$nom, $prenom, $email, $promotion, $hache, $id]);
    } else {
        $req = db()->prepare(
            "UPDATE Utilisateur SET nom = ?, prenom = ?, email = ?, promotion = ? WHERE idUser = ?"
        );
        $req->execute([$nom, $prenom, $email, $promotion, $id]);
    }

    repondreJson(['success' => true]);
}


function supprimerEtudiant(int $id): void
{
    $req = db()->prepare("SELECT idUser FROM Utilisateur WHERE idUser = ? AND role = 'etudiant'");
    $req->execute([$id]);
    if (!$req->fetch()) erreurJson('Étudiant introuvable.', 404);


    $req = db()->prepare("DELETE FROM Utilisateur WHERE idUser = ?");
    $req->execute([$id]);

    repondreJson(['success' => true]);
}
