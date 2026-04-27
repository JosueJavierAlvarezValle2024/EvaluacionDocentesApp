<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecNM | Portal de Evaluación Docente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; height: 100vh; display: flex; flex-direction: column; }
        .tecnm-header { background-color: #fff; padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .tecnm-logo { height: 60px; }
        .main-content { flex: 1; display: flex; align-items: center; justify-content: center; }
        .login-card { border: none; border-radius: 15px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); background: #fff; }
        .btn-tecnm { background-color: #1B396A; color: white; border: none; } /* Azul TecNM */
        .btn-tecnm:hover { background-color: #13284a; color: white; }
    </style>
    
</head>
<body>

<header class="tecnm-header text-center">
    <div class="container">
        <img src="logo_tecnm.png" alt="Logo TecNM" class="tecnm-logo mb-2">
        <h5 class="text-muted">Tecnológico Nacional de México</h5>
    </div>
</header>

<div class="main-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card p-5">
                    <div class="card-body">
                        <h3 class="text-center mb-1 fw-bold" style="color: #1B396A;">Bienvenido</h3>
                        <p class="text-center text-muted mb-4">Sistema de Evaluación Docente</p>
                        
                        <form action="login_process.php" method="POST">
                            <div class="mb-3">
                                <label for="matricula" class="form-label text-secondary">Matrícula Estudiantil</label>
                                <input type="text" class="form-control form-control-lg" id="matricula" name="matricula" placeholder="Ej. 19280345" required autofocus style="border-radius: 10px;">
                            </div>
                            
                            <div class="mb-4">
                                <label for="contrasena" class="form-label text-secondary">Contraseña</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="••••••••" required style="border-radius: 10px;">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-tecnm btn-lg" style="border-radius: 10px;">Iniciar Sesión</button>
                            </div>
                        </form>
                    </div>
                </div>
                <p class="text-center text-muted mt-4 small">© 2023 TecNM - Fase de Pruebas</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>