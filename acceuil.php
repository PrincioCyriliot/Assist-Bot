<?php
session_start();
require_once __DIR__ . '/connexion.php';

// 1. Récupération et conversion stricte des paramètres URL
if (isset($_GET['idListeCourse'])) {
    $_SESSION['idListeCourse'] = (int) $_GET['idListeCourse'];
}
if (isset($_GET['idClient1'])) {
    $_SESSION['idClient'] = (int) $_GET['idClient1'];
} elseif (isset($_GET['idClient2'])) {
    $_SESSION['idClient'] = (int) $_GET['idClient2'];
}

$idClient = isset($_SESSION['idClient']) ? (int) $_SESSION['idClient'] : null;
$idListeCourse = isset($_SESSION['idListeCourse']) ? (int) $_SESSION['idListeCourse'] : null;

// 2. Vérification / Création du CLIENT en BDD (Sécurisé via Requête Préparée)
$clientValide = false;
if ($idClient) {
    $stmtC = mysqli_prepare($link, "SELECT IdClient FROM Client WHERE IdClient = ?");
    mysqli_stmt_bind_param($stmtC, "i", $idClient);
    mysqli_stmt_execute($stmtC);
    $resC = mysqli_stmt_get_result($stmtC);
    if ($resC && mysqli_num_rows($resC) > 0) {
        $clientValide = true;
    }
}

// Si le client n'existe pas en BDD, on en crée un nouveau immédiatement
if (!$clientValide) {
    mysqli_query($link, "INSERT INTO Client (Nom) VALUES ('Client Invité')");
    $idClient = (int) mysqli_insert_id($link);
    $_SESSION['idClient'] = $idClient;
}

// 3. Vérification / Création de la LISTECOURSES en BDD
$listeValide = false;
if ($idListeCourse) {
    $stmtL = mysqli_prepare($link, "SELECT IdListeCourses FROM ListeCourses WHERE IdListeCourses = ?");
    mysqli_stmt_bind_param($stmtL, "i", $idListeCourse);
    mysqli_stmt_execute($stmtL);
    $resL = mysqli_stmt_get_result($stmtL);
    if ($resL && mysqli_num_rows($resL) > 0) {
        $listeValide = true;
    }
}

// Si la liste n'existe pas en BDD, on la crée rattachée au BON $idClient (garanti existant)
if (!$listeValide) {
    $stmtInsL = mysqli_prepare($link, "INSERT INTO ListeCourses (IdClient) VALUES (?)");
    mysqli_stmt_bind_param($stmtInsL, "i", $idClient);
    mysqli_stmt_execute($stmtInsL);
    $idListeCourse = (int) mysqli_insert_id($link);
    $_SESSION['idListeCourse'] = $idListeCourse;
}

