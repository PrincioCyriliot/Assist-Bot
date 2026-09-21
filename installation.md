# 🛠️ Guide d'Installation et Configuration — Assist-Bot

Ce guide détaille l'ensemble des prérequis matériels, le schéma de la base de données MySQL, la procédure de génération du mot de passe administrateur sécurisé et la mise en place du serveur.

---

## 📦 1. Composants Matériels Requis (Robot)

| Composant | Description | Quantité |
| :--- | :--- | :--- |
| **ESP32 NodeMCU** | Carte de développement principale (Wi-Fi) | 1 |
| **Driver Moteur L298N / DRV8825** | Module de puissance pour moteurs | 1 ou 2 |
| **Moteurs CC + Encodeurs** | Moteurs de propulsion | 2 |
| **Capteurs Ultrasons (HC-SR04)** | Détection d'obstacles en rayon | 2 ou 3 |
| **Régulateur de Tension (LM2596)** | Module Buck Step-Down pour 5V | 1 |
| **Batteries Li-Ion 18650 + BMS** | Alimentation autonome du robot (7.4V/12V) | 1 kit |
| **Châssis Robotique + Roues** | Structure mécanique du robot | 1 |

---

## 💻 2. Prérequis Logiciels

* **Serveur Web :** Apache2 ou Nginx avec PHP 8.0+.
* **Base de données :** MySQL 5.7+ ou MariaDB 10.4+.
* **Extensions PHP requises :** `mysqli`, `json`.

---

## 🗄️ 3. Structure de la Base de Données (MySQL)

Exécutez le script SQL suivant dans votre gestionnaire de base de données (phpMyAdmin ou terminal MySQL) :

```sql
CREATE DATABASE IF NOT EXISTS `smartstore` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smartstore`;

-- Table des Administrateurs
CREATE TABLE IF NOT EXISTS `Admin` (
    `IdAdmin` INT AUTO_INCREMENT PRIMARY KEY,
    `Nom` VARCHAR(50) NOT NULL,
    `Email` VARCHAR(100) UNIQUE NOT NULL,
    `MotDePasse` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des Catégories / Rayons
CREATE TABLE IF NOT EXISTS `Categorie` (
    `IdCategorie` INT AUTO_INCREMENT PRIMARY KEY,
    `NomCategorie` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des Produits
CREATE TABLE IF NOT EXISTS `Produits` (
    `IdProduit` INT AUTO_INCREMENT PRIMARY KEY,
    `Nom` VARCHAR(100) NOT NULL,
    `Prix` DECIMAL(10,2) NOT NULL,
    `QuantiteStock` INT NOT NULL DEFAULT 0,
    `Image` VARCHAR(255) DEFAULT NULL,
    `PosX` INT NOT NULL DEFAULT 0,
    `PosY` INT NOT NULL DEFAULT 0,
    `IdCategorie` INT,
    FOREIGN KEY (`IdCategorie`) REFERENCES `Categorie`(`IdCategorie`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des Listes d'Achat
CREATE TABLE IF NOT EXISTS `ListeCourse` (
    `IdListeCourse` INT AUTO_INCREMENT PRIMARY KEY,
    `DateCreation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `Statut` ENUM('en_cours', 'terminee') DEFAULT 'en_cours'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table de liaison Produits / Liste
CREATE TABLE IF NOT EXISTS `ListeProduits` (
    `IdListeProduits` INT AUTO_INCREMENT PRIMARY KEY,
    `IdListeCourse` INT NOT NULL,
    `IdProduit` INT NOT NULL,
    `QuantiteAcheter` INT NOT NULL DEFAULT 1,
    FOREIGN KEY (`IdListeCourse`) REFERENCES `ListeCourse`(`IdListeCourse`) ON DELETE CASCADE,
    FOREIGN KEY (`IdProduit`) REFERENCES `Produits`(`IdProduit`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🔑 4. Génération du Hash du Mot de Passe Administrateur

Pour sécuriser l'accès administrateur, utilisez un hash BCRYPT généré en PHP.

1. Créez un fichier temporaire `hash.php` :

```php
<?php
$password = "VotreMotDePasseSecurise123";
echo password_hash($password, PASSWORD_BCRYPT);
?>
```

2. Exécutez le script et récupérez la chaîne générée.
3. Insérez le premier compte administrateur en BDD :

```sql
INSERT INTO `Admin` (`Nom`, `Email`, `MotDePasse`) 
VALUES ('Admin', 'admin@store.com', 'REMPLACER_PAR_LE_HASH_OBTENU');
```

---

## ⚙️ 5. Configuration de l'Application Web

1. **Fichier `connexion.php` :**
   Configurez les identifiants de connexion MySQL :

```php
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "smartstore";

$link = mysqli_connect($host, $user, $pass, $db);

if (!$link) {
    die("Erreur de connexion MySQL : " . mysqli_connect_error());
}
?>
```

2. **Permissions des fichiers d'état JSON :**
   Assurez-vous que le serveur web possède les droits d'écriture sur les fichiers JSON suivants :
   * `commande_queue.json`
   * `position_actuelle.json`
   * `etat_robot.json`
   * `erreur_robot.json`

---

## 📡 6. Endpoint Communication ESP32 ↔ Serveur

L'ESP32 s'interfère avec le serveur PHP via les endpoints suivants :

* **Récupération des commandes (Queue) :** `GET /queue.php`
* **Mise à jour de la position :** `POST /reception.php` avec body `{"type":"position", "x":12, "y":30}`
* **Mise à jour de l'état :** `POST /reception.php` avec body `{"type":"etat", "mode":"guide", "phase":"mouvement"}`
* **Signalement d'erreur :** `POST /reception.php` avec body `{"type":"erreur", "code":500, "message":"Obstacle detecte"}`
