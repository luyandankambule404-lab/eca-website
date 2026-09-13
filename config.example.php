<?php

class Database {
    private $host = "127.0.0.1";
    private $db_name = "YOUR_DB_NAME";
    private $username = "YOUR_DB_USER";
    private $password = "YOUR_DB_PASSWORD";
    public $conn;

    public function getConnection($failHard = true) {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
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
