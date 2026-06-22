<?php 
namespace App; 
  
use PDO; 
use PDOException; 
  
final class Database
{
    private static ?PDO $pdo = null; 
  
    public static function get(): PDO 
    { 
        if (self::$pdo) return self::$pdo; 
  
        $dsn = sprintf( 
            'mysql:host=%s;port=%s;dbname=%s;charset=%s', 
            'reseau.proxy.rlwy.net', 
            '21110', 
            'books_api', 
            'utf8mb4' 
        ); 
        try { 
            self::$pdo = new PDO($dsn, 'root', 'OscdIdHhpPOPwpbIDNijqPDlzHWRnRCl', [ 
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
                PDO::ATTR_EMULATE_PREPARES   => false, 
            ]);
        } catch (PDOException $e) { 
            error_log('[DB] ' . $e->getMessage()); 
            throw new \RuntimeException('Database connection failed', 500, $e); 
        } 
        return self::$pdo; 
    } 
}
