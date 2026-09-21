<?php

    include "connexion.php";

    if(isset($_POST['idListeProduit']) && isset($_POST['idListeCourse'])){
        $idListeCourse = $_POST['idListeCourse'];
        $idListeProduit = $_POST['idListeProduit'];

        $req = mysqli_query($link, "SELECT * FROM ListeProduits WHERE IdListeProduits = '$idListeProduit'");
        $row = mysqli_fetch_array($req);
        $qtt = (int)$row["QuantiteAcheter"];

        if(isset($_POST['moins']) && $qtt>1){
            $qtt-=1;
        }elseif(isset($_POST['plus'])){
            $qtt+=1;
        }
        mysqli_query($link, "UPDATE ListeProduits SET QuantiteAcheter = '$qtt' WHERE IdListeProduits = '$idListeProduit'");
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppression</title>
</head>
<body>
    <form id="retour" action="panier.php" method="post">
        <input type="hidden" name="idListeCourse" value="<?php echo $idListeCourse; ?>">
    </form>
    <script>
        document.getElementById("retour").submit();
    </script>
</body>
</html>