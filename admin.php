<?php
session_start();

require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/config.php';

// === VÉRIFICATION DU TOKEN PAR POST ===
$token_valid = isset($_SESSION['token_valid']) && $_SESSION['token_valid'] === true;
$token_error = '';

if (isset($_POST['check_token'])) {
    $access_token = $_POST['access_token'] ?? '';
    $token_valid = false;

    if (!empty($access_token)) {
        $admins = mysqli_query($link, "SELECT TokenHash FROM Admin");

        while ($admin = mysqli_fetch_assoc($admins)) {
            if (password_verify($access_token, $admin['TokenHash'])) {
                $token_valid = true;
                $_SESSION['token_valid'] = true;
                break;
            }
        }
    }

    if ($token_valid) {
        header('Location: admin.php');
        exit;
    } else {
        $token_error = 'Code d’accès incorrect.';
    }
}


// === LOGIN ===
$login_error = '';

if ($token_valid || (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true)) {

    if (isset($_POST['login'])) {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = mysqli_prepare(
            $link,
            "SELECT IdAdmin, Username, PasswordHash
             FROM Admin
             WHERE Username = ?"
        );

        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);

        if ($admin && password_verify($password, $admin['PasswordHash'])) {

            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_id'] = $admin['IdAdmin'];
            $_SESSION['admin_name'] = $admin['Username'];
            $_SESSION['admin_avatar'] = 'admin-avatar.png';

            header('Location: admin.php');
            exit;

        } else {

            $login_error = 'Mot de passe incorrect.';
        }
    }
}


// === PROTECTION DE LA PAGE ===
if (
    !$token_valid &&
    (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true)
) {
    $is_logged = false;
} else {
    $is_logged = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
}


// === LOGOUT ===
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

//  VÉRIFICATION 

//  TRAITEMENTS (uniquement si connecté) 
$feedback = '';
$feedbackType = '';

if ($is_logged) {

    // Ajout d'une nouvelle catégorie
    if (isset($_POST['add_category']) && !empty(trim($_POST['new_category']))) {
        $new_category = trim($_POST['new_category']);
        
        $check = mysqli_query($link, "SELECT IdCategorie FROM Categorie
         WHERE NomCategorie = '$new_category'");
        if (mysqli_num_rows($check) == 0) {
            if (mysqli_query($link, "INSERT INTO Categorie (NomCategorie)
             VALUES ('$new_category')")) {
                $feedback = 'Catégorie "' . $new_category . 
                '" ajoutée avec succès.';
                $feedbackType = 'success';
            } else {
                $feedback = 'Erreur lors de l\'ajout.';
                $feedbackType = 'error';
            }
        } else {
            $feedback = 'Cette catégorie existe déjà.';
            $feedbackType = 'error';
        }
    }

    // Ajout d'un produit
    if (isset($_POST['nom'], $_POST['prix'], $_POST['categorie'],
     $_POST['quantite'])) {
        $nom = trim($_POST['nom']);
        $prix = (float) $_POST['prix'];
        $categorie = $_POST['categorie'];
        $quantite = max(0, (int) $_POST['quantite']);
        $information = isset($_POST['information']) ? 
        trim($_POST['information']) : '';
        $chemin = isset($_POST['chemin']) ? 
        trim($_POST['chemin']) : '';
        $image = '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error']
         === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $ext = strtolower(pathinfo($_FILES['image']['name'],
             PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true)) {
                $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', 
                basename($_FILES['image']['name']));
                $image = $safe;
                move_uploaded_file($_FILES['image']['tmp_name'], 
                __DIR__ . '/image/' . $safe);
            }
        }
        
        $stmt = mysqli_prepare($link,
         'INSERT INTO Produit (NomProduit, PrixUnitaire, IdCategorie, QuantiteExistant,
         Information, Image, Chemin) VALUES (?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sdissss', $nom, $prix, $categorie, 
        $quantite, $information, $image, $chemin);
        
        if (mysqli_stmt_execute($stmt)) {
            $feedback = 'Produit ajouté avec succès.';
            $feedbackType = 'success';
        } else {
            $feedback = 'Impossible d\'ajouter le produit.';
            $feedbackType = 'error';
        }
    }
    // Suppression d'un produit
