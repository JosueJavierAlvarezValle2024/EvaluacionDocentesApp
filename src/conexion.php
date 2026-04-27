<?php
$host = "db"; 
$port = "3306";
$dbname = "EvaluacionDocentesDB";
$username = "root";
$password = "Admin123!";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ya no ponemos ningún echo aquí. 
    // Si no hay error, PHP simplemente seguirá con el siguiente archivo.

} catch (PDOException $e) {
    // Solo mostramos mensaje si REALMENTE hay un error
    die("Error de conexión: " . $e->getMessage());
}
?>