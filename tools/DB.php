<?php

require_once(__DIR__."/../../vendor/autoload.php");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/../../");
$dotenv->load();

try{
  $db = new PDO('mysql:host='.$_ENV["DB_HOST"].';dbname='.$_ENV["DB_NAME"].';charset=utf8', $_ENV["DB_USER"], $_ENV["DB_PASSWORD"]);
}
catch (PDOException $e) {
    echo 'Echec de la connexion : ' . $e->getMessage();
    exit;
}