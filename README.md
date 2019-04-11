# Memory Game
Jeu de mémoire pour cours de programmation

## Configuration serveur

* MySQL
* PHP

## Possibilités d'évolution du jeu

### De MySQL à Mongo
Le données du jeu sont actuellement stockées en JSON dans un champs text de MySQL.
Il serait intéressant d'optimiser ce stockage grâce à une base Mongo 
qui est construite de façon native sur le format JSON.

### Haute disponibilité
Afin de rendre l'application utilisable par un nombre important de joueurs simultanément, 
il serait intéressant de développer un sytème
de cache.
Ex: Memcache, Redis...

### Écran d'accueil
Création d'un écran d'acceuil afin d'organiser les nouvelles fonctionnalités.
* Un champs permettant de spécifier son pseudo
* un bouton "Nouvelle partie"

### Nouvelle condition de victoire
Ajouter le stockage des données d'essais de paires, dans le but de l'ajouter comme condition de victoire.
Le nombre d'essais apparaitra alors dans le tableau des scores.

### Difficulté
Donner la possibilité au joueur de modifier la difficulté du jeu (Temps limite, nombre de paires, nombre d'essais...)

### Version mobile
Modifier la feuille de style, et le code Javascript, pour rendre le jeu compatible avec mobile.

### Amélioration visuelle et expérience de jeu
Ajouter un compte à rebour à la fin du temps limite, à l'aide de gros chiffres grossissants sur l'écran.
Ce qui augmentera la tension à la fin de la partie.
Les chiffres étant transparent pour ne pas géner la zone de jeu.

Rendre l'affichage du résultat plus attractif, grâce a une sorte de fenêtre modale, invitant a relancer une partie.