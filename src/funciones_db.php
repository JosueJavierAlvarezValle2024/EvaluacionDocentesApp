<?php
// =========================================================
// MODELO: FUNCIONES DE BASE DE DATOS (MVC Ligero)
// =========================================================

// --- FUNCIONES DEL ADMINISTRADOR ---

function contarDocentes($conn) {
    return $conn->query("SELECT COUNT(*) FROM Docentes")->fetchColumn();
}

function contarEvaluaciones($conn) {
    return $conn->query("SELECT COUNT(*) FROM Evaluaciones")->fetchColumn();
}

function obtenerDatosGrafica($conn) {
    $sql = "
        SELECT c.NombreCarrera, 
               IFNULL(ROUND(AVG((e.P1_Claridad + e.P2_Aplicacion + e.P3_Dinamica + e.P4_Compromiso + e.P5_Respeto + 
                                 e.P6_Disposicion + e.P7_Participacion + e.P8_Programa + e.P9_Calificaciones + e.P10_Recomendacion)/10), 2), 0) as promedio 
        FROM Carreras c 
        LEFT JOIN Docentes d ON c.CarreraID = d.CarreraID 
        LEFT JOIN Evaluaciones e ON d.DocenteID = e.DocenteID 
        WHERE c.CarreraID != 99 
        GROUP BY c.NombreCarrera";
    return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// --- FUNCIONES DEL ALUMNO ---

function obtenerNombreCarrera($conn, $matricula) {
    $stmt = $conn->prepare("SELECT c.NombreCarrera FROM Alumnos a JOIN Carreras c ON a.CarreraID = c.CarreraID WHERE a.Matricula = :m");
    $stmt->execute(['m' => $matricula]);
    $res = $stmt->fetch();
    return $res['NombreCarrera'] ?? 'Carrera no asignada';
}

function obtenerDocentesParaEvaluar($conn, $carrera_id, $matricula) {
    // Consulta que trae a los docentes e identifica si ya fueron evaluados
    $stmt = $conn->prepare("
        SELECT d.*, 
               (SELECT COUNT(*) FROM Evaluaciones e 
                WHERE e.DocenteID = d.DocenteID AND e.Matricula = :m_eval) as YaEvaluado
        FROM Docentes d 
        WHERE d.CarreraID = :cid
    ");
    $stmt->execute([
        'cid' => $carrera_id,
        'm_eval' => $matricula
    ]);
    return $stmt->fetchAll();
}
?>