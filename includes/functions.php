<?php
function getCategorias($conn) {
    $sql = "SELECT * FROM categorias ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getServiciosDestacados($conn, $limit = 6) {
    $sql = "SELECT s.*, p.nombre_empresa, p.ubicacion, c.nombre as categoria 
            FROM servicios s 
            JOIN proveedores p ON s.proveedor_id = p.id 
            JOIN categorias c ON p.categoria_id = c.id 
            WHERE s.disponibilidad = 1 
            ORDER BY s.created_at DESC 
            LIMIT :limit";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getServiciosFiltrados($conn, $filtros = []) {
    $sql = "SELECT s.*, p.nombre_empresa, p.ubicacion, p.sostenible, c.nombre as categoria 
            FROM servicios s 
            JOIN proveedores p ON s.proveedor_id = p.id 
            JOIN categorias c ON p.categoria_id = c.id 
            WHERE s.disponibilidad = 1";
    
    $params = [];
    
    if (!empty($filtros['search'])) {
        $sql .= " AND (s.titulo LIKE :search OR s.descripcion LIKE :search OR p.nombre_empresa LIKE :search)";
        $params[':search'] = '%' . $filtros['search'] . '%';
    }
    
    if (!empty($filtros['categoria_id'])) {
        $sql .= " AND p.categoria_id = :categoria_id";
        $params[':categoria_id'] = $filtros['categoria_id'];
    }
    
    if (!empty($filtros['precio_min'])) {
        $sql .= " AND s.precio >= :precio_min";
        $params[':precio_min'] = $filtros['precio_min'];
    }
    
    if (!empty($filtros['precio_max'])) {
        $sql .= " AND s.precio <= :precio_max";
        $params[':precio_max'] = $filtros['precio_max'];
    }
    
    if (!empty($filtros['ubicacion'])) {
        $sql .= " AND p.ubicacion LIKE :ubicacion";
        $params[':ubicacion'] = '%' . $filtros['ubicacion'] . '%';
    }
    
    if (!empty($filtros['sostenible'])) {
        $sql .= " AND p.sostenible = 1";
    }
    
    $sql .= " ORDER BY s.rating_promedio DESC, s.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getServicioById($conn, $servicio_id) {
    $sql = "SELECT s.*, p.nombre_empresa, p.telefono, p.email, p.ubicacion, p.lat, p.lng, 
                   p.sostenible, p.descripcion as descripcion_proveedor, c.nombre as categoria,
                   c.id as categoria_id
            FROM servicios s 
            JOIN proveedores p ON s.proveedor_id = p.id 
            JOIN categorias c ON p.categoria_id = c.id 
            WHERE s.id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $servicio_id);
    $stmt->execute();
    return $stmt->fetch();
}

function getProveedores($conn, $limit = null) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre, 
                   COUNT(s.id) as total_servicios,
                   AVG(s.rating_promedio) as rating_promedio
            FROM proveedores p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN servicios s ON p.id = s.proveedor_id AND s.disponibilidad = 1
            GROUP BY p.id
            ORDER BY p.verificado DESC, p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($limit) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtener proveedores filtrados
 */
function getProveedoresFiltrados($conn, $filtros = []) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre, 
                   COUNT(s.id) as total_servicios,
                   AVG(s.rating_promedio) as rating_promedio,
                   COUNT(DISTINCT s.id) as servicios_activos
            FROM proveedores p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN servicios s ON p.id = s.proveedor_id AND s.disponibilidad = 1
            WHERE 1=1";
    
    $params = [];
    
    // Filtro por búsqueda
    if (!empty($filtros['search'])) {
        $sql .= " AND (p.nombre_empresa LIKE :search OR p.descripcion LIKE :search OR p.ubicacion LIKE :search)";
        $params[':search'] = '%' . $filtros['search'] . '%';
    }
    
    // Filtro por categoría
    if (!empty($filtros['categoria_id'])) {
        $sql .= " AND p.categoria_id = :categoria_id";
        $params[':categoria_id'] = $filtros['categoria_id'];
    }
    
    // Filtro por verificación
    if (!empty($filtros['verificado'])) {
        $sql .= " AND p.verificado = 1";
    }
    
    // Filtro por sostenibilidad
    if (!empty($filtros['sostenible'])) {
        $sql .= " AND p.sostenible = 1";
    }
    
    // Filtro por ubicación
    if (!empty($filtros['ubicacion'])) {
        $sql .= " AND p.ubicacion LIKE :ubicacion";
        $params[':ubicacion'] = '%' . $filtros['ubicacion'] . '%';
    }
    
    $sql .= " GROUP BY p.id ORDER BY p.verificado DESC, p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtener un proveedor por ID con información completa
 */
function getProveedorById($conn, $proveedor_id) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono,
                   COUNT(s.id) as total_servicios,
                   AVG(s.rating_promedio) as rating_promedio,
                   COUNT(DISTINCT s.id) as servicios_activos,
                   SUM(s.total_resenas) as total_resenas,
                   SUM(s.total_visitas) as total_visitas
            FROM proveedores p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN servicios s ON p.id = s.proveedor_id AND s.disponibilidad = 1
            WHERE p.id = :proveedor_id
            GROUP BY p.id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':proveedor_id', $proveedor_id);
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Obtener proveedores destacados (mejor valorados)
 */
