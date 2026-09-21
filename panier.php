<?php
session_start();
require_once __DIR__ . '/connexion.php';

// Récupérer l'ID de la liste de courses
$idListeCourse = isset($_GET['idListeCourse']) ? (int)$_GET['idListeCourse'] : null;
if (!$idListeCourse && isset($_SESSION['idListeCourse'])) {
    $idListeCourse = (int)$_SESSION['idListeCourse'];
}

// Si toujours pas d'idListeCourse, on essaie de le créer
if (!$idListeCourse) {
    // Créer une nouvelle liste de courses
    $reqClient = mysqli_query($link, "SELECT IdClient FROM Client ORDER BY IdClient DESC LIMIT 1");
    if (mysqli_num_rows($reqClient) > 0) {
        $rowClient = mysqli_fetch_array($reqClient);
        $idClient = $rowClient['IdClient'];
    } else {
        // Créer un client si besoin
        mysqli_query($link, "INSERT INTO Client(Nom) VALUES('Visiteur')");
        $idClient = mysqli_insert_id($link);
    }
    
    mysqli_query($link, "INSERT INTO ListeCourses(IdClient) VALUES('$idClient')");
    $idListeCourse = mysqli_insert_id($link);
    $_SESSION['idListeCourse'] = $idListeCourse;
    $_SESSION['idClient'] = $idClient;
    
    header('Location: panier.php?idListeCourse=' . $idListeCourse);
    exit;
}

// Action + (augmenter la quantité)
if (isset($_GET['action']) && $_GET['action'] === 'plus' && isset($_GET['id'])) {
    $idListeProduit = (int)$_GET['id'];
    $req = mysqli_query($link, "SELECT QuantiteAcheter, IdProduit FROM ListeProduits WHERE IdListeProduits = '$idListeProduit'");
    $row = mysqli_fetch_array($req);
    if ($row) {
        $idProduit = $row['IdProduit'];
        // Vérifier le stock
        $stockReq = mysqli_query($link, "SELECT QuantiteExistant FROM Produit WHERE IdProduit = '$idProduit'");
        $stockRow = mysqli_fetch_array($stockReq);
        $stock = (int)$stockRow['QuantiteExistant'];
        
        if ($stock > 0) {
            $newQtt = $row['QuantiteAcheter'] + 1;
            $prixReq = mysqli_query($link, "SELECT PrixUnitaire FROM Produit WHERE IdProduit = '$idProduit'");
            $prixRow = mysqli_fetch_array($prixReq);
            $prixTotal = $prixRow['PrixUnitaire'] * $newQtt;
            mysqli_query($link, "UPDATE ListeProduits SET QuantiteAcheter = '$newQtt', PrixTotalParProduit = '$prixTotal' WHERE IdListeProduits = '$idListeProduit'");
            // Diminuer le stock
            mysqli_query($link, "UPDATE Produit SET QuantiteExistant = QuantiteExistant - 1 WHERE IdProduit = '$idProduit'");
        }
    }
    header('Location: panier.php?idListeCourse=' . $idListeCourse);
    exit;
}

// Action - (diminuer la quantité)
if (isset($_GET['action']) && $_GET['action'] === 'moins' && isset($_GET['id'])) {
    $idListeProduit = (int)$_GET['id'];
    $req = mysqli_query($link, "SELECT QuantiteAcheter, IdProduit FROM ListeProduits WHERE IdListeProduits = '$idListeProduit'");
    $row = mysqli_fetch_array($req);
    if ($row) {
        $idProduit = $row['IdProduit'];
        $quantite = (int)$row['QuantiteAcheter'];
        
        if ($quantite > 1) {
            $newQtt = $quantite - 1;
            $prixReq = mysqli_query($link, "SELECT PrixUnitaire FROM Produit WHERE IdProduit = '$idProduit'");
            $prixRow = mysqli_fetch_array($prixReq);
            $prixTotal = $prixRow['PrixUnitaire'] * $newQtt;
            mysqli_query($link, "UPDATE ListeProduits SET QuantiteAcheter = '$newQtt', PrixTotalParProduit = '$prixTotal' WHERE IdListeProduits = '$idListeProduit'");
            // Remettre en stock
            mysqli_query($link, "UPDATE Produit SET QuantiteExistant = QuantiteExistant + 1 WHERE IdProduit = '$idProduit'");
        } else {
            // Si quantité = 1, supprimer et remettre en stock
            mysqli_query($link, "UPDATE Produit SET QuantiteExistant = QuantiteExistant + 1 WHERE IdProduit = '$idProduit'");
            mysqli_query($link, "DELETE FROM ListeProduits WHERE IdListeProduits = '$idListeProduit'");
        }
    }
    header('Location: panier.php?idListeCourse=' . $idListeCourse);
    exit;
}
// Supprimer un produit du panier
if (isset($_GET['supprimer'])) {
    $idListeProduit = (int)$_GET['supprimer'];
    mysqli_query($link, "DELETE FROM ListeProduits WHERE IdListeProduits = '$idListeProduit'");
    header('Location: panier.php?idListeCourse=' . $idListeCourse);
    exit;
}

