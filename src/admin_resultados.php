<?php
session_start();
require 'conexion.php';

// 1. Candado de Seguridad
$matricula_admin = '123456';
if (!isset($_SESSION['matricula']) || $_SESSION['matricula'] !== $matricula_admin) {
    header("Location: dashboard.php");
    exit;
}

$filtro_carrera = $_GET['carrera_id'] ?? '';
$stmt_carreras = $conn->query("SELECT * FROM Carreras ORDER BY NombreCarrera ASC");
$carreras = $stmt_carreras->fetchAll(PDO::FETCH_ASSOC);

// 2. Consulta para la Tabla de Resultados
$sql_resumen = "
    SELECT 
        d.DocenteID,
        d.NombreCompleto AS Docente,
        c.NombreCarrera AS Carrera,
        COUNT(e.EvaluacionID) AS TotalEvaluaciones,
        IFNULL(ROUND(AVG((e.P1_Claridad + e.P2_Aplicacion + e.P3_Dinamica + e.P4_Compromiso + e.P5_Respeto + 
                          e.P6_Disposicion + e.P7_Participacion + e.P8_Programa + e.P9_Calificaciones + e.P10_Recomendacion) / 10), 2), 0) AS PromedioGlobal
    FROM Docentes d
    JOIN Carreras c ON d.CarreraID = c.CarreraID
    LEFT JOIN Evaluaciones e ON d.DocenteID = e.DocenteID
";

if ($filtro_carrera != '') {
    $sql_resumen .= " WHERE d.CarreraID = :carrera_id ";
}
$sql_resumen .= " GROUP BY d.DocenteID, d.NombreCompleto, c.NombreCarrera ORDER BY PromedioGlobal DESC";

$stmt = $conn->prepare($sql_resumen);
if ($filtro_carrera != '') $stmt->execute(['carrera_id' => $filtro_carrera]);
else $stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================================
// 🔒 NUEVO: OBTENER LOS ÚLTIMOS LOGS DE AUDITORÍA
// =========================================================
$stmt_audit = $conn->query("SELECT * FROM AuditoriaAdmin ORDER BY FechaAcceso DESC LIMIT 10");
$logs = $stmt_audit->fetchAll(PDO::FETCH_ASSOC);
// =========================================================
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TecNM | Reporte Directivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <style>
        body { background-color: #F8FAFC; }
        .navbar-tecnm { background-color: #1B396A; }
        .table-tecnm thead { background-color: #1B396A; color: white; }
        .filter-section { background: white; border-radius: 10px; padding: 20px; margin-bottom: 25px; }
        .btn-audit { background-color: #475569; color: white; }
        .btn-audit:hover { background-color: #334155; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-tecnm shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">📊 SEVAL - Panel Directivo</a>
        <div class="d-flex gap-2">
            <button class="btn btn-audit btn-sm" data-bs-toggle="modal" data-bs-target="#modalAuditoria">
                📜 Historial de Accesos
            </button>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">Regresar</a>
        </div>
    </div>
</nav>

<div class="container py-2">
    
    <div class="filter-section shadow-sm border">
        <form method="GET" class="row align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold text-secondary small">Filtrar por Carrera:</label>
                <select name="carrera_id" class="form-select">
                    <option value="">Todas las Carreras</option>
                    <?php foreach ($carreras as $c): ?>
                        <option value="<?php echo $c['CarreraID']; ?>" <?php echo ($filtro_carrera == $c['CarreraID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['NombreCarrera']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 fw-bold">🔍 Aplicar Filtro</button>
            </div>
            <div class="col-md-2">
                <a href="admin_resultados.php" class="btn btn-light w-100 border">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0" style="color: #1B396A;">Resultados Generales</h4>
        <div class="btn-group shadow-sm">
            <button onclick="exportarPDF()" class="btn btn-danger btn-sm">📄 PDF</button>
            <button onclick="exportarExcel()" class="btn btn-success btn-sm">📊 Excel</button>
            <button onclick="exportarXML()" class="btn btn-warning btn-sm text-dark">⚙️ XML</button>
            <button onclick="exportarTXT()" class="btn btn-secondary btn-sm">📝 TXT</button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 text-center align-middle table-tecnm">
                    <thead>
                        <tr>
                            <th>Docente</th>
                            <th>Carrera</th>
                            <th>Evaluaciones</th>
                            <th>Promedio Global</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $fila): ?>
                            <tr>
                                <td class="text-start fw-semibold px-3"><?php echo htmlspecialchars($fila['Docente']); ?></td>
                                <td><?php echo htmlspecialchars($fila['Carrera']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $fila['TotalEvaluaciones']; ?></span></td>
                                <td><strong>⭐ <?php echo $fila['PromedioGlobal']; ?></strong></td>
                                <td>
                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal_<?php echo $fila['DocenteID']; ?>">
                                        Detalles
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAuditoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">📜 Registro de Auditoría (Últimos 10 Accesos)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha y Hora</th>
                            <th>IP de Acceso</th>
                            <th>Navegador / Sistema</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if($logs): ?>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['AuditoriaID']; ?></td>
                                <td><strong><?php echo $log['FechaAcceso']; ?></strong></td>
                                <td><code><?php echo $log['IP_Acceso']; ?></code></td>
                                <td class="text-muted"><?php echo substr($log['Navegador'], 0, 50) . '...'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-3">No hay registros aún.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer bg-light">
                <p class="small text-muted me-auto mb-0">* Registros automáticos del servidor</p>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const datosLimpios = <?php echo json_encode(array_map(function($d){
    return ["Docente" => $d['Docente'], "Carrera" => $d['Carrera'], "Evaluaciones" => $d['TotalEvaluaciones'], "Promedio" => $d['PromedioGlobal']];
}, $resultados)); ?>;

function exportarExcel() {
    const worksheet = XLSX.utils.json_to_sheet(datosLimpios);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Resultados");
    XLSX.writeFile(workbook, "Reporte_SEVAL.xlsx");
}

function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text("Reporte Directivo SEVAL - TecNM", 14, 15);
    doc.autoTable({
        startY: 25,
        head: [['Docente', 'Carrera', 'Evaluaciones', 'Promedio']],
        body: datosLimpios.map(d => [d.Docente, d.Carrera, d.Evaluaciones, d.Promedio]),
        headStyles: { fillColor: [27, 57, 106] }
    });
    doc.save("Reporte_SEVAL.pdf");
}

function exportarTXT() {
    let txt = "REPORTE SEVAL\n";
    datosLimpios.forEach(d => txt += `${d.Docente} | ${d.Carrera} | Evals: ${d.Evaluaciones} | Prom: ${d.Promedio}\n`);
    const blob = new Blob([txt], { type: 'text/plain' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = "Reporte_SEVAL.txt";
    link.click();
}

function exportarXML() {
    let xml = '<' + '?xml version="1.0" encoding="UTF-8"?' + '>\n<ReporteSEVAL>\n';
    datosLimpios.forEach(d => {
        xml += `  <Docente>\n    <Nombre>${d.Docente}</Nombre>\n    <Promedio>${d.Promedio}</Promedio>\n  </Docente>\n`;
    });
    xml += '</ReporteSEVAL>';
    const blob = new Blob([xml], { type: 'application/xml' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = "Reporte_SEVAL.xml";
    link.click();
}
</script>

</body>
</html>