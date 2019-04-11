<?php

class User
{
    /**
     * Ne peut être modifié que par la classe courante et ses enfants
     * @var My_mysqli $sql_db
     */
    protected $sql_db;

    /**
     * Nom de la table mysql
     * @var string $table_name
     */
    private $table_name = 'memory_user';

    /**
     * Tableau de données contenant les information de jeu de l'utilisateur
     * @var array $data
     */
    private $data;

    public function __construct()
    {
        // Initialisation du tableau de configuration
        $config = [];

        // Chargement des données de configurations de la base de données, ici MySQL
        require('config/db.php');
        $mysql_config = $config['mysql'];

        // On implémente notre driver mysqli surchargé en lui fournissant notre configuration
        // Surchargé le driver permet d'en simplifié l'usage
        require_once('class/My_mysqli.php');
        $this->sql_db = new My_mysqli($mysql_config);
    }

    /**
     * Génère un UUID v4
     * Version 4 UUIDs are pseudo-random.
     * @return string
     */
    private function uuid_v4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Récupère l'id utilisateur
     * @return string
     */
    public function getUUID(){
        return $this->data['uuid'];
    }

    /**
     * Récupère l'utilisateur courant ou en initialise un nouveau
     * @return array|string
     */
    public function getUser()
    {
        if ( ! empty($_COOKIE['uuid'])) {

            // filtre sur les données entrantes avant insertion en base de données
            $uuid = $this->sql_db->real_escape_string($_COOKIE['uuid']);
            $sql_str = /** @lang sql */
                <<<SQL
                SELECT * FROM `$this->table_name` where `uuid` = '$uuid'
SQL;
            $user = $this->sql_db->get($sql_str, null, true);
            if ( ! empty($user)) {
                // On retourne le dernier jeu enrefistré pour l'utilisateur courant
                $this->data = $user;
                // On décode le JSON du jeu
                $this->data['game_data'] = json_decode($this->data['user_current_game'], true);
                // On supprime la clé du jeu encodé en JSON
                unset($this->data['user_current_game']);
            } else {
                // On retourne un tableau de données pour l'utilisateur avec un jeu vide
                $this->data = [
                    'uuid' => $_COOKIE['uuid'],
                    'game_data' => null,
                ];
            }
        } else {
            // On retourne un tableau de données par défaut en cas de nouvel utilisateur avec un jeu vide
            $this->data = [
                'uuid' => $this->uuid_v4(),
                'game_data' => null,
            ];
        }

        return $this->data;

    }

    /**
     * Récupère le jeu sauvegardé de l'utilisateurs
     * @return mixed
     */
    public function getGameData()
    {
        return $this->data['game_data'];
    }

    /**
     * Récupère les cartes sauvegardées de l'utilisateurs
     * @return mixed
     */
    public function getCards()
    {
        return $this->data['game']['cards'];
    }

    /**
     * Sauvegarde l'utilisateur
     * @param string $uuid
     * @param array  $game
     * @return bool
     */
    public function save($game = [])
    {

        // On vérifie les données reçues
        if ( ! is_array($game)) {
            return false;
        }

        // On encode le tableau de jeu en JSON
        $game = json_encode($game);

        $uuid = $this->data['uuid'];

        // Requête SQL permettant d'ajouter un nouvel utilisateur ou de le mettre à jour s'il existe déjà
        $sql_str = /** @lang sql */
            <<<SQL
                INSERT INTO `$this->table_name` 
                (`user_current_game`,`uuid`)
                VALUES('$game','$uuid')
                ON DUPLICATE KEY UPDATE
                `user_current_game` = '$game'
SQL;
        if ($this->sql_db->query($sql_str)) {
            // date d'expiration du cookie à 30 jours
            $expire = time() + 60 * 60 * 24 * 30;

            // on stocke l'identifiant de l'utilisateur dans ses cookie et on renvoie le résultat
            return setcookie('uuid', $uuid, $expire, '/', $_SERVER['HTTP_HOST']);
        }

    }

}