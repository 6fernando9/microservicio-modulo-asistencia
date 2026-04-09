<?php

namespace App\Datos\Config;
use PDO;
use PDOException;

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host     = Secrets::dbHost();
        $this->port     = Secrets::dbPort();
        $this->db_name  = Secrets::dbName();
        $this->username = Secrets::dbUser();
        $this->password = Secrets::dbPass();
    }



    public function getConnectionPostgresDatabase() {
        $this->conn = null;
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name}";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            #agregamos mensaje de conexion exitosa
        } catch(PDOException $exception) {
            // En producción, es mejor loguear esto y no mostrarlo al usuario
            die("Error de conexión: " . $exception->getMessage());
        }
        return $this->conn;
    }
}