<?php
require_once __DIR__ . '/connexion.php';

echo "<h2>🔍 Diagnostic</h2>";

// 1. Vérifier la connexion
echo "<h3>1. Connexion BDD</h3>";
if ($link) {
    echo "✅ Connexion OK<br>";
} else {
    echo "❌ Erreur : " . mysqli_connect_error();
    exit;
}

// 2. Vérifier la table Admin
echo "<h3>2. Table Admin</h3>";
$result = mysqli_query($link, "SELECT * FROM Admin");
if (!$result) {
    echo "❌ Erreur : " . mysqli_error($link);
    exit;
}

$count = mysqli_num_rows($result);
echo "Nombre de lignes : <strong>$count</strong><br><br>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<div style='background:#f0f0f0; padding:10px; margin:5px 0; border-radius:5px;'>";
    echo "ID : " . $row['IdAdmin'] . "<br>";
    echo "Username : <strong>" . $row['Username'] . "</strong><br>";
    echo "PasswordHash : <code>" . substr($row['PasswordHash'], 0, 30) . "...</code><br>";
    echo "TokenHash : <code>" . substr($row['TokenHash'], 0, 30) . "...</code><br>";
    echo "</div>";
}

// 3. Tester le token
echo "<h3>3. Test du token '</h3>";
$token = 'apollo11';
$result = mysqli_query($link, "SELECT Username, TokenHash FROM Admin");

if (mysqli_num_rows($result) == 0) {
    echo "❌ Aucun admin dans la table !";
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($token, $row['TokenHash'])) {
            echo "✅ Token <strong>VALIDE</strong> pour " . $row['Username'] . "<br>";
        } else {
            echo "❌ Token <strong>INVALIDE</strong> pour " . $row['Username'] . "<br>";
        }
    }
}
?>