<?php
session_start();
require 'conexion.php'; 

// Corrección de las variables recibidas del POST
$matricula = $_POST['matricula'] ?? '';
$password = $_POST['password'] ?? '';


// 1. Buscamos al usuario en la base de datos
$stmt = $conn->prepare("SELECT * FROM Alumnos WHERE Matricula = :matricula");
$stmt->execute(['matricula' => $matricula]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Verificamos si existe y si la contraseña (Hash) coincide
if ($alumno && password_verify($password, $alumno['ContrasenaHash'])) {
    
    // Creamos las variables de sesión básicas
    $_SESSION['matricula'] = $alumno['Matricula'];
    $_SESSION['carrera_id'] = $alumno['CarreraID'];
    
    // Asignamos el nombre correcto (Identidad Dual)
    if ($alumno['Matricula'] === '123456') {
        $_SESSION['nombre'] = "Dirección Académica";
        
        // =========================================================
        // 🔒 MÓDULO DE AUDITORÍA: REGISTRO DE ACCESO DEL ADMIN
        // =========================================================
        try {
            $ip = $_SERVER['REMOTE_ADDR']; 
            $user_agent = $_SERVER['HTTP_USER_AGENT']; 
            
            $stmt_log = $conn->prepare("INSERT INTO AuditoriaAdmin (Matricula, IP_Acceso, Navegador) VALUES (:m, :ip, :ua)");
            $stmt_log->execute([
                'm'  => $alumno['Matricula'],
                'ip' => $ip,
                'ua' => $user_agent
            ]);
        } catch (PDOException $e) {
            // Si la tabla no existe o falla, el sistema lo ignora silenciosamente 
            // para no dejar al director fuera de su panel.
            error_log("Error al registrar auditoría: " . $e->getMessage());
        }
        // =========================================================

    } else {
        $_SESSION['nombre'] = $alumno['NombreCompleto'] ?? $alumno['Nombre'];
    }
    
    // REDIRECCIÓN NATIVA E INSTANTÁNEA
    header("Location: dashboard.php");
    exit;
}
// Si el código llega hasta aquí, significa que el login falló.
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