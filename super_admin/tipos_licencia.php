<?php
session_start();

// Verificar si el usuario está logueado y es super admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}

// Incluir archivo de conexión
require_once '../conexion.php';

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';

// Procesar eliminación si se solicita
if (isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    $id_eliminar = $_GET['eliminar'];
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        // Verificar si el tipo de licencia tiene licencias asociadas
        $stmt = $conn->prepare("SELECT COUNT(*) FROM licencias WHERE id_tipo_licencia = ?");
        $stmt->execute([$id_eliminar]);
        $tiene_licencias = ($stmt->fetchColumn() > 0);
        
        if ($tiene_licencias) {
            $mensaje = "No se puede eliminar el tipo de licencia porque tiene licencias asociadas.";
            $tipo_mensaje = "danger";
        } else {
            // Eliminar el tipo de licencia
            $stmt = $conn->prepare("DELETE FROM tipo_licencia WHERE id = ?");
            $stmt->execute([$id_eliminar]);
            
            $mensaje = "Tipo de licencia eliminado correctamente.";
            $tipo_mensaje = "success";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar el tipo de licencia: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener listado de tipos de licencia
try {
    $db = new Database();
    $conn = $db->connect();
    
    // Búsqueda
    $busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
    $where = '';
    $params = [];
    
    if (!empty($busqueda)) {
        $where = "WHERE nombre LIKE ? OR descripcion LIKE ?";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    // Paginación
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $por_pagina = 10;
    $inicio = ($pagina - 1) * $por_pagina;
    
    // Contar total de registros
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tipo_licencia $where");
    $stmt->execute($params);
    $total_registros = $stmt->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Obtener tipos de licencia para la página actual
    $stmt = $conn->prepare("SELECT tl.*, 
                           (SELECT COUNT(*) FROM licencias WHERE id_tipo_licencia = tl.id) as total_licencias
                           FROM tipo_licencia tl
                           $where
                           ORDER BY tl.id DESC
                           LIMIT $inicio, $por_pagina");
    $stmt->execute($params);
    $tipos_licencia = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensaje = "Error al obtener los tipos de licencia: " . $e->getMessage();
    $tipo_mensaje = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tipos de Licencia - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-shield-lock"></i> Panel Super Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="empresas.php">Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="licencias.php">Licencias</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios.php">Usuarios</a>
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
                            <a class="nav-link" href="empresas.php">
                                <i class="bi bi-building"></i> Empresas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="licencias.php">
                                <i class="bi bi-key"></i> Licencias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="usuarios.php">
                                <i class="bi bi-people"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="tipos_licencia.php">
                                <i class="bi bi-tags"></i> Tipos de Licencia
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reportes.php">
                                <i class="bi bi-file-earmark-bar-graph"></i> Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="configuracion.php">
                                <i class="bi bi-gear"></i> Configuración
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Tipos de Licencia</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="tipos_licencia_crear.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Nuevo Tipo de Licencia
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Buscador -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="buscar" placeholder="Buscar por nombre o descripción..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <?php if (isset($_GET['buscar']) && !empty($_GET['buscar'])): ?>
                                <a href="tipos_licencia.php" class="btn btn-secondary">Limpiar filtros</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de tipos de licencia -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-table me-1"></i> Listado de Tipos de Licencia
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Duración (días)</th>
                                        <th>Licencias</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($tipos_licencia) > 0): ?>
                                        <?php foreach ($tipos_licencia as $tipo): ?>
                                        <tr>
                                            <td><?php echo $tipo['id']; ?></td>
                                            <td><?php echo htmlspecialchars($tipo['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($tipo['descripcion']); ?></td>
                                            <td><?php echo $tipo['duracion_dias']; ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $tipo['total_licencias']; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="tipos_licencia_editar.php?id=<?php echo $tipo['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <?php if ($tipo['total_licencias'] == 0): ?>
                                                    <a href="#" class="btn btn-sm btn-danger" title="Eliminar" 
                                                       onclick="confirmarEliminar(<?php echo $tipo['id']; ?>, '<?php echo htmlspecialchars($tipo['nombre']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                    <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" title="No se puede eliminar" disabled>
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No se encontraron tipos de licencia.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($pagina <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo ($pagina == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo ($pagina >= $total_paginas) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" aria-label="Siguiente">
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
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro de que desea eliminar el tipo de licencia <span id="nombreTipo"></span>?
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
            document.getElementById('nombreTipo').textContent = nombre;
            document.getElementById('btnEliminar').href = 'tipos_licencia.php?eliminar=' + id;
            
            var modal = new bootstrap.Modal(document.getElementById('eliminarModal'));
            modal.show();
        }
    </script>
</body>
</html>