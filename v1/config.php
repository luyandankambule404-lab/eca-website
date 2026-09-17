<?php
require_once __DIR__ . '/includes/env.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct()
    {
        $this->host = eca_env('ECA_DB_HOST', '127.0.0.1');
        $this->db_name = eca_env('ECA_DB_NAME', 'eca_local');
        $this->username = eca_env('ECA_DB_USER', 'root');
        $this->password = eca_env('ECA_DB_PASS', '');
    }

    public function getConnection($failHard = true) {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_TIMEOUT => 2,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]
            );
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            $this->conn = null;
            if ($failHard) {
                if (!function_exists('eca_server_error')) {
                    require_once __DIR__ . '/includes/http.php';
                }
                eca_server_error('The local database is not available.');
            }
        }

        return $this->conn;
    }
}
?>
