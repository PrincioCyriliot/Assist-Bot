<?php
session_start();
require_once __DIR__ . '/connexion.php';

// Vérifier si la commande est validée
if (!isset($_GET['idListeCourse'])) {
    header('Location: acceuil.php');
    exit;
}

$idListeCourse = (int)$_GET['idListeCourse'];

// Supprimer les produits du panier
mysqli_query($link, "DELETE FROM ListeProduits WHERE IdListeCourses = '$idListeCourse'");

// Récupérer le client
$reqClient = mysqli_query($link, "SELECT IdClient FROM ListeCourses WHERE IdListeCourses = '$idListeCourse'");
$rowClient = mysqli_fetch_array($reqClient);
$idClient = $rowClient['IdClient'] ?? null;

// Supprimer la liste de courses
mysqli_query($link, "DELETE FROM ListeCourses WHERE IdListeCourses = '$idListeCourse'");

// Le client n'est pas supprimé
// mysqli_query($link, "DELETE FROM Client WHERE IdClient = '$idClient'");

session_destroy();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci !</title>
    <link rel="stylesheet" href="style5.css">
</head>

<body>

<div class="particles">
    <?php for($i = 0; $i < 20; $i++): ?>
        <div class="particle"
             style="left: <?= rand(5, 95) ?>%;
                    animation-delay: <?= rand(0, 15) ?>s;
                    animation-duration: <?= rand(10, 20) ?>s;
                    width: <?= rand(3, 8) ?>px;
                    height: <?= rand(3, 8) ?>px;">
        </div>
    <?php endfor; ?>
</div>

