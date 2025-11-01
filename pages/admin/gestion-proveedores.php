<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = "Gestión de Proveedores";
include '../../includes/header.php';
include '../../includes/database.php';

$database = new Database();
$conn = $database->getConnection();

// Acciones
$action = $_GET['action'] ?? '';
$proveedor_id = $_GET['id'] ?? 0;

// Verificar proveedor
if($action == 'verify' && $proveedor_id) {
    $stmt = $conn->prepare("UPDATE proveedores SET verificado = 1 WHERE id = :id");
    $stmt->bindParam(':id', $proveedor_id);
    $stmt->execute();
    header('Location: gestion-proveedores.php?message=verified');
    exit;
}

// Suspender proveedor
if($action == 'suspend' && $proveedor_id) {
    $stmt = $conn->prepare("UPDATE proveedores SET verificado = 0 WHERE id = :id");
    $stmt->bindParam(':id', $proveedor_id);
    $stmt->execute();
    header('Location: gestion-proveedores.php?message=suspended');
    exit;
}

// Obtener proveedores
$stmt = $conn->prepare("
    SELECT p.*, c.nombre as categoria_nombre,
           COUNT(s.id) as total_servicios,
           COUNT(r.id) as total_reservas,
           AVG(r.total) as ingreso_total
    FROM proveedores p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN servicios s ON p.id = s.proveedor_id
    LEFT JOIN reservas r ON s.id = r.servicio_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-dashboard">
    <div class="admin-sidebar">
        <h2>Panel Admin</h2>
        <nav class="admin-nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="gestion-servicios.php">🛍️ Servicios</a>
            <a href="gestion-proveedores.php" class="active">👥 Proveedores</a>
            <a href="gestion-reservas.php">📅 Reservas</a>
            <a href="gestion-usuarios.php">👤 Usuarios</a>
            <a href="configuracion.php">⚙️ Configuración</a>
        </nav>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Gestión de Proveedores</h1>
            <p>Administra todos los proveedores de la plataforma</p>

            <div class="admin-actions">
                <a href="?action=add" class="btn btn-primary">➕ Agregar Proveedor</a>
                <a href="exportar-proveedores.php" class="btn btn-secondary">📊 Exportar CSV</a>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="admin-stats">
            <div class="stat-card">
                <h3>Total Proveedores</h3>
                <span class="stat-number"><?= count($proveedores) ?></span>
            </div>
            <div class="stat-card">
                <h3>Verificados</h3>
                <span class="stat-number">
                    <?= count(array_filter($proveedores, function($p) { return $p['verificado']; })) ?>
                </span>
            </div>
            <div class="stat-card">
                <h3>Por Verificar</h3>
                <span class="stat-number">
                    <?= count(array_filter($proveedores, function($p) { return !$p['verificado']; })) ?>
                </span>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if(isset($_GET['message'])): ?>
        <div class="alert alert-success">
            <?php
                switch($_GET['message']) {
                    case 'verified': echo "Proveedor verificado correctamente"; break;
                    case 'suspended': echo "Proveedor suspendido correctamente"; break;
                    case 'added': echo "Proveedor agregado correctamente"; break;
                }
                ?>
        </div>
        <?php endif; ?>

        <!-- Tabla de proveedores -->
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Proveedor</th>
                        <th>Contacto</th>
                        <th>Categoría</th>
                        <th>Servicios</th>
                        <th>Reservas</th>
                        <th>Ingresos</th>
                        <th>Estado</th>
                        <th>Sostenible</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($proveedores as $proveedor): ?>
                    <tr>
                        <td>#<?= str_pad($proveedor['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="proveedor-cell">
                                <div class="proveedor-avatar">
                                    <?= substr($proveedor['nombre_empresa'], 0, 2) ?>
                                </div>
                                <div class="proveedor-info">
                                    <strong><?= $proveedor['nombre_empresa'] ?></strong>
                                    <small><?= $proveedor['ubicacion'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="contacto-info">
                                <div>📧 <?= $proveedor['email'] ?></div>
                                <div>📞 <?= $proveedor['telefono'] ?></div>
                            </div>
                        </td>
                        <td><?= $proveedor['categoria_nombre'] ?></td>
                        <td><?= $proveedor['total_servicios'] ?></td>
                        <td><?= $proveedor['total_reservas'] ?></td>
                        <td>$<?= number_format($proveedor['ingreso_total'] ?? 0, 2) ?></td>
                        <td>
                            <span
                                class="status-badge <?= $proveedor['verificado'] ? 'status-active' : 'status-pending' ?>">
                                <?= $proveedor['verificado'] ? 'Verificado' : 'Por verificar' ?>
                            </span>
                        </td>
                        <td>
                            <?php if($proveedor['sostenible']): ?>
                            <span class="badge-sostenible">♻️ Sostenible</span>
                            <?php else: ?>
                            <span class="badge-no-sostenible">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="editar-proveedor.php?id=<?= $proveedor['id'] ?>" class="btn-action btn-edit"
                                    title="Editar">
                                    ✏️
                                </a>

                                <?php if(!$proveedor['verificado']): ?>
                                <a href="?action=verify&id=<?= $proveedor['id'] ?>" class="btn-action btn-verify"
                                    title="Verificar" onclick="return confirm('¿Verificar este proveedor?')">
                                    ✅
                                </a>
                                <?php else: ?>
                                <a href="?action=suspend&id=<?= $proveedor['id'] ?>" class="btn-action btn-suspend"
                                    title="Suspender" onclick="return confirm('¿Suspender este proveedor?')">
                                    ⚠️
                                </a>
                                <?php endif; ?>

                                <a href="../proveedor-detalle.php?id=<?= $proveedor['id'] ?>"
                                    class="btn-action btn-view" title="Ver perfil" target="_blank">
                                    👁️
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>