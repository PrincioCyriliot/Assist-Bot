<?php
// ============================================================
// aligner.php - Declenche par le bouton "Aligner la grille"
// Demande au robot de memoriser son cap actuel comme etant "+x"
// pour la grille physique en place
// ============================================================

require "queue.php";

ajouterMessageQueue(["type" => "aligner"]);

echo "Demande d'alignement envoyee. Assurez-vous que le robot est bien oriente face a la direction souhaitee pour +x avant de continuer.";
?>
