<?php
// ============================================================
// demarrage.php
// Page intermediaire entre start.php et acceuil.php.
// Attend que le robot soit effectivement passe en mode suivi
// (confirmation via etat_robot.json) puis redirige vers
// acceuil.php. Si le robot ne repond pas dans les 15 s, on
// redirige quand meme avec un avertissement.
// ============================================================

session_start();

$idClient1 = isset($_GET['idClient1']) ? (int)$_GET['idClient1'] : 0;
$idListeCourse = isset($_GET['idListeCourse']) ? (int)$_GET['idListeCourse'] : 0;

$urlAcceuil = "acceuil.php?idClient1=" . urlencode($idClient1) .
              "&idListeCourse=" . urlencode($idListeCourse);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demarrage du robot</title>
    <link rel="stylesheet" href="interface.css">
    <style>
        .demarrage-wrap {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            padding: 30px;
        }
        .demarrage-wrap .message {
            margin-top: 25px;
            font-size: 20px;
            color: #17212b;
            max-width: 420px;
            line-height: 1.5;
        }
        .demarrage-wrap .sub {
            margin-top: 12px;
            font-size: 14px;
            color: #697680;
        }
        .demarrage-wrap .failed {
            color: #a24e4e;
            font-weight: 600;
        }
    </style>
</head>
<body class="page-guide">

<div class="demarrage-wrap">
    <div class="spinner" id="spinner"></div>
    <div class="message" id="message">
        Le robot se met en mode suivi...
    </div>
    <div class="sub" id="sub">
        Veuillez patienter quelques secondes.
    </div>
</div>

<script>
const URL_ACCUEIL = "<?= $urlAcceuil ?>";
const DELAI_MAX = 15000;
const INTERVALLE = 800;
const debut = Date.now();

let interval = setInterval(verifierSuivi, INTERVALLE);

function verifierSuivi() {
    fetch("voir_etat.php", { cache: "no-store" })
        .then(r => r.json())
        .then(data => {
            if (data && data.mode === "suivi") {
                clearInterval(interval);
                document.getElementById("message").innerText = "Robot pret.";
                document.getElementById("sub").innerText = "Redirection...";
                setTimeout(() => window.location.href = URL_ACCUEIL, 300);
                return;
            }
            if (Date.now() - debut > DELAI_MAX) {
                clearInterval(interval);
                document.getElementById("spinner").style.display = "none";
                document.getElementById("message").innerHTML =
                    '<span class="failed">Le robot n\'a pas repondu.</span>';
                document.getElementById("sub").innerHTML =
                    'Verifiez qu\'il est allume et connecte au Wi-Fi. ' +
                    '<br><a href="' + URL_ACCUEIL + '" style="color:#246f7e; font-weight:600;">Continuer quand meme</a>';
            }
        })
        .catch(() => { /* on reessaiera au prochain tick */ });
}
</script>

</body>
</html>