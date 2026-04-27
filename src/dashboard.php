<?php
session_start();
require 'conexion.php';

// Verificamos que haya una sesión activa
if (!isset($_SESSION['matricula'])) {
    header("Location: index.php");
    exit;
}

$matricula = $_SESSION['matricula'];
$nombre_usuario = $_SESSION['nombre'];
$matricula_admin = '123456'; // Tu matrícula de Administrador
$es_admin = ($matricula === $matricula_admin);

// Si no es admin, obtenemos su carrera y sus docentes asignados
$docentes = [];
$carrera_nombre = "Departamento de Evaluación";

if (!$es_admin) {
    // 1. Obtener el nombre de la carrera del alumno
    $stmt_carrera = $conn->prepare("SELECT c.NombreCarrera FROM Alumnos a JOIN Carreras c ON a.CarreraID = c.CarreraID WHERE a.Matricula = :m");
    $stmt_carrera->execute(['m' => $matricula]);
    $res_carrera = $stmt_carrera->fetch();
    $carrera_nombre = $res_carrera['NombreCarrera'] ?? 'Carrera no asignada';

    // 2. Obtener los docentes de esa carrera
    $stmt_docentes = $conn->prepare("SELECT * FROM Docentes WHERE CarreraID = :cid");
    $stmt_docentes->execute(['cid' => $_SESSION['carrera_id']]);
    $docentes = $stmt_docentes->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEVAL | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #E2E8F0; min-height: 100vh; }
        .navbar-tecnm { background-color: #1B396A; }
        
        /* Estilo para las tarjetas (Modo Noche Institucional) */
        .card-custom { 
            background-color: #0F172A; 
            border: none; 
            border-radius: 15px; 
            transition: transform 0.3s ease;
            color: white;
        }
        .card-custom:hover { transform: translateY(-5px); }
        
        .btn-evaluar { 
            background-color: #1B396A; 
            color: white; 
            border: 1px solid #3b82f6;
        }
        .btn-evaluar:hover { background-color: #3b82f6; color: white; }
        
        .admin-box {
            background: white;
            border-radius: 20px;
            border-left: 8px solid #f59e0b; /* Amarillo advertencia */
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-tecnm shadow mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="logo_tecnm.png" alt="Logo" height="30" class="me-2" style="filter: brightness(0) invert(1);">
            SEVAL - TecNM
        </a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3 d-none d-md-inline">Sesión: <strong><?php echo $nombre_usuario; ?></strong></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold" style="color: #1B396A;">
                <?php echo $es_admin ? "Panel de Control Administrativo" : "Mis Evaluaciones Pendientes"; ?>
            </h2>
            <p class="text-muted">
                <?php echo $es_admin ? "Área de Gestión Académica" : "Carrera: <strong>$carrera_nombre</strong>"; ?>
            </p>
        </div>
        
        <?php if ($es_admin): ?>
        <div class="col-md-4 text-md-end">
            <a href="admin_resultados.php" class="btn btn-warning btn-lg shadow fw-bold">
                📊 Ver Reportes Directivos
            </a>
        </div>
        <?php endif; ?>
    </div>

    <hr class="mb-5">

    <?php if ($es_admin): ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="admin-box p-5 shadow-sm text-center">
                    <div class="display-1 mb-4">⚙️</div>
                    <h3 class="fw-bold text-dark">Modo Administrador Activo</h3>
                    <p class="text-secondary fs-5">
                        Usted ha ingresado con privilegios de supervisión. En este apartado no se muestran listas de evaluación estudiantil. 
                        Por favor, utilice el botón superior para acceder al análisis de resultados generales.
                    </p>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if (count($docentes) > 0): ?>
                <?php foreach ($docentes as $doc): ?>
                <div class="col">
                    <div class="card card-custom h-100 shadow">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($doc['NombreCompleto']); ?>&background=1B396A&color=fff&size=128" 
                                     class="rounded-circle shadow-sm" alt="Docente">
                            </div>
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($doc['NombreCompleto']); ?></h5>
                            <p class="text-info small mb-4">Docente Titular</p>
                            
                            <a href="evaluacion.php?docente_id=<?php echo $doc['DocenteID']; ?>" 
                               class="btn btn-evaluar w-100 py-2 fw-bold">
                                Iniciar Evaluación
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h4 class="text-muted">No tienes docentes asignados para evaluar en este momento.</h4>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>