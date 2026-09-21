<?php
// ============================================================
// commande.php - Interroge par l'ESP32 (requete GET periodique)
// Renvoie le prochain message en file d'attente, ou "aucun"
// ============================================================

header('Content-Type: application/json');
require "queue.php";

$message = recupererMessageQueue();

if ($message === null) {
    echo json_encode(["type" => "aucun"]);
} else {
    echo json_encode($message);
}
?>
