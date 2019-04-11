<?php

/**
 * Classe de surcharge du driver mysqli
 * @return object connected
 */
class My_mysqli extends mysqli
{

    /** @var array $config configuration */
    protected $config = [];

    /**
     * Constructor
     */
    public function __construct($config)
    {
        parent::__construct();
        $driver = new mysqli_driver();
        $driver->report_mode = MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR;
        $driver->reconnect = true;

        $this->config = $config;
        try {
            $this->connect(
                $this->config['host'],
                $this->config['user'],
                $this->config['pass'],
                $this->config['db']
            );
        } catch (mysqli_sql_exception $e) {
            die("Error while connecting to MySQL : ".$e->getMessage());
        }

        if ($this->connect_errno) {
            die(
                "Error while connecting to MySQL : (".$this->connect_errno.") ".$this->connect_error
            );
        }
    }

    /**
     * Execution d'une requête SQL et renvoi le résultat dans un tableau de données
     * @param string     $sql      requête SQL
     * @param string     $pk       Clé primaire mysql (identifiant unique)
     * @param bool|false $one      Si l'option est a "true" renvoi l'élémént courant du tableau de résultat
     * @return array|string
     */
    public function get($sql, $pk = null, $one = false)
    {
        $rows = [];
        try {
            // Exécute la requête
            $result = $this->query($sql);
        } catch (Exception $e) {
            // Si une erreur est survenue lors de l'execution de la requête on remonte une erreur
            die("Error while querying MySQL : ".$e->getMessage());
        }

        // Si le résultat de la requête n'est pas vide on le traite
        if ( ! empty($result)) {

            // On résupère toutes les lignes de résultat avec le nom des colonnes
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {

                // Si on a spécifié une clé primaire dans les paramètres
                // alors celle-ci est utilisé comme clé de tableau de données
                if ( ! is_null($pk)) {
                    $rows[$row[$pk]] = $row;
                } else {
                    $rows[] = $row;
                }
            }
        }

        // Si une seule ligne a été demandé dans les paramètres alors on renvoi uniquement la première ligne
        if ($one and is_array($rows)) {
            $rows = current($rows);
        }

        // On retourne le tableau de données contenant les données du résultat de requête
        return $rows;
    }

}