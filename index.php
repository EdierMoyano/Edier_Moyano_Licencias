<?php
session_start();

// Redirigir si ya está logueado
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] == 1) {
        header('Location: super_admin/dashboard.php');
    } else {
        header('Location: general/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Licencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h1 class="mb-4">Sistema de Gestión de Licencias</h1>
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="mb-4">Seleccione su tipo de acceso</h2>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h3>Administrador</h3>
                                        <p>Acceso para administradores de empresas</p>
                                        <a href="general/index.php" class="btn btn-primary">Acceder</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h3>Super Administrador</h3>
                                        <p>Acceso para super administradores del sistema</p>
                                        <a href="super_admin/index.php" class="btn btn-danger">Acceder</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>