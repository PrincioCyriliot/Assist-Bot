<?php
// ============================================================
// calibrer.php - Declenche par le bouton "Calibrer le QMC"
// Demande au robot de lancer une calibration autonome du QMC5883L
// (rotation automatique + sauvegarde en EEPROM)
// ============================================================

require "queue.php";

ajouterMessageQueue(["type" => "calibrer"]);

echo "Demande de calibration envoyee. Le robot va tourner sur lui-meme automatiquement, ne le touchez pas pendant l'operation.";
?>
