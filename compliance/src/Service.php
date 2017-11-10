<?php
namespace Compliance;

use Exception;

class Service
{
    /**
     * Config Data
     * @var array
     */
    protected $config;

    /**
     * @var \PDO
     */
    protected $dbh;

    /**
     * @var \PDOStatement
     */
    protected $select;

    /**
     * @var \PDOStatement
     */
    protected $insert;

    /**
     * @var \PDOStatement
     */
    protected $update;

    /**
     * @var \PDOStatement
     */
    protected $mark;

    /**
     * @var \PDOStatement
     */
    protected $found;

    /**
     * @var \PDOStatement
     */
    protected $stale;

    public function __construct($config)
    {
        $this->config = $config;
        $this->dbh = new \PDO('mysql:host=' . $config['wakeup']['host'] . ';dbname=' . $config['compliance']['dbname'], $config['compliance']['dbuser'], $config['compliance']['dbpass']);

        $this->select = $this->dbh->prepare('SELECT * FROM `pages` WHERE `url` = ?');
        $this->insert = $this->dbh->prepare('INSERT INTO `pages` (`url`) VALUES (?)');
        $this->update = $this->dbh->prepare('UPDATE `pages` SET `updated`= :date WHERE `url` = :url');
        $this->mark   = $this->dbh->prepare('UPDATE `pages` SET `requested`=:date WHERE `url` = :url');
        $this->found  = $this->dbh->prepare('INSERT INTO `found` (`url`) VALUES (:url, :keyword, :date)');
        $this->stale  = $this->dbh->prepare('SELECT * FROM `pages` WHERE (`updated` < :updated OR `updated` IS NULL) AND (`requested` < :requested OR `requested` IS NULL)');
    }

    public function fetchStalePages($date)
    {
        if(!($date instanceof \DateTime)){
            $date = new \DateTime($date);
        }

        if(!$this->stale->execute([
            'updated' => $date->format("Y-m-d H:i:s"),
            'requested' => $date->format("Y-m-d H:i:s")
        ])){
            var_dump($this->stale->errorInfo());
        }

        return $this->stale->fetchAll();
    }

    public function fetchPageByUrl($url)
    {
        $this->select->execute([$url]);
        if($this->select->rowCount() == 0){
            return false;
        }

        return $this->select->fetch();
    }

    public function markRequested($url)
    {
        $date = date("Y-m-d H:i:s");
        $this->mark->execute([
            'url' => $url,
            'date' => $date
        ]);
    }

    public function updatePage($url, $keywords = [])
    {
        $date = date("Y-m-d H:i:s");

        foreach ($keywords as $keyword) {
            $this->found->execute([
                'url'     => $url,
                'keyword' => $keyword,
                'date'    => $date
            ]);
        }

        $this->update->execute([
            'url' => $url,
            'date' => $date
        ]);
    }

    public function addPage($url)
    {
        if($this->fetchPageByUrl($url)){
            return;
        }

        $this->insert->execute([$url]);
    }

    public function createTable()
    {
        $this->dbh->exec("DROP TABLE `pages`");

        $sql = "CREATE TABLE pages(
          `url` VARCHAR(255) PRIMARY KEY, 
          `updated` DATETIME,
          `requested` DATETIME
        );";

        if(0 !== $this->dbh->exec($sql)){
            error_log('could not create pages table');
            error_log(json_encode($this->dbh->errorInfo()));
            return;
        }

        $this->dbh->exec("DROP TABLE `found`");

        $sql = "CREATE table found(
          `id` INT( 11 ) AUTO_INCREMENT PRIMARY KEY, 
          `url` VARCHAR(255),
          `keyword` VARCHAR(20),
          `date` DATETIME
        );";

        if(0 !== $this->dbh->exec($sql)){
            error_log('could not create found table');
            error_log(json_encode($this->dbh->errorInfo()));
            return;
        }
        error_log('created database');
    }
}