<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct()
    {
        $this->host = getenv('ECA_DB_HOST') ?: '127.0.0.1';
        $this->db_name = getenv('ECA_DB_NAME') ?: 'YOUR_DB_NAME';
        $this->username = getenv('ECA_DB_USER') ?: 'YOUR_DB_USER';
        $this->password = getenv('ECA_DB_PASS') !== false ? (string) getenv('ECA_DB_PASS') : 'YOUR_DB_PASSWORD';
    }

    public function getConnection($failHard = true) {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            if ($failHard) {
                echo "Connection error: " . $exception->getMessage();
                exit;
            }
            error_log("Database connection error: " . $exception->getMessage());
            $this->conn = null;
        }

        return $this->conn;
    }
}
