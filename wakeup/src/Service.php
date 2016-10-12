<?php
namespace Wakeup;

class Service
{
    /**
     * Config Data
     * @var array
     */
    protected $config;

    protected $dbh;

    public function __construct($config)
    {
        $this->config = $config;
        $this->dbh = new \PDO('mysql:host=localhost;dbname=' . $config['wakeup']['dbname'], $config['wakeup']['dbuser'], $config['wakeup']['dbpass']);
        $this->insert = $this->dbh->prepare('INSERT INTO `requests` (`date`, `number`, `name`, `message`) VALUES (:date, :number, :name, :message)');
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

        error_log(json_encode($this->insert->errorInfo()));
    }

    public function fetchAllWakups()
    {
        return iterator_to_array($this->dbh->query('SELECT `date`, `number`, `message` FROM `requests`'));
    }

    public function createTable()
    {
        $sql = "CREATE table requests(
          `request_id` INT( 11 ) AUTO_INCREMENT PRIMARY KEY, 
          `date` DATETIME, 
          `number` VARCHAR(20), 
          `name` VARCHAR(32), 
          `message` VARCHAR(140)
        );";

        if(0 !== $this->dbh->exec($sql)){
            error_log('could not create database');
            return;
        }

        error_log('created database');
    }
}