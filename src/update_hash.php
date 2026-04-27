<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'conexion.php'; 

$matricula = '123456';
$password_real = '12345';
// Generamos el hash perfecto
$hash_perfecto = password_hash($password_real, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("UPDATE Alumnos SET ContrasenaHash = :hash WHERE Matricula = :matricula");
    $stmt->execute(['hash' => $hash_perfecto, 'matricula' => $matricula]);
    echo "<h2>✅ Hash actualizado con éxito.</h2>";
    echo "<a href='index.php'>Regresar al Login</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>