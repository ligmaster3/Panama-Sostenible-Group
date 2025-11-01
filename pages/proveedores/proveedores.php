<?php
$pageTitle = "Proveedores Sostenibles";
include '../../includes/header.php';
include '../../includes/database.php';
include '../../includes/functions.php';
include '../../includes/auth.php';

$database = new Database();
$conn = $database->getConnection();

// Obtener parámetros de búsqueda
$search = $_GET['search'] ?? '';
$categoriaId = $_GET['categoria'] ?? '';
$verificado = isset($_GET['verificado']);
$sostenible = isset($_GET['sostenible']);

// Obtener proveedores filtrados
$sql = "SELECT p.*, c.nombre as categoria_nombre, 
               COUNT(s.id) as total_servicios,
               AVG(s.rating_promedio) as rating_promedio,
               COUNT(DISTINCT s.id) as servicios_activos
        FROM proveedores p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN servicios s ON p.id = s.proveedor_id AND s.disponibilidad = 1
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (p.nombre_empresa LIKE :search OR p.descripcion LIKE :search OR p.ubicacion LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($categoriaId)) {
    $sql .= " AND p.categoria_id = :categoria_id";
    $params[':categoria_id'] = $categoriaId;
}

if ($verificado) {
    $sql .= " AND p.verificado = 1";
}

if ($sostenible) {
    $sql .= " AND p.sostenible = 1";
}

$sql .= " GROUP BY p.id ORDER BY p.verificado DESC, p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$proveedores = $stmt->fetchAll();

$categorias = getCategorias($conn);