// Vider le panier
if (isset($_GET['vider'])) {
    $req = mysqli_query($link, "SELECT IdProduit, QuantiteAcheter FROM ListeProduits WHERE IdListeCourses = '$idListeCourse'");
    while ($row = mysqli_fetch_array($req)) {
        $idProduit = $row['IdProduit'];
        $quantite = (int)$row['QuantiteAcheter'];
        mysqli_query($link, "UPDATE Produit SET QuantiteExistant = QuantiteExistant + '$quantite' WHERE IdProduit = '$idProduit'");
    }
    mysqli_query($link, "DELETE FROM ListeProduits WHERE IdListeCourses = '$idListeCourse'");
    header('Location: panier.php?idListeCourse=' . $idListeCourse);
    exit;
}

// Mettre à jour la quantité
if (isset($_POST['update'])) {
    $idListeProduit = (int)$_POST['idListeProduit'];
    $quantite = (int)$_POST['quantite'];
    if ($quantite > 0) {
        // Récupérer le prix du produit
        $req = mysqli_query($link, "SELECT lp.IdProduit, p.PrixUnitaire FROM ListeProduits lp JOIN Produit p ON lp.IdProduit = p.IdProduit WHERE lp.IdListeProduits = '$idListeProduit'");
        $row = mysqli_fetch_array($req);
        $prixTotal = $row['PrixUnitaire'] * $quantite;
        mysqli_query($link, "UPDATE ListeProduits SET QuantiteAcheter = '$quantite', PrixTotalParProduit = '$prixTotal' WHERE IdListeProduits = '$idListeProduit'");
    }
    header('Location: panier.php?idListeCourse=' . $idListeCourse);
    exit;
}

// Récupérer les produits du panier depuis la base de données
$query = "SELECT lp.IdListeProduits, lp.QuantiteAcheter, lp.PrixTotalParProduit, p.IdProduit, p.NomProduit, p.PrixUnitaire, p.Image 
          FROM ListeProduits lp 
          JOIN Produit p ON lp.IdProduit = p.IdProduit 
          WHERE lp.IdListeCourses = '$idListeCourse'";
$result = mysqli_query($link, $query);

// Stocker les articles dans un tableau pour l'affichage
$panier_items = [];
$total = 0;
$nb_articles = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $panier_items[] = $row;
    $total += $row['PrixTotalParProduit'];
    $nb_articles += $row['QuantiteAcheter'];
}

// Récupérer l'ID client
$reqClient = mysqli_query($link, "SELECT IdClient FROM ListeCourses WHERE IdListeCourses = '$idListeCourse'");
$rowClient = mysqli_fetch_array($reqClient);
$idClient = $rowClient['IdClient'] ?? null;

