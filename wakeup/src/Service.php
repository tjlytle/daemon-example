<?php
namespace Wakeup;

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
    protected $insert;

    /**
     * @var \PDOStatement
     */
    protected $active;

    public function __construct($config)
    {
        $this->config = $config;
        $this->dbh = new \PDO('mysql:host=localhost;dbname=' . $config['wakeup']['dbname'], $config['wakeup']['dbuser'], $config['wakeup']['dbpass']);
        $this->insert = $this->dbh->prepare('INSERT INTO `requests` (`date`, `number`, `name`, `message`) VALUES (:date, :number, :name, :message)');
        $this->active = $this->dbh->prepare('SELECT `request_id`, `date`, `number`, `message` FROM `requests` WHERE `date` < ? AND `queued` = 0');
        $this->queue  = $this->dbh->prepare('UPDATE `requests` SET `queued` = 1 WHERE `request_id` = ?');
    }

    public function addWakeup($date, $number, $name, $message = null)
    {
        if(!($date instanceof \DateTime)){
            $date = new \DateTime($date);
        }

        $number = filter_var($number, FILTER_SANITIZE_NUMBER_INT);

        if(!$message){
            $message = 'Hey %1s, this is your wakeup call!';
        }

        $message = sprintf($message, $name);

        $this->insert->bindValue(':date', $date->format("Y-m-d H:i:s"));
        $this->insert->bindValue(':number', $number);
        $this->insert->bindValue(':name', $name);
        $this->insert->bindValue(':message', $message);

        $this->insert->execute();
    }

    public function fetchActiveWakeup($date)
    {
        if(!($date instanceof \DateTime)){
            $date = new \DateTime($date);
        }

        if(!$this->active->execute([$date->format("Y-m-d H:i:s")])){
            error_log(json_encode($this->dbh->errorCode()));
            error_log(json_encode($this->active->errorInfo()));
            return [];
        }

        return $this->active->fetchAll();
    }

    public function markQueued($id)
    {
        $this->queue->execute([$id]);
    }

    public function fetchAllWakeups()
    {
        return iterator_to_array($this->dbh->query('SELECT `date`, `number`, `message` FROM `requests`'));
    }

    public function createTable()
    {
        $this->dbh->exec('DROP TABLE `requests`');

        $sql = "CREATE TABLE requests(
          `request_id` INT( 11 ) AUTO_INCREMENT PRIMARY KEY, 
          `date` DATETIME, 
          `number` VARCHAR(20), 
          `name` VARCHAR(32), 
          `message` VARCHAR(140),
          `queued` TINYINT(1) DEFAULT 0
        );";

        if(0 !== $this->dbh->exec($sql)){
            error_log('could not create database');
            error_log(json_encode($this->dbh->errorInfo()));
            return;
        }

        error_log('created database');
    }
}