<main class="finish-container">

    <div class="robot-finish" id="robotFinish">
        <div class="robot-display">
            <svg class="robot-svg" viewBox="0 0 240 330" id="robotSvg">

        <defs>
            <!-- Dégradé du corps -->
            <linearGradient id="robotBody" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#ffffff"/>
                <stop offset="70%" stop-color="#f4f7fa"/>
                <stop offset="100%" stop-color="#dfeaf2"/>
            </linearGradient>

            <!-- Dégradé bleu -->
            <linearGradient id="robotBlue" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#56d7f5"/>
                <stop offset="100%" stop-color="#178bc4"/>
            </linearGradient>

            <!-- Lumière de l'ampoule -->
            <radialGradient id="bulbGlow">
                <stop offset="0%" stop-color="#fff9b0" stop-opacity="1"/>
                <stop offset="50%" stop-color="#ffd85c" stop-opacity=".5"/>
                <stop offset="100%" stop-color="#ffd85c" stop-opacity="0"/>
            </radialGradient>

            <!-- Ombre du robot -->
            <filter id="robotShadow">
                <feDropShadow
                    dx="0"
                    dy="15"
                    stdDeviation="12"
                    flood-color="#38bdf8"
                    flood-opacity=".25"
                />
            </filter>

        </defs>


        <!-- OMBRE -->

        <ellipse
            cx="120"
            cy="315"
            rx="65"
            ry="10"
            fill="#000"
            opacity=".25"
        />


        <!-- ANTENNE -->

        <line
            x1="120"
            y1="22"
            x2="120"
            y2="45"
            stroke="#cdd8df"
            stroke-width="5"
            stroke-linecap="round"
        />

        <circle
            cx="120"
            cy="17"
            r="7"
            fill="#39c5ed"
            class="antenna-light"
        />


        <!-- OREILLE GAUCHE -->

        <g class="ear left-ear">

            <ellipse
                cx="58"
                cy="82"
                rx="25"
                ry="62"
                fill="url(#robotBlue)"
            />

            <ellipse
                cx="62"
                cy="76"
                rx="14"
                ry="48"
                fill="#ffffff"
            />

        </g>

        <!-- OREILLE DROITE -->

        <g class="ear right-ear">

            <ellipse
                cx="182"
                cy="82"
                rx="25"
                ry="62"
                fill="url(#robotBlue)"
            />

            <ellipse
                cx="178"
                cy="76"
                rx="14"
                ry="48"
                fill="#ffffff"
            />

        </g>


        <!-- TÊTE -->

        <g filter="url(#robotShadow)">

            <rect
                x="55"
                y="45"
                width="130"
                height="105"
                rx="40"
                fill="url(#robotBody)"
            />

            <!-- Écran noir -->

            <rect
                x="72"
                y="65"
                width="96"
                height="62"
                rx="25"
                fill="#050609"
            />


            <!-- ŒIL GAUCHE -->

            <path
                class="robot-eye left-eye"
                d="M85 94 Q85 78 101 78 Q107 78 110 94 Q107 108 101 108 Q85 108 85 94Z"
                fill="#4aa8ff"
            />


            <!-- ŒIL DROIT -->

            <path
                class="robot-eye right-eye"
                d="M130 94 Q133 78 139 78 Q155 78 155 94 Q155 108 139 108 Q133 108 130 94Z"
                fill="#4aa8ff"
            />

        </g>


        <!-- CORPS -->

        <rect
            x="62"
            y="142"
            width="116"
            height="105"
            rx="32"
            fill="url(#robotBody)"
            filter="url(#robotShadow)"
        />


        <!-- ÉPAULES BLEUES -->

        <circle
            cx="65"
            cy="165"
            r="13"
            fill="#35bce9"
        />

        <circle
            cx="175"
            cy="165"
            r="13"
            fill="#35bce9"
        />

        <!-- LUMIÈRE AUTOUR AMPOULE -->

        <circle
            cx="98"
            cy="140"
            r="40"
            fill="url(#bulbGlow)"
            class="bulb-glow"
        />

        <!-- AMPOULE -->

        <g
            class="bulb-group"
            transform="translate(120 190)"
        >

            <!-- Rayons -->

            <g class="bulb-rays">

                <line x1="0" y1="-34" x2="0" y2="-45"/>
                <line x1="25" y1="-25" x2="34" y2="-34"/>
                <line x1="-25" y1="-25" x2="-34" y2="-34"/>
                <line x1="34" y1="0" x2="45" y2="0"/>
                <line x1="-34" y1="0" x2="-45" y2="0"/>

            </g>


            <!-- Ampoule -->

            <path
                d="M-17 -5
                C-17 -20 -7 -29 0 -29
                C7 -29 17 -20 17 -5
                C17 5 11 10 8 16
                L-8 16
                C-11 10 -17 5 -17 -5Z"
                fill="#fff4a6"
                class="bulb"
            />


            <!-- Culot -->

            <rect
                x="-9"
                y="15"
                width="18"
                height="6"
                rx="2"
                fill="#d9b85a"
            />

            <rect
                x="-7"
                y="22"
                width="14"
                height="5"
                rx="2"
                fill="#b9963f"
            />

        </g>

        <!-- BRAS GAUCHE -->

        <g class="arm left-arm">

            <rect
                x="30"
                y="158"
                width="25"
                height="65"
                rx="12"
                fill="url(#robotBlue)"
            />

            <circle
                cx="42"
                cy="228"
                r="14"
                fill="#ffffff"
            />

        </g>


        <!-- BRAS DROIT -->

        <g class="arm right-arm">

            <rect
                x="185"
                y="158"
                width="25"
                height="65"
                rx="12"
                fill="url(#robotBlue)"
            />

            <circle
                cx="198"
                cy="228"
                r="14"
                fill="#ffffff"
            />

        </g>


        <!-- BAS DU CORPS -->

        <ellipse
            cx="120"
            cy="250"
            rx="42"
            ry="12"
            fill="#38bdf8"
        />


    </svg>
        </div>
    </div>

    <section class="finish-content">
        <span class="success-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-check" viewBox="0 0 16 16">
            <path d="M11.354 6.354a.5.5 0 0 0-.708-.708L8 8.293 6.854 7.146a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0z"/>
            <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zm3.915 10L3.102 4h10.796l-1.313 7zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
            </svg> Commande confirmée
        </span>

        <h1>Merci pour votre achat !</h1>

        <div class="decorative-line"></div>

        <p>Votre commande a été validée avec succès.</p>

        <p class="secondary-text">
            Nous espérons vous revoir très bientôt.
        </p>

        <div class="sub-message">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-stars" viewBox="0 0 16 16">
            <path d="M7.657 6.247c.11-.33.576-.33.686 0l.645 1.937a2.89 2.89 0 0 0 1.829 1.828l1.936.645c.33.11.33.576 0 .686l-1.937.645a2.89 2.89 0 0 0-1.828 1.829l-.645 1.936a.361.361 0 0 1-.686 0l-.645-1.937a2.89 2.89 0 0 0-1.828-1.828l-1.937-.645a.361.361 0 0 1 0-.686l1.937-.645a2.89 2.89 0 0 0 1.828-1.828zM3.794 1.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387A1.73 1.73 0 0 0 4.593 5.69l-.387 1.162a.217.217 0 0 1-.412 0L3.407 5.69A1.73 1.73 0 0 0 2.31 4.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387A1.73 1.73 0 0 0 3.407 2.31zM10.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732L9.1 2.137a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z"/>
            </svg> Bonne journée !
        </div>

        <a href="start.php" class="finish-btn">
            <span>A bientot</span>
            <span class="arrow">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-heart" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M10.5 3.5a2.5 2.5 0 0 0-5 0V4h5zm1 0V4H15v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V4h3.5v-.5a3.5 3.5 0 1 1 7 0M14 14V5H2v9a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1M8 7.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                </svg></span>
        </a>
    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const robot = document.getElementById('robotFinish');

    if (!robot) return;

    // Petite réaction de joie à l'arrivée sur la page.
    setTimeout(function () {
        robot.classList.add('celebrate');

        setTimeout(function () {
            robot.classList.remove('celebrate');
        }, 1200);
    }, 350);
});
</script>

</body>
</html>
