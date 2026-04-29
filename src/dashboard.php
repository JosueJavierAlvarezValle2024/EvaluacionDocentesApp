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

// --- LÓGICA DE DISTRIBUCIÓN DE DATOS ---

if ($es_admin) {
    // =========================================================
    // CONSULTAS EXCLUSIVAS PARA EL ADMINISTRADOR
    // =========================================================
    // A. Conteo de Docentes
    $count_docs = $conn->query("SELECT COUNT(*) FROM Docentes")->fetchColumn();
    
    // B. Conteo de Evaluaciones
    $count_evals = $conn->query("SELECT COUNT(*) FROM Evaluaciones")->fetchColumn();
    
    // C. Datos para la Gráfica: Promedio por Carrera (excluyendo la carrera 99 del Admin)
    $sql_grafica = "
        SELECT c.NombreCarrera, 
               IFNULL(ROUND(AVG((e.P1_Claridad + e.P2_Aplicacion + e.P3_Dinamica + e.P4_Compromiso + e.P5_Respeto + 
                                 e.P6_Disposicion + e.P7_Participacion + e.P8_Programa + e.P9_Calificaciones + e.P10_Recomendacion)/10), 2), 0) as promedio 
        FROM Carreras c 
        LEFT JOIN Docentes d ON c.CarreraID = d.CarreraID 
        LEFT JOIN Evaluaciones e ON d.DocenteID = e.DocenteID 
        WHERE c.CarreraID != 99 
        GROUP BY c.NombreCarrera";
    $datos_grafica = $conn->query($sql_grafica)->fetchAll(PDO::FETCH_ASSOC);

} else {
    // =========================================================
    // CONSULTAS EXCLUSIVAS PARA EL ALUMNO (Tu código original)
    // =========================================================
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

<nav class="navbar navbar-dark shadow mb-4" style="background-color: #1B396A;">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">📊 SEVAL TecNM</a>
        <div class="text-white small">
            Usuario: <strong><?php echo $_SESSION['nombre']; ?></strong> | 
            <a href="logout.php" class="text-white ms-2 text-decoration-none">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    
    <h2 class="fw-bold mb-4" style="color: #1B396A;">
        <?php echo $es_admin ? "Panel de Inteligencia Académica" : "Mis Evaluaciones Pendientes"; ?>
    </h2>

    <?php if ($es_admin): ?>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center" style="border-radius: 15px;">
                    <div class="display-6 mb-2">👤</div>
                    <h3 class="fw-bold"><?php echo $count_docs; ?></h3>
                    <p class="text-muted mb-0">Docentes Registrados</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center" style="border-radius: 15px;">
                    <div class="display-6 mb-2">📑</div>
                    <h3 class="fw-bold"><?php echo $count_evals; ?></h3>
                    <p class="text-muted mb-0">Evaluaciones Totales</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 text-center" style="border-radius: 15px;">
                    <div class="display-6 mb-2">🏁</div>
                    <a href="admin_resultados.php" class="btn btn-warning fw-bold w-100 mt-2 shadow-sm">Ver Reportes y Exportar</a>
                    <p class="text-muted mt-2 small">Análisis detallado</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px;">
                    <h5 class="fw-bold mb-4">📈 Promedio de Satisfacción por Carrera (Escala 1-5)</h5>
                    <canvas id="graficaCarreras" style="max-height: 350px;"></canvas>
                </div>
            </div>
        </div>

        <script>
            const ctx = document.getElementById('graficaCarreras').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($datos_grafica, 'NombreCarrera')); ?>,
                    datasets: [{
                        label: 'Calificación Promedio',
                        data: <?php echo json_encode(array_column($datos_grafica, 'promedio')); ?>,
                        backgroundColor: '#1B396A',
                        borderColor: '#3b82f6',
                        borderWidth: 1,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: { 
                        y: { 
                            beginAtZero: true, 
                            max: 5,
                            ticks: { stepSize: 1 }
                        } 
                    }
                }
            });
        </script>

    <?php else: ?>
        <p class="text-muted mb-4">Carrera: <strong><?php echo $carrera_nombre; ?></strong></p>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($docentes as $doc): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm text-center p-4 border-0" style="background-color: #0F172A; color: white; border-radius: 15px;">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($doc['NombreCompleto']); ?>&background=1B396A&color=fff" class="rounded-circle mx-auto mb-3" width="80">
                        <h5 class="fw-bold"><?php echo htmlspecialchars($doc['NombreCompleto']); ?></h5>
                        <p class="small text-info">Docente de Carrera</p>
                        <a href="evaluacion.php?docente_id=<?php echo $doc['DocenteID']; ?>" class="btn btn-primary btn-sm w-100 mt-auto">Evaluar Docente</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>