// AJOUT AU PANIER (GET)
if (isset($_GET['ajouter']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = mysqli_prepare($link, "SELECT * FROM Produit 
    WHERE IdProduit = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $produit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($produit && $idListeCourse) {
        $stock = (int)$produit['QuantiteExistant'];
        if ($stock <= 0) {
            $_SESSION['message'] = 'Stock insuffisant
             pour ce produit.';
            $_SESSION['message_type'] = 'warning';
            $redirect = 'acceuil.php';
            if (isset($_SESSION['idClient'])) {
                $redirect .= '?idClient1=' . $_SESSION['idClient'];
                if (isset($_SESSION['idListeCourse'])) {
                    $redirect .= '&idListeCourse=' .
                     $_SESSION['idListeCourse'];
                }
            }
            header('Location: ' . $redirect);
            exit;
        }
        
        $prix = (float) $produit['PrixUnitaire'];
        $prixTotal = $prix;
        
        $check = mysqli_query($link, "SELECT * FROM ListeProduits
         WHERE IdListeCourses = '$idListeCourse' AND IdProduit = '$id'");
        if (mysqli_num_rows($check) > 0) {
            $row = mysqli_fetch_array($check);
            $newQtt = $row['QuantiteAcheter'] + 1;
            $newPrixTotal = $prix * $newQtt;
            mysqli_query($link, "UPDATE ListeProduits SET QuantiteAcheter = '$newQtt', 
            PrixTotalParProduit = '$newPrixTotal' WHERE IdListeProduits = '" .
             $row['IdListeProduits'] . "'");
            $nouveauStock = $stock - 1;
            mysqli_query($link, "UPDATE Produit SET QuantiteExistant = '$nouveauStock'
             WHERE IdProduit = '$id'");
        } else {
            mysqli_query($link, "INSERT INTO listeproduits(IdListeCourses, IdProduit, 
            QuantiteAcheter, PrixTotalParProduit) VALUES('$idListeCourse',
             '$id', 1, '$prixTotal')");
            $nouveauStock = $stock - 1;
            mysqli_query($link, "UPDATE Produit SET QuantiteExistant = '$nouveauStock'
             WHERE IdProduit = '$id'");
        }
        $_SESSION['message'] = 'Produit ajouté au panier.';
        $_SESSION['message_type'] = 'success';
    }
    
    $redirect = 'acceuil.php';
    if (isset($_SESSION['idClient'])) {
        $redirect .= '?idClient1=' . $_SESSION['idClient'];
        if (isset($_SESSION['idListeCourse'])) {
            $redirect .= '&idListeCourse=' . $_SESSION['idListeCourse'];
        }
    }
    header('Location: ' . $redirect);
    exit;
}

// AJOUT AU PANIER (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idProduit'])
     && isset($_POST['idListeCourse'])) {
    $id = (int) $_POST['idProduit'];
    $idListeCourse = (int) $_POST['idListeCourse'];
    $quantite = isset($_POST['quantite']) ? (int) $_POST['quantite'] : 1;
    
    $prodReq = mysqli_query($link, "SELECT PrixUnitaire, QuantiteExistant
     FROM Produit WHERE IdProduit = '$id'");
    $prodRow = mysqli_fetch_array($prodReq);
    $prix = (float) $prodRow['PrixUnitaire'];
    $stock = (int) $prodRow['QuantiteExistant'];
    
    if ($quantite > $stock) {
        $_SESSION['message'] = 'Stock insuffisant.';
        $_SESSION['message_type'] = 'warning';
        header('Location: acceuil.php?idClient1=' . $_SESSION['idClient'] . 
        '&idListeCourse=' . $idListeCourse);
        exit;
    }
    
    $prixTotal = $prix * $quantite;
    
    $check = mysqli_query($link, "SELECT * FROM ListeProduits
     WHERE IdListeCourses = '$idListeCourse' AND IdProduit = '$id'");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_array($check);
        $newQtt = $row['QuantiteAcheter'] + $quantite;
        $newPrixTotal = $prix * $newQtt;
        mysqli_query($link, "UPDATE ListeProduits SET QuantiteAcheter =
         '$newQtt', PrixTotalParProduit = '$newPrixTotal' WHERE IdListeProduits = '" 
         . $row['IdListeProduits'] . "'");
        $nouveauStock = $stock - $quantite;
        mysqli_query($link, "UPDATE Produit SET QuantiteExistant = '$nouveauStock'
         WHERE IdProduit = '$id'");
    } else {
        mysqli_query($link, "INSERT INTO ListeProduits(IdListeCourses, IdProduit,
         QuantiteAcheter, PrixTotalParProduit) VALUES('$idListeCourse', '$id', 
         '$quantite', '$prixTotal')");
        $nouveauStock = $stock - $quantite;
        mysqli_query($link, "UPDATE Produit SET QuantiteExistant = '$nouveauStock' 
        WHERE IdProduit = '$id'");
    }
    
    
    $_SESSION['message'] = 'Produit ajouté au panier.';
    $_SESSION['message_type'] = 'success';
    
    
    $redirect = 'acceuil.php';
    if (isset($_SESSION['idClient'])) {
        $redirect .= '?idClient1=' . $_SESSION['idClient'];
        if (isset($_SESSION['idListeCourse'])) {
            $redirect .= '&idListeCourse=' . $_SESSION['idListeCourse'];
        }
    }
    header('Location: ' . $redirect);
    exit;
}
// Recherche et filtres
$where = '';
$params = [];
$types = '';

$recherche = trim($_GET['recherche'] ?? '');
$cat = $_GET['cat'] ?? 'tout';
$prix_min = isset($_GET['prix_min']) ? (float)$_GET['prix_min'] : null;
$prix_max = isset($_GET['prix_max']) ? (float)$_GET['prix_max'] : null;

if ($recherche !== '') {
    $where = "NomProduit LIKE ?";
    $params[] = "%$recherche%";
    $types .= 's';
} elseif ($cat !== '' && $cat !== 'tout') {
    $where = "IdCategorie = ?";
    $params[] = $cat;
    $types .= 's';
}

// Construction de la requête
$sql = "SELECT * FROM Produit";
$conditions = [];

// Ajouter la condition de recherche/catégorie (sans le mot WHERE)
if ($where !== '') {
    $conditions[] = $where;
}

// Ajouter le filtre prix min
if ($prix_min !== null && $prix_min > 0) {
    $conditions[] = "PrixUnitaire >= $prix_min";
}

// Ajouter le filtre prix max
if ($prix_max !== null && $prix_max > 0) {
    $conditions[] = "PrixUnitaire <= $prix_max";
}

// Si il y a des conditions, on ajoute WHERE une seule fois
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY NomProduit ASC";

$stmt = mysqli_prepare($link, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);


// Compter les articles
$nb_articles = 0;
if ($idListeCourse) {
    $countResult = mysqli_query($link, "SELECT SUM(QuantiteAcheter) as total 
    FROM ListeProduits WHERE IdListeCourses = '$idListeCourse'");
    if ($countResult) {
        $countRow = mysqli_fetch_array($countResult);
        $nb_articles = (int) $countRow['total'];
    }
}

$categories = [];
$catResult = mysqli_query($link, "SELECT IdCategorie, NomCategorie 
FROM Categorie ORDER BY NomCategorie ASC");
while ($catRow = mysqli_fetch_assoc($catResult)) {
    $categories[$catRow['IdCategorie']] = $catRow['NomCategorie'];
}

function imageProduit(?string $image): string {
    $filename = basename((string) $image);
    if ($filename !== '' && is_file(__DIR__ . '/image/' . $filename)) {
        return 'image/' . rawurlencode($filename);
    }
    return 'image/default.png';
}

// Construction des paramètres d'URL
$urlParams = '';
if (isset($_SESSION['idClient'])) {
    $urlParams .= 'idClient1=' . $_SESSION['idClient'];
}
if (isset($_SESSION['idListeCourse'])) {
    $urlParams .= ($urlParams ? '&' : '') . 'idListeCourse=' .
     $_SESSION['idListeCourse'];
}
$urlParams = $urlParams ? '?' . $urlParams : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AssistBot</title>
<link rel="stylesheet" href="style3.css">
</head>
<body>
<header class="topbar">
    <a class="brand" href="acceuil.php<?= $urlParams ?>">
        <span class="logo-wrap"><img src="image/logo-assistbot.png" alt="AssistBot"></span>
        <span><strong>AssistBot</strong></span>
    </a>
    <nav class="nav-actions">
        <a href="#" class="nav-link" onclick="activerSuivi(event)">Mode suivi</a>
        <a href="panier.php<?= $idListeCourse ? '?idListeCourse=' . $idListeCourse : '' ?>" class="cart-link">Panier <span class="cart-count"><?= $nb_articles ?></span></a>
            <?php if (isset($_SESSION['message'])): ?>
            <div class="toast <?= htmlspecialchars($_SESSION['message_type'] ?? 'success') ?>" id="toast">
                <span><?= htmlspecialchars($_SESSION['message']) ?></span>
                <button onclick="document.getElementById('toast').remove()" class="message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-plus" viewBox="0 0 16 16">
                    <path d="M9 5.5a.5.5 0 0 0-1 0V7H6.5a.5.5 0 0 0 0 1H8v1.5a.5.5 0 0 0 1 0V8h1.5a.5.5 0 0 0 0-1H9z"/>
                    <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zm3.915 10L3.102 4h10.796l-1.313 7zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                    </svg>
                </button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="hero-copy">
            <h1>Bienvenue,<br><span>Bonne course.</span></h1>
            <p>Parcourez le catalogue, composez votre panier et consultez les disponibilités de chaque produit.</p>
            <div class="hero-actions">
                <a class="hero-primary" href="#produits">Voir le catalogue <span aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1"/>
                    </svg></span>
                </a>
                <a class="hero-secondary" href="panier.php<?= $idListeCourse ? '?idListeCourse=' . $idListeCourse : '' ?>">Ouvrir le panier</a>
            </div>
        </div>
        <div class="hero-logo-card">
            <div class="orb"></div>
            <img src="image/logo-assistbot.png" alt="Logo AssistBot">
        </div>
    </section>

    <section class="catalogue" id="produits">
        <div class="section-heading">
            <div>
                <span class="eyebrow">CATALOGUE</span>
                <h2>Choisissez vos produits</h2>
            </div>
            <span class="result-count"><?= mysqli_num_rows($result) ?> résultat(s)</span>
        </div>
        
        <div class="filters">
            <!-- Filtre par prix -->
            <form class="search" method="get" action="acceuil.php#produits" style="margin-top: 10px;">
                <?php if (isset($_SESSION['idClient'])): ?>
                    <input type="hidden" name="idClient1" value="<?= $_SESSION['idClient'] ?>">
                <?php endif; ?>
                <?php if (isset($_SESSION['idListeCourse'])): ?>
                    <input type="hidden" name="idListeCourse" value="<?= $_SESSION['idListeCourse'] ?>">
                <?php endif; ?>
                <?php if (isset($_GET['cat']) && $_GET['cat'] !== 'tout'): ?>
                    <input type="hidden" name="cat" value="<?= htmlspecialchars($_GET['cat']) ?>">
                <?php endif; ?>
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cash-coin" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8m5-4a5 5 0 1 1-10 0 5 5 0 0 1 10 0"/>
                    <path d="M9.438 11.944c.047.596.518 1.06 1.363 1.116v.44h.375v-.443c.875-.061 1.386-.529 1.386-1.207 0-.618-.39-.936-1.09-1.1l-.296-.07v-1.2c.376.043.614.248.671.532h.658c-.047-.575-.54-1.024-1.329-1.073V8.5h-.375v.45c-.747.073-1.255.522-1.255 1.158 0 .562.378.92 1.007 1.066l.248.061v1.272c-.384-.058-.639-.27-.696-.563h-.668zm1.36-1.354c-.369-.085-.569-.26-.569-.522 0-.294.216-.514.572-.578v1.1zm.432.746c.449.104.655.272.655.569 0 .339-.257.571-.709.614v-1.195z"/>
                    <path d="M1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4.083q.088-.517.258-1H3a2 2 0 0 0-2-2V3a2 2 0 0 0 2-2h10a2 2 0 0 0 2 2v3.528c.38.34.717.728 1 1.154V1a1 1 0 0 0-1-1z"/>
                    <path d="M9.998 5.083 10 5a2 2 0 1 0-3.132 1.65 6 6 0 0 1 3.13-1.567"/>
                    </svg> Prix :
                </span>
                <input type="number" name="prix_min" placeholder="Min" value="<?= isset($_GET['prix_min']) ? htmlspecialchars($_GET['prix_min']) : '' ?>"  class="max-min">
                <span>à</span>
                <input type="number" name="prix_max" placeholder="Max" value="<?= isset($_GET['prix_max']) ? htmlspecialchars($_GET['prix_max']) : '' ?>" class="max-min">
                <button type="submit" class="max-min fiter" >Filtrer</button>
                <?php if (isset($_GET['prix_min']) || isset($_GET['prix_max'])): ?>
                    <a href="acceuil.php<?= $urlParams ?><?= isset($_GET['cat']) && $_GET['cat'] !== 'tout' ? '&cat=' . urlencode($_GET['cat']) : '' ?>" class="reset-filter">✕ Réinitialiser</a>
                <?php endif; ?>
            </form>
            <form class="search" method="get" action="acceuil.php#produits" >
                <?php if (isset($_SESSION['idClient'])): ?>
                    <input type="hidden" name="idClient1" value="<?= $_SESSION['idClient'] ?>">
                <?php endif; ?>
                <?php if (isset($_SESSION['idListeCourse'])): ?>
                    <input type="hidden" name="idListeCourse" value="<?= $_SESSION['idListeCourse'] ?>">
                <?php endif; ?>
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg>
                </span>
                <input type="search" name="recherche" value="<?= htmlspecialchars($recherche) ?>" placeholder="Rechercher un produit...">
                <button type="submit">Rechercher</button>
            </form>
            
            <div class="categories">
                <a class="chip <?= $cat === 'tout' && $recherche === '' ? 'selected' : '' ?>" href="acceuil.php<?= $urlParams ?>#produits">Tout</a>
                <?php foreach ($categories as $key => $label): ?>
                    <a class="chip <?= $cat === $key ? 'selected' : '' ?>" href="acceuil.php?cat=<?= urlencode($key) ?><?= $urlParams ? '&' . ltrim($urlParams, '?') : '' ?>#produits">
                        <?= htmlspecialchars($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="products">
        <?php if (mysqli_num_rows($result) > 0): while ($row = mysqli_fetch_assoc($result)):
            $stock = (int)$row['QuantiteExistant'];
            $status = $stock > 20 ? ['En stock','ok'] : ($stock > 5 ? ['Stock limité','low'] : ['Rupture','out']);
            $addUrl = 'acceuil.php?ajouter=1&id=' . (int)$row['IdProduit'];
            if (isset($_SESSION['idClient'])) {
                $addUrl .= '&idClient1=' . $_SESSION['idClient'];
            }
            if (isset($_SESSION['idListeCourse'])) {
                $addUrl .= '&idListeCourse=' . $_SESSION['idListeCourse'];
            }
        ?>
            <article class="product-card">
                <div class="product-body">
                    <img src="<?= htmlspecialchars(imageProduit($row['Image'])) ?>" alt="<?= htmlspecialchars($row['NomProduit']) ?>">
                    <span class="stock <?= $status[1] ?>"><?= $status[0] ?></span>
                    <span class="category-label"><?= htmlspecialchars($categories[$row['IdCategorie']] ?? 'Non catégorisé') ?></span>
                    <h3><?= htmlspecialchars($row['NomProduit']) ?></h3>
                    <div class="product-bottom">
                        <strong><?= number_format((float)$row['PrixUnitaire'], 2, ',', ' ') ?> Ar</strong>
                        <span><?= $stock ?> en stock</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a class="add-btn <?= $stock <= 0 ? 'disabled' : '' ?>" href="<?= $stock > 0 ? $addUrl : '#' ?>">
                            <?= $stock > 0 ? '+ Ajouter au panier' : 'Indisponible' ?>
                        </a>
                        <!-- Bouton Détails vers detailProduit.php -->
                        <form action="detailProduit.php" method="post" style="margin: 0;">
                            <input type="hidden" name="idListeCourse" value="<?= $idListeCourse ?>">
                            <input type="hidden" name="details" value="<?= $row['IdProduit'] ?>">
                            <button type="submit" class="voir">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endwhile; else: ?>
            <div class="empty-state">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg>
                </div>
                <h3>Aucun produit trouvé</h3>
                <a href="acceuil.php<?= $urlParams ?>">Réinitialiser</a>
            </div>
        <?php endif; ?>
        </div>
    </section>
</main>
<footer><span>AssistBot</span> — Interface de controle</footer>
<script>
function activerSuivi(e) {
    e.preventDefault();
    fetch("changer_mode.php?valeur=suivi", { method: "POST" })
        .then(r => r.text())
        .then(() => {
            const lien = e.target;
            const ancien = lien.innerText;
            lien.innerText = "Suivi active ✓";
            setTimeout(() => { lien.innerText = ancien; }, 2000);
        })
        .catch(() => alert("Impossible d'activer le mode suivi."));
}
</script>
</body>
</html>