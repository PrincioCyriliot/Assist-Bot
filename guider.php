<?php
// ============================================================
// guider.php - Declenche par le bouton "Guider"
// Deux usages :
//   - Depuis detailProduit.php : POST avec idProduit + redirect=chargement
//   - Depuis index.html (test)  : POST avec destination explicite
// ============================================================

session_start();
require "queue.php";
require "pcc.php";
require_once __DIR__ . '/connexion.php';

$idProduit = isset($_POST['idProduit']) ? (int)$_POST['idProduit'] : 0;
$destination = null;
$nomProduit = '';

// --- 1. Cas normal : produit clique par l'utilisateur ---
if ($idProduit > 0) {
    $stmt = mysqli_prepare($link, "SELECT NomProduit, Chemin FROM Produit WHERE IdProduit = ?");
    mysqli_stmt_bind_param($stmt, 'i', $idProduit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $produit = mysqli_fetch_assoc($res);

    if ($produit && !empty($produit['Chemin'])) {
        $destination = strtoupper(trim($produit['Chemin']));
        $nomProduit = $produit['NomProduit'];
    }
}
// --- 2. Cas test : destination fournie directement (index.html) ---
else if (isset($_POST['destination']) || isset($_GET['destination'])) {
    $destination = strtoupper(trim($_POST['destination'] ?? $_GET['destination']));
}

if ($destination === null) {
    http_response_code(400);
    echo "Aucune destination fournie.";
    exit;
}

// --- 3. Valider la destination ---
$sommetsValides = ['A','B','C','D','E','F','G','H','I'];
if (!in_array($destination, $sommetsValides, true)) {
    $msg = "Destination invalide : $destination.";
    if (isset($_POST['redirect'])) {
        $_SESSION['message'] = $msg . " Ce produit n'a pas de sommet valide en base.";
        $_SESSION['message_type'] = 'warning';
        header('Location: acceuil.php');
        exit;
    }
    http_response_code(400);
    echo $msg;
    exit;
}

// --- 4. Lire la position actuelle du robot ---
$positionData = [];
if (file_exists("position_actuelle.json")) {
    $positionData = json_decode(file_get_contents("position_actuelle.json"), true);
}
$x = $positionData["x"] ?? 0;
$y = $positionData["y"] ?? 0;

// --- 5. Calculer le chemin ---
$commande = calculerChemin($x, $y, $destination);

// --- 6. Mettre en file d'attente ---
ajouterMessageQueue(["type" => "mode", "valeur" => "guide"]);
ajouterMessageQueue(["type" => "chemin", "commande" => $commande]);

// --- 7. Si appele depuis la boutique, rediriger vers chargement.php ---
if (isset($_POST['redirect']) && $_POST['redirect'] === 'chargement') {
    $_SESSION['guide_idClient2'] = isset($_POST['idClient2']) ? (int)$_POST['idClient2'] : null;
    $_SESSION['guide_idListeCourse'] = isset($_POST['idListeCourse']) ? (int)$_POST['idListeCourse'] : null;
    $_SESSION['guide_destination'] = $destination;
    $_SESSION['guide_nomProduit'] = $nomProduit;
    header('Location: chargement.php');
    exit;
}

// Sinon (appel depuis index.html pour test)
echo "Chemin calcule (" . count($commande) . " etapes) vers $destination et mis en file d'attente.";
?>