if (isset($_GET['supprimer'])) {
    $id = (int) $_GET['supprimer'];
    
    // Récupérer l'image pour la supprimer aussi
    $req = mysqli_query($link, "SELECT Image FROM Produit 
    WHERE IdProduit = '$id'");
    $row = mysqli_fetch_array($req);
    if ($row && !empty($row['Image'])) {
        $image_path = __DIR__ . '/image/' . $row['Image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Supprimer les références dans ListeProduits
    //  (contrainte de clé étrangère)
    mysqli_query($link, "DELETE FROM ListeProduits 
    WHERE IdProduit = '$id'");
    
    // Supprimer le produit
    mysqli_query($link, "DELETE FROM Produit WHERE IdProduit = '$id'");
    
    $feedback = 'Produit supprimé avec succès.';
    $feedbackType = 'warning';
    header('Location: admin.php');
    exit;
}

// Modification d'un produit
    if (isset($_POST['update_product'])) {
        $id = (int) $_POST['edit_id'];
        $nom = trim($_POST['edit_nom']);
        $prix = (float) $_POST['edit_prix'];
        $categorie = (int) $_POST['edit_categorie'];
        $quantite = max(0, (int) $_POST['edit_quantite']);
        $information = trim($_POST['edit_information'] ?? '');
        $chemin = trim($_POST['edit_chemin'] ?? '');
        
        // Gestion de l'image
        $image = $_POST['edit_image_actuelle'] ?? '';
        
        if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $ext = strtolower(pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed, true)) {
                // Supprimer l'ancienne image
                if (!empty($image) && file_exists(__DIR__ . '/image/' . $image)) {
                    unlink(__DIR__ . '/image/' . $image);
                }
                $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['edit_image']['name']));
                $image = $safe;
                move_uploaded_file($_FILES['edit_image']['tmp_name'], __DIR__ . '/image/' . $safe);
            }
        }
        
        $stmt = mysqli_prepare($link, 'UPDATE Produit SET NomProduit = ?, PrixUnitaire = ?, IdCategorie = ?, QuantiteExistant = ?, Information = ?, Image = ?, Chemin = ? WHERE IdProduit = ?');
        mysqli_stmt_bind_param($stmt, 'sdisissi', $nom, $prix, $categorie, $quantite, $information, $image, $chemin, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $feedback = 'Produit modifié avec succès.';
            $feedbackType = 'success';
        } else {
            $feedback = 'Impossible de modifier le produit.';
            $feedbackType = 'error';
        }
        header('Location: admin.php');
        exit;
    }

    // Réinitialisation du catalogue
    if (isset($_POST['reset'])) {
        // Supprimer toutes les images
        $req = mysqli_query($link, "SELECT Image FROM Produit
         WHERE Image != ''");
        while ($row = mysqli_fetch_array($req)) {
            $image_path = __DIR__ . '/image/' . $row['Image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        mysqli_query($link, 'DELETE FROM Produit');
        mysqli_query($link, 'ALTER TABLE Produit AUTO_INCREMENT = 1');
        $feedback = 'Catalogue réinitialisé.';
        $feedbackType = 'warning';
        header('Location: admin.php');
        exit;
    }
   // Ajout d'une nouvelle catégorie
    if (isset($_POST['add_category']) &&
     !empty(trim($_POST['new_category']))) {
        $new_category = trim($_POST['new_category']);
    
        $check = mysqli_query($link, "SELECT IdCategorie 
        FROM Categorie WHERE NomCategorie 
        = '$new_category'");
        if (mysqli_num_rows($check) == 0) {
            if (mysqli_query($link, "INSERT INTO Categorie (NomCategorie) 
            VALUES ('$new_category')")) {
                $feedback = 'Catégorie "' . $new_category . 
                '" ajoutée avec succès.';
                $feedbackType = 'success';
            } else {
                $feedback = 'Erreur lors de l\'ajout.';
                $feedbackType = 'error';
            }
        }else {
            $feedback = 'Cette catégorie existe déjà.';
            $feedbackType = 'error';
    }
}
}

// Récupération des produits
$products = mysqli_query($link, 'SELECT * FROM Produit 
ORDER BY IdProduit DESC');

$categories = [];
$catResult = mysqli_query($link, "SELECT IdCategorie,
 NomCategorie FROM Categorie ORDER BY NomCategorie ASC");
while ($catRow = mysqli_fetch_assoc($catResult)) {
    $categories[$catRow['IdCategorie']] = $catRow['NomCategorie'];
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>AssistBot — Administration</title>
    <link rel="stylesheet" href="style1.css">
</head>
<body>
<script>
// === PLEIN ÉCRAN PERSISTANT ===

function enterFullscreen() {
    var elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen().catch(() => {});
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
    } else if (elem.msRequestFullscreen) {
        elem.msRequestFullscreen();
    }
}

// 1. Activer au premier clic
document.addEventListener('click', function(e) {
    if (e.target.closest('.nav-link')) {
        return;
    }
    enterFullscreen();
}, { once: true });

// 2. Rester en plein écran - si on sort, on y retourne
document.addEventListener('fullscreenchange', function() {
    if (!document.fullscreenElement) {
        // Attendre un peu puis réactiver
        setTimeout(enterFullscreen, 100);
    }
});

// 3. Éviter les sorties accidentelles
document.addEventListener('keydown', function(e) {
    // Empêcher Échap (qui sort du plein écran)
    if (e.key === 'Escape' || e.key === 'Esc') {
        e.preventDefault();
        e.stopPropagation();
        // Réactiver immédiatement
        setTimeout(enterFullscreen, 50);
        return false;
    }
});

// 4. Détecter la sortie et réagir immédiatement
document.addEventListener('webkitfullscreenchange', function() {
    if (!document.webkitFullscreenElement) {
        setTimeout(enterFullscreen, 100);
    }
});

document.addEventListener('msfullscreenchange', function() {
    if (!document.msFullscreenElement) {
        setTimeout(enterFullscreen, 100);
    }
});

// 5. Réactiver périodiquement pour être sûr
setInterval(function() {
    if (!document.fullscreenElement && 
        !document.webkitFullscreenElement && 
        !document.msFullscreenElement) {
        enterFullscreen();
    }
}, 2000);
</script>
<?php if (!$token_valid && !$is_logged): ?>
    <!-- PAGE DE TOKEN -->
    <div class="login-overlay">
        <div class="login-wrapper">
            <!-- Robot animé -->
            <div class="robot-section" id="robotSection">
                <div class="robot-display">
                        <!-- Robot SVG -->
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

    <p class="robot-speech" id="robotSpeech">Touchez-moi</p>
                </div>
    </div>
            <!-- FORMULAIRE D'ACCÈS PAR TOKEN -->
            <div class="login-card visible" id="loginCard">
                <div class="login-header">
                    <img src="image/logo-assistbot.png" alt="AssistBot" class="login-logo">
                    <h2>Accès sécurisé</h2>
                    <p>Entrez votre code d'accès</p>
                </div>

                <form method="post" class="login-form">
                    <div class="form-group">
                        <span class="input-icons">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-lock" viewBox="0 0 16 16">
                                <path d="M5.072 0.56a1 1 0 0 1 .856 0l4.5 2.25A1 1 0 0 1 11 3.7v3.55c0 2.84-1.73 5.38-4.36 6.43a1 1 0 0 1-.72 0C3.29 12.63 1.56 10.09 1.56 7.25V3.7a1 1 0 0 1 .572-.89zM2.56 3.99v3.26c0 2.38 1.42 4.51 3.72 5.5 2.3-.99 3.72-3.12 3.72-5.5V3.99L6.28 2.12z"/>
                                <path d="M6.28 5.5a1.5 1.5 0 0 0-1.5 1.5c0 .56.31 1.05.77 1.31v1.44h1.46V8.31c.46-.26.77-.75.77-1.31a1.5 1.5 0 0 0-1.5-1.5"/>
                            </svg>
                        </span>
                        <label for="access_token">Code d'accès</label>
                        <div class="password-input">
                            <input type="password" id="access_token" name="access_token"
                                   placeholder="Entrez votre code d'accès" required autocomplete="off">
                        </div>
                    </div>

                    <button type="submit" name="check_token" class="login-submit">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
                            </svg></span> Vérifier l'accès
                    </button>

                    <?php if (!empty($token_error)): ?>
                        <div class="login-error">
                            <?= htmlspecialchars($token_error) ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            </div>
        </div>
    </div>

 <script>
    document.addEventListener('DOMContentLoaded', function() {
        const robotSection = document.getElementById('robotSection');
        const loginCard = document.getElementById('loginCard');
        const robotSpeech = document.getElementById('robotSpeech');
        let isActive = false;
        robotSpeech.textContent = ' Système désactivé';
        robotSection.addEventListener('click', function() {
            if (!isActive) {
                isActive = true;
                robotSection.classList.add('robot-active');
                robotSpeech.textContent = ' Système activé !';
                setTimeout(function() {
                    loginCard.classList.add('visible');
                    robotSpeech.textContent = ' Entrez vos identifiants';
                }, 700);
            } else {
                isActive = false;
                robotSection.classList.remove('robot-active');
                loginCard.classList.remove('visible');
                robotSpeech.textContent = ' Système désactivé';
            }
        });
    });
    </script>

    <?php elseif (!$is_logged): ?>
    <!-- PAGE DE LOGIN -->
    <div class="login-overlay">
        <div class="login-wrapper">
            <div class="login-card visible" id="loginCard">
                <div class="login-header">
                    <img src="image/logo-assistbot.png" alt="AssistBot" class="login-logo">
                    <h2>Bienvenue</h2>
                    <p>Connectez-vous à votre espace</p>
                </div>

                <form method="post" class="login-form">
                    <div class="form-group">
                        <span class="input-icons">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
                            <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                            </svg>
                        </span>
                        <label for="username">Identifiant</label>
                        <input type="text" id="username" name="username"
                               placeholder="Votre identifiant" required>
                    </div>

                    <div class="form-group">
                        <div class="password-container">
                            <span class="input-icons">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M8 0a4 4 0 0 1 4 4v2.05a2.5 2.5 0 0 1 2 2.45v5a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 13.5v-5a2.5 2.5 0 0 1 2-2.45V4a4 4 0 0 1 4-4M4.5 7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7zM8 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3"/>
                                </svg>
                            </span>
                            <label for="password">Mot de passe</label>
                            <div class="password-input">
                                <input type="password" id="password" name="password"
                                       placeholder="Entrez votre mot de passe" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="login" class="login-submit">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
                            </svg>
                        </span> Se connecter
                    </button>

                    <?php if (!empty($login_error)): ?>
                        <div class="login-error">
                            <?= htmlspecialchars($login_error) ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- PAGE ADMINISTRATION -->
    <header class="topbar">
        <a class="brand" href="acceuil.php">
            <span class="logo-wrap">
                <img src="image/logo-assistbot.png" alt="AssistBot">
            </span>
            <span>
                <strong>AssistBot</strong>
                <small>Administration</small>
            </span>
        </a>
        <div class="admin-header">
            <nav class="nav-actions">
                <a href="acceuil.php" class="nav-link">Accueil</a>
            </nav>
            <div class="admin-user">
                <div class="avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                        </svg>
                </div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur') ?></div>
                    <div class="user-role">Administrateur</div>
                </div>
                <a href="admin.php?logout=1" class="logout-btn">Déconnexion</a>
            </div>
        </div>
    </header>

    <main class="page-main admin-page">
        <div class="section-heading">
            <div>
                <span class="eyebrow">BACK OFFICE</span>
                <h2>Gestion du catalogue</h2>
            </div>
            <span class="result-count"><?= mysqli_num_rows($products) ?> produit(s)</span>
        </div>
        
        <?php if ($feedback): ?>
            <div class="admin-alert <?= $feedbackType ?>">
                <?= htmlspecialchars($feedback) ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-grid">
            <!-- AJOUTER UN PRODUIT -->
            <section class="admin-card">
                <div class="card-title">
                    <span class="mini-icon">+</span>
                    <div>
                        <h3>Ajouter un produit</h3>
                        <p>Ajoutez un article au catalogue.</p>
                    </div>
                </div>
                <form method="post" enctype="multipart/form-data" class="product-form">
                    <label>Nom du produit
                        <input type="text" name="nom" required>
                    </label>
                    <label>Prix unitaire
                        <input type="number" name="prix" min="0" step="0.01" required>
                    </label>
                    <label>Catégorie
                    <select name="categorie" required>
                        <option value="">Choisir...</option>
                        <?php foreach ($categories as $id => $nom): ?>
                            <option value="<?= $id ?>"><?= htmlspecialchars($nom) ?></option>
                        <?php endforeach; ?>
                    </select>
                    </label>
                    <label>Quantité en stock
                        <input type="number" name="quantite" min="0" required>
                    </label>
                    <label>Information
                        <input type="text" name="information" placeholder="Description du produit">
                    </label>
                    <label>Chemin
                        <input type="text" name="chemin" placeholder="Emplacement dans le rayon">
                    </label>
                    <label>Image du produit
                        <input type="file" name="image" accept="image/*">
                    </label>
                    <button class="primary-btn" type="submit">Ajouter au catalogue</button>
                </form>
            </section>
            
            <!-- OUTILS -->
            <section class="admin-card danger-card">
                <div class="card-title">
                    <span class="mini-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
                            <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>
                            <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>
                        </svg>
                    </span>
                    <div>
                        <h3>Outils</h3>
                        <p>Actions sur le catalogue.</p>
                    </div>
                </div>
                <form method="post" class="reset-form">
                    <p>La réinitialisation supprime tous les produits de la table <b>Produit</b>. La base de données et sa structure restent inchangées.</p>
                    <button class="danger-btn" name="reset" onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser le catalogue ?')">Réinitialiser le catalogue</button>
                </form>
               <!-- AJOUTER UNE CATÉGORIE -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e4e9ed;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-journal-plus" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 5.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V10a.5.5 0 0 1-1 0V8.5H6a.5.5 0 0 1 0-1h1.5V6a.5.5 0 0 1 .5-.5"/>
                        <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2"/>
                        <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1z"/>
                        </svg> Ajouter une catégorie
                    </h4>
                    <form method="post" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <input type="text" name="new_category" placeholder="Nom de la nouvelle catégorie" required style="flex: 1; min-width: 150px; padding: 10px; border: 1px solid #e4e9ed; border-radius: 8px;">
                        <button type="submit" name="add_category" style="padding: 10px 20px; background: #2d8fa3; color: #fff; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-plus-fill" viewBox="0 0 16 16">
                            <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M8.5 7v1.5H10a.5.5 0 0 1 0 1H8.5V11a.5.5 0 0 1-1 0V9.5H6a.5.5 0 0 1 0-1h1.5V7a.5.5 0 0 1 1 0"/>
                            </svg> Ajouter
                        </button>
                    </form>
                    <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap;">
                        <?php foreach ($categories as $id => $nom): ?>
                            <span style="background: #eef3f4; padding: 4px 12px; border-radius: 20px; font-size: 12px; color: #246f7e;"><?= htmlspecialchars($nom) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>

        <!-- LISTE DES PRODUITS -->
        <section class="admin-card inventory">
                <!-- ADMIN DANS LA CARTE OUTILS -->
        <div class="admin-in-card">
            <div class="admin-info">
                <div class="admin-avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                        </svg>
                </div>
                <div>
                    <div class="admin-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrateur') ?></div>
                    <div class="admin-role">Administrateur</div>
                </div>
            </div>
        </div>          
        <!-- LISTE DES PRODUITS -->
        <section class="admin-card inventory">
            <div class="card-title">
                <span class="mini-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-basket-fill" viewBox="0 0 16 16">
                        <path d="M5.071 1.243a.5.5 0 0 1 .858.514L3.383 6h9.234L10.07 1.757a.5.5 0 1 1 .858-.514L13.783 6H15.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H15v5a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9H.5a.5.5 0 0 1-.5-.5v-2A.5.5 0 0 1 .5 6h1.717zM3.5 10.5a.5.5 0 1 0-1 0v3a.5.5 0 0 0 1 0zm2.5 0a.5.5 0 1 0-1 0v3a.5.5 0 0 0 1 0zm2.5 0a.5.5 0 1 0-1 0v3a.5.5 0 0 0 1 0zm2.5 0a.5.5 0 1 0-1 0v3a.5.5 0 0 0 1 0zm2.5 0a.5.5 0 1 0-1 0v3a.5.5 0 0 0 1 0z"/>
                    </svg>
                </span>
                <div>
                    <h3>Produits enregistrés</h3>
                    <p>Gérez votre catalogue.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = mysqli_fetch_assoc($products)): ?>
                            <tr>
                                <td><?= $p['IdProduit'] ?></td>
                                <td><?= htmlspecialchars($p['NomProduit']) ?></td>
                                <td><?= htmlspecialchars($categories[$p['Categorie']] ?? $p['Categorie']) ?></td>
                                <td><?= number_format($p['PrixUnitaire'], 2, ',', ' ') ?> Ar</td>
                                <td><span class="stock-pill"><?= $p['QuantiteExistant'] ?></span></td>
                                <td>
                                    <?php if (!empty($p['Image'])): ?>
                                        <img src="image/<?= htmlspecialchars($p['Image']) ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns"><!-- Bouton Modifier le produit -->
                                        <button class="btn-small btn-edit" onclick="openEditModal(<?= $p['IdProduit'] ?>, '<?= htmlspecialchars(addslashes($p['NomProduit'])) ?>', <?= $p['PrixUnitaire'] ?>, <?= $p['IdCategorie'] ?>, <?= $p['QuantiteExistant'] ?>, '<?= htmlspecialchars(addslashes($p['Information'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($p['Image'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($p['Chemin'] ?? '')) ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                                <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                                            </svg>Modifier
                                        </button>
                                        <!-- Bouton Supprimer -->
                                        <a href="admin.php?supprimer=<?= $p['IdProduit'] ?>" class="btn-small btn-delete" onclick="return confirm('Supprimer ce produit ?')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                            <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>
                                            </svg>Supprimer
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
 </main>

    <!-- MODAL MODIFICATION STOCK -->
    <!-- MODAL MODIFICATION PRODUIT -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box" style="width: 600px;">
            <h3>Modifier le produit</h3>
            <p id="editProductTitle">Produit</p>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="hidden" name="edit_image_actuelle" id="edit_image_actuelle">
                
                <label>Nom du produit</label>
                <input type="text" name="edit_nom" id="edit_nom" required style="width: 100%; padding: 10px; border: 1px solid #e4e9ed; border-radius: 8px; margin-bottom: 10px;">
                
                <label>Prix unitaire (Ar)</label>
                <input type="number" name="edit_prix" id="edit_prix" min="0" step="0.01" required style="width: 100%; padding: 10px; border: 1px solid #e4e9ed; border-radius: 8px; margin-bottom: 10px;">
                
                <label>Catégorie</label>
                <select name="edit_categorie" id="edit_categorie" required style="width: 100%; padding: 10px; border: 1px solid #e4e9ed; border-radius: 8px; margin-bottom: 10px;">
                    <?php foreach ($categories as $id => $nom): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($nom) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label>Quantité en stock</label>
                <input type="number" name="edit_quantite" id="edit_quantite" min="0" required style="width: 100%; padding: 10px; border: 1px solid #e4e9ed; border-radius: 8px; margin-bottom: 10px;">
                
                <label>Information</label>
                <input type="text" name="edit_information" id="edit_information" style="width: 100%; padding: 10px; border: 1px solid #e4e9ed; border-radius: 8px; margin-bottom: 10px;">
                
                <label>Chemin</label>
                <input type="text" name="edit_chemin" id="edit_chemin" style="width: 100%; padding: 10px; border: 1px solid #e4e9ed; border-radius: 8px; margin-bottom: 10px;">
                
                <label>Image (laisser vide pour garder l'actuelle)</label>
                <input type="file" name="edit_image" accept="image/*" style="width: 100%; padding: 10px; border: 1px solid #e4e9ed; border-radius: 8px; margin-bottom: 15px;">
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" name="update_product" class="btn-confirm">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-overlay" id="stockModal">
        <div class="modal-box">
            <h3>Modifier le stock</h3>
            <p id="modalProductName">Produit</p>
            <form method="post">
                <input type="hidden" name="id_produit" id="modalProductId">
                <label>Nouvelle quantité en stock</label>
                <input type="number" name="stock" id="modalStockValue" min="0" required>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeStockModal()">Annuler</button>
                    <button type="submit" name="update_stock" class="btn-confirm">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, nom, prix, categorie, quantite, information, image, chemin) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nom').value = nom;
            document.getElementById('edit_prix').value = prix;
            document.getElementById('edit_categorie').value = categorie;
            document.getElementById('edit_quantite').value = quantite;
            document.getElementById('edit_information').value = information;
            document.getElementById('edit_image_actuelle').value = image;
            document.getElementById('edit_chemin').value = chemin;
            document.getElementById('editProductTitle').textContent = 'Produit : ' + nom;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>

    <script>
        function openStockModal(id, name, stock) {
            document.getElementById('modalProductId').value = id;
            document.getElementById('modalProductName').textContent = 'Produit : ' + name;
            document.getElementById('modalStockValue').value = stock;
            document.getElementById('stockModal').classList.add('active');
        }

        function closeStockModal() {
            document.getElementById('stockModal').classList.remove('active');
        }

        document.addEventListener('click', function(e) {
            var modal = document.getElementById('stockModal');
            if (e.target === modal) {
                closeStockModal();
            }
        });
    </script>
    <footer><span>AssistBot</span> — Administration</footer>
<?php endif; ?>
</body>
</html>