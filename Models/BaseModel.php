<?php
class BaseModel
{
    public $connection;
    public $host;
    public $username;
    public $password;
    public $db_name;
    public $charset;

    public function __construct($host, $username, $password, $db_name, $charset = 'utf8')
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->db_name = $db_name;
        $this->charset = $charset;

        // Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=' .$this->charset;
        // Set options
        $options = array(
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    		PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_PERSISTENT    => false 
        );
        // Create a new PDO instanace
        try{
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        }
        // Catch any errors
        catch(PDOException $e){
            throw new Exception($e->getMessage(), 1);
        }
    }
    
    public function get($query, $params = []) {
        $res = $this->connection->prepare($query);
        $res->execute($params);
    
        return $res->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function find($query, $params = []) {
        $res = $this->connection->prepare($query);
        $res->execute($params);
        
        return $res->fetch(PDO::FETCH_ASSOC);
    }
}
