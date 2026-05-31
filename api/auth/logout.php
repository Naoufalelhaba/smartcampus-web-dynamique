<?php
// =====================================================================
//  POST /auth/logout.php
//  Ferme la session de l'utilisateur.
// =====================================================================

require_once __DIR__ . '/../config/init.php';

$_SESSION = [];          // on vide les données de session
session_destroy();       // on détruit la session côté serveur

repondreJson(['success' => true, 'message' => 'Déconnecté.']);
