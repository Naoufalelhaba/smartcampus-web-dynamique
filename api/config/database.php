<?php


function db(): PDO
{
    static $pdo = null;              

    if ($pdo === null) {
        $hote        = '127.0.0.1';
        $port        = 3306;
        $base        = 'smartcampus';
        $utilisateur = 'root';
        $motDePasse  = '';             

        $dsn = "mysql:host=$hote;port=$port;dbname=$base;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        
            PDO::ATTR_EMULATE_PREPARES   => false,               
        ];

        $pdo = new PDO($dsn, $utilisateur, $motDePasse, $options);
    }

    return $pdo;
}
