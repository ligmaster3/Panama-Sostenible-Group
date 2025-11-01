<?php
$pageTitle = "Servicios Sostenibles";

// Incluir archivos con rutas correctas
include '../../config/database.php';
include '../../includes/functions.php';
include '../../includes/auth.php';
include '../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Obtener parámetros de búsqueda
$search = $_GET['search'] ?? '';
$categoriaId = $_GET['categoria'] ?? '';
$precioMin = $_GET['precio_min'] ?? '';
$precioMax = $_GET['precio_max'] ?? '';
$ubicacion = $_GET['ubicacion'] ?? '';
$sostenible = isset($_GET['sostenible']);

// Obtener servicios filtrados
$servicios = getServiciosFiltrados($conn, [
    'search' => $search,
    'categoria_id' => $categoriaId,
    'precio_min' => $precioMin,
    'precio_max' => $precioMax,
    'ubicacion' => $ubicacion,
    'sostenible' => $sostenible
]);

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

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panamá Sostenible Group - Turismo y Desarrollo Responsable</title>
    <link rel="stylesheet" href="../../css/services-styles.css">
    <link rel="stylesheet" href="../../css/components/footer.css">
    <link rel="stylesheet" href="../../css/components/header.css">
</head>

<body>

</body>
<div class="page-header">
    <div class="container">
        <h1>
            <?php if($categoriaNombre): ?>
            Servicios de <?= htmlspecialchars($categoriaNombre) ?>
            <?php else: ?>
            Servicios Sostenibles
            <?php endif; ?>
        </h1>
        <p>Descubre experiencias auténticas y responsables en Panamá</p>

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="../../Index.php">Inicio</a>
            <span>></span>
            <a href="../../servicios.php">Servicios</a>
            <?php if($categoriaNombre): ?>
            <span>></span>
            <span><?= htmlspecialchars($categoriaNombre) ?></span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<div class="container">
    <div class="services-layout">
        <!-- Sidebar de Filtros -->
        <aside class="filters-sidebar">
            <div class="filters-header">
                <h3>🔍 Filtros</h3>
                <button type="button" class="btn-clear" onclick="limpiarFiltros()">Limpiar</button>
            </div>

            <form method="GET" class="filters-form">
                <!-- Mantener la categoría en el formulario -->
                <?php if($categoriaId): ?>
                <input type="hidden" name="categoria" value="<?= $categoriaId ?>">
                <?php endif; ?>

                <!-- Búsqueda -->
                <div class="filter-group">
                    <label>Buscar servicios</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Guía, hotel, restaurante..." class="form-control">
                </div>

                <!-- Categoría (solo si no hay categoría seleccionada) -->
                <?php if(!$categoriaId): ?>
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
                <?php endif; ?>

                <!-- Precio -->
                <div class="filter-group">
                    <label>Rango de Precio ($)</label>
                    <div class="price-range">
                        <input type="number" name="precio_min" value="<?= htmlspecialchars($precioMin) ?>"
                            placeholder="Mín" class="form-control">
                        <span>a</span>
                        <input type="number" name="precio_max" value="<?= htmlspecialchars($precioMax) ?>"
                            placeholder="Máx" class="form-control">
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="filter-group">
                    <label>Ubicación</label>
                    <input type="text" name="ubicacion" value="<?= htmlspecialchars($ubicacion) ?>"
                        placeholder="Ciudad o región" class="form-control">
                </div>

                <!-- Sostenible -->
                <div class="filter-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="sostenible" value="1" <?= $sostenible ? 'checked' : '' ?>>
                        <span class="checkmark"></span>
                        Solo servicios sostenibles
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    🔍 Aplicar Filtros
                </button>
            </form>

            <!-- Información de la categoría -->
            <?php if($categoriaNombre): ?>
            <div class="category-info">
                <h4>📁 Categoría Seleccionada</h4>
                <p><strong><?= htmlspecialchars($categoriaNombre) ?></strong></p>
                <a href="servicios.php" class="btn btn-outline btn-sm">
                    Ver todas las categorías
                </a>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Contenido Principal -->
        <main class="services-content">
            <!-- Header -->
            <div class="content-header">
                <div class="results-info">
                    <h2>
                        <?php if($categoriaNombre): ?>
                        <?= count($servicios) ?> servicios en <?= htmlspecialchars($categoriaNombre) ?>
                        <?php else: ?>
                        <?= count($servicios) ?> servicios encontrados
                        <?php endif; ?>
                    </h2>

                    <!-- Filtros activos -->
                    <?php if($search || $categoriaId || $sostenible || $precioMin || $precioMax || $ubicacion): ?>
                    <div class="active-filters">
                        <strong>Filtros aplicados:</strong>
                        <?php if($search): ?>
                        <span class="filter-tag">
                            "<?= htmlspecialchars($search) ?>"
                            <a href="?<?= http_build_query(array_merge($_GET, ['search' => ''])) ?>"
                                title="Quitar filtro">&times;</a>
                        </span>
                        <?php endif; ?>

                        <?php if($precioMin || $precioMax): ?>
                        <span class="filter-tag">
                            Precio:
                            <?= $precioMin ? '$' . $precioMin : 'Mín' ?>
                            -
                            <?= $precioMax ? '$' . $precioMax : 'Máx' ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['precio_min' => '', 'precio_max' => ''])) ?>"
                                title="Quitar filtro">&times;</a>
                        </span>
                        <?php endif; ?>

                        <?php if($ubicacion): ?>
                        <span class="filter-tag">
                            Ubicación: <?= htmlspecialchars($ubicacion) ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['ubicacion' => ''])) ?>"
                                title="Quitar filtro">&times;</a>
                        </span>
                        <?php endif; ?>

                        <?php if($sostenible): ?>
                        <span class="filter-tag">
                            Sostenible
                            <a href="?<?= http_build_query(array_merge($_GET, ['sostenible' => ''])) ?>"
                                title="Quitar filtro">&times;</a>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Grid de Servicios -->
            <div class="services-grid">
                <?php if(empty($servicios)): ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>No se encontraron servicios</h3>
                    <p>
                        <?php if($categoriaId): ?>
                        No hay servicios en la categoría "<?= htmlspecialchars($categoriaNombre) ?>" con los filtros
                        aplicados.
                        <?php else: ?>
                        No hay servicios que coincidan con tu búsqueda.
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
                    <a href="servicios.php" class="btn btn-primary">Ver todos los servicios</a>
                </div>
                <?php else: ?>
                <?php foreach($servicios as $servicio): ?>
                <div class="service-card">
                    <div class="service-image">
                        <img src="<?= json_decode($servicio['imagenes'])[0] ?? '../../assets/images/placeholder-service.jpg' ?>"
                            alt="<?= htmlspecialchars($servicio['titulo']) ?>" loading="lazy">

                        <!-- Badges -->
                        <div class="service-badges">
                            <?php if($servicio['sostenible']): ?>
                            <span class="badge sustainable" title="Servicio sostenible verificado">♻️ Sostenible</span>
                            <?php endif; ?>

                            <?php if($servicio['rating_promedio'] >= 4.5): ?>
                            <span class="badge popular" title="Muy bien valorado">⭐ Popular</span>
                            <?php endif; ?>

                            <?php if(strtotime($servicio['created_at']) > strtotime('-7 days')): ?>
                            <span class="badge new" title="Nuevo servicio">🆕 Nuevo</span>
                            <?php endif; ?>
                        </div>

                        <button class="favorite-btn" data-service-id="<?= $servicio['id'] ?>"
                            onclick="toggleFavorite(<?= $servicio['id'] ?>, this)">
                            ♡
                        </button>
                    </div>

                    <div class="service-info">
                        <h3>
                            <a href="servicio-detalle.php?id=<?= $servicio['id'] ?>">
                                <?= htmlspecialchars($servicio['titulo']) ?>
                            </a>
                        </h3>

                        <p class="provider">
                            <strong>Proveedor:</strong> <?= htmlspecialchars($servicio['nombre_empresa']) ?>
                        </p>

                        <p class="location">
                            📍 <?= htmlspecialchars($servicio['ubicacion']) ?>
                        </p>

                        <div class="service-meta">
                            <div class="rating">
                                <?php
                                        $rating = $servicio['rating_promedio'];
                                        $fullStars = floor($rating);
                                        $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                        
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= $fullStars): ?>
                                ⭐
                                <?php elseif ($i == $fullStars + 1 && $hasHalfStar): ?>
                                ⭐
                                <?php else: ?>
                                ☆
                                <?php endif;
                                        endfor;
                                        ?>
                                <span class="rating-text">
                                    <?= $rating ? number_format($rating, 1) : 'Nuevo' ?>
                                    <?php if($servicio['total_resenas'] > 0): ?>
                                    <span class="reviews-count">(<?= $servicio['total_resenas'] ?>)</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="category"><?= $servicio['categoria'] ?></span>
                        </div>

                        <p class="description">
                            <?= substr(htmlspecialchars($servicio['descripcion']), 0, 120) ?>
                            <?= strlen($servicio['descripcion']) > 120 ? '...' : '' ?>
                        </p>

                        <div class="service-footer">
                            <div class="price">
                                $<?= number_format($servicio['precio'], 2) ?>
                                <small>/<?= $servicio['tipo_precio'] ?></small>
                            </div>
                            <div class="service-actions">
                                <a href="servicio-detalle.php?id=<?= $servicio['id'] ?>" class="btn btn-primary btn-sm">
                                    Ver Detalles
                                </a>
                                <?php if(isLoggedIn()): ?>
                                <button class="btn btn-outline btn-sm" onclick="quickReserve(<?= $servicio['id'] ?>)">
                                    Reservar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
