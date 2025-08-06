<?php
// db.php - Database connection and helper functions

// ../includes/db.php
$host = 'localhost'; // Tên server
$dbname = 'com_nieu'; // Tên database
$username = 'root'; // Tên người dùng MySQL
$password = '08042005'; // Mật khẩu MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Ưu tiên sử dụng biến môi trường, sau đó đến hằng số từ config
        $host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
        $dbname = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'com_nieu');
        $username = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
        $password = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '08042005');
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'",
            PDO::ATTR_PERSISTENT => false
        ];
        
        try {
            $this->connection = new PDO($dsn, $username, $password, $options);
            
            // Thiết lập cấu hình bổ sung
            $this->connection->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->connection->exec("SET sql_mode = 'STRICT_ALL_TABLES'");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection error. Please try again later.");
        }
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // ==============================================
    // CÁC PHƯƠNG THỨC TRUY VẤN CƠ BẢN
    // ==============================================
    
    public function fetchAll($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->sanitizeParams($params));
        return $stmt->fetchAll();
    }
    
    public function fetchOne($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->sanitizeParams($params));
        return $stmt->fetch();
    }
    
    public function execute($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($this->sanitizeParams($params));
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // ==============================================
    // CÁC PHƯƠNG THỨC NÂNG CAO
    // ==============================================
    
    /**
     * Thực hiện transaction
     */
    public function transaction(callable $callback) {
        $this->connection->beginTransaction();
        try {
            $result = $callback($this);
            $this->connection->commit();
            return $result;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
    
    /**
     * Chèn nhiều bản ghi cùng lúc
     */
    public function insertBatch($table, array $data) {
        if (empty($data)) return 0;
        
        $columns = implode(', ', array_keys($data[0]));
        $placeholders = rtrim(str_repeat('?,', count($data[0])), ',');
        $values = [];
        
        foreach ($data as $row) {
            $values = array_merge($values, array_values($row));
        }
        
        $placeholderGroups = rtrim(str_repeat("($placeholders),", count($data)), ',');
        $sql = "INSERT INTO $table ($columns) VALUES $placeholderGroups";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($this->sanitizeParams($values));
        return $stmt->rowCount();
    }
    
    /**
     * Tạo điểm địa lý cho MySQL
     */
    public function preparePoint($latitude, $longitude) {
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }
        return $this->connection->quote("POINT($latitude $longitude)");
    }
    
    // ==============================================
    // TIỆN ÍCH BẢO MẬT
    // ==============================================
    
    /**
     * Làm sạch tham số truy vấn
     */
    private function sanitizeParams($params) {
        return array_map(function($param) {
            if (is_array($param)) {
                return array_map([$this, 'basicSanitize'], $param);
            }
            return $this->basicSanitize($param);
        }, $params);
    }
    
    private function basicSanitize($value) {
        if (is_numeric($value)) return $value;
        if ($value === null) return null;
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}