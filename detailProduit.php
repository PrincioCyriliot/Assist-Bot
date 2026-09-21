<?php
    include "connexion.php";

    // Initialiser les variables
    $idListeCourse = null;
    $idClient2 = null;
    $row = null;

    if(isset($_POST['idListeCourse'])){
        $idListeCourse = $_POST['idListeCourse'];

        $reqCourse = mysqli_query($link, "SELECT * FROM ListeCourses WHERE IdListeCourses = '$idListeCourse'");
        $rowCourse = mysqli_fetch_array($reqCourse);
        $idClient2 = (int)$rowCourse["IdClient"];
    }

    if(isset($_POST['details'])){
        $id = $_POST['details'];
        $req = mysqli_query($link, "SELECT * FROM Produit WHERE IdProduit = '$id'");
        $row = mysqli_fetch_assoc($req);
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du produit</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #020617;
            color: white;
        }
        .menu {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background-color: #1e293b;
        }

        .retour {
            border: none;
            padding: 12px 18px;
            border-radius: 10px;
            background-color: #38bdf8;
            color: black;
            cursor:pointer;
        }

        .product-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .product-card {
            display: flex;
            width: 800px;
            background-color: #1e293b;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 0 25px #38bdf8;
        }

        .product-image {
            width: 50%;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details {
            width: 50%;
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .product-details h1 {
            margin: 0;
        }

        .price {
            font-size: 22px;
            color: #38bdf8;
            font-weight: bold;
        }

        .description {
            font-size: 16px;
        }

        .options input {
            padding: 8px;
            border-radius: 8px;
            border: none;
        }


        .buy-btn {
            margin-top: auto;
            padding: 15px;
            border-radius: 12px;
            border: none;
            background-color: #38bdf8;
            color: black;
            font-size: 18px;
            cursor: pointer;
        }
        
        .btn{
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }



        /* ===================== RESPONSIVE ===================== */
        @media screen and (max-width: 768px){
            .product-card {
                flex-direction: column;
                width: 95%;
            }

            .product-image, .product-details {
                width: 100%;
            }

            .product-image img {
                height: 250px;
            }
        }
    </style>
    <link rel="stylesheet" href="interface.css">
</head>
<body class="page-product">

    <!-- BARRE DE NAVIGATION -->
    <div class="menu">
        <!-- Bouton retour panier -->
        <form method="post" action="panier.php">
            <input type="hidden" name="idListeCourse" value="<?php echo $idListeCourse; ?>">
            <input type="submit" value="← Retour au panier" class="retour">
        </form>
        
        <!-- Bouton retour accueil -->
        <?php if ($idClient2 !== null): ?>
            <form method="post" action="acceuil.php">
                <input type="hidden" name="idClient2" value="<?php echo $idClient2; ?>">
                <input type="submit" value="← Accueil" class="retour" class="retourac">
            </form>
        <?php endif; ?>
    </div>

    <!-- ZONE PRODUIT -->
    <div class="product-container">
        <div class="product-card">
            <div class="product-image">
                <?php 
                if ($row !== null) {
                    $imagePath = "image/" . $row["Image"];
                    if (!empty($row["Image"]) && file_exists($imagePath)) {
                        echo '<img src="' . $imagePath . '" alt="Image_Produit">';
                    } else {
                        echo '<img src="image/default.png" alt="Image par défaut">';
                    }
                }
                ?>
            </div>
            <div class="product-details">
                <?php if ($row !== null): ?>
                <h1><?php echo $row["NomProduit"]; ?></h1>
                <p class="price"><?php echo $row["PrixUnitaire"] ?>Ar</p>
                <?php 
                $catName = 'Non catégorisé';
                if (!empty($row["IdCategorie"])) {
                    $catReq = mysqli_query($link, "SELECT NomCategorie FROM Categorie WHERE IdCategorie = '" . $row["IdCategorie"] . "'");
                    $catRow = mysqli_fetch_assoc($catReq);
                    if ($catRow) {
                        $catName = $catRow['NomCategorie'];
                    }
                }
                ?>
                <p class="description">Categorie : <?php echo $catName ?><br><?php echo $row["Information"] ?></p>
                <div class="btn">
                    <form action="panier.php" method="post">
                        <input type="hidden" name="idListeCourse" value="<?php echo $idListeCourse; ?>">
                        <input type="hidden" name="quantite" value="1">
                        <input type="hidden" name="idProduit" value="<?php echo $row["IdProduit"]; ?>">
                        <button type="submit" class="buy-btn">Ajouter au panier</button>
                    </form>
                    <form action="guider.php" method="post">
                        <input type="hidden" name="idClient2" value="<?php echo $idClient2; ?>">
                        <input type="hidden" name="idListeCourse" value="<?php echo $idListeCourse; ?>">
                        <input type="hidden" name="idProduit" value="<?php echo $row["IdProduit"]; ?>">
                        <input type="hidden" name="redirect" value="chargement">
                        <button type="submit" class="buy-btn">Guider</button>
                    </form>
                </div>
                <?php else: ?>
                    <p>Produit introuvable.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>