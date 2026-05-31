<?php


require_once __DIR__ . '/config/init.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        lireSeances();
        break;
    case 'POST':
        exigerRole('admin');
        creerSeance();
        break;
    case 'PUT':
        exigerRole('admin');
        if ($id === null) erreurJson('Identifiant de séance manquant.');
        modifierSeance($id);
        break;
    case 'DELETE':
        exigerRole('admin');
        if ($id === null) erreurJson('Identifiant de séance manquant.');
        supprimerSeance($id);
        break;
    default:
        erreurJson('Méthode non autorisée.', 405);
}
function lireSeances(): void
{
    $user         = exigerConnexion();
    $promotion    = trim($_GET['promotion'] ?? '');
    $idCours      = isset($_GET['idCours'])      ? (int) $_GET['idCours']      : null;
    $idEnseignant = isset($_GET['idEnseignant']) ? (int) $_GET['idEnseignant'] : null;

    $sql = "SELECT s.idSeance, s.idCours, s.jour, s.heureDebut, s.heureFin, s.salle,
                   c.nomCours, c.promotion, c.idEnseignant,
                   CONCAT(u.prenom, ' ', u.nom) AS enseignant
            FROM Seance s
            JOIN Cours c ON c.idCours = s.idCours
            LEFT JOIN Utilisateur u ON u.idUser = c.idEnseignant
            WHERE 1 = 1";
    $params = [];

    if ($promotion !== '')    { $sql .= " AND c.promotion = ?";    $params[] = $promotion; }
    if ($idCours !== null)    { $sql .= " AND s.idCours = ?";      $params[] = $idCours; }
    if ($idEnseignant !== null) { $sql .= " AND c.idEnseignant = ?"; $params[] = $idEnseignant; }

    $aucunFiltre = ($promotion === '' && $idCours === null && $idEnseignant === null);
    if ($aucunFiltre && $user['role'] === 'etudiant') {
   
        $sql .= " AND s.idCours IN (SELECT idCours FROM Inscription WHERE idEtudiant = ?)";
        $params[] = (int) $user['idUser'];
    } elseif ($aucunFiltre && $user['role'] === 'enseignant') {
       
        $sql .= " AND c.idEnseignant = ?";
        $params[] = (int) $user['idUser'];
    }

    $sql .= " ORDER BY FIELD(s.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), s.heureDebut";

    $req = db()->prepare($sql);
    $req->execute($params);
    repondreJson($req->fetchAll());
}
function creerSeance(): void
{
    $v = validerSeance(corpsJson());

    $conflit = trouverConflit($v['jour'], $v['heureDebut'], $v['heureFin'], $v['salle'],
                              $v['cours']['promotion'], $v['cours']['idEnseignant'], null);
    if ($conflit) {
        erreurJson(messageConflit($conflit, $v['salle'], $v['cours']['promotion']), 409);
    }

    $req = db()->prepare("INSERT INTO Seance (idCours, jour, heureDebut, heureFin, salle) VALUES (?, ?, ?, ?, ?)");
    $req->execute([$v['idCours'], $v['jour'], $v['heureDebut'], $v['heureFin'], $v['salle']]);

    repondreJson(['success' => true, 'idSeance' => (int) db()->lastInsertId()], 201);
}


function modifierSeance(int $id): void
{
    $req = db()->prepare("SELECT idSeance FROM Seance WHERE idSeance = ?");
    $req->execute([$id]);
    if (!$req->fetch()) erreurJson('Séance introuvable.', 404);

    $v = validerSeance(corpsJson());

    $conflit = trouverConflit($v['jour'], $v['heureDebut'], $v['heureFin'], $v['salle'],
                              $v['cours']['promotion'], $v['cours']['idEnseignant'], $id);
    if ($conflit) {
        erreurJson(messageConflit($conflit, $v['salle'], $v['cours']['promotion']), 409);
    }

    $req = db()->prepare("UPDATE Seance SET idCours = ?, jour = ?, heureDebut = ?, heureFin = ?, salle = ? WHERE idSeance = ?");
    $req->execute([$v['idCours'], $v['jour'], $v['heureDebut'], $v['heureFin'], $v['salle'], $id]);

    repondreJson(['success' => true]);
}

