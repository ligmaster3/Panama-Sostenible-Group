<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = "Gestión de Servicios";
include '../../includes/header.php';
include '../../includes/database.php';

$database = new Database();
$conn = $database->getConnection();

// Acciones
$action = $_GET['action'] ?? '';
$service_id = $_GET['id'] ?? 0;

// Eliminar servicio
if($action == 'delete' && $service_id) {
    $stmt = $conn->prepare("UPDATE servicios SET disponibilidad = 0 WHERE id = :id");
    $stmt->bindParam(':id', $service_id);
    $stmt->execute();
    header('Location: gestion-servicios.php?message=deleted');
    exit;
}

// Activar servicio
if($action == 'activate' && $service_id) {
    $stmt = $conn->prepare("UPDATE servicios SET disponibilidad = 1 WHERE id = :id");
    $stmt->bindParam(':id', $service_id);
    $stmt->execute();
    header('Location: gestion-servicios.php?message=activated');
    exit;
}

// Obtener servicios
$stmt = $conn->prepare("
    SELECT s.*, p.nombre_empresa, c.nombre as categoria_nombre,
           COUNT(r.id) as total_reservas,
           AVG(r.total) as ingreso_promedio
    FROM servicios s
    JOIN proveedores p ON s.proveedor_id = p.id
    JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN reservas r ON s.id = r.servicio_id
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$stmt->execute();
$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-dashboard">
    <div class="admin-sidebar">
        <h2>Panel Admin</h2>
        <nav class="admin-nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="gestion-servicios.php" class="active">🛍️ Servicios</a>
            <a href="gestion-proveedores.php">👥 Proveedores</a>
            <a href="gestion-reservas.php">📅 Reservas</a>
            <a href="gestion-usuarios.php">👤 Usuarios</a>
            <a href="configuracion.php">⚙️ Configuración</a>
        </nav>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Gestión de Servicios</h1>
            <p>Administra todos los servicios de la plataforma</p>

            <div class="admin-actions">
                <a href="?action=add" class="btn btn-primary">➕ Agregar Servicio</a>
                <a href="exportar-servicios.php" class="btn btn-secondary">📊 Exportar CSV</a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="admin-filters">
            <input type="text" id="searchServices" placeholder="Buscar servicios...">
            <select id="filterCategory">
                <option value="">Todas las categorías</option>
                <option value="activo">Activos</option>
                <option value="inactivo">Inactivos</option>
            </select>
            <select id="filterStatus">
                <option value="">Todos los estados</option>
                <option value="disponible">Disponibles</option>
                <option value="nodisponible">No disponibles</option>
            </select>
        </div>

        <!-- Mensajes -->
        <?php if(isset($_GET['message'])): ?>
        <div class="alert alert-success">
            <?php
                switch($_GET['message']) {
                    case 'deleted': echo "Servicio eliminado correctamente"; break;
                    case 'activated': echo "Servicio activado correctamente"; break;
                    case 'added': echo "Servicio agregado correctamente"; break;
                    case 'updated': echo "Servicio actualizado correctamente"; break;
                }
                ?>
        </div>
        <?php endif; ?>

        <!-- Tabla de servicios -->
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Servicio</th>
                        <th>Proveedor</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Reservas</th>
                        <th>Ingresos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($servicios as $servicio): ?>
                    <tr>
                        <td>#<?= str_pad($servicio['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="service-cell">
                                <img src="<?= json_decode($servicio['imagenes'])[0] ?>" alt="<?= $servicio['titulo'] ?>"
                                    class="service-thumb">
                                <div class="service-info">
                                    <strong><?= $servicio['titulo'] ?></strong>
                                    <small><?= substr($servicio['descripcion'], 0, 50) ?>...</small>
                                </div>
                            </div>
                        </td>
                        <td><?= $servicio['nombre_empresa'] ?></td>
                        <td><?= $servicio['categoria_nombre'] ?></td>
                        <td>$<?= $servicio['precio'] ?></td>
                        <td><?= $servicio['total_reservas'] ?></td>
                        <td>$<?= number_format($servicio['ingreso_promedio'] ?? 0, 2) ?></td>
                        <td>
                            <span
                                class="status-badge <?= $servicio['disponibilidad'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $servicio['disponibilidad'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="editar-servicio.php?id=<?= $servicio['id'] ?>" class="btn-action btn-edit"
                                    title="Editar">
                                    ✏️
                                </a>

                                <?php if($servicio['disponibilidad']): ?>
                                <a href="?action=delete&id=<?= $servicio['id'] ?>" class="btn-action btn-delete"
                                    title="Desactivar"
                                    onclick="return confirm('¿Estás seguro de desactivar este servicio?')">
                                    ❌
                                </a>
                                <?php else: ?>
                                <a href="?action=activate&id=<?= $servicio['id'] ?>" class="btn-action btn-activate"
                                    title="Activar">
                                    ✅
                                </a>
                                <?php endif; ?>

                                <a href="../servicio-detalle.php?id=<?= $servicio['id'] ?>" class="btn-action btn-view"
                                    title="Ver" target="_blank">
                                    👁️
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Estadísticas -->
        <div class="admin-stats">
            <div class="stat-card">
                <h3>Total Servicios</h3>
                <span class="stat-number"><?= count($servicios) ?></span>
            </div>
            <div class="stat-card">
                <h3>Servicios Activos</h3>
                <span class="stat-number">
                    <?= count(array_filter($servicios, function($s) { return $s['disponibilidad']; })) ?>
                </span>
            </div>
            <div class="stat-card">
                <h3>Ingresos Totales</h3>
                <span class="stat-number">
                    $<?= number_format(array_sum(array_column($servicios, 'ingreso_promedio')), 2) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<script>
// Filtros en tiempo real
document.getElementById('searchServices').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.admin-table tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

document.getElementById('filterCategory').addEventListener('change', function(e) {
    filterTable();
});

document.getElementById('filterStatus').addEventListener('change', function(e) {
    filterTable();
});

function filterTable() {
    const category = document.getElementById('filterCategory').value;
    const status = document.getElementById('filterStatus').value;

    document.querySelectorAll('.admin-table tbody tr').forEach(row => {
        let show = true;

        if (category) {
            // Implementar filtro por categoría
        }

        if (status) {
            const isActive = row.querySelector('.status-badge').classList.contains('status-active');
            if (status === 'activo' && !isActive) show = false;
            if (status === 'inactivo' && isActive) show = false;
        }

        row.style.display = show ? '' : 'none';
    });
}
</script>

<?php include '../../includes/footer.php'; ?>