<?php

function utilisateurConnecte(): ?array
{
    return $_SESSION['utilisateur'] ?? null;
}

function exigerConnexion(): array
{
    $u = utilisateurConnecte();
    if ($u === null) {
        erreurJson('Non authentifié. Veuillez vous connecter.', 401);
    }
    return $u;
}

function exigerRole(string ...$rolesAutorises): array
{
    $u = exigerConnexion();
    if (!in_array($u['role'], $rolesAutorises, true)) {
        erreurJson('Accès interdit pour votre rôle.', 403);
    }
    return $u;
}
