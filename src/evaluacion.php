<?php
session_start();
require 'conexion.php';

// Generar Token CSRF si no existe en la sesión
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['matricula']) || !isset($_GET['docente_id'])) {
    header("Location: dashboard.php");
    exit;
}

$docente_id = $_GET['docente_id'];

// Obtener nombre del docente
$stmt = $conn->prepare("SELECT NombreCompleto FROM Docentes WHERE DocenteID = :id");
$stmt->execute(['id' => $docente_id]);
$docente = $stmt->fetch();
// --- NUEVO CANDADO DE SEGURIDAD ---
if (!$docente) {
    // Si el docente no existe, mandamos una alerta y regresamos al dashboard
    echo "<script>
            alert('Error de seguridad: El docente solicitado no existe en la base de datos.'); 
            window.location.href='dashboard.php';
          </script>";
    exit;
}
// -----------------------
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TecNM | Evaluación Docente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #E2E8F0; }
        .navbar-tecnm { background-color: #1B396A; }
        
        /* --- MODO OSCURO PARA LAS PREGUNTAS --- */
        .card-evaluacion { 
            background-color: #0F172A; 
            border: none; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.15); 
        }
        
        /* Forzar los textos a blanco */
        .question-text, .card-evaluacion h4, .card-evaluacion h5, .card-evaluacion p, .card-evaluacion strong { 
            color: #F8FAFC !important; 
        }
        
        /* Tarjeta especial de instrucciones */
        .instrucciones { 
            background-color: #1E293B; /* Un azul noche ligeramente más claro */
            border-left: 5px solid #3b71ca; 
        }
        
        /* Ajustes de las estrellas */
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2.3rem; color: #475569; cursor: pointer; transition: color 0.2s; }
        .star-rating input:checked ~ label { color: #ecc94b; }
        .star-rating label:hover, .star-rating label:hover ~ label { color: #ecc94b; }
        
        /* Ajustar la caja de comentarios (textarea) al modo oscuro */
        textarea.form-control {
            background-color: #1E293B;
            color: white;
            border: 1px solid #475569;
        }
        textarea.form-control::placeholder { color: #94A3B8; }
        textarea.form-control:focus { background-color: #0F172A; color: white; }

        .btn-tecnm { background-color: #1B396A; color: white; border-radius: 8px; font-weight: bold; }
        .btn-tecnm:hover { background-color: #13284a; color: white; }
    </style>
    
</head>
<body>

<nav class="navbar navbar-dark navbar-tecnm shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="logo_tecnm.png" alt="Logo" height="30" class="me-2" style="filter: brightness(0) invert(1);">
            Sistema de Evaluación
        </a>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">Regresar al Panel</a>
    </div>
</nav>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <div class="card card-evaluacion p-4 mb-4 instrucciones">
                <h5 class="fw-bold" style="color: #1B396A;">¿Cómo contestar?</h5>
                <p class="mb-0 text-dark">
                    Por favor, lee cada enunciado y selecciona el número de estrellas que mejor represente tu opinión, donde: <br>
                    <strong>1 estrella:</strong> Muy Insatisfecho | <strong>5 estrellas:</strong> Excelente / Muy Satisfecho.
                </p>
            </div>

            <div class="card card-evaluacion p-4 mb-4 text-center">
                <h4 class="fw-bold mb-1">Evaluación de Desempeño</h4>
                <p class="text-muted">Docente: <span class="text-primary fw-bold"><?php echo $docente['NombreCompleto']; ?></span></p>
            </div>

            <div class="card shadow-sm border-0 mb-4 p-3" style="border-radius: 15px;">
    <div class="d-flex justify-content-between small fw-bold mb-2" style="color: #1B396A;">
        <span>Progreso de la Evaluación</span>
        <span id="textoProgreso">0 / 10 Preguntas Respondidas</span>
    </div>
    <div class="progress" style="height: 12px; border-radius: 10px;">
        <div id="barraProgreso" class="progress-bar progress-bar-striped progress-bar-animated" 
             role="progressbar" style="width: 0%; background-color: #1B396A;" 
             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
</div>

            <form id="formEvaluacion" action="procesar_evaluacion.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="docente_id" value="<?php echo $docente_id; ?>">

                <?php
                $preguntas = [
                    "P1_Claridad" => "1. El docente explica los temas de manera clara y comprensible.",
                    "P2_Aplicacion" => "2. Relaciona la teoría con casos prácticos y reales.",
                    "P3_Dinamica" => "3. Utiliza herramientas tecnológicas y dinámicas en clase.",
                    "P4_Compromiso" => "4. Muestra puntualidad y compromiso con el curso.",
                    "P5_Respeto" => "5. Fomenta un ambiente de respeto y cordialidad.",
                    "P6_Disposicion" => "6. Muestra disposición para resolver dudas fuera de clase.",
                    "P7_Participacion" => "7. Promueve la participación de los alumnos.",
                    "P8_Programa" => "8. Cumple con el temario establecido al inicio.",
                    "P9_Calificaciones" => "9. Califica de manera objetiva y entrega resultados a tiempo.",
                    "P10_Recomendacion" => "10. ¿Recomendarías a este docente a otros estudiantes?"
                ];

                foreach ($preguntas as $key => $texto) {
                    echo "
                    <div class='card card-evaluacion mb-3 p-3'>
                        <span class='question-text'>$texto</span>
                        <div class='star-rating'>";
                    for ($i = 5; $i >= 1; $i--) {
                        echo "<input type='radio' id='{$key}_$i' name='$key' value='$i' required>";
                        echo "<label for='{$key}_$i'>★</label>";
                    }
                    echo "    </div>
                    </div>";
                }
                ?>

                <div class="card card-evaluacion mb-4 p-3">
                    <span class="question-text">Comentarios Adicionales</span>
                    <textarea name="comentarios" class="form-control" rows="3" placeholder="Opcional: Comparte tus sugerencias aquí..."></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-tecnm btn-lg shadow">Enviar Evaluación</button>
                    <a href="dashboard.php" class="btn btn-light border">Cancelar y Regresar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

// ==========================================
    // 🚀 LÓGICA DE LA BARRA DE PROGRESO
    // ==========================================
    const opciones = document.querySelectorAll('input[type="radio"]');
    const barraProgreso = document.getElementById('barraProgreso');
    const textoProgreso = document.getElementById('textoProgreso');

    opciones.forEach(opcion => {
        opcion.addEventListener('change', () => {
            // Contamos cuántas preguntas ya tienen respuesta
            const totalRespondidas = document.querySelectorAll('input[type="radio"]:checked').length;
            
            // Calculamos el porcentaje (como son 10 preguntas, cada una vale 10%)
            const porcentaje = (totalRespondidas / 10) * 100;
            
            // Animamos la barra y actualizamos el texto
            barraProgreso.style.width = porcentaje + '%';
            textoProgreso.innerText = totalRespondidas + ' / 10 Preguntas Respondidas';
            
            // Si llega a 10, la pintamos de verde (Éxito)
            if(totalRespondidas === 10) {
                barraProgreso.style.backgroundColor = '#198754'; // Verde Bootstrap
            } else {
                barraProgreso.style.backgroundColor = '#1B396A'; // Azul TecNM
            }
        });
    });
    // ==========================================


document.getElementById('formEvaluacion').addEventListener('submit', function(e) {
    // 1. Evitamos que la página haga la recarga tradicional
    e.preventDefault(); 

    // ==========================================
    // 🛡️ VALIDACIÓN DE PREGUNTAS COMPLETAS
    // ==========================================
    const totalRespondidas = document.querySelectorAll('input[type="radio"]:checked').length;
    
    if (totalRespondidas < 10) {
        Swal.fire({
            icon: 'warning',
            title: '¡Evaluación Incompleta!',
            text: 'Por favor, asegúrate de calificar las 10 preguntas antes de enviar.',
            confirmButtonColor: '#1B396A',
            confirmButtonText: 'Revisar'
        });
        return; // Esto detiene la ejecución y evita que se envíe en blanco
    }
    // ==========================================

    // 2. Recolectamos todas las respuestas (las estrellas)
    const formData = new FormData(this);

    // 3. Enviamos los datos a procesar_evaluacion.php en segundo plano
    fetch('procesar_evaluacion.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // 4. Mostramos el SweetAlert de éxito con los colores del TecNM
        Swal.fire({
            title: '¡Evaluación Completada!',
            text: 'Tus respuestas han sido guardadas de forma segura.',
            icon: 'success',
            confirmButtonColor: '#1B396A',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // 5. Redirigimos al Dashboard suavemente
                window.location.href = 'dashboard.php';
            }
        });
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'Hubo un problema al enviar la evaluación. Intenta de nuevo.',
            confirmButtonColor: '#1B396A'
        });
    });
});
</script>

</body>
</html>