// Obtener nombre de categoría si hay filtro
$categoriaNombre = '';
if ($categoriaId) {
    foreach($categorias as $cat) {
        if ($cat['id'] == $categoriaId) {
            $categoriaNombre = $cat['nombre'];
            break;
        }
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1>
            <?php if($categoriaNombre): ?>
            Proveedores de <?= htmlspecialchars($categoriaNombre) ?>
            <?php else: ?>
            Proveedores Sostenibles
            <?php endif; ?>
        </h1>
        <p>Conoce a nuestros socios comprometidos con el turismo responsable</p>

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="../../index.php">Inicio</a>
            <span>></span>
            <span>Proveedores</span>
            <?php if($categoriaNombre): ?>
            <span>></span>
            <span><?= htmlspecialchars($categoriaNombre) ?></span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<div class="container">
    <div class="providers-layout">
        <!-- Sidebar de Filtros -->
        <aside class="filters-sidebar">
            <div class="filters-header">
                <h3>🔍 Filtros</h3>
                <button type="button" class="btn-clear" onclick="limpiarFiltros()">Limpiar</button>
            </div>

            <form method="GET" class="filters-form">
                <!-- Búsqueda -->
                <div class="filter-group">
                    <label>Buscar proveedores</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Nombre, ubicación..." class="form-control">
                </div>

                <!-- Categoría -->
                <div class="filter-group">
                    <label>Categoría</label>
                    <select name="categoria" class="form-control">
                        <option value="">Todas las categorías</option>
                        <?php foreach($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoriaId == $cat['id'] ? 'selected' : '' ?>>
                            <?= $cat['nombre'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtros de Estado -->
                <div class="filter-group">
                    <label>Estado del Proveedor</label>
                    <div class="checkbox-filters">
                        <label class="checkbox-label">
                            <input type="checkbox" name="verificado" value="1" <?= $verificado ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            Solo verificados
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="sostenible" value="1" <?= $sostenible ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            Solo sostenibles
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    🔍 Aplicar Filtros
                </button>
            </form>

            <!-- Estadísticas -->
            <div class="providers-stats">
                <h4>📊 Estadísticas</h4>
                <div class="stat-item">
                    <span>Total proveedores:</span>
                    <strong><?= count($proveedores) ?></strong>
                </div>
                <div class="stat-item">
                    <span>Verificados:</span>
                    <strong><?= count(array_filter($proveedores, function($p) { return $p['verificado']; })) ?></strong>
                </div>
                <div class="stat-item">
                    <span>Sostenibles:</span>
                    <strong><?= count(array_filter($proveedores, function($p) { return $p['sostenible']; })) ?></strong>
                </div>
            </div>

            <!-- Información de la categoría -->
            <?php if($categoriaNombre): ?>
            <div class="category-info">
                <h4>📁 Categoría Seleccionada</h4>
                <p><strong><?= htmlspecialchars($categoriaNombre) ?></strong></p>
                <a href="../../pages/proveedores/proveedores.php" class="btn btn-outline btn-sm">
                    Ver todas las categorías
                </a>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Contenido Principal -->
        <main class="providers-content">
            <!-- Header -->
            <div class="content-header">
                <div class="results-info">
                    <h2>
                        <?php if($categoriaNombre): ?>
                        <?= count($proveedores) ?> proveedores en <?= htmlspecialchars($categoriaNombre) ?>
                        <?php else: ?>
                        <?= count($proveedores) ?> proveedores encontrados
                        <?php endif; ?>
                    </h2>

                    <!-- Filtros activos -->
                    <?php if($search || $categoriaId || $verificado || $sostenible): ?>
                    <div class="active-filters">
                        <strong>Filtros aplicados:</strong>
                        <?php if($search): ?>
                        <span class="filter-tag">
                            "<?= htmlspecialchars($search) ?>"
                            <a href="?<?= http_build_query(array_merge($_GET, ['search' => ''])) ?>"
                                title="Quitar filtro">&times;</a>
                        </span>
                        <?php endif; ?>

                        <?php if($verificado): ?>
                        <span class="filter-tag">
                            Verificados
                            <a href="?<?= http_build_query(array_merge($_GET, ['verificado' => ''])) ?>"
                                title="Quitar filtro">&times;</a>
                        </span>
                        <?php endif; ?>

                        <?php if($sostenible): ?>
                        <span class="filter-tag">
                            Sostenibles
                            <a href="?<?= http_build_query(array_merge($_GET, ['sostenible' => ''])) ?>"
                                title="Quitar filtro">&times;</a>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Ordenamiento -->
                <div class="sort-options">
                    <label>Ordenar por:</label>
                    <select onchange="cambiarOrden(this.value)">
                        <option value="recientes">Más recientes</option>
                        <option value="nombre">Nombre A-Z</option>
                        <option value="rating">Mejor valorados</option>
                        <option value="servicios">Más servicios</option>
                    </select>
                </div>
            </div>

            <!-- Grid de Proveedores -->
            <div class="providers-grid">
                <?php if(empty($proveedores)): ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>No se encontraron proveedores</h3>
                    <p>
                        <?php if($categoriaId): ?>
                        No hay proveedores en la categoría "<?= htmlspecialchars($categoriaNombre) ?>" con los filtros
                        aplicados.
                        <?php else: ?>
                        No hay proveedores que coincidan con tu búsqueda.
                        <?php endif; ?>
                    </p>
                    <div class="suggestions">
                        <p>Prueba con:</p>
                        <ul>
                            <li>✅ Quitar algunos filtros</li>
                            <li>✅ Buscar términos más generales</li>
                            <li>✅ Explorar otras categorías</li>
                        </ul>
                    </div>
                    <a href="proveedores.php" class="btn btn-primary">Ver todos los proveedores</a>
                </div>
                <?php else: ?>
                <?php foreach($proveedores as $proveedor): ?>
                <div class="provider-card">
                    <div class="provider-header">
                        <div class="provider-avatar">
                            <?php if($proveedor['imagen_perfil']): ?>
                            <img src="<?= htmlspecialchars($proveedor['imagen_perfil']) ?>"
                                alt="<?= htmlspecialchars($proveedor['nombre_empresa']) ?>">
                            <?php else: ?>
                            <span class="avatar-placeholder">
                                <?= strtoupper(substr($proveedor['nombre_empresa'], 0, 2)) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="provider-badges">
                            <?php if($proveedor['verificado']): ?>
                            <span class="badge verified" title="Proveedor verificado">✅ Verificado</span>
                            <?php endif; ?>
                            <?php if($proveedor['sostenible']): ?>
                            <span class="badge sustainable" title="Proveedor sostenible">♻️ Sostenible</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="provider-info">
                        <h3>
                            <a href="proveedor-detalle.php?id=<?= $proveedor['id'] ?>">
                                <?= htmlspecialchars($proveedor['nombre_empresa']) ?>
                            </a>
                        </h3>

                        <p class="provider-category">
                            📁 <?= htmlspecialchars($proveedor['categoria_nombre']) ?>
                        </p>

                        <p class="provider-location">
                            📍 <?= htmlspecialchars($proveedor['ubicacion']) ?>
                        </p>

                        <div class="provider-stats">
                            <div class="stat">
                                <strong><?= $proveedor['servicios_activos'] ?? 0 ?></strong>
                                <span>Servicios</span>
                            </div>
                            <div class="stat">
                                <strong><?= $proveedor['rating_promedio'] ? number_format($proveedor['rating_promedio'], 1) : 'Nuevo' ?></strong>
                                <span>Rating</span>
                            </div>
                        </div>

                        <p class="provider-description">
                            <?= substr(htmlspecialchars($proveedor['descripcion'] ?? 'Sin descripción disponible'), 0, 120) ?>
                            <?= strlen($proveedor['descripcion'] ?? '') > 120 ? '...' : '' ?>
                        </p>

                        <div class="provider-contact">
                            <div class="contact-info">
                                <span class="email">📧 <?= htmlspecialchars($proveedor['email']) ?></span>
                                <span class="phone">📞 <?= htmlspecialchars($proveedor['telefono']) ?></span>
                            </div>
                        </div>

                        <div class="provider-actions">
                            <a href="proveedor-detalle.php?id=<?= $proveedor['id'] ?>" class="btn btn-primary btn-sm">
                                Ver Perfil
                            </a>
                            <a href="../servicios/servicios.php?proveedor=<?= $proveedor['id'] ?>"
                                class="btn btn-outline btn-sm">
                                Ver Servicios
                            </a>
                            <?php if(isLoggedIn()): ?>
                            <button class="btn btn-outline btn-sm"
                                onclick="contactarProveedor(<?= $proveedor['id'] ?>)">
                                Contactar
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Paginación -->
            <?php if(count($proveedores) > 12): ?>
            <div class="pagination">
                <button class="pagination-btn prev" disabled>← Anterior</button>
                <div class="pagination-numbers">
                    <button class="pagination-number active">1</button>
                    <button class="pagination-number">2</button>
                    <button class="pagination-number">3</button>
                    <span>...</span>
                </div>
                <button class="pagination-btn next">Siguiente →</button>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Modal de Contacto -->
<div id="contactModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Contactar Proveedor</h3>
            <button class="modal-close" onclick="closeModal('contactModal')">×</button>
        </div>
        <div class="modal-body">
            <form id="contactForm">
                <input type="hidden" id="contactProviderId">
                <div class="form-group">
                    <label>Asunto</label>
                    <input type="text" id="contactSubject" required class="form-control"
                        placeholder="Asunto de tu mensaje">
                </div>
                <div class="form-group">
                    <label>Mensaje</label>
                    <textarea id="contactMessage" required class="form-control" rows="5"
                        placeholder="Escribe tu mensaje aquí..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('contactModal')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar Mensaje</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
function limpiarFiltros() {
    window.location.href = 'proveedores.php';
}

function cambiarOrden(orden) {
    const url = new URL(window.location);
    url.searchParams.set('orden', orden);
    window.location.href = url.toString();
}

function contactarProveedor(providerId) {
    <?php if(!isLoggedIn()): ?>
    window.location.href = '../../pages/login.php?redirect=' + encodeURIComponent(window.location.href);
    return;
    <?php endif; ?>

    document.getElementById('contactProviderId').value = providerId;
    openModal('contactModal');
}

// Manejar formulario de contacto
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = {
        proveedor_id: document.getElementById('contactProviderId').value,
        asunto: document.getElementById('contactSubject').value,
        mensaje: document.getElementById('contactMessage').value,
        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
    };

    try {
        const response = await fetch('../../includes/contact_provider.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Mensaje enviado exitosamente', 'success');
            closeModal('contactModal');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al enviar el mensaje', 'error');
    }
});

// Utilidades de modal
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">×</button>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Mejorar la experiencia de búsqueda
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    }
});
</script>

