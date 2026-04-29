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

// Obtener el desglose de promedios por pregunta y los comentarios de un docente
function obtenerDetallesDocente($conn, $docente_id) {
    // 1. Promedios por cada una de las 10 preguntas
    $sql_promedios = "
        SELECT 
            AVG(P1_Claridad) as p1, AVG(P2_Aplicacion) as p2, AVG(P3_Dinamica) as p3, 
            AVG(P4_Compromiso) as p4, AVG(P5_Respeto) as p5, AVG(P6_Disposicion) as p6, 
            AVG(P7_Participacion) as p7, AVG(P8_Programa) as p8, AVG(P9_Calificaciones) as p9, 
            AVG(P10_Recomendacion) as p10 
        FROM Evaluaciones 
        WHERE DocenteID = :id";
    
    $stmt_p = $conn->prepare($sql_promedios);
    $stmt_p->execute(['id' => $docente_id]);
    $promedios = $stmt_p->fetch(PDO::FETCH_ASSOC);

    // 2. Comentarios de texto (omitimos los vacíos)
    $stmt_c = $conn->prepare("SELECT Comentarios FROM Evaluaciones WHERE DocenteID = :id AND Comentarios != ''");
    $stmt_c->execute(['id' => $docente_id]);
    $comentarios = $stmt_c->fetchAll(PDO::FETCH_COLUMN);

    return [
        'promedios' => $promedios,
        'comentarios' => $comentarios
    ];
}
?>