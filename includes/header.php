<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userAvatar = $isLoggedIn && isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : '';
$userRole = $isLoggedIn && isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Determinar título de página
$pageTitles = [
    'index' => 'Inicio',
    'servicios' => 'Servicios',
    'proveedores' => 'Proveedores',
    'sobre-nosotros' => 'Sobre Nosotros',
    'contacto' => 'Contacto',
    'dashboard' => 'Dashboard',
    'login' => 'Iniciar Sesión',
    'registro' => 'Registrarse'
];
$currentPageTitle = $pageTitles[$currentPage] ?? 'Panamá Sostenible';

// Configuración del sitio
$siteConfig = [
    'phone' => '+507 123-4567',
    'email' => 'info@panamasostenible.com',
    'site_name' => 'Panamá Sostenible Group',
    'description' => 'Plataforma de servicios turísticos sostenibles en Panamá'
];
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteConfig['site_name']); ?> -
        <?php echo htmlspecialchars($currentPageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($siteConfig['description']); ?>">

    <!-- Preload crítico -->
    <link rel="preload" href="/../css/style.css" as="style">
    <link rel="preload" href="/../css/components/header.css" as="style">
    <link rel="preload" href="/../assets/icons/bluesky.svg" as="image">
    <link rel="preload" href="../../assets/icons/bluesky.svg" as="image">

    <!-- Styles -->
    <link rel="stylesheet" href="/../css/style.css">
    <link rel="stylesheet" href="/../css/components/footer.css">
    <link rel="stylesheet" href="/../css/responsive.css">
    <link rel="stylesheet" href="/../css/components/header.css">

    <!-- Favicon -->
    <link rel="icon" href="/../assets/icons/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="/../assets/icons/apple-touch-icon.png">

    <!-- Meta tags adicionales -->
    <meta name="theme-color" content="#2E8B57">
    <meta name="robots" content="index, follow">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?php echo $siteConfig['site_name']; ?>",
        "description": "<?php echo $siteConfig['description']; ?>",
        "url": "<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>",
        "telephone": "<?php echo $siteConfig['phone']; ?>",
        "email": "<?php echo $siteConfig['email']; ?>"
    }
    </script>

</head>

