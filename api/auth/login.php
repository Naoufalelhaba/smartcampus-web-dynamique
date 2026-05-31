<?php
// =====================================================================
//  POST /auth/login.php
//  Corps attendu (JSON) : { "email": "...", "motDePasse": "..." }
//  Vérifie les identifiants et ouvre une session.
// =====================================================================

require_once __DIR__ . '/../config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    erreurJson('Méthode non autorisée.', 405);
}

$data       = corpsJson();
$email      = trim($data['email'] ?? '');
$motDePasse = $data['motDePasse'] ?? '';

if ($email === '' || $motDePasse === '') {
    erreurJson('Email et mot de passe obligatoires.');
}

// Recherche de l'utilisateur par email (requête préparée -> anti-injection SQL).
$req = db()->prepare(
    'SELECT idUser, nom, prenom, email, mot_de_passe, role, promotion
     FROM Utilisateur
     WHERE email = ?'
);
$req->execute([$email]);
$user = $req->fetch();

// password_verify compare le mot de passe saisi avec le haché stocké en base.
if (!$user || !password_verify($motDePasse, $user['mot_de_passe'])) {
    erreurJson('Email ou mot de passe incorrect.', 401);
}

// Sécurité : on régénère l'identifiant de session (protection contre la fixation de session).
session_regenerate_id(true);

// On ne stocke JAMAIS le mot de passe en session : seulement les infos utiles.
$_SESSION['utilisateur'] = [
    'idUser'    => (int) $user['idUser'],
    'nom'       => $user['nom'],
    'prenom'    => $user['prenom'],
    'email'     => $user['email'],
    'role'      => $user['role'],
    'promotion' => $user['promotion'],
];

repondreJson(['success' => true, 'utilisateur' => $_SESSION['utilisateur']]);
