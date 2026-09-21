<?php
// ============================================================
// voir_etat.php - Renvoie l'etat actuel du robot (JSON)
// Utilise par l'interface web pour affichage en direct
// ============================================================

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (file_exists("etat_robot.json")) {
    $contenu = file_get_contents("etat_robot.json");
    $data = json_decode($contenu, true);
    if ($data === null) {
        echo json_encode(["type" => "etat", "mode" => "inconnu", "phase" => "inconnu"]);
    } else {
        echo $contenu;
    }
} else {
    echo json_encode(["type" => "etat", "mode" => "none", "phase" => "inactif"]);
}
?>