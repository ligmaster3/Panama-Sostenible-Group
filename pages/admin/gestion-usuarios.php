<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = "Gestión de Usuarios";
include '../../includes/header.php';
include '../../includes/database.php';

$database = new Database();
$conn = $database->getConnection();

// Acciones
$action = $_GET['action'] ?? '';
$usuario_id = $_GET['id'] ?? 0;

// Cambiar rol de usuario
if($action == 'make_admin' && $usuario_id) {
    $stmt = $conn->prepare("UPDATE usuarios SET rol = 'admin' WHERE id = :id");
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    header('Location: gestion-usuarios.php?message=admin_granted');
    exit;
}

if($action == 'make_user' && $usuario_id) {
    $stmt = $conn->prepare("UPDATE usuarios SET rol = 'user' WHERE id = :id");
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    header('Location: gestion-usuarios.php?message=admin_revoked');
    exit;
}

// Bloquear/Desbloquear usuario
if($action == 'block' && $usuario_id) {
    $stmt = $conn->prepare("UPDATE usuarios SET activo = 0 WHERE id = :id");
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    header('Location: gestion-usuarios.php?message=blocked');
    exit;
}

if($action == 'unblock' && $usuario_id) {
    $stmt = $conn->prepare("UPDATE usuarios SET activo = 1 WHERE id = :id");
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    header('Location: gestion-usuarios.php?message=unblocked');
    exit;
}

// Obtener usuarios
$stmt = $conn->prepare("
    SELECT u.*, 
           COUNT(DISTINCT r.id) as total_reservas,
           COUNT(DISTINCT p.id) as total_proveedores,
           COALESCE(SUM(r.total), 0) as total_gastado
    FROM usuarios u
    LEFT JOIN reservas r ON u.id = r.usuario_id
    LEFT JOIN proveedores p ON u.id = p.usuario_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-dashboard">
    <div class="admin-sidebar">
        <h2>Panel Admin</h2>
        <nav class="admin-nav">
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="gestion-servicios.php">🛍️ Servicios</a>
            <a href="gestion-proveedores.php">👥 Proveedores</a>
            <a href="gestion-reservas.php">📅 Reservas</a>
            <a href="gestion-usuarios.php" class="active">👤 Usuarios</a>
            <a href="configuracion.php">⚙️ Configuración</a>
        </nav>
    </div>

    <div class="admin-content">
        <div class="admin-header">
            <h1>Gestión de Usuarios</h1>
            <p>Administra todos los usuarios de la plataforma</p>

            <div class="admin-actions">
                <a href="exportar-usuarios.php" class="btn btn-secondary">📊 Exportar CSV</a>
                <a href="registro-usuario.php" class="btn btn-primary">➕ Agregar Usuario</a>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="admin-stats">
            <div class="stat-card">
                <h3>Total Usuarios</h3>
                <span class="stat-number"><?= count($usuarios) ?></span>
            </div>
            <div class="stat-card">
                <h3>Administradores</h3>
                <span class="stat-number">
                    <?= count(array_filter($usuarios, function($u) { return $u['rol'] == 'admin'; })) ?>
                </span>
            </div>
            <div class="stat-card">
                <h3>Usuarios Activos</h3>
                <span class="stat-number">
                    <?= count(array_filter($usuarios, function($u) { return $u['activo']; })) ?>
                </span>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if(isset($_GET['message'])): ?>
        <div class="alert alert-success">
            <?php
                switch($_GET['message']) {
                    case 'admin_granted': echo "Permisos de administrador concedidos"; break;
                    case 'admin_revoked': echo "Permisos de administrador revocados"; break;
                    case 'blocked': echo "Usuario bloqueado correctamente"; break;
                    case 'unblocked': echo "Usuario desbloqueado correctamente"; break;
                }
                ?>
        </div>
        <?php endif; ?>

        <!-- Tabla de usuarios -->
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Contacto</th>
                        <th>Rol</th>
                        <th>Reservas</th>
                        <th>Proveedores</th>
                        <th>Total Gastado</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $usuario): ?>
                    <tr>
                        <td>#<?= str_pad($usuario['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="usuario-cell">
                                <div class="usuario-avatar">
                                    <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                                </div>
                                <div class="usuario-info">
                                    <strong><?= $usuario['nombre'] ?></strong>
                                    <small>@<?= $usuario['username'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="contacto-info">
                                <div>📧 <?= $usuario['email'] ?></div>
                                <div>📞 <?= $usuario['telefono'] ?? 'No especificado' ?></div>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge <?= $usuario['rol'] == 'admin' ? 'role-admin' : 'role-user' ?>">
                                <?= ucfirst($usuario['rol']) ?>
                            </span>
                        </td>
                        <td><?= $usuario['total_reservas'] ?></td>
                        <td><?= $usuario['total_proveedores'] ?></td>
                        <td>$<?= number_format($usuario['total_gastado'], 2) ?></td>
                        <td>
                            <span class="status-badge <?= $usuario['activo'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $usuario['activo'] ? 'Activo' : 'Bloqueado' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($usuario['created_at'])) ?></td>
                        <td>
                            <div class="action-buttons">
                                <!-- Cambiar rol -->
                                <?php if($usuario['rol'] == 'user'): ?>
                                <a href="?action=make_admin&id=<?= $usuario['id'] ?>" class="btn-action btn-admin"
                                    title="Hacer administrador"
                                    onclick="return confirm('¿Dar permisos de administrador a este usuario?')">
                                    👑
                                </a>
                                <?php else: ?>
                                <a href="?action=make_user&id=<?= $usuario['id'] ?>" class="btn-action btn-user"
                                    title="Quitar administrador"
                                    onclick="return confirm('¿Quitar permisos de administrador a este usuario?')">
                                    👤
                                </a>
                                <?php endif; ?>

                                <!-- Bloquear/Desbloquear -->
                                <?php if($usuario['activo']): ?>
                                <a href="?action=block&id=<?= $usuario['id'] ?>" class="btn-action btn-block"
                                    title="Bloquear usuario" onclick="return confirm('¿Bloquear este usuario?')">
                                    🔒
                                </a>
                                <?php else: ?>
                                <a href="?action=unblock&id=<?= $usuario['id'] ?>" class="btn-action btn-unblock"
                                    title="Desbloquear usuario">
                                    🔓
                                </a>
                                <?php endif; ?>

                                <a href="editar-usuario.php?id=<?= $usuario['id'] ?>" class="btn-action btn-edit"
                                    title="Editar">
                                    ✏️
                                </a>

                                <a href="../perfil.php?id=<?= $usuario['id'] ?>" class="btn-action btn-view"
                                    title="Ver perfil" target="_blank">
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