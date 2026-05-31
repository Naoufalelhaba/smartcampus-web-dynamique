<?php
// =====================================================================
//  GET /auth/me.php
//  Renvoie l'utilisateur connecté. Utilisé par le frontend au chargement
//  pour savoir qui est connecté et adapter l'affichage selon le rôle.
// =====================================================================

require_once __DIR__ . '/../config/init.php';

$u = utilisateurConnecte();

if ($u === null) {
    repondreJson(['authentifie' => false]);
}

repondreJson(['authentifie' => true, 'utilisateur' => $u]);
