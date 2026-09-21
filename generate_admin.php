<?php
require_once __DIR__ . '/connexion.php';
$username='admin';
$token = 'apollo11';
$tokenHash = password_hash($token, PASSWORD_DEFAULT);
// Mise a jour du token
$stmt = mysqli_prepare($link, "UPDATE Admin SET TokenHash = ? WHERE Username = '$username'");
mysqli_stmt_bind_param($stmt, 's', $tokenHash);

if (mysqli_stmt_execute($stmt)) {
    echo "Token généré et mis à jour avec succès !<br>";
    echo "Nouveau token : <strong>$token</strong><br>";
    echo "Token par Hachage: <strong>$tokenHash</strong><br>";
    echo "<br><a href='diagnostique.php'>Tester avec diagnostique.php</a>";
    echo "<br><br><strong> Apollo11 :p !</strong<br><br><br>";
} else {
    echo " Erreur : " . mysqli_error($link);
}
// Mot de passe
$password = 'admin';
$masque_du_password = '********';
// Génération du hash
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Mise à jour du mot de passe de l'admin
$stmt = mysqli_prepare( $link,"UPDATE Admin SET PasswordHash = ? WHERE Username = '$username'"
);
mysqli_stmt_bind_param($stmt, 's', $passwordHash);
if (mysqli_stmt_execute($stmt)) {
    echo "Mot de passe généré mis à jour avec succès !<br>";
    echo "Mot de passe : <strong>" . htmlspecialchars($masque_du_password) . "</strong><br>";
    echo "Nouveau PasswordHash : <strong>" . htmlspecialchars($passwordHash) . "</strong><br>";
    echo "<br><strong>Apollo11 :p !</strong>";
} else {

    echo "Erreur : " . mysqli_error($link);
}
?>