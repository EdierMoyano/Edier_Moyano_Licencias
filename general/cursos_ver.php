<?php
session_start();

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 2) {
    header('Location: ../index.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion.php';

// Verificar si se proporcionó un ID de curso
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: cursos.php');
    exit;
}

$curso_id = (int)$_GET['id'];
$mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Obtener información del curso
    $stmt = $conn->prepare("SELECT * FROM cursos WHERE id = ? AND id_creador = ?");
    $stmt->execute([$curso_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() == 0) {
        // El curso no existe o no pertenece al usuario actual
        header('Location: cursos.php');
        exit;
    }
    
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas de asistencia
    $stmt = $conn->prepare("SELECT 
                           COUNT(*) as total_registros,
                           COUNT(DISTINCT documento) as total_asistentes
                           FROM asistencia 
                           WHERE curso_id = ?");
    $stmt->execute([$curso_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener las últimas 10 asistencias
    $stmt = $conn->prepare("SELECT * FROM asistencia 
                           WHERE curso_id = ? 
                           ORDER BY fecha_hora DESC 
                           LIMIT 10");
    $stmt->execute([$curso_id]);
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Curso - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
        }
        .curso-header {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            border-left: 4px solid;
            border-radius: 4px;
        }
        .stat-card.primary {
            border-left-color: #007bff;
        }
        .stat-card.success {
            border-left-color: #28a745;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building"></i> Panel Administrador
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cursos.php">
                            <i class="bi bi-book"></i> Cursos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="asistencias.php">
                            <i class="bi bi-calendar-check"></i> Asistencias
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="cursos.php">
                                <i class="bi bi-book"></i> Cursos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="asistencias.php">
                                <i class="bi bi-calendar-check"></i> Asistencias
                            </a>
                        </li>
                        
                    </ul>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalles del Curso</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="cursos_editar.php?id=<?php echo $curso_id; ?>" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-pencil"></i> Editar Curso
                            </a>
                            <a href="asistencias_registrar.php?curso=<?php echo $curso_id; ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-calendar-check"></i> Registrar Asistencia
                            </a>
                        </div>
                        <a href="cursos.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <?php if ($mensaje == 'creado'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> El curso ha sido creado correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php elseif ($mensaje == 'actualizado'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> El curso ha sido actualizado correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Información del curso -->
                <div class="curso-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h2><?php echo htmlspecialchars($curso['nombre']); ?></h2>
                            <p class="text-muted">
                                <?php 
                                $hoy = date('Y-m-d');
                                if ($curso['f_inicio'] > $hoy) {
                                    echo '<span class="badge bg-warning">Próximo</span>';
                                } elseif ($curso['f_fin'] < $hoy) {
                                    echo '<span class="badge bg-secondary">Finalizado</span>';
                                } else {
                                    echo '<span class="badge bg-success">Activo</span>';
                                }
                                ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <p><strong>Creado por:</strong> <?php echo $_SESSION['user_name']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-info-circle me-1"></i> Información del Curso
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Descripción:</div>
                                    <div class="col-md-9">
                                        <?php echo !empty($curso['descripcion']) ? nl2br(htmlspecialchars($curso['descripcion'])) : '<em>Sin descripción</em>'; ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Fecha de Inicio:</div>
                                    <div class="col-md-9"><?php echo date('d/m/Y', strtotime($curso['f_inicio'])); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Fecha de Fin:</div>
                                    <div class="col-md-9"><?php echo date('d/m/Y', strtotime($curso['f_fin'])); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-