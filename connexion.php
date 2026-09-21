<?php
    $user = "root";
    $mdp = "";
    $bd  = "COMMERCE";
    $serveur = "localhost";

    try {
        $pdo = new PDO("mysql:host=$serveur;dbname=$bd;charset=utf8mb4", $user, $mdp);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
    $link = mysqli_connect($serveur, $user, $mdp, $bd);
?>