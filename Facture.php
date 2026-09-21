<?php
session_start();
require_once __DIR__ . '/connexion.php';

// --- VALIDER LA COMMANDE ---
if (isset($_GET['valider']) && isset($_GET['idListeCourse'])) {
    $idListeCourse = (int)$_GET['idListeCourse'];
    
    // Supprimer les produits du panier
    $delete = mysqli_query($link, "DELETE FROM ListeProduits WHERE IdListeCourses = '$idListeCourse'");
    
    if ($delete) {
        // Rediriger vers l'accueil avec un message de succès
        header('Location: finsession.php?success=1');
        exit;
    } else {
        header('Location: finsession.php?error=1');
        exit;
    }
}

// Récupérer l'ID de la liste de courses
$idListeCourse = isset($_GET['idListeCourse']) ? (int)$_GET['idListeCourse'] : (isset($_SESSION['idListeCourse']) ? (int)$_SESSION['idListeCourse'] : null);

if (!$idListeCourse) {
    header('Location: acceuil.php');
    exit;
}

// Récupérer les produits du panier depuis la base de données
$query = "SELECT lp.QuantiteAcheter, lp.PrixTotalParProduit, p.NomProduit, p.PrixUnitaire 
          FROM ListeProduits lp 
          JOIN Produit p ON lp.IdProduit = p.IdProduit 
          WHERE lp.IdListeCourses = '$idListeCourse'";
$result = mysqli_query($link, $query);

// Calculer le total et le nombre d'articles
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
?>
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>AssistBot — Facture</title>
        <link rel="stylesheet" href="Facture.css">
    </head>
    <body>
        <header class="topbar">
            <a class="brand" href="index.php">
                <span class="logo-wrap">
                    <img src="image/logo-assistbot.png" alt="AssistBot">
                </span>
                <span>
                    <strong>AssistBot</strong>
                    <small>Facturation</small>
                </span>
                <nav class="nav-actions">
                    <a href="acceuil.php<?= $idClient ? '?idClient1=' . $idClient . '&idListeCourse=' . $idListeCourse : '' ?>" class="nav-link">Accueil
                    </a>
                    <a href="panier.php<?= $idListeCourse ? '?idListeCourse=' . $idListeCourse : '' ?>" class="cart-link active-cart">Panier <span class="cart-count"><?= $nb_articles ?></span>
                    </a></nav>
        </header>
        <main class="invoice-page">
            <div class="invoice">
                <div class="invoice-head">
                    <div>
                        <span class="eyebrow">ASSISTBOT</span>
                        <h1>Facture</h1>
                        <p>Document récapitulatif de votre panier</p>
                    </div>
                    <div class="invoice-logo">
                        <img src="image/facture.png" alt="AssistBot">
<!--C'est la commande qui est le manamoatra numero de facture (date du jour de l'achat Ymd-His) la+-->
                        <span>N° FAC-<?= date('Ymd-His') ?></span>
                    </div>
                </div>
                <?php if ($panier_items): ?>
                    <div class="invoice-meta">
                        <div>
                            <small>Date</small>
<!--Meme commande mais pour recuperer le jour androany la+-->
                            <b><?= date('d/m/Y H:i',strtotime('+1 hour')) ?></b>
                        </div>
                        <div>
                            <small>Client</small>
                            <b>Visiteur</b>
                        </div>
                        <div>
                            <small>Articles</small>
                            <b><?= $nb_articles ?></b>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Qté</th>
                                <th>Prix unitaire</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($panier_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['NomProduit']) ?></td>
                                    <td><?= $item['QuantiteAcheter'] ?></td>
                                    <td><?= number_format($item['PrixUnitaire'], 2, ',', ' ') ?> Ar</td>
                                    <td><?= number_format($item['PrixTotalParProduit'], 2, ',', ' ') ?> Ar</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="invoice-total">
                        <span>Total TTC</span>
                        <strong><?= number_format($total, 2, ',', ' ') ?> Ar</strong>
                    </div>
                    <div class="invoice-actions">
                        <a href="panier.php<?= $idListeCourse ? '?idListeCourse=' . $idListeCourse : '' ?>" class="btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                            </svg> Retour au panier
                        </a>
                        <a href="finsession.php?idListeCourse=<?= $idListeCourse ?>" class="btn-primary" onclick="return confirm('Valider la commande ?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-check-fill" viewBox="0 0 16 16">
                            <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m-1.646-7.646-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L8 8.293l2.646-2.647a.5.5 0 0 1 .708.708"/>
                            </svg> Valider
                        </a>
                    </div>
                <?php else: ?>
                    <div class="invoice-empty">
                        <h2>Aucune facture</h2>
                        <p>Votre panier est actuellement vide.</p>
                        <a href="acceuil.php<?= $idClient ? '?idClient1=' . $idClient . '&idListeCourse=' . $idListeCourse : '' ?>" class="btn-primary">Retour aux produits</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        <footer><span>AssistBot</span> — Facture</footer>
    </body>
</html>