<body>
    <!-- Top Bar -->
    <div class="top-bar" id="topBar">
        <div class="container-topbar">
            <div class="top-bar-left">
                <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $siteConfig['phone']); ?>" class="top-link"
                    aria-label="Llamar por teléfono">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path
                            d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span><?php echo htmlspecialchars($siteConfig['phone']); ?></span>
                </a>
                <a href="mailto:<?php echo htmlspecialchars($siteConfig['email']); ?>" class="top-link"
                    aria-label="Enviar correo electrónico">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span><?php echo htmlspecialchars($siteConfig['email']); ?></span>
                </a>
            </div>
            <div class="top-bar-right">
                <div class="social-icons">
                    <a href="https://facebook.com" class="social-icon" aria-label="Síguenos en Facebook" target="_blank"
                        rel="noopener noreferrer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </a>
                    <a href="https://instagram.com" class="social-icon" aria-label="Síguenos en Instagram"
                        target="_blank" rel="noopener noreferrer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path
                                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069z" />
                        </svg>
                    </a>
                    <a href="https://twitter.com" class="social-icon" aria-label="Síguenos en Twitter" target="_blank"
                        rel="noopener noreferrer">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path
                                d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                        </svg>
                    </a>
                </div>
                <div class="language-selector" role="button" aria-label="Selector de idioma" tabindex="0"
                    id="langSelector">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                        <path
                            d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"
                            stroke="currentColor" stroke-width="2" />
                    </svg>
                    <span>ES</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header" id="mainHeader" role="banner">
        <nav class="navbar" aria-label="Navegación principal">
            <div class="container-nav">
                <!-- Logo -->
                <div class="nav-brand">
                    <a href="index.php" class="logo"
                        aria-label="Página de inicio - <?php echo htmlspecialchars($siteConfig['site_name']); ?>">
                        <div class="logo-wrapper">
                            <img src="../../assets/icons/bluesky.svg" alt="" class=" logo-img" width="40" height="40">
                            <div class="logo-badge" aria-hidden="true">ECO</div>
                        </div>
                        <div class="logo-text">
                            <strong><?php echo htmlspecialchars($siteConfig['site_name']); ?></strong>
                            <small>Turismo y Desarrollo Responsable</small>
                        </div>
                    </a>
                </div>

                <!-- Main Navigation -->
                <div class="nav-center">
                    <ul class="nav-menu" role="menubar">
                        <li class="nav-item" role="none">
                            <a href="../../Index.php"
                                class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>" role="menuitem"
                                aria-current="<?php echo $currentPage === 'index' ? 'page' : 'false'; ?>">
                                Inicio
                            </a>
                        </li>
                        <li class="nav-item has-mega-menu" role="none">
                            <a href="../../pages/servicios/servicios.php"
                                class="nav-link <?php echo $currentPage === 'servicios' ? 'active' : ''; ?>"
                                role="menuitem" aria-haspopup="true" aria-expanded="false"
                                aria-current="<?php echo $currentPage === 'servicios' ? 'page' : 'false'; ?>">
                                Servicios
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </a>
                            <div class="mega-menu" role="menu" aria-label="Submenú de servicios">
                                <div class="mega-menu-content">
                                    <div class="mega-menu-section">
                                        <h4>Experiencias</h4>
                                        <a href="../../pages/servicios/servicios.php?categoria=guia"
                                            role="menuitem">Tours Guiados</a>
                                        <a href="../../pages/servicios/servicios.php?categoria=aventura"
                                            role="menuitem">Aventura</a>
                                        <a href="../../pages/servicios/servicios.php?categoria=ecoturismo"
                                            role="menuitem">Ecoturismo</a>
                                    </div>
                                    <div class="mega-menu-section">
                                        <h4>Alojamiento</h4>
                                        <a href="../../pages/servicios/servicios.php?categoria=hotel"
                                            role="menuitem">Hoteles Eco</a>
                                        <a href="../../pages/servicios/servicios.php?categoria=lodge"
                                            role="menuitem">Lodges</a>
                                        <a href="../../pages/servicios/servicios.php?categoria=camping"
                                            role="menuitem">Glamping</a>
                                    </div>
                                    <div class="mega-menu-section">
                                        <h4>Gastronomía</h4>
                                        <a href="../../pages/servicios/servicios.php?categoria=restaurante"
                                            role="menuitem">Restaurantes</a>
                                        <a href="../../pages/servicios/servicios.php?categoria=cafe"
                                            role="menuitem">Cafés Locales</a>
                                        <a href="../../pages/servicios/servicios.php?categoria=mercado"
                                            role="menuitem">Mercados</a>
                                    </div>
                                    <div class="mega-menu-section featured">
                                        <h4>Destacado</h4>
                                        <div class="featured-card">
                                            <div class="featured-info">
                                                <strong>Tour Especial</strong>
                                                <small>Descubre la biodiversidad panameña</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item" role="none">
                            <a href="../../pages/proveedores/proveedores.php"
                                class="nav-link <?php echo $currentPage === 'proveedores' ? 'active' : ''; ?>"
                                role="menuitem"
                                aria-current="<?php echo $currentPage === 'proveedores' ? 'page' : 'false'; ?>">
                                Proveedores
                            </a>
                        </li>
                        <li class="nav-item" role="none">
                            <a href="sobre-nosotros.php"
                                class="nav-link <?php echo $currentPage === 'sobre-nosotros' ? 'active' : ''; ?>"
                                role="menuitem"
                                aria-current="<?php echo $currentPage === 'sobre-nosotros' ? 'page' : 'false'; ?>">
                                Nosotros
                            </a>
                        </li>
                        <li class="nav-item" role="none">
                            <a href="contacto.php"
                                class="nav-link <?php echo $currentPage === 'contacto' ? 'active' : ''; ?>"
                                role="menuitem"
                                aria-current="<?php echo $currentPage === 'contacto' ? 'page' : 'false'; ?>">
                                Contacto
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="nav-actions">
                    <!-- Search Button -->
                    <button class="search-btn" id="searchBtn" aria-label="Abrir búsqueda" aria-expanded="false">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" />
                            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>

                    <?php if($isLoggedIn): ?>
                    <!-- Notifications -->
                    <div class="notifications-btn">
                        <button class="icon-btn" aria-label="Notificaciones" aria-expanded="false" id="notifBtn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <span class="badge" aria-label="3 notificaciones no leídas">3</span>
                        </button>
                    </div>

                    <!-- User Menu -->
                    <div class="user-menu">
                        <button class="user-btn" id="userMenuBtn" aria-label="Menú de usuario" aria-expanded="false"
                            aria-haspopup="true">
                            <div class="user-avatar">
                                <?php if($userAvatar): ?>
                                <img src="<?php echo htmlspecialchars($userAvatar); ?>"
                                    alt="Avatar de <?php echo htmlspecialchars($userName); ?>" width="32" height="32">
                                <?php else: ?>
                                <span aria-hidden="true"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                                <?php endif; ?>
                                <span class="status-dot" aria-label="Usuario en línea"></span>
                            </div>
                        </button>
                        <div class="user-dropdown" id="userDropdown" role="menu" aria-label="Menú de usuario">
                            <div class="dropdown-user">
                                <div class="user-avatar large">
                                    <?php if($userAvatar): ?>
                                    <img src="<?php echo htmlspecialchars($userAvatar); ?>"
                                        alt="Avatar de <?php echo htmlspecialchars($userName); ?>" width="48"
                                        height="48">
                                    <?php else: ?>
                                    <span aria-hidden="true"><?php echo strtoupper(substr($userName, 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($userName); ?></strong>
                                    <small>
                                        <?php 
                                        $roleLabels = [
                                            'admin' => 'Administrador',
                                            'provider' => 'Proveedor',
                                            'user' => 'Explorador Sostenible'
                                        ];
                                        echo $roleLabels[$userRole] ?? 'Usuario';
                                        ?>
                                    </small>
                                </div>
                            </div>
                            <div class="dropdown-menu">
                                <a href="dashboard.php" class="dropdown-link" role="menuitem">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor"
                                            stroke-width="2" />
                                        <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor"
                                            stroke-width="2" />
                                        <rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor"
                                            stroke-width="2" />
                                        <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor"
                                            stroke-width="2" />
                                    </svg>
                                    Dashboard
                                </a>
                                <a href="mis-reservas.php" class="dropdown-link" role="menuitem">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor"
                                            stroke-width="2" />
                                        <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                    Mis Reservas
                                </a>
                                <a href="favoritos.php" class="dropdown-link" role="menuitem">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path
                                            d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    Favoritos
                                </a>
                                <a href="perfil.php" class="dropdown-link" role="menuitem">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    Mi Perfil
                                </a>

                                <?php if($userRole === 'admin' || $userRole === 'provider'): ?>
                                <div class="dropdown-divider"></div>
                                <a href="admin/" class="dropdown-link" role="menuitem">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 15l-5.5 3.5 1.5-6-5-4.5 6.5-.5L12 2l2.5 5.5 6.5.5-5 4.5 1.5 6z"
                                            stroke="currentColor" stroke-width="2" />
                                    </svg>
                                    Panel de Control
                                </a>
                                <?php endif; ?>

                                <div class="dropdown-divider"></div>
                                <a href="includes/logout.php" class="dropdown-link danger" role="menuitem">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Auth Buttons -->
                    <div class="auth-group">
                        <a href="login.php" class="btn-text" aria-label="Iniciar sesión">Iniciar Sesión</a>
                        <a href="registro.php" class="btn-solid" aria-label="Crear cuenta">Registrarse</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Mobile Toggle -->
                <button class="mobile-toggle" id="mobileToggle" aria-label="Menú principal" aria-expanded="false"
                    aria-controls="mobileDrawer">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Mobile Menu -->
    <div class="mobile-drawer" id="mobileDrawer" role="dialog" aria-label="Menú móvil" aria-modal="true" hidden>
        <div class="drawer-header">
            <div class="drawer-logo">
                <img src="assets/icons/bluesky.svg" alt="" width="32" height="32">
                <span>Menú Principal</span>
            </div>
            <button class="drawer-close" id="drawerClose" aria-label="Cerrar menú">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </button>
        </div>

        <div class="drawer-content">
            <?php if($isLoggedIn): ?>
            <div class="drawer-user">
                <div class="user-avatar">
                    <?php if($userAvatar): ?>
                    <img src="<?php echo htmlspecialchars($userAvatar); ?>"
                        alt="Avatar de <?php echo htmlspecialchars($userName); ?>" width="40" height="40">
                    <?php else: ?>
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($userName); ?></strong>
                    <small>
                        <?php 
                        $roleLabels = [
                            'admin' => 'Administrador',
                            'provider' => 'Proveedor', 
                            'user' => 'Explorador Sostenible'
                        ];
                        echo $roleLabels[$userRole] ?? 'Usuario';
                        ?>
                    </small>
                </div>
            </div>
            <?php endif; ?>

            <nav class="drawer-nav" aria-label="Navegación móvil">
                <a href="../../Index.php" class="drawer-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>"
                    aria-current="<?php echo $currentPage === 'index' ? 'page' : 'false'; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2" />
                    </svg>
                    Inicio
                </a>
                <a href="servicios.php" class="drawer-link <?php echo $currentPage === 'servicios' ? 'active' : ''; ?>"
                    aria-current="<?php echo $currentPage === 'servicios' ? 'page' : 'false'; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor"
                            stroke-width="2" />
                    </svg>
                    Servicios
                </a>
                <a href="proveedores.php"
                    class="drawer-link <?php echo $currentPage === 'proveedores' ? 'active' : ''; ?>"
                    aria-current="<?php echo $currentPage === 'proveedores' ? 'page' : 'false'; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path
                            d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            stroke="currentColor" stroke-width="2" />
                    </svg>
                    Proveedores
                </a>
                <a href="sobre-nosotros.php"
                    class="drawer-link <?php echo $currentPage === 'sobre-nosotros' ? 'active' : ''; ?>"
                    aria-current="<?php echo $currentPage === 'sobre-nosotros' ? 'page' : 'false'; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                        <path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2" />
                    </svg>
                    Sobre Nosotros
                </a>
                <a href="contacto.php" class="drawer-link <?php echo $currentPage === 'contacto' ? 'active' : ''; ?>"
                    aria-current="<?php echo $currentPage === 'contacto' ? 'page' : 'false'; ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            stroke="currentColor" stroke-width="2" />
                    </svg>
                    Contacto
                </a>
            </nav>

            <?php if($isLoggedIn): ?>
            <div class="drawer-divider"></div>
            <div class="drawer-section">
                <h4>Mi Cuenta</h4>
                <a href="dashboard.php" class="drawer-link small">Dashboard</a>
                <a href="mis-reservas.php" class="drawer-link small">Mis Reservas</a>
                <a href="favoritos.php" class="drawer-link small">Favoritos</a>
                <a href="perfil.php" class="drawer-link small">Mi Perfil</a>

                <?php if($userRole === 'admin' || $userRole === 'provider'): ?>
                <a href="admin/" class="drawer-link small">Panel de Control</a>
                <?php endif; ?>
            </div>
            <div class="drawer-divider"></div>
            <a href="includes/logout.php" class="drawer-link danger">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor"
                        stroke-width="2" />
                </svg>
                Cerrar Sesión
            </a>
            <?php else: ?>
            <div class="drawer-auth">
                <a href="login.php" class="btn-outline full">Iniciar Sesión</a>
                <a href="registro.php" class="btn-solid full">Crear Cuenta</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="drawer-overlay" id="drawerOverlay" aria-hidden="true" tabindex="-1"></div>

    <!-- Search Modal -->
    <div id="searchModal" class="search-modal" aria-hidden="true" hidden>
        <!-- Contenido del modal de búsqueda -->
    </div>

    <script>
    // Script mejorado con animaciones interactivas
    document.addEventListener('DOMContentLoaded', function() {
        const topBar = document.getElementById('topBar');
        const mainHeader = document.getElementById('mainHeader');
        const mobileToggle = document.getElementById('mobileToggle');
        const mobileDrawer = document.getElementById('mobileDrawer');
        const drawerClose = document.getElementById('drawerClose');
        const drawerOverlay = document.getElementById('drawerOverlay');
        const searchBtn = document.getElementById('searchBtn');
        const langSelector = document.getElementById('langSelector');

        let lastScroll = 0;

        // Efecto scroll en header
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            // Agregar clase scrolled al header
            if (currentScroll > 50) {
                mainHeader.classList.add('scrolled');
            } else {
                mainHeader.classList.remove('scrolled');
            }

            // Ocultar top bar al hacer scroll
            if (currentScroll > 100) {
                topBar.classList.add('hidden');
            } else {
                topBar.classList.remove('hidden');
            }

            lastScroll = currentScroll;
        });

        // Mobile menu toggle
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => {
                const isExpanded = mobileToggle.getAttribute('aria-expanded') === 'true';
                mobileToggle.setAttribute('aria-expanded', !isExpanded);
                mobileToggle.classList.toggle('active');
                mobileDrawer.classList.toggle('active');
                mobileDrawer.hidden = isExpanded;
                drawerOverlay.classList.toggle('active');
                document.body.style.overflow = isExpanded ? '' : 'hidden';
            });
        }

        // Cerrar drawer
        if (drawerClose) {
            drawerClose.addEventListener('click', closeMobileMenu);
        }

        if (drawerOverlay) {
            drawerOverlay.addEventListener('click', closeMobileMenu);
        }

        function closeMobileMenu() {
            if (mobileToggle) {
                mobileToggle.setAttribute('aria-expanded', 'false');
                mobileToggle.classList.remove('active');
            }
            if (mobileDrawer) {
                mobileDrawer.classList.remove('active');
                mobileDrawer.hidden = true;
            }
            if (drawerOverlay) {
                drawerOverlay.classList.remove('active');
            }
            document.body.style.overflow = '';
        }

        // Cambiar idioma
        if (langSelector) {
            langSelector.addEventListener('click', function() {
                const span = this.querySelector('span');
                span.textContent = span.textContent === 'ES' ? 'EN' : 'ES';
            });
        }

        // Search button
        if (searchBtn) {
            searchBtn.addEventListener('click', function() {
                this.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
                // Aquí implementar modal de búsqueda
                console.log('Abrir búsqueda');
            });
        }

        // Notifications
        const notifBtn = document.getElementById('notifBtn');
        if (notifBtn) {
            notifBtn.addEventListener('click', function() {
                console.log('Ver notificaciones');
            });
        }

        // Mega menu accessibility
        const megaMenuItems = document.querySelectorAll('.has-mega-menu');
        megaMenuItems.forEach(item => {
            const link = item.querySelector('.nav-link');

            item.addEventListener('mouseenter', () => {
                if (link) link.setAttribute('aria-expanded', 'true');
            });

            item.addEventListener('mouseleave', () => {
                if (link) link.setAttribute('aria-expanded', 'false');
            });
        });

        // User menu accessibility
        const userMenu = document.querySelector('.user-menu');
        const userMenuBtn = document.getElementById('userMenuBtn');

        if (userMenu && userMenuBtn) {
            userMenu.addEventListener('mouseenter', () => {
                userMenuBtn.setAttribute('aria-expanded', 'true');
            });

            userMenu.addEventListener('mouseleave', () => {
                userMenuBtn.setAttribute('aria-expanded', 'false');
            });
        }

        // Ripple effect en botones
        function createRipple(event) {
            const button = event.currentTarget;
            const ripple = document.createElement('span');
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255,255,255,0.5);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                `;

            button.style.position = 'relative';
            button.style.overflow = 'hidden';
            button.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        }

        // Agregar ripple a todos los botones
        document.querySelectorAll('button, .btn-solid, .btn-text').forEach(btn => {
            btn.addEventListener('click', createRipple);
        });

        // Smooth scroll para anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#' && href.length > 1) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });

        // Parallax effect en logo
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const logoImg = document.querySelector('.logo-img');
            if (logoImg && scrolled < 500) {
                const parallax = scrolled * 0.1;
                logoImg.style.transform = `translateY(${parallax}px) rotate(${scrolled * 0.05}deg)`;
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            // ESC para cerrar mobile menu
            if (e.key === 'Escape') {
                closeMobileMenu();
            }
        });

        // Trap focus en mobile menu cuando está abierto
        if (mobileDrawer) {
            mobileDrawer.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    const focusableElements = mobileDrawer.querySelectorAll(
                        'a[href], button, [tabindex]:not([tabindex="-1"])'
                    );
                    const firstElement = focusableElements[0];
                    const lastElement = focusableElements[focusableElements.length - 1];

                    if (e.shiftKey && document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    } else if (!e.shiftKey && document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            });
        }

        // Preload images on hover (mega menu)
        document.querySelectorAll('.has-mega-menu').forEach(item => {
            item.addEventListener('mouseenter', () => {
                const images = item.querySelectorAll('img[loading="lazy"]');
                images.forEach(img => {
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                    }
                });
            });
        });

        // Performance: Debounce scroll
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                // Acciones adicionales después del scroll
            }, 150);
        }, {
            passive: true
        });

        console.log('🌿 Header interactivo cargado - Panamá Sostenible');
    });
    </script>

    <script src="/../js/menu.js" defer></script>
    <script src="/../js/footer.js" defer></script>

    <!-- Main Content -->
    <main id="main-content" role="main">