<?php
    include "connexion.php";
    require_once __DIR__ . '/queue.php';

    if(isset($_POST['NewPerson1'])){
        $NewPerson1 = $_POST['NewPerson1'];
        mysqli_query($link, "INSERT INTO Client(Nom) 
        VALUES('$NewPerson1')");
        $idClient1 = mysqli_insert_id($link);
        mysqli_query($link, "INSERT INTO
         ListeCourses(IdClient) VALUES('$idClient1')");
        $idListeCourse = mysqli_insert_id($link);

        // Demarrage du robot en mode suivi
        ajouterMessageQueue(["type" => "mode", "valeur" => "suivi"]);

        // Redirection vers la page d'attente qui confirmera
        // la prise en compte par le robot avant de continuer
        header("Location: demarrage.php?idClient1=".urlencode($idClient1).
        "&idListeCourse=".$idListeCourse);
        exit();
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<link rel="display" href="display.json">
<meta name="theme-color" content="#ffffff">
    <title>Démarrage</title>
    <style>
        body {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: 'Segoe UI', Arial, sans-serif;
        background: linear-gradient(135deg, #1badba, #2756a7, #14147a);
        color: #fff;
        padding: 30px;
        overflow: hidden;
    }

        .power-btn {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 3px solid #38bdf8;
            background-color: #142239;
            cursor: pointer;
            position: relative;
            display: block;
            margin: 0 auto;
            box-shadow: 0 0 20px #38bdf8;
            transition: all 0.3s ease;
            color: #38bdf8;
        }

        button{
            font-size: 25px;
            font-family : segoe UI, Roboto, Helvetica, Arial,sans-serif;
        }

        .power-btn:hover {
            box-shadow: 0 0 30px #0ea5e9, 0 0 50px #38bdf8;
            transform: scale(1.1);
        }

        @media screen and (max-width: 480px) {
            .power-btn {
                width: 70px;
                height: 70px;
            }
            .power-btn::before {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>

        <form method="post">
            <input type="hidden" name="NewPerson1" value="client">
            <button type="submit" class="power-btn">START</button>
        </form>
</body>
</html>