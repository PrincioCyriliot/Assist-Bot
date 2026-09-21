<?php
// ============================================================
// queue.php - Gestion de la file d'attente des messages
// ============================================================

// Ajoute un message (tableau associatif) a la fin de la file d'attente
function ajouterMessageQueue($message, $fichier = "commande_queue.json") {
    $queue = [];
    if (file_exists($fichier)) {
        $contenu = json_decode(file_get_contents($fichier), true);
        if (is_array($contenu)) {
            $queue = $contenu;
        }
    }
    $queue[] = $message;
    file_put_contents($fichier, json_encode($queue));
}

// Retire et renvoie le premier message de la file d'attente (FIFO)
// Renvoie null s'il n'y a rien a envoyer
function recupererMessageQueue($fichier = "commande_queue.json") {
    if (!file_exists($fichier)) {
        return null;
    }

    $queue = json_decode(file_get_contents($fichier), true);
    if (!is_array($queue) || count($queue) == 0) {
        return null;
    }

    $message = array_shift($queue);
    file_put_contents($fichier, json_encode($queue));
    return $message;
}
?>
