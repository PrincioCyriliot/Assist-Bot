<?php
    include "connexion.php";

    if(isset($_GET['check'])){
        mysqli_query($link, "UPDATE Session SET Statue = 1 ORDER BY IdSession DESC LIMIT 1");
    }
?>