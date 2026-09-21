<?php
// ============================================================
// pcc.php - Carte du parcours + calcul du plus court chemin
// ============================================================

/*$Tableau_sommet = [
    ["coordonnee"=>[0,0], "origine"=>"x1",  "chemin"=>[["A","x",1],["x4","y",2]]],
    ["coordonnee"=>[1,0], "origine"=>"A",   "chemin"=>[["x1","x",-1],["x2","x",2]]],
    ["coordonnee"=>[3,0], "origine"=>"x2",  "chemin"=>[["A","x",-2],["B","x",1],["x5","y",2]]],
    ["coordonnee"=>[4,0], "origine"=>"B",   "chemin"=>[["x2","x",-1],["C","x",3]]],
    ["coordonnee"=>[7,0], "origine"=>"C",   "chemin"=>[["B","x",-3],["x3","x",1]]],
    ["coordonnee"=>[8,0], "origine"=>"x3",  "chemin"=>[["C","x",-1],["x6","y",2]]],
    ["coordonnee"=>[0,2], "origine"=>"x4",  "chemin"=>[["x1","y",-2],["x9","y",2],["L","x",1]]],
    ["coordonnee"=>[1,2], "origine"=>"L",   "chemin"=>[["x4","x",-1],["x7","x",1],["F","y",2]]],
    ["coordonnee"=>[2,2], "origine"=>"x7",  "chemin"=>[["L","x",-1],["x5","x",1],["x8","y",2]]],
    ["coordonnee"=>[3,2], "origine"=>"x5",  "chemin"=>[["x7","x",-1],["D","x",1],["x2","y",-2]]],
    ["coordonnee"=>[4,2], "origine"=>"D",   "chemin"=>[["x5","x",-1],["E","x",2]]],
    ["coordonnee"=>[6,2], "origine"=>"E",   "chemin"=>[["D","x",-2],["x14","x",1]]],
    ["coordonnee"=>[7,2], "origine"=>"x14", "chemin"=>[["E","x",-1],["x6","x",1],["x13","y",2]]],
    ["coordonnee"=>[8,2], "origine"=>"x6",  "chemin"=>[["x15","y",2],["x3","y",-2],["x14","x",-1]]],
    ["coordonnee"=>[0,4], "origine"=>"x9",  "chemin"=>[["x10","y",2],["x4","y",-2],["F","x",1]]],
    ["coordonnee"=>[1,4], "origine"=>"F",   "chemin"=>[["x9","x",-1],["x8","x",1],["L","y",-2]]],
    ["coordonnee"=>[2,4], "origine"=>"x8",  "chemin"=>[["F","x",-1],["G","x",1],["x7","y",-2]]],
    ["coordonnee"=>[3,4], "origine"=>"G",   "chemin"=>[["x8","x",-1],["x12","x",2]]],
    ["coordonnee"=>[5,4], "origine"=>"x12", "chemin"=>[["G","x",-2],["H","x",1],["x11","y",2]]],
    ["coordonnee"=>[6,4], "origine"=>"H",   "chemin"=>[["x12","x",-1],["x13","x",1]]],
    ["coordonnee"=>[7,4], "origine"=>"x13", "chemin"=>[["H","x",-1],["x15","x",1],["x14","y",-2]]],
    ["coordonnee"=>[8,4], "origine"=>"x15", "chemin"=>[["x16","y",2],["x6","y",-2],["x13","x",-1]]],
    ["coordonnee"=>[0,6], "origine"=>"x10", "chemin"=>[["x9","y",-2],["I","x",1]]],
    ["coordonnee"=>[1,6], "origine"=>"I",   "chemin"=>[["x10","x",-1],["J","x",3]]],
    ["coordonnee"=>[4,6], "origine"=>"J",   "chemin"=>[["I","x",-3],["x11","x",1]]],
    ["coordonnee"=>[5,6], "origine"=>"x11", "chemin"=>[["J","x",-1],["K","x",1],["x12","y",-2]]],
    ["coordonnee"=>[6,6], "origine"=>"K",   "chemin"=>[["x11","x",-1],["x16","x",2]]],
    ["coordonnee"=>[8,6], "origine"=>"x16", "chemin"=>[["K","x",-2],["x15","y",-2]]]
];*/
$Tableau_sommet = [
    ["coordonnee"=>[0,0], "origine"=>"A",  "chemin"=>[["B","y",1],["H","x",1]]],
    ["coordonnee"=>[1,0], "origine"=>"B",   "chemin"=>[["A","y",-1],["C","y",1],["I","x",1]]],
    ["coordonnee"=>[2,0], "origine"=>"C",  "chemin"=>[["B","y",-1],["D","x",1]]],
    ["coordonnee"=>[2,1], "origine"=>"D",   "chemin"=>[["I","y",-1],["C","x",-1],["E","x",1]]],
    ["coordonnee"=>[2,2], "origine"=>"E",   "chemin"=>[["F","y",-1],["D","x",-1]]],
    ["coordonnee"=>[1,2], "origine"=>"F",  "chemin"=>[["G","y",-1],["I","x",-1],["E","y",1]]],
    ["coordonnee"=>[0,2], "origine"=>"G",  "chemin"=>[["F","y",1],["H","x",-1]]],
    ["coordonnee"=>[0,1], "origine"=>"H",   "chemin"=>[["G","x",1],["I","y",1],["A","x",-1]]],
    ["coordonnee"=>[1,1], "origine"=>"I",  "chemin"=>[["B","x",-1],["D","y",1],["F","x",1],["H","y",-1]]]
];

