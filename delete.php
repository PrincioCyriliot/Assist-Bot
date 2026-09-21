<?php

    include "connexion.php";

    if(isset($_POST['delete']) && isset($_POST['idListeCourse'])){
        $idListeCourse = $_POST['idListeCourse'];
        $idListeProduit = $_POST['delete'];
        mysqli_query($link, "DELETE FROM ListeProduits WHERE IdListeProduits = '$idListeProduit'");
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