function limpiarFiltros() {
    // Mantener solo la categoría si viene de explorar
    const urlParams = new URLSearchParams(window.location.search);
    const categoria = urlParams.get('categoria');

    if (categoria) {
        window.location.href = `servicios.php?categoria=${categoria}`;
    } else {
        window.location.href = 'servicios.php';
    }
}

async function toggleFavorite(serviceId, button) {
    <?php if(!isLoggedIn()): ?>
    window.location.href = '../../pages/login.php?redirect=' + encodeURIComponent(window.location.href);
    return;
    <?php endif; ?>

    try {
        const response = await fetch('../../includes/favoritos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                servicio_id: serviceId,
                action: 'toggle',
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
            })
        });

        const data = await response.json();

        if (data.success) {
            if (data.favorito) {
                button.innerHTML = '♥';
                button.classList.add('active');
                showNotification('Servicio agregado a favoritos', 'success');
            } else {
                button.innerHTML = '♡';
                button.classList.remove('active');
                showNotification('Servicio removido de favoritos', 'info');
            }
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al procesar la solicitud', 'error');
    }
}

function quickReserve(serviceId) {
    alert('Funcionalidad de reserva rápida para servicio ID: ' + serviceId);
    // Aquí puedes implementar un modal de reserva rápida
}

function showNotification(message, type = 'info') {
    // Implementación simple de notificaciones
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
    }, 3000);
}

