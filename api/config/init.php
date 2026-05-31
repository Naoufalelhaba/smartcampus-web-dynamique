<?php
// =====================================================================
//  Fichier inclus au DÉBUT de chaque endpoint de l'API.
//  Il prépare : en-têtes HTTP, session, fonctions utilitaires, accès BDD + auth.
// =====================================================================

require_once __DIR__ . '/database.php';

// ---- En-têtes CORS : autoriser le frontend React à appeler l'API avec le cookie de session ----
$origine = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origine !== '') {
    header("Access-Control-Allow-Origin: $origine");      // on renvoie l'origine exacte...
    header('Access-Control-Allow-Credentials: true');     // ...pour autoriser l'envoi des cookies
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Le navigateur envoie une requête OPTIONS "préliminaire" (preflight) avant un POST/PUT/DELETE.
// On y répond immédiatement, sans traitement.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---- Session PHP avec un cookie sécurisé ----
session_set_cookie_params([
    'httponly' => true,    // cookie inaccessible en JavaScript -> protège contre le vol par XSS
    'samesite' => 'Lax',   // le cookie n'est pas envoyé lors de requêtes inter-sites suspectes
]);
session_start();

// ---- Fonctions utilitaires de réponse JSON ----
function repondreJson($donnees, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($donnees);
    exit;
}

function erreurJson(string $message, int $code = 400): void
{
    repondreJson(['success' => false, 'message' => $message], $code);
}

// Lit et décode le corps JSON d'une requête POST/PUT. Renvoie un tableau.
function corpsJson(): array
{
    $brut = file_get_contents('php://input');
    $data = json_decode($brut, true);
    return is_array($data) ? $data : [];
}

// Filet de sécurité : toute exception non gérée (ex : erreur SQL) renvoie un JSON propre,
// jamais une page d'erreur HTML. Le détail part dans le log du serveur (pour le debug).
set_exception_handler(function (Throwable $e): void {
    error_log('[SmartCampus] ' . $e->getMessage());
    repondreJson(['success' => false, 'message' => 'Erreur serveur.'], 500);
});

require_once __DIR__ . '/auth.php';