function cartImage(?string $image): string { 
    $filename = basename((string) $image);
    if ($filename !== '' && is_file(__DIR__ . '/image/' . $filename)) {
        return 'image/' . rawurlencode($filename);
    }
    return 'image/default.png'; 
}
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>AssistBot — Panier</title>
        <link rel="stylesheet" href="style1.css">
    </head>
    <body>
        <header class="topbar">
            <a class="brand" href="acceuil.php<?= $idClient ? '?idClient1=' . $idClient . '&idListeCourse=' . $idListeCourse : '' ?>">
                <span class="logo-wrap">
                    <img src="image/logo-assistbot.png" alt="AssistBot">
                </span>
                <span>
                    <strong>AssistBot</strong>
                </span>
            </a>
            <nav class="nav-actions">
                <a href="acceuil.php<?= $idClient ? '?idClient1=' . $idClient . '&idListeCourse=' . $idListeCourse : '' ?>" class="cart-link active-cart">Accueil</a>
            </nav>
        </header>
        <main class="page-main">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">ESPACE CLIENT</span>
                    <h2>Votre panier</h2>
                </div>
                <a class="back-link" href="index.php">
                    <svg xmlns="http://www.w;3.org/200 gap0/svg" width=":16" height="16"  fill="currentColor" class="bi bi-5arrow-left" viewBox="0 px0 16 16;">
                    <path fill- marginrule="evenodd" d="M:15 8a .5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                    </svg> Continuer les achats</a>
            </div>
                <?php if($nb_articles > 0): ?>
                    <div class="cart-layout">
                        <section class="cart-items">
                            <?php foreach($panier_items as $item): ?>
                                <article class="cart-item">
                                    <img src="<?= htmlspecialchars(cartImage($item['Image'])) ?>" alt="<?= htmlspecialchars($item['NomProduit']) ?>">
                                    <div class="cart-info">
                                        <span>Produit</span>
                                        <h3><?= htmlspecialchars($item['NomProduit']) ?></h3>
                                        <p><?= number_format($item['PrixUnitaire'],2,',',' ') ?> Ar l'unité</p>
                                    </div>
                                    <div class="return">
                                    <form action="detailProduit.php" method="post" style="margin: 0; display: inline;">
                                        <input type="hidden" name="idListeCourse" value="<?= $idListeCourse ?>">
                                        <input type="hidden" name="details" value="<?= $item['IdProduit'] ?>">
                                        <button type="submit" class="voir">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                    </div>
                                    <div class="quantity">
                                        <a href="panier.php?idListeCourse=<?= $idListeCourse ?>&action=moins&id=<?= $item['IdListeProduits'] ?>" title="Diminuer la quantité">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-dash" viewBox="0 0 16 16">
                                            <path d="M6.5 7a.5.5 0 0 0 0 1h4a.5.5 0 0 0 0-1z"/>
                                            <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zm3.915 10L3.102 4h10.796l-1.313 7zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                                            </svg>
                                        </a>
                                        <b><?= $item['QuantiteAcheter'] ?></b>
                                        <a href="panier.php?idListeCourse=<?= $idListeCourse ?>&action=plus&id=<?= $item['IdListeProduits'] ?>" title="Augmenter la quantité">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-plus" viewBox="0 0 16 16">
                                            <path d="M9 5.5a.5.5 0 0 0-1 0V7H6.5a.5.5 0 0 0 0 1H8v1.5a.5.5 0 0 0 1 0V8h1.5a.5.5 0 0 0 0-1H9z"/>
                                            <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zm3.915 10L3.102 4h10.796l-1.313 7zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                                            </svg>
                                        </a>
                                        <strong class="line-total"><?= number_format($item['PrixTotalParProduit'],2,',',' ') ?> Ar</strong>
                                        <a class="remove" href="panier.php?idListeCourse=<?= $idListeCourse ?>&supprimer=<?= $item['IdListeProduits'] ?>" title="Supprimer" onclick="return confirm('Supprimer ce produit ?')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3" viewBox="0 0 16 16">
                                            <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>
                                            </svg>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                            <a class="clear-link" href="panier.php?idListeCourse=<?= $idListeCourse ?>&vider=1" onclick="return confirm('Vider le panier ?')">Vider le panier</a>
                        </section>
                        <aside class="summary">
                            <span class="eyebrow">RÉCAPITULATIF</span>
                            <h3>Résumé de commande</h3>
                            <div>
                                <span>Articles</span>
                                <b><?= $nb_articles ?></b>
                            </div>
                            <div>
                                <span>Sous-total</span>
                                <b><?= number_format($total,2,',',' ') ?> Ar</b>
                            </div>
                            <hr>
                            <div class="grand-total">
                                <span>Total</span>
                                <b><?= number_format($total,2,',',' ') ?> Ar</b>
                            </div>
                            <a class="checkout" href="Facture.php?idListeCourse=<?= $idListeCourse ?>">Voir la facture 
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-receipt-cutoff" viewBox="0 0 16 16">
                                <path d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5M11.5 4a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1z"/>
                                <path d="M2.354.646a.5.5 0 0 0-.801.13l-.5 1A.5.5 0 0 0 1 2v13H.5a.5.5 0 0 0 0 1h15a.5.5 0 0 0 0-1H15V2a.5.5 0 0 0-.053-.224l-.5-1a.5.5 0 0 0-.8-.13L13 1.293l-.646-.647a.5.5 0 0 0-.708 0L11 1.293l-.646-.647a.5.5 0 0 0-.708 0L9 1.293 8.354.646a.5.5 0 0 0-.708 0L7 1.293 6.354.646a.5.5 0 0 0-.708 0L5 1.293 4.354.646a.5.5 0 0 0-.708 0L3 1.293zm-.217 1.198.51.51a.5.5 0 0 0 .707 0L4 1.707l.646.647a.5.5 0 0 0 .708 0L6 1.707l.646.647a.5.5 0 0 0 .708 0L8 1.707l.646.647a.5.5 0 0 0 .708 0L10 1.707l.646.647a.5.5 0 0 0 .708 0L12 1.707l.646.647a.5.5 0 0 0 .708 0l.509-.51.137.274V15H2V2.118z"/>
                                </svg>
                            </a>
                        </aside>
                    </div>
                <?php else: ?>
                    <div class="empty-cart">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart4" viewBox="0 0 16 16">
                        <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5M3.14 5l.5 2H5V5zM6 5v2h2V5zm3 0v2h2V5zm3 0v2h1.36l.5-2zm1.11 3H12v2h.61zM11 8H9v2h2zM8 8H6v2h2zM5 8H3.89l.5 2H5zm0 5a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0m9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0"/>
                        </svg>
                        <h3>Votre panier est vide</h3>
                        <a class="checkout small" href="acceuil.php<?= $idClient ? '?idClient1=' . $idClient . '&idListeCourse=' . $idListeCourse : '' ?>">Découvrir les produits</a>
                    </div>
                <?php endif; ?>
        </main>
        <footer>
            <span>AssistBot</span> — Panier
        </footer>
    </body>
</html>