function supprimerSeance(int $id): void
{
    $req = db()->prepare("SELECT idSeance FROM Seance WHERE idSeance = ?");
    $req->execute([$id]);
    if (!$req->fetch()) erreurJson('Séance introuvable.', 404);

    $req = db()->prepare("DELETE FROM Seance WHERE idSeance = ?");
    $req->execute([$id]);
    repondreJson(['success' => true]);
}


function validerSeance(array $data): array
{
    $idCours    = (int) ($data['idCours'] ?? 0);
    $jour       = trim($data['jour'] ?? '');
    $heureDebut = trim($data['heureDebut'] ?? '');
    $heureFin   = trim($data['heureFin'] ?? '');
    $salle      = trim($data['salle'] ?? '');

    if ($idCours <= 0 || $jour === '' || $heureDebut === '' || $heureFin === '' || $salle === '') {
        erreurJson('Tous les champs (cours, jour, heures, salle) sont obligatoires.');
    }
    if (!in_array($jour, ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'], true)) {
        erreurJson('Jour invalide.');
    }
    if (!estHeureValide($heureDebut) || !estHeureValide($heureFin)) {
        erreurJson('Heures invalides (format attendu HH:MM).');
    }
    if ($heureFin <= $heureDebut) {
        erreurJson('L\'heure de fin doit être après l\'heure de début.');
    }

    $req = db()->prepare("SELECT idCours, promotion, idEnseignant FROM Cours WHERE idCours = ?");
    $req->execute([$idCours]);
    $cours = $req->fetch();
    if (!$cours) erreurJson('Cours introuvable.', 404);

    return [
        'idCours' => $idCours, 'jour' => $jour, 'heureDebut' => $heureDebut,
        'heureFin' => $heureFin, 'salle' => $salle, 'cours' => $cours,
    ];
}

function estHeureValide(string $h): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $h);
}


function trouverConflit(string $jour, string $heureDebut, string $heureFin, string $salle,
                        string $promotion, ?int $idEnseignant, ?int $idSeanceExclue): ?array
{
    $sql = "SELECT s.idSeance, s.salle, s.jour, s.heureDebut, s.heureFin,
                   c.nomCours, c.promotion, c.idEnseignant
            FROM Seance s
            JOIN Cours c ON c.idCours = s.idCours
            WHERE s.jour = ?
              AND s.heureDebut < ?
              AND ? < s.heureFin
              AND ( s.salle = ?
                    OR c.promotion = ?
                    OR (c.idEnseignant IS NOT NULL AND c.idEnseignant = ?) )";
    $params = [$jour, $heureFin, $heureDebut, $salle, $promotion, $idEnseignant];

    if ($idSeanceExclue !== null) {
        $sql .= " AND s.idSeance <> ?";
        $params[] = $idSeanceExclue;
    }
    $sql .= " LIMIT 1";

    $req = db()->prepare($sql);
    $req->execute($params);
    return $req->fetch() ?: null;
}

function messageConflit(array $conflit, string $salle, string $promotion): string
{
    $creneau = "le {$conflit['jour']} de " . substr($conflit['heureDebut'], 0, 5)
             . " à " . substr($conflit['heureFin'], 0, 5);

    if ($conflit['salle'] === $salle) {
        return "Conflit : la salle $salle est déjà occupée $creneau (cours « {$conflit['nomCours']} »).";
    }
    if ($conflit['promotion'] === $promotion) {
        return "Conflit : la promotion $promotion a déjà cours $creneau (« {$conflit['nomCours']} »).";
    }
    return "Conflit : l'enseignant a déjà un cours $creneau (« {$conflit['nomCours']} »).";
}
