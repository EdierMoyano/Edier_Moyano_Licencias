<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 2) {
    header('Location: ../index.php');
    exit;
}

require_once '../conexion.php';
try {
    $db = new Database();
    $conn = $db->connect();
    
    $empresa_id = $_SESSION['user_empresa_id'];
    
    $query = "SELECT COUNT(*) FROM licencias 
            WHERE id_empresa = :empresa_id AND estado = 'Activa' 
        AND fecha_fin >= CURDATE()";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $_SESSION['licencia_expirada'] = true;
        $_SESSION['mensaje_licencia'] = "La licencia de su empresa ha expirado. Contacte con el proveedor.";
        header('Location: login.php'); 
        exit;
    }
    

    $query_stats = "SELECT 
                    COUNT(*) as total_cursos,
                    COUNT(CASE WHEN f_inicio <= CURDATE() AND f_fin >= CURDATE() THEN 1 END) as cursos_activos,
                    COUNT(CASE WHEN f_inicio > CURDATE() THEN 1 END) as cursos_futuros,
                    COUNT(CASE WHEN f_fin < CURDATE() THEN 1 END) as cursos_pasados
                    FROM cursos 
                    WHERE id_creador = :user_id";
    
    $stmt_stats = $conn->prepare($query_stats);
    $stmt_stats->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_STR);
    $stmt_stats->execute();
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    
    $query_cursos = "SELECT * FROM cursos 
                    WHERE id_creador = :user_id 
                    ORDER BY f_inicio DESC";
    
    $stmt_cursos = $conn->prepare($query_cursos);
    $stmt_cursos->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_STR);
    $stmt_cursos->execute();
    $cursos = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);
    
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
    <title>Dashboard Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
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
        .stat-card.warning {
            border-left-color: #ffc107;
        }
        .stat-card.danger {
            border-left-color: #dc3545;
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cursos.php">
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
            
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cursos.php">
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
            
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="cursos_crear.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Nuevo Curso
                        </a>
                    </div>
                </div>
                
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">Total Cursos</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['total_cursos']; ?></h2>
                                    </div>
                                    <div class="text-primary">
                                        <i class="bi bi-book fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">Cursos Activos</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['cursos_activos']; ?></h2>
                                    </div>
                                    <div class="text-success">
                                        <i class="bi bi-play-circle fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">Próximos Cursos</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['cursos_futuros']; ?></h2>
                                    </div>
                                    <div class="text-warning">
                                        <i class="bi bi-calendar-event fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title text-muted mb-0">Cursos Finalizados</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['cursos_pasados']; ?></h2>
                                    </div>
                                    <div class="text-danger">
                                        <i class="bi bi-check-circle fs-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
               
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-building me-1"></i> Información de la Empresa
                                </h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Empresa:</strong> <?php echo $_SESSION['user_empresa']; ?></p>
                                <p><strong>Usuario:</strong> <?php echo $_SESSION['user_name']; ?></p>
                                <p><strong>Estado:</strong> <span class="badge bg-success">Licencia Activa</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-info-circle me-1"></i> Acciones Rápidas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="cursos_crear.php" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Crear Nuevo Curso
                                    </a>
                                    <a href="asistencias.php" class="btn btn-success">
                                        <i class="bi bi-calendar-check me-1"></i> Registrar Asistencia
                                    </a>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-book me-1"></i> Mis Cursos Recientes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($cursos) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Lugar</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $contador = 0;
                                    foreach ($cursos as $curso): 
                                        if ($contador >= 5) break; 
                                        $contador++;
                                        
                                        
                                        $hoy = date('Y-m-d');
                                        if ($curso['f_inicio'] > $hoy) {
                                            $estado = '<span class="badge bg-warning">Próximo</span>';
                                        } elseif ($curso['f_fin'] < $hoy) {
                                            $estado = '<span class="badge bg-secondary">Finalizado</span>';
                                        } else {
                                            $estado = '<span class="badge bg-success">Activo</span>';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($curso['nombre']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($curso['f_inicio'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($curso['f_fin'])); ?></td>
                                        <td><?php echo htmlspecialchars($curso['lugar']); ?></td>
                                        <td><?php echo $estado; ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="cursos_ver.php?id=<?php echo $curso['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="cursos_editar.php?id=<?php echo $curso['id']; ?>" class="btn btn-sm btn-warning" title="Editar curso">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="asistencias_registrar.php?curso=<?php echo $curso['id']; ?>" class="btn btn-sm btn-success" title="Registrar asistencia">
                                                    <i class="bi bi-calendar-check"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($cursos) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="cursos.php" class="btn btn-outline-primary">Ver todos los cursos</a>
                            </div>
                        <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i> No ha creado ningún curso todavía.
                                <a href="cursos_crear.php" class="alert-link">Crear mi primer curso</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>