function getProveedoresDestacados($conn, $limit = 6) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre,
                   COUNT(s.id) as total_servicios,
                   AVG(s.rating_promedio) as rating_promedio,
                   COUNT(DISTINCT s.id) as servicios_activos
            FROM proveedores p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN servicios s ON p.id = s.proveedor_id AND s.disponibilidad = 1
            WHERE p.verificado = 1
            GROUP BY p.id
            HAVING rating_promedio >= 4.0 AND servicios_activos > 0
            ORDER BY rating_promedio DESC, servicios_activos DESC
            LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtener proveedores por categoría
 */
function getProveedoresByCategoria($conn, $categoria_id, $limit = null) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre,
                   COUNT(s.id) as total_servicios,
                   AVG(s.rating_promedio) as rating_promedio
            FROM proveedores p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN servicios s ON p.id = s.proveedor_id AND s.disponibilidad = 1
            WHERE p.categoria_id = :categoria_id AND p.verificado = 1
            GROUP BY p.id
            ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':categoria_id', $categoria_id);
    
    if ($limit) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtener servicios de un proveedor
 */
function getServiciosByProveedor($conn, $proveedor_id, $limit = null) {
    $sql = "SELECT s.*, c.nombre as categoria_nombre
            FROM servicios s
            LEFT JOIN categorias c ON s.proveedor_id = p.categoria_id
            WHERE s.proveedor_id = :proveedor_id AND s.disponibilidad = 1
            ORDER BY s.rating_promedio DESC, s.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':proveedor_id', $proveedor_id);
    
    if ($limit) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtener reseñas de los servicios de un proveedor
 */
function getResenasByProveedor($conn, $proveedor_id, $limit = null) {
    $sql = "SELECT r.*, s.titulo as servicio_titulo, u.nombre as usuario_nombre,
                   u.imagen_perfil as usuario_imagen
            FROM resenas r
            JOIN servicios s ON r.servicio_id = s.id
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE s.proveedor_id = :proveedor_id
            ORDER BY r.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':proveedor_id', $proveedor_id);
    
    if ($limit) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtener estadísticas de proveedores
 */
function getEstadisticasProveedores($conn) {
    $sql = "SELECT 
                COUNT(*) as total_proveedores,
                SUM(verificado) as proveedores_verificados,
                SUM(sostenible) as proveedores_sostenibles,
                AVG((SELECT COUNT(*) FROM servicios s WHERE s.proveedor_id = p.id AND s.disponibilidad = 1)) as servicios_promedio,
                AVG((SELECT AVG(rating_promedio) FROM servicios s WHERE s.proveedor_id = p.id)) as rating_promedio
            FROM proveedores p";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Verificar si un usuario es proveedor
 */
function esProveedor($conn, $usuario_id) {
    $sql = "SELECT id FROM proveedores WHERE usuario_id = :usuario_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    return $stmt->fetch() !== false;
}

/**
 * Obtener proveedor por usuario ID
 */
function getProveedorByUsuarioId($conn, $usuario_id) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre
            FROM proveedores p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.usuario_id = :usuario_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Buscar proveedores por término
 */
function buscarProveedores($conn, $termino, $limit = 10) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre,
                   COUNT(s.id) as total_servicios,
                   AVG(s.rating_promedio) as rating_promedio
            FROM proveedores p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN servicios s ON p.id = s.proveedor_id AND s.disponibilidad = 1
            WHERE (p.nombre_empresa LIKE :termino OR 
                  p.descripcion LIKE :termino OR 
                  p.ubicacion LIKE :termino OR
                  c.nombre LIKE :termino)
            AND p.verificado = 1
            GROUP BY p.id
            ORDER BY p.verificado DESC, rating_promedio DESC
            LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':termino', '%' . $termino . '%');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtener proveedores cercanos por ubicación
 */
function getProveedoresCercanos($conn, $lat, $lng, $radio_km = 50, $limit = 10) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre,
                   (6371 * acos(cos(radians(:lat)) * cos(radians(p.lat)) * 
                   cos(radians(p.lng) - radians(:lng)) + sin(radians(:lat)) * 
                   sin(radians(p.lat)))) as distancia,
                   COUNT(s.id) as total_servicios,
                   AVG(s.rating_promedio) as rating_promedio
            FROM proveedores p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN servicios s ON p.id = s.proveedor_id AND s.disponibilidad = 1
            WHERE p.lat IS NOT NULL AND p.lng IS NOT NULL
            AND p.verificado = 1
            GROUP BY p.id
            HAVING distancia <= :radio_km
            ORDER BY distancia ASC, rating_promedio DESC
            LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':lat', $lat);
    $stmt->bindParam(':lng', $lng);
    $stmt->bindParam(':radio_km', $radio_km);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtener proveedores con mejores ratings
 */
function getProveedoresTopRating($conn, $limit = 10) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre,
                   AVG(s.rating_promedio) as rating_promedio,
                   COUNT(s.id) as total_servicios
            FROM proveedores p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN servicios s ON p.id = s.proveedor_id AND s.disponibilidad = 1
            WHERE p.verificado = 1
            GROUP BY p.id
            HAVING rating_promedio >= 4.0 AND total_servicios > 0
            ORDER BY rating_promedio DESC, total_servicios DESC
            LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtener proveedores nuevos
 */
function getProveedoresNuevos($conn, $limit = 10) {
    $sql = "SELECT p.*, c.nombre as categoria_nombre,
                   COUNT(s.id) as total_servicios
            FROM proveedores p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN servicios s ON p.id = s.proveedor_id AND s.disponibilidad = 1
            WHERE p.verificado = 1
            AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
?>