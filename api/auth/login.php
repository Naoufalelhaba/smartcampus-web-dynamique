<?php

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

$req = db()->prepare(
    'SELECT idUser, nom, prenom, email, mot_de_passe, role, promotion
     FROM Utilisateur
     WHERE email = ?'
);
$req->execute([$email]);
$user = $req->fetch();

if (!$user || !password_verify($motDePasse, $user['mot_de_passe'])) {
    erreurJson('Email ou mot de passe incorrect.', 401);
}

session_regenerate_id(true);

$_SESSION['utilisateur'] = [
    'idUser'    => (int) $user['idUser'],
    'nom'       => $user['nom'],
    'prenom'    => $user['prenom'],
    'email'     => $user['email'],
    'role'      => $user['role'],
    'promotion' => $user['promotion'],
];

repondreJson(['success' => true, 'utilisateur' => $_SESSION['utilisateur']]);
