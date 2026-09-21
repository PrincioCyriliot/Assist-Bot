# 🤖 Assist-Bot — Smart In-Store Navigational Assistant

> **Transformez l'expérience d'achat en magasin grâce à un système de guidage robotique en temps réel.**

---

## 💡 À propos du projet

**Assist-Bot** est une plateforme complète combinant une application web interactive et un robot guide physique piloté par un microcontrôleur ESP32. 

Conçu pour les supermarchés et grands magasins, Assist-Bot permet aux utilisateurs de sélectionner des produits dans un catalogue interactif, de constituer une liste de courses et de se faire guider automatiquement à travers les rayons jusqu'à leurs articles.

### 🌟 Fonctionnalités Principales

* ** Catalogue & Panier en temps réel :** Consultation des produits par catégories, filtres de disponibilité et ajustement dynamique des quantités.
* ** Système de Guidage Actif :** Communication en continu avec le robot pour naviguer jusqu'à la position $(X, Y)$ d'un produit.
* ** Queue FIFO & Commande :** Gestion synchrone des instructions envoyées au robot (`queue.php` / `commande_queue.json`).
* ** Télémétrie & Télégestion :** Envoi et lecture en direct des coordonnées, de l'état du robot et des remontées d'erreurs en JSON sans mise en cache.
* ** Console Administrateur :** Gestion des stocks, ajout/modification d'articles et réinitialisation du système.
* ** Interface Responsive & Effets Visuels :** Animations d'état du robot, retours visuels (chargement, suivi, célébration d'arrivée) et compatibilité tablettes.
* ** Algorithme de Moore dijkstra
---

## 🏗️ Architecture Globale

```
                ┌─────────────────────────┐
                │   Interface Web Client  │
                └────────────┬────────────┘
                             │ (Requests HTTP GET/POST)
                             ▼
                ┌─────────────────────────┐
                │  Serveur PHP + MySQL    │
                └────────────┬────────────┘
                             │ (Files JSON / Queue System)
                             ▼
                ┌─────────────────────────┐
                │   ESP32 (NavBot)        │
                │  - Moteurs & Drivers    │
                │  - Capteurs Ultrasons   │
                └─────────────────────────┘
```

---

## 🛠️ Stack Technique

* **Front-end :** HTML5, CSS3 (Keyframes, CSS Grid, Modèles Flexbox, Thème Futuriste), JavaScript (Fetch API).
* **Back-end :** PHP 8.x, Fichiers JSON de synchronisation stateful.
* **Base de données :** MySQL / MariaDB.
* **Hardware & IoT :** ESP32, Driver Moteur (L298N/DRV8825), Capteurs Ultrasons HC-SR04, Alimentation Li-Ion.

---

## 📝 Licence

Projet 2 ème année Electronique Systèmes Informatique et Intelligence Artificielle à l'ISPM
