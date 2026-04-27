<?php
session_start();
require 'conexion.php'; 

$matricula = $_POST['matricula'] ?? '';
$password = $_POST['password'] ?? '';

// 1. Buscamos al alumno en la base de datos
$stmt = $conn->prepare("SELECT * FROM Alumnos WHERE Matricula = :matricula");
$stmt->execute(['matricula' => $matricula]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Verificamos si existe el alumno y si la contraseña (Hash) coincide
if ($alumno && password_verify($password, $alumno['ContrasenaHash'])) {
    // Si todo es correcto, creamos las variables de sesión
    $_SESSION['matricula'] = $alumno['Matricula'];
    $_SESSION['carrera_id'] = $alumno['CarreraID'];
    
    // 👇 SOLUCIÓN: GUARDAMOS EL NOMBRE EN LA MOCHILA DE SESIÓN 👇
    $_SESSION['nombre'] = $alumno['NombreCompleto'] ?? $alumno['Nombre']; 
    
    // REDIRECCIÓN NATIVA DE PHP (Instantánea y sin fallos)
    header("Location: dashboard.php");
    exit;
}
// Si el código llega hasta aquí abajo, significa que el login falló.
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #F8FAFC; } 
    </style>
</head>
<body>

<script>
    // Lanzamos el modal elegante bloqueando la pantalla si falla
    Swal.fire({
        icon: 'error',
        title: 'Acceso Denegado',
        text: 'La matrícula o la contraseña son incorrectas. Por favor, verifica tus datos.',
        confirmButtonColor: '#1B396A', 
        confirmButtonText: 'Intentar de nuevo',
        allowOutsideClick: false,
        background: '#ffffff',
        customClass: {
            title: 'fs-4 fw-bold text-dark'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'index.php';
        }
    });
</script>

</body>
</html>