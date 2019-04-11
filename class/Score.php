<?php

class Score
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
    private $table_name = 'memory_score';

    /**
     * Liste des scores
     * @var array $scores
     */
    private $scores = [];

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
     * Récupère la liste des scores
     * @return array
     */
    public function get()
    {
        if (empty($this->scores)) {
            if ( ! empty($_COOKIE['uuid'])) {
                // filtre sur les données entrantes avant insertion en base de données
                $uuid = $this->sql_db->real_escape_string($_COOKIE['uuid']);
                $sql_str = /** @lang sql */
                    <<<SQL
                        SELECT * 
                        FROM $this->table_name 
                        WHERE uuid = '$uuid'
                        ORDER BY score_game_won DESC, score_elapsed_time, score_pair_found desc
                        LIMIT 5
SQL;
                $this->scores = $this->sql_db->get($sql_str);
            }
        }

        return $this->scores;

    }

    /**
     * Sauvergarde le score en base de données
     * @param $uuid
     * @param $elapsed_time
     * @return bool|mysqli_result
     */
    public function save($uuid, $data)
    {
        // filtre sur les données entrantes avant insertion en base de données
        $uuid = $this->sql_db->real_escape_string($uuid);
        $game_won = intval($data['game_won']);
        $pair_found = intval($data['pair_found']);
        $pair_total = intval($data['pair_total']);
        $elapsed_time = intval($data['timer']);

        $sql_str = /** @lang sql */
            <<<SQL
            INSERT INTO $this->table_name 
            SET 
              uuid = '$uuid',
              score_game_won = $game_won,
              score_pair_found = $pair_found,
              score_pair_total = $pair_total,
              score_elapsed_time = $elapsed_time
SQL;

        $result = $this->sql_db->query($sql_str);

        return $result;
    }

}