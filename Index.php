<?php
include 'config/database.php';
include 'includes/functions.php';

$database = new Database();
$conn = $database->getConnection();

$categorias = getCategorias($conn);
$serviciosDestacados = getServiciosDestacados($conn);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panamá Sostenible Group - Turismo y Desarrollo Responsable</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Panamá Sostenible Group</h1>
            <p>Conectamos viajeros con experiencias auténticas y servicios responsables</p>

            <!-- Búsqueda -->
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="¿Qué servicio buscas?">
                <select id="categoriaSelect">
                    <option value="">Todas las categorías</option>
                    <?php foreach($categorias as $categoria): ?>
                    <option value="<?= $categoria['id'] ?>"><?= $categoria['nombre'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button onclick="buscarServicios()">Buscar</button>
            </div>          
        </div>
    </section>

    <!-- Categorías -->
    <section class="categorias">
        <div class="container">
            <h2>Nuestros Servicios </h2>
            <div class="categorias-grid">
                <?php foreach($categorias as $categoria): ?>
                <div class="categoria-card" style="--categoria-color: <?= $categoria['color'] ?? '#2E8B57' ?>;">
                    <?php if(!empty($categoria['imagen_categoria'])): ?>
                    <div class="categoria-imagen">
                        <img src="<?= htmlspecialchars($categoria['imagen_categoria']) ?>"
                            alt="<?= htmlspecialchars($categoria['nombre']) ?>">
                        <div class="categoria-overlay"></div>
                    </div>
                    <?php endif; ?>
                    <div class="categoria-content">
                        <h3><?= htmlspecialchars($categoria['nombre']) ?></h3>
                        <p><?= htmlspecialchars($categoria['descripcion']) ?></p>
                        <a href="pages/servicios/servicios.php?categoria=<?= $categoria['id'] ?>" class="btn-explorar">
                            Explorar
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Servicios Destacados -->
    <section class="servicios-destacados">
        <div class="container">
            <h2>Experiencias Destacadas</h2>
            <div class="servicios-grid">
                <?php foreach($serviciosDestacados as $servicio): ?>
                <div class="servicio-card">
                    <img src="<?= json_decode($servicio['imagenes'])[0] ?>" alt="<?= $servicio['titulo'] ?>">
                    <div class="servicio-info">
                        <h3><?= $servicio['titulo'] ?></h3>
                        <p class="proveedor"><?= $servicio['nombre_empresa'] ?></p>
                        <p class="ubicacion">📍 <?= $servicio['ubicacion'] ?></p>
                        <p class="precio">$<?= $servicio['precio'] ?> / <?= $servicio['tipo_precio'] ?></p>
                        <a href="servicio.php?id=<?= $servicio['id'] ?>" class="btn">Ver Detalles</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>

</html>