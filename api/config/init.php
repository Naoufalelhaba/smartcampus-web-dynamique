<?php


require_once __DIR__ . '/database.php';

$origine = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origine !== '') {
    header("Access-Control-Allow-Origin: $origine");   
    header('Access-Control-Allow-Credentials: true');    
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');


if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}


session_set_cookie_params([
    'httponly' => true,   
    'samesite' => 'Lax',  
]);
session_start();

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


function corpsJson(): array
{
    $brut = file_get_contents('php://input');
    $data = json_decode($brut, true);
    return is_array($data) ? $data : [];
}


set_exception_handler(function (Throwable $e): void {
    error_log('[SmartCampus] ' . $e->getMessage());
    repondreJson(['success' => false, 'message' => 'Erreur serveur.'], 500);
});

require_once __DIR__ . '/auth.php';