// Mejorar la experiencia de filtros
document.addEventListener('DOMContentLoaded', function() {
    // Agregar funcionalidad de enter en búsqueda
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
/* Estilos adicionales para la página de servicios */
.services-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    margin: 2rem 0;
}

.category-info {
    background: #e8f5e8;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
    border-left: 4px solid #2E8B57;
}

.category-info h4 {
    margin: 0 0 0.5rem 0;
    color: #2E8B57;
}

.active-filters {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
    margin-top: 0.5rem;
}

.filter-tag {
    background: #3498db;
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 15px;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-tag a {
    color: white;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
    padding: 2px 5px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
}

.filter-tag a:hover {
    background: rgba(255, 255, 255, 0.3);
}

.service-badges {
    position: absolute;
    top: 10px;
    left: 10px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.badge {
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge.sustainable {
    background: #27ae60;
    color: white;
}

.badge.popular {
    background: #f39c12;
    color: white;
}

.badge.new {
    background: #e74c3c;
    color: white;
}

.service-actions {
    display: flex;
    gap: 0.5rem;
}

.suggestions {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
    text-align: left;
}

.suggestions ul {
    margin: 0.5rem 0 0 0;
    padding-left: 1.5rem;
}

.suggestions li {
    margin-bottom: 0.3rem;
}

/* Responsive */
@media (max-width: 768px) {
    .services-layout {
        grid-template-columns: 1fr;
    }

    .active-filters {
        flex-direction: column;
        align-items: flex-start;
    }

    .service-actions {
        flex-direction: column;
    }
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    color: white;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 1rem;
    animation: slideIn 0.3s ease;
}

.notification.success {
    background: #27ae60;
}

.notification.error {
    background: #e74c3c;
}

.notification.info {
    background: #3498db;
}

.notification button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 1.2rem;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }

    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>