// Recherche le nom du sommet correspondant a des coordonnees donnees
function recherche($x, $y, $tableau) {
    foreach ($tableau as $tab) {
        $coordonnee = $tab["coordonnee"];
        if ($coordonnee[0] == $x && $coordonnee[1] == $y) {
            return $tab["origine"];
        }
    }
    return "pas dans le tableau";
}

// Calcule le plus court chemin entre une position (x,y) et un sommet destination.
// Retourne directement un tableau de mouvements prets a etre envoyes au robot :
// [["x",1],["y",2], ...]
function calculerChemin($x, $y, $destination) {
    global $Tableau_sommet;

    // Recherche du sommet de depart (ou du plus proche si (x,y) n'est pas un sommet exact)
    $check = recherche($x, $y, $Tableau_sommet);
    if ($check != "pas dans le tableau") {
        $origine = $check;
    } else {
        $i = 1;
        $trouve = false;
        while ($i <= 5 && !$trouve) {
            $check = recherche($x - $i, $y, $Tableau_sommet);
            if ($check != "pas dans le tableau") { $origine = $check; $x -= $i; $trouve = true; break; }
            $check = recherche($x + $i, $y, $Tableau_sommet);
            if ($check != "pas dans le tableau") { $origine = $check; $x += $i; $trouve = true; break; }
            $check = recherche($x, $y - $i, $Tableau_sommet);
            if ($check != "pas dans le tableau") { $origine = $check; $y -= $i; $trouve = true; break; }
            $check = recherche($x, $y + $i, $Tableau_sommet);
            if ($check != "pas dans le tableau") { $origine = $check; $y += $i; $trouve = true; break; }
            $i++;
        }
        if (!$trouve) {
            return [];   // aucun chemin possible
        }
    }

    $origine_general = $origine;
    $Sommet_deja_passes = [];
    $Sommet_en_cours_d_utilisation = [$origine => [$origine, 0]];
    $Sauvegarde_chemin = [];

    // Algorithme de Dijkstra simplifie
    while ($origine != $destination) {
        foreach ($Sommet_en_cours_d_utilisation as $cle => $val) {
            $origine = $cle;
            $distance_effectuee = $val[1];
            break;
        }
        foreach ($Sommet_en_cours_d_utilisation as $cle => $val) {
            if ($val[1] < $distance_effectuee) {
                $origine = $cle;
                $distance_effectuee = $val[1];
            }
        }

        foreach ($Tableau_sommet as $s) {
            if ($s['origine'] == $origine) {
                foreach ($s['chemin'] as $j) {
                    $check = 0;
                    foreach ($Sommet_deja_passes as $sommet) {
                        if ($j[0] == $sommet) { $check = 1; break; }
                    }
                    if ($check == 0) {
                        if ($j[2] > 0) {
                            $Sommet_en_cours_d_utilisation[$j[0]] = [$origine, $j[2] + $distance_effectuee];
                        } else {
                            $Sommet_en_cours_d_utilisation[$j[0]] = [$origine, -$j[2] + $distance_effectuee];
                        }
                    }
                }
            }
        }

        array_push($Sommet_deja_passes, $origine);

        foreach ($Sommet_en_cours_d_utilisation as $cle => $val) {
            if ($cle == $origine) {
                $Sauvegarde_chemin[$cle] = [$val[0], $val[1]];
                unset($Sommet_en_cours_d_utilisation[$cle]);
            }
        }
    }

    // Reconstruction du chemin (liste ordonnee des sommets a traverser)
    $fin = $destination;
    $Chemin = [$destination];
    while ($fin != $origine_general) {
        foreach ($Sauvegarde_chemin as $cle => $val) {
            if ($cle == $fin) {
                array_unshift($Chemin, $val[0]);
                $fin = $val[0];
                break;
            }
        }
    }

    // Conversion de la liste de sommets en liste de mouvements ["axe", valeur]
    $commande = [];
    for ($i = 0; $i < count($Chemin) - 1; $i++) {
        foreach ($Tableau_sommet as $tab) {
            if ($tab["origine"] == $Chemin[$i]) {
                foreach ($tab["chemin"] as $sommet) {
                    if ($sommet[0] == $Chemin[$i + 1]) {
                        $commande[] = [$sommet[1], $sommet[2]];
                    }
                }
            }
        }
    }

    return $commande;
}
?>
