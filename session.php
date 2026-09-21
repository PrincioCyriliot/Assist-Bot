<?php
include "connexion.php";

if(isset($_POST['idClient2']) && isset($_POST['idProduit'])){
    $idClient2 = $_POST['idClient2'];
    $idProduit = $_POST['idProduit'];

    mysqli_query($link, "INSERT INTO Session(IdClient, IdProduit) VALUES('$idClient2', '$idProduit')");
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Mode Guide</title>

<style>

/* STYLE GLOBAL */

body{
    margin:0;
    height:100vh;

    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;

    font-family:Arial, Helvetica, sans-serif;

    background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);
    color:white;
}

/* TITRE */

/*h1{
    font-size:36px;
    letter-spacing:4px;
    text-transform:uppercase;

    color:#00c6ff;

    text-shadow:
        0 0 10px #00c6ff,
        0 0 20px #00c6ff,
        0 0 40px #00c6ff;
}*/

/* MESSAGE */

.message{
    margin-top:20px;
    font-size:22px;
    text-align:center;
}

/* SPINNER */

.spinner{
    width:70px;
    height:70px;

    border:6px solid #2c5364;
    border-top:6px solid #00c6ff;
    border-bottom:6px solid #00c6ff;

    border-radius:50%;

    animation:spin 3s linear infinite;

    box-shadow:0 0 25px #00c6ff;
}

@keyframes spin{
    0%{transform:rotate(0deg);}
    100%{transform:rotate(360deg);}
}

/* EFFET LUMIERE */

body::before{
    content:"";
    position:absolute;

    width:300px;
    height:300px;

    background:radial-gradient(circle,#00c6ff,transparent);

    filter:blur(80px);

    z-index:-1;
}

</style>
<link rel="stylesheet" href="interface.css">
</head>

<body class="page-guide">

<!-- <h1>MODE GUIDE</h1> -->

<div class="spinner" id="spinner"></div>

<div class="message" id="message">
Suivez-moi, je vais vous guider vers le produit...
</div>

<script>

let interval = setInterval(() => {

    fetch("chargement.php")
    .then(res => res.text())
    .then(data => {

        if(data.trim() === "ok"){

            // arrêter les vérifications
            clearInterval(interval);

            // changer le message
            document.getElementById("message").innerText =
            "Destination atteinte. Merci de votre confiance";

            // arrêter le spinner
            document.getElementById("spinner").style.display = "none";

            // redirection après 2 secondes
            setTimeout(() => {
                window.location.href = "acceuil.php?idClient2=<?php echo $idClient2; ?>";
            }, 2000);
        }

    });

}, 1000);

</script>

</body>
</html>
