<?php
// =====================================================================
//  Connexion à la base de données MySQL via PDO.
//  PDO permet les requêtes préparées -> protection contre les injections SQL.
//  La fonction db() renvoie toujours la MÊME connexion (pratique et efficace).
// =====================================================================

function db(): PDO
{
    static $pdo = null;                 // gardée en mémoire entre les appels

    if ($pdo === null) {
        $hote        = '127.0.0.1';
        $port        = 3306;
        $base        = 'smartcampus';
        $utilisateur = 'root';
        $motDePasse  = '';              // WAMP : root sans mot de passe par défaut

        $dsn = "mysql:host=$hote;port=$port;dbname=$base;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // les erreurs SQL lèvent une exception
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // résultats sous forme de tableaux associatifs
            PDO::ATTR_EMULATE_PREPARES   => false,                   // vraies requêtes préparées (plus sûr)
        ];

        $pdo = new PDO($dsn, $utilisateur, $motDePasse, $options);
    }

    return $pdo;
}
