<?php
// ============================================================
// reception.php - Recoit les messages POST envoyes par l'ESP32
// (position du robot, changement d'etat, ou erreur)
// ============================================================

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["type"])) {
    http_response_code(400);
    echo "Donnees invalides";
    exit;
}

if ($data["type"] === "position") {
    file_put_contents("position_actuelle.json", json_encode($data));
    echo "Position sauvegardee";
}
elseif ($data["type"] === "etat") {
    file_put_contents("etat_robot.json", json_encode($data));
    echo "Etat sauvegarde";
}
elseif ($data["type"] === "erreur") {
    // Nouveau canal : l'ESP32 signale un probleme (JSON invalide,
    // grille non alignee, buffer plein, etc.). On ajoute un horodatage
    // pour que le front puisse ignorer une erreur trop ancienne.
    $data["timestamp"] = time();
    file_put_contents("erreur_robot.json", json_encode($data));
    echo "Erreur sauvegardee";
}
else {
    http_response_code(400);
    echo "Type inconnu";
}
?>