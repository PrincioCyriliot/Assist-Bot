<?php
    include "connexion.php";

    if(isset($_GET['val'])){
        $req = mysqli_query($link, "SELECT COUNT(*) AS ligne FROM Session");
        $row = mysqli_fetch_array($req);
        
        if($row['ligne']>0){
            $req1 = mysqli_query($link, "SELECT * FROM Session ORDER BY IdSession DESC LIMIT 1");
            $row1 = mysqli_fetch_array($req1);

            if($row1["Statue"] == '0'){
                $idProduit = $row1["IdProduit"];
                $req2 = mysqli_query($link, "SELECT * FROM Produit WHERE IdProduit = '$idProduit'");
                $row2 = mysqli_fetch_array($req2);
                $chemin = $row2["Chemin"];
                echo $chemin;
            }

        }
        
    }
    
?>