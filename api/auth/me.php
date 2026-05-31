<?php


require_once __DIR__ . '/../config/init.php';

$u = utilisateurConnecte();

if ($u === null) {
    repondreJson(['authentifie' => false]);
}

repondreJson(['authentifie' => true, 'utilisateur' => $u]);
