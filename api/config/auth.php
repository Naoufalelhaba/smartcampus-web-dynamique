<?php
// =====================================================================
//  Authentification et contrôle des rôles.
//  Ces fonctions sont réutilisées par TOUS les endpoints protégés.
// =====================================================================

// Renvoie l'utilisateur actuellement connecté (stocké en session), ou null.
function utilisateurConnecte(): ?array
{
    return $_SESSION['utilisateur'] ?? null;
}

// Bloque la requête (401) si personne n'est connecté. Sinon renvoie l'utilisateur.
function exigerConnexion(): array
{
    $u = utilisateurConnecte();
    if ($u === null) {
        erreurJson('Non authentifié. Veuillez vous connecter.', 401);
    }
    return $u;
}

// Bloque la requête (403) si le rôle de l'utilisateur n'est pas autorisé.
// Exemple d'usage : exigerRole('admin');  ou  exigerRole('enseignant', 'admin');
function exigerRole(string ...$rolesAutorises): array
{
    $u = exigerConnexion();
    if (!in_array($u['role'], $rolesAutorises, true)) {
        erreurJson('Accès interdit pour votre rôle.', 403);
    }
    return $u;
}
