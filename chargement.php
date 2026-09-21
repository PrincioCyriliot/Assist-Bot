<?php
// ============================================================
// chargement.php
// Affiche un spinner pendant que le robot navigue vers la
// destination. Poll etat_robot.json jusqu'a ce que le Mega
// signale phase="arrive" + mode="none". Affiche alors un
// message d'arrivee et un bouton pour reprendre le suivi.
// ============================================================

session_start();

$idClient2 = $_SESSION['guide_idClient2'] ?? null;
$idListeCourse = $_SESSION['guide_idListeCourse'] ?? null;
$nomProduit = $_SESSION['guide_nomProduit'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guidage en cours</title>
    <link rel="stylesheet" href="interface.css">
    <style>
        /* Conteneur principal identique a demarrage.php */
        .chargement-wrap {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            padding: 30px;
        }

        /* CORRECTIF : les blocs internes doivent eux aussi etre
           des flex centrés, sinon leur contenu (le spinner par
           exemple, qui est un bloc a taille fixe) se retrouve
           aligne a gauche. */
        #spinnerBlock,
        #arrivalBlock {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .chargement-wrap .message {
            margin-top: 25px;
            font-size: 20px;
            color: #17212b;
            max-width: 460px;
            line-height: 1.5;
        }

        .chargement-wrap .message small {
            display: block;
            margin-top: 6px;
            font-size: 14px;
            color: #697680;
            opacity: 0.9;
        }

        /* Bloc "arrivee" : cache tant que le robot navigue. */
        .hidden { display: none !important; }

        .arrived-badge {
            display: inline-block;
            padding: 8px 18px;
            background: rgba(97, 169, 129, .15);
            border: 1px solid rgba(97, 169, 129, .4);
            border-radius: 999px;
            color: #4d8a68;
            font-weight: 700;
            margin-bottom: 22px;
            font-size: 13px;
            letter-spacing: .5px;
        }

        .arrival-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 28px;
        }

        .arrival-actions button,
        .arrival-actions a {
            padding: 12px 22px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-resume {
            background: #17212b;
            color: #fff;
        }
        .btn-resume:hover { background: #246f7e; }

        .btn-stop {
            background: #fff;
            color: #17212b;
            border: 1px solid #e4e9ed;
        }
        .btn-stop:hover { background: #f5f7f8; }
    </style>
</head>
<body class="page-guide">

<div class="chargement-wrap">

    <!-- Bloc 1 : en cours de navigation -->
    <div id="spinnerBlock">
        <div class="spinner"></div>
        <div class="message">
            Suivez-moi, je vous guide vers le produit...
            <?php if ($nomProduit): ?>
                <small><?= htmlspecialchars($nomProduit) ?></small>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bloc 2 : arrivee -->
    <div id="arrivalBlock" class="hidden">
        <div class="arrived-badge">DESTINATION ATTEINTE</div>
        <div class="message">
            Le robot est arrive devant le produit.
            <small>Vous pouvez le reprendre en suivi.</small>
        </div>
        <div class="arrival-actions">
            <button class="btn-resume" onclick="reprendreSuivi()">
                Reprendre le mode suivi
            </button>
            <a class="btn-stop" href="acceuil.php<?= $idClient2 ? '?idClient2=' . $idClient2 : '' ?>">
                Laisser le robot a l'arret
            </a>
        </div>
    </div>

</div>

<script>
let interval = null;
let dejaArrive = false;

function verifierArrivee() {
    fetch("voir_etat.php", { cache: "no-store" })
        .then(r => r.json())
        .then(data => {
            if (data && data.phase === "arrive" && data.mode === "none" && !dejaArrive) {
                dejaArrive = true;
                clearInterval(interval);
                document.getElementById("spinnerBlock").classList.add("hidden");
                document.getElementById("arrivalBlock").classList.remove("hidden");
            }
        })
        .catch(() => { /* on reessaiera au prochain tick */ });
}

function reprendreSuivi() {
    fetch("changer_mode.php?valeur=suivi", { method: "POST" })
        .then(r => r.text())
        .then(() => {
            window.location.href = "acceuil.php<?= $idClient2 ? '?idClient2=' . $idClient2 : '' ?>";
        })
        .catch(() => {
            alert("Impossible d'activer le mode suivi. Verifiez que le robot est bien connecte.");
        });
}

interval = setInterval(verifierArrivee, 1500);
verifierArrivee();
</script>

</body>
</html>