<style>
.providers-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    margin: 2rem 0;
}

.providers-stats {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    margin-top: 1rem;
}

.providers-stats h4 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.stat-item:last-child {
    border-bottom: none;
}

.providers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.provider-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.provider-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.provider-header {
    position: relative;
    padding: 1.5rem 1.5rem 0;
}

.provider-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 1rem;
    border: 3px solid #fff;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

.provider-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #2E8B57, #1A535C);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
}

.provider-badges {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.badge.verified {
    background: #28a745;
    color: white;
}

.badge.sustainable {
    background: #27ae60;
    color: white;
}

.provider-info {
    padding: 0 1.5rem 1.5rem;
}

.provider-info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.3rem;
}

.provider-info h3 a {
    color: #2c3e50;
    text-decoration: none;
}

.provider-info h3 a:hover {
    color: #3498db;
}

.provider-category {
    color: #7f8c8d;
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
}

.provider-location {
    color: #7f8c8d;
    margin: 0 0 1rem 0;
    font-size: 0.9rem;
}

.provider-stats {
    display: flex;
    gap: 1.5rem;
    margin: 1rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.provider-stats .stat {
    text-align: center;
    flex: 1;
}

.provider-stats .stat strong {
    display: block;
    font-size: 1.5rem;
    color: #2E8B57;
    margin-bottom: 0.2rem;
}

.provider-stats .stat span {
    font-size: 0.8rem;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.provider-description {
    color: #7f8c8d;
    line-height: 1.5;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.provider-contact {
    margin-bottom: 1.5rem;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.contact-info span {
    font-size: 0.85rem;
    color: #7f8c8d;
}

.provider-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.sort-options {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sort-options label {
    font-weight: 600;
    color: #2c3e50;
}

.sort-options select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    background: white;
}

/* Responsive */
@media (max-width: 968px) {
    .providers-layout {
        grid-template-columns: 1fr;
    }

    .providers-grid {
        grid-template-columns: 1fr;
    }

    .provider-stats {
        flex-direction: column;
        gap: 1rem;
    }

    .provider-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .provider-header {
        padding: 1rem 1rem 0;
    }

    .provider-info {
        padding: 0 1rem 1rem;
    }

    .provider-badges {
        position: static;
        flex-direction: row;
        margin-bottom: 1rem;
    }
}
</style>