<?php

/**
 * On inclu le fichier de classe pour l'utilisateur courant
 */
require_once('class/User.php');

/**
 * On inclu le fichier de classe pour gérer les scores
 */
require_once('class/Score.php');

/**
 * Class Memory_game
 */
class Memory_game
{

    /**
     * Instance de classe de l'utilisateur courant
     * @var object $user
     */
    public $user;

    /**
     * Instance de classe du score
     * @var object $score
     */
    public $score;

    /**
     * Tableau de données du jeu
     * Ne peut être modifié que par la classe courante
     * @var array $cards
     */
    private $game_data;

    /**
     * Nombre de pairs présentent sur le plateau de jeu (18 max)
     * Constante qui ne sera pas modifié
     * @var int PAIR_COUNT
     */
    const PAIR_COUNT = 14;

    /**
     * Nombre de secondes avant que la partie soit perdu
     * Constante qui ne sera pas modifié
     * @var int GAME_TIME
     */
    const GAME_TIME = 150;

    /**
     * Memory_game constructor.
     * Méthode lancé à l'implémentaion de la classe
     */
    public function __construct()
    {
        $this->user = new User();
        $this->user->getUser();
        $this->score = new Score();
    }

    /**
     * Intialize game
     */
    public function initGame()
    {
        // Si la variable faisant référence aux données de jeu est vide, on les récupère de la base de données
        // pour l'utilisateur courant
        if (empty($this->game_data)) {
            $game_data_from_user = $this->user->getGameData();
            if ( ! empty($game_data_from_user)) {
                $game_data_from_user['start'] = time();
                $this->setGameData($game_data_from_user);
            }
        }
        // Si aucune données n'a pu être récupéré de l'utilisateur courant in initialise une nouvelle partie
        // Sinon on repositionne le début de partie en fonction de l'état du timer précédemment sauvegardé
        if (empty($this->game_data)) {
            $this->setGameData([
                'cards' => $this->setCards(),
                'pair_found' => 0,
                'pair_total' => self::PAIR_COUNT,
                'start' => time(),
                'timer' => 0,
                'first_pick' => false,
            ]);
        } else {
            $this->game_data['start'] = time() - $this->game_data['timer'];
        }
        // On sauvegarde la partie pour l'utilisateur courant
        $this->user->save($this->game_data);
    }

    /**
     * Réinitialise le jeu
     */
    public function resetGame()
    {

        // Les données du jeu sont vide
        $this->setGameData([]);

        // On sauvegarde les données de jeu vide en base de données
        $this->user->save($this->game_data);
    }

    /**
     * Mélangeur de carte
     * @param $cards
     * @return array
     */
    private function shuffleCards($cards)
    {
        // on récupère les clé du tableau de cartes
        $keys = array_keys($cards);

        // on mélange les clés
        shuffle($keys);

        // on reconstitue le tableau de données des cartes mélangés
        $shuffle_cards = [];
        foreach ($keys as $key) {
            $shuffle_cards[] = $cards[$key];
        }

        return $shuffle_cards;
    }

    /**
     * Méthode de création du paquet de cartes
     * @return array
     */
    public function setCards()
    {

        // On initialise le paquet de cartes
        $cards = [];

        // Si un utilisateur est existant on récupère son paquet
        if ($this->user) {
            $cards = $this->user->getCards();
        }

        // Si la collection de cartes n'a pas déjà été crée alors, on récupère les données en Base de données
        // et on construit la collection, sinon on retourne la collection déjà existante
        if (empty($cards)) {

            // Initialisation de l'id de pair de carte
            $pair_id = 0;

            // On boucle tant que le paquet de cartes
            while (count($cards) < self::PAIR_COUNT * 2) {

                // On incrémente l'id de pair de carte
                $pair_id++;

                // On ajoute 2 fois la même carte afin de créer des paires
                for ($i = 0; $i < 2; $i++) {

                    // Création d'une nouvelle carte
                    $card = [
                        'pair_id' => $pair_id,
                    ];

                    // on implémente et on ajoute une carte à la collection
                    $cards[] = $card;

                }

            }

            // Pour finir on mélange les cartes
            $cards = $this->shuffleCards($cards);
        }

        // On retourne le paquet
        return $cards;

    }

    /**
     * Vérifie le choix utilisateur
     * @param integer $index
     * @return array
     */
    public function checkCard($index)
    {
        // On initialise la variable qui contiendra le résultat retourné
        $result = [];

        // Si le données de jeux sont vide ont les initialise
        if (empty($this->game_data)) {
            $this->setGameData($this->user->getGameData());
        }

        // Si le choix de carte est le premier de la partie , alors le temps est réinitialisé,
        // et on mémorise la première action
        if (empty($this->game_data['first_pick'])) {
            $this->game_data['start'] = time();
            $this->game_data['timer'] = 0;
            $this->game_data['first_pick'] = true;
        }

        // Si les données de jeu ne sont vide et que la première a déjà été choisi
        if ( ! empty($this->game_data) and $this->game_data['first_pick']) {
            // On calcule le temps écoulé depuis le début de la partie
            $this->game_data['timer'] = time() - $this->game_data['start'];
        }

        // Si le temps de jeu a dépasser la limite la partie est perdu et le timer est égal à la limite
        if ($this->game_data['timer'] > self::GAME_TIME) {
            $this->game_data['timer'] = self::GAME_TIME;
            $result = $this->gameLost();
        } else {

            // On vérifie si la liste des cartes est vide
            if (empty($this->game_data['cards'])) {
                $this->game_data['cards'] = $this->setCards();
            }

            // On à jour les valeurs tu tableau de données donnant le résultat du choix utilisateur
            $result['card'] = $this->game_data['cards'][$index];
            $result['timer'] = $this->game_data['timer'];
            $result['last_card_index'] = $_SESSION['last_card_index'];

            // Si une précédente carte a été choisi on vérifie la concordance des 2 cartes
            if ( ! is_null($_SESSION['last_card_index'])) {

                if ($this->game_data['cards'][$index]['pair_id'] === $this->game_data['cards'][$_SESSION['last_card_index']]['pair_id']) {
                    // La paire séléctionné est valide
                    $result['state'] = 'success';

                    // On marque les deux carte comme faisant partie d'une paire découverte
                    $this->game_data['cards'][$index]['ok'] = 1;
                    $this->game_data['cards'][$_SESSION['last_card_index']]['ok'] = 1;

                    // on incrémente le nombre de paires trouvées
                    $this->game_data['pair_found']++;

                    // on vérifie si toutes les paires ont été trouvées
                    if ($this->game_data['pair_found'] == self::PAIR_COUNT) {
                        // si oui on termine la partie comme gagnée
                        $result = $this->gameWon() + $result;

                    }
                } else {
                    // La paire séléctionné est invalide
                    $result['state'] = 'error';
                }

                // on réinitialise le premier choix
                unset($_SESSION['last_card_index']);
            } else {
                // Premier choix de paire de cartes
                $result['state'] = 'first-pick';

                // On mémorise l'index de la dernière carte choisie
                $_SESSION['last_card_index'] = $index;
            }

            // on sauvegarde la partie
            $this->user->save($this->game_data);
        }

        // on retourne le résultat au javascript
        return $result;

    }

    /**
     * Récupère les scores
     * @return mixed
     */
    public function getScores()
    {
        // On retourne le liste des scores
        return $this->score->get();
    }

    /**
     * Récupère les cartes
     * @return mixed
     */
    public function getCards()
    {
        // On retourne l'état des cartes de la partie
        return $this->game_data['cards'];
    }

    /**
     * Récupère le temps de jeu permis
     * @return integer
     */
    public function getGameTime()
    {
        // On retourne la limite de temps de partie
        return self::GAME_TIME;
    }

    /**
     * Récupère le timer
     * @return mixed
     */
    public function getTimer()
    {
        // On retourne l'état actuel du timer
        return $this->game_data['timer'];
    }

    /**
     * Récupère le pourcentage de timer déjà écoulé
     * @return mixed
     */
    public function getTimerPercent()
    {
        // Si le timer n'est pas vide on retourne le pourcentage de temps écoulé par rapport au teps limite
        // Sinon on retourn 0%
        if ( ! empty($this->game_data['timer'])) {
            return round($this->game_data['timer'] / self::GAME_TIME * 100);
        } else {
            return 0;
        }
    }

    /**
     * Met à jour le tableau de données de jeu
     * @param array $game_data
     * @return Memory_game
     */
    private function setGameData($game_data)
    {
        // Si le variable est un tableau de données alors le met a jour le tableau de données du jeu
        if (is_array($game_data)) {
            $this->game_data = $game_data;
        }

        return $this;
    }

    /**
     * Défaite
     */
    private function gameLost()
    {
        // On marque le jeu comme perdu (champ binaire en BDD)
        $this->game_data['game_won'] = 0;

        // On signifie au javascript que l'état de la partie est perdu
        $result = ['state' => 'lost'];

        // On termine la partie
        return $this->gameFinish($result);
    }

    /**
     * Victoire
     */
    private function gameWon()
    {
        // On marque le jeu comme gagné (champ binaire en BDD)
        $this->game_data['game_won'] = 1;

        // On signifie au javascript que l'état de la partie est gagné
        $result = ['state' => 'won'];

        // On termine la partie
        return $this->gameFinish($result);
    }

    private function gameFinish($result)
    {

        // on sauve le résultat
        $this->score->save($this->user->getUUID(), $this->game_data);

        // on réinitialise le jeu
        $this->resetGame();

        // On sauvegarde le nouvel état de la partie pour l'utilisateur
        $this->user->save($this->game_data);

        // on retourne le résultat
        return $result;

    }

}