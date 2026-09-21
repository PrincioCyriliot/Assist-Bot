<?php
// ============================================================
// changer_mode.php - Declenche par les boutons Start/Stop
// Met en file d'attente un changement de mode simple
// ============================================================

require "queue.php";

$valeur = $_GET["valeur"] ?? "none";

// Securite : on n'accepte que les modes connus
$modesValides = ["suivi", "guide", "none"];
if (!in_array($valeur, $modesValides)) {
    http_response_code(400);
    echo "Mode invalide";
    exit;
}

ajouterMessageQueue(["type" => "mode", "valeur" => $valeur]);

echo "Mode '$valeur' mis en file d'attente.";
?>
