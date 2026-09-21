<?php
// ============================================================
// voir_erreur.php - Renvoie la derniere erreur recente (JSON)
// Renvoie null si aucune erreur, ou si la derniere date de plus
// de 10 secondes (l'erreur disparait toute seule de l'interface).
// ============================================================

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$file = 'erreur_robot.json';
if (!file_exists($file)) {
    echo json_encode(null);
    exit;
}

$contenu = file_get_contents($file);
$data = json_decode($contenu, true);

if (!$data || !isset($data['timestamp'])) {
    echo json_encode(null);
    exit;
}

if (time() - $data['timestamp'] > 10) {
    echo json_encode(null);
    exit;
}

echo $contenu;
?>