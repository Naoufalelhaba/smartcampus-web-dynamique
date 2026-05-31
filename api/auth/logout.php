<?php


require_once __DIR__ . '/../config/init.php';

$_SESSION = [];       
session_destroy();      

repondreJson(['success' => true, 'message' => 'Déconnecté.']);
