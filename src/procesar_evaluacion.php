<?php
session_start();
require 'conexion.php';

// =========================================================
// 🛡️ VALIDACIÓN DE SEGURIDAD CSRF
// =========================================================
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    // Si no hay token o no coincide, bloqueamos el proceso
    echo json_encode(['status' => 'error', 'message' => 'Bloqueo de Seguridad: Petición no autorizada (CSRF Token inválido).']);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['matricula'])) {
    $matricula = $_SESSION['matricula'];
    $docente_id = $_POST['docente_id'];
    $comentarios = $_POST['comentarios'];

    try {
        $sql = "INSERT INTO Evaluaciones (Matricula, DocenteID, P1_Claridad, P2_Aplicacion, P3_Dinamica, 
                P4_Compromiso, P5_Respeto, P6_Disposicion, P7_Participacion, P8_Programa, 
                P9_Calificaciones, P10_Recomendacion, Comentarios) 
                VALUES (:mat, :doc, :p1, :p2, :p3, :p4, :p5, :p6, :p7, :p8, :p9, :p10, :com)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'mat' => $matricula, 'doc' => $docente_id,
            'p1' => $_POST['P1_Claridad'], 'p2' => $_POST['P2_Aplicacion'],
            'p3' => $_POST['P3_Dinamica'], 'p4' => $_POST['P4_Compromiso'],
            'p5' => $_POST['P5_Respeto'], 'p6' => $_POST['P6_Disposicion'],
            'p7' => $_POST['P7_Participacion'], 'p8' => $_POST['P8_Programa'],
            'p9' => $_POST['P9_Calificaciones'], 'p10' => $_POST['P10_Recomendacion'],
            'com' => $comentarios
        ]);

        echo "<script>alert('¡Evaluación guardada con éxito!'); window.location.href='dashboard.php';</script>";

    } catch (PDOException $e) {
        // Aquí aplicamos el Plan de Pruebas: Si intenta repetir, saltará el error de UNIQUE
        if ($e->getCode() == 23000) {
            echo "<script>alert('Error: Ya has evaluado a este docente.'); window.location.href='dashboard.php';</script>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}