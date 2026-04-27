<?php
require 'conexion.php';

// La contraseña para todos será '12345'
$pass_hash = password_hash("12345", PASSWORD_DEFAULT);

$alumnos = [
    ['123456', 'Josue Alvarez', 3],       // Informática
    ['2021001', 'Ana Garcia', 1],         // Industrial
    ['2021002', 'Pedro Sanchez', 2],      // Mecánica
    ['2021003', 'Laura Martinez', 4],     // Gestión
    ['2021004', 'Javier Lopez', 5],       // Electrónica
    ['2021005', 'Carmen Ruiz', 6]         // Renovables
];

try {
    // 1. PRIMERO limpiamos las evaluaciones para que no haya registros "huérfanos"
    $conn->exec("DELETE FROM Evaluaciones");
    
    // 2. AHORA SÍ podemos limpiar la tabla de alumnos con seguridad
    $conn->exec("DELETE FROM Alumnos");

    // 3. Insertamos a todos los alumnos nuevos
    $stmt = $conn->prepare("INSERT INTO Alumnos (Matricula, NombreCompleto, ContrasenaHash, CarreraID) VALUES (?, ?, ?, ?)");

    foreach ($alumnos as $alumno) {
        $stmt->execute([$alumno[0], $alumno[1], $pass_hash, $alumno[2]]);
    }

    echo "<h3 style='color:green;'>✅ Sistema de Alumnos Restaurado con Éxito</h3>";
    echo "<p>Las restricciones de llaves foráneas se respetaron. Ya puedes entrar con cualquier matrícula de la lista (contraseña 12345).</p>";
    echo "<ul>
            <li><b>2021001</b> (Industrial)</li>
            <li><b>2021002</b> (Mecánica)</li>
            <li><b>123456</b> (Informática)</li>
          </ul>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>