<?php
// ============================================================
// voir_position.php - Renvoie la position actuelle du robot (JSON)
// Utilise par l'interface web pour affichage en direct
// ============================================================

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (file_exists("position_actuelle.json")) {
    $contenu = file_get_contents("position_actuelle.json");
    $data = json_decode($contenu, true);
    if ($data === null) {
        echo json_encode(["type" => "position", "x" => 0, "y" => 0]);
    } else {
        echo $contenu;
    }
} else {
    echo json_encode(["type" => "position", "x" => 0, "y" => 0]);
}
?>