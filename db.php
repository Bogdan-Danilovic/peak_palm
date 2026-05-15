<?php
/**
 * db.php - Konfiguracioni fajl za povezivanje sa MySQL bazom podataka.
 * Koristimo PDO (PHP Data Objects) jer je sigurniji i moderniji od starijih metoda.
 */

$host = 'localhost';
$db   = 'peak_palm';
$user = 'root';
$pass = ''; // Default XAMPP lozinka je prazna
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Pokušaj povezivanja
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Ako konekcija ne uspe, prikaži grešku (pomoći će nam pri testiranju)
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

/**
 * Napomena: Ovaj fajl ćemo uključivati u svaki drugi PHP fajl 
 * kojem trebaju podaci iz baze koristeći: include 'db.php';
 */
?>