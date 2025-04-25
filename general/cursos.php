<?php
session_start();

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 2) {
    header('Location: ../index.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion.php';

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';

// Verificar si se solicita eliminar un curso
if (isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        // Verificar que el curso pertenezca al usuario actual
        $stmt = $conn->prepare("SELECT COUNT(*) FROM cursos WHERE id = ? AND id_creador = ?");
        $stmt->execute([$id_eliminar, $_SESSION['user_id']]);
        
        if ($stmt->fetchColumn() > 0) {
            // Primero eliminar las asistencias asociadas
            $stmt = $conn->prepare("DELETE FROM asistencia WHERE curso_id = ?");
            $stmt->execute([$id_eliminar]);
            
            // Luego eliminar el curso
            $stmt = $conn->prepare("DELETE FROM cursos WHERE id = ?");
            $stmt->execute([$id_eliminar]);
            
            $mensaje = "Curso eliminado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "No tiene permisos para eliminar este curso.";
            $tipo_mensaje = "danger";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar el curso: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener listado de cursos
try {
    $db = new Database();
    $conn = $db->connect();
    
    // Filtros
    $where = ["id_creador = :user_id"];
    $params = [':user_id' => $_SESSION['user_id']];
    
    
    
    
    if (isset($_GET['estado']) && !empty($_GET['estado'])) {
        $hoy = date('Y-m-d');
        
        if ($_GET['estado'] == 'activo') {
            $where[] = "f_inicio <= :hoy AND f_fin >= :hoy";
            $params[':hoy'] = $hoy;
        } elseif ($_GET['estado'] == 'proximo') {
            $where[] = "f_inicio > :hoy";
            $params[':hoy'] = $hoy;
        } elseif ($_GET['estado'] == 'finalizado') {
            $where[] = "f_fin < :hoy";
            $params[':hoy'] = $hoy;
        }
    }
    
    // Búsqueda por nombre o lugar
    if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
        $busqueda = $_GET['buscar'];
        $where[] = "(nombre LIKE :busqueda OR lugar LIKE :busqueda)";
        $params[':busqueda'] = "%$busqueda%";
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Paginación
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $por_pagina = 10;
    $inicio = ($pagina - 1) * $por_pagina;
    
    // Contar total de registros
    $stmt = $conn->prepare("SELECT COUNT(*) FROM cursos $whereClause");
    $stmt->execute($params);
    $total_registros = $stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Obtener cursos para la página actual
    $query = "SELECT * FROM cursos $whereClause ORDER BY f_inicio DESC LIMIT $inicio, $por_pagina";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener los cursos: " . $e->getMessage();
    $tipo_mensaje = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cursos - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
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
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Cursos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="cursos_crear.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Nuevo Curso
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-funnel me-1"></i> Filtros y Búsqueda
                    </div>
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos</option>
                                    <option value="activo" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'activo') ? 'selected' : ''; ?>>Activos</option>
                                    <option value="proximo" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'proximo') ? 'selected' : ''; ?>>Próximos</option>
                                    <option value="finalizado" <?php echo (isset($_GET['estado']) && $_GET['estado'] == 'finalizado') ? 'selected' : ''; ?>>Finalizados</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="buscar" class="form-label">Buscar</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="buscar" name="buscar" placeholder="Buscar por nombre o lugar..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <?php if (isset($_GET['estado']) || isset($_GET['buscar'])): ?>
                                <a href="cursos.php" class="btn btn-secondary">Limpiar filtros</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table me-1"></i> Listado de Cursos
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Lugar</th>
                                        <th>Estado</th>
                                        <th>Asistentes</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($cursos) > 0): ?>
                                        <?php foreach ($cursos as $curso):
                                            // Determinar estado del curso 
                                            $hoy = date('Y-m-d');
                                            if ($curso['f_inicio'] > $hoy) {
                                                $estado = '<span class="badge bg-warning">Próximo</span>';
                                            } elseif ($curso['f_fin'] < $hoy) {
                                                $estado = '<span class="badge bg-secondary">Finalizado</span>';
                                            } else {
                                                $estado = '<span class="badge bg-success">Activo</span>';
                                            }
                                            
                                            // Contar asistentes
                                            $stmt = $conn->prepare("SELECT COUNT(DISTINCT documento) FROM asistencia WHERE curso_id = ?");
                                            $stmt->execute([$curso['id']]);
                                            $total_asistentes = $stmt->fetchColumn();
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($curso['nombre']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($curso['f_inicio'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($curso['f_fin'])); ?></td>
                                            <td><?php echo htmlspecialchars($curso['lugar']); ?></td>
                                            <td><?php echo $estado; ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $total_asistentes; ?></span>
                                            </td>
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
                                                    <button type="button" class="btn btn-sm btn-danger" title="Eliminar curso" 
                                                            onclick="confirmarEliminar(<?php echo $curso['id']; ?>, '<?php echo htmlspecialchars($curso['nombre']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No se encontraron cursos.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($total_paginas > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($pagina <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo isset($_GET['estado']) ? '&estado=' . $_GET['estado'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo ($pagina == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo isset($_GET['estado']) ? '&estado=' . $_GET['estado'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($pagina >= $total_paginas) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo isset($_GET['estado']) ? '&estado=' . $_GET['estado'] : ''; ?><?php echo isset($_GET['buscar']) ? '&buscar=' . urlencode($_GET['buscar']) : ''; ?>" aria-label="Siguiente">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="eliminarModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar el curso <strong id="nombreCurso"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer y eliminará todas las asistencias asociadas.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnEliminar" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarEliminar(id, nombre) {
            document.getElementById('nombreCurso').textContent = nombre;
            document.getElementById('btnEliminar').href = 'cursos.php?eliminar=' + id;
            
            var modal = new bootstrap.Modal(document.getElementById('eliminarModal'));
            modal.show();
        }
    </script>
</body>
</html>