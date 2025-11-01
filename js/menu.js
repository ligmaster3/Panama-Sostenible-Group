// Menu Manager - Gestión completa del header y navegación
class MenuManager {
    constructor() {
        this.isMobile = window.innerWidth < 768;
        this.isScrolled = false;
        this.isDrawerOpen = false;
        this.isUserMenuOpen = false;
        this.isSearchOpen = false;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupAccessibility();
        this.handleScroll();
        this.setupResizeObserver();
        
        // Cerrar menús al hacer clic fuera
        this.setupClickOutside();
        
        console.log('MenuManager initialized');
    }

    bindEvents() {
        // Mobile toggle
        const mobileToggle = document.getElementById('mobileToggle');
        const drawerClose = document.getElementById('drawerClose');
        const drawerOverlay = document.getElementById('drawerOverlay');
        
        if (mobileToggle) {
            mobileToggle.addEventListener('click', this.toggleDrawer.bind(this));
            mobileToggle.addEventListener('keydown', this.handleToggleKeydown.bind(this));
        }
        
        if (drawerClose) {
            drawerClose.addEventListener('click', this.closeDrawer.bind(this));
        }
        
        if (drawerOverlay) {
            drawerOverlay.addEventListener('click', this.closeDrawer.bind(this));
        }

        // User menu
        const userMenuBtn = document.getElementById('userMenuBtn');
        if (userMenuBtn) {
            userMenuBtn.addEventListener('click', this.toggleUserMenu.bind(this));
            userMenuBtn.addEventListener('keydown', this.handleUserMenuKeydown.bind(this));
        }

        // Search button
        const searchBtn = document.getElementById('searchBtn');
        if (searchBtn) {
            searchBtn.addEventListener('click', this.toggleSearch.bind(this));
        }

        // Mega menu hover
        this.setupMegaMenu();

        // Scroll event
        window.addEventListener('scroll', this.throttle(this.handleScroll.bind(this), 100));

        // Keyboard navigation
        document.addEventListener('keydown', this.handleGlobalKeydown.bind(this));

        // Focus management
        this.setupFocusManagement();
    }

    setupAccessibility() {
        // Agregar roles ARIA dinámicamente si es necesario
        const megaMenuItems = document.querySelectorAll('.has-mega-menu .nav-link');
        megaMenuItems.forEach(item => {
            item.setAttribute('aria-haspopup', 'true');
            item.setAttribute('aria-expanded', 'false');
        });
    }

    setupResizeObserver() {
        const resizeObserver = new ResizeObserver(entries => {
            for (let entry of entries) {
                const newIsMobile = entry.contentRect.width < 768;
                if (newIsMobile !== this.isMobile) {
                    this.isMobile = newIsMobile;
                    this.handleResize();
                }
            }
        });

        resizeObserver.observe(document.body);
    }

    handleResize() {
        if (!this.isMobile && this.isDrawerOpen) {
            this.closeDrawer();
        }
        
        // Ajustar comportamientos específicos para mobile/desktop
        this.adjustMenuBehavior();
    }

    adjustMenuBehavior() {
        const megaMenus = document.querySelectorAll('.has-mega-menu');
        
        if (this.isMobile) {
            // En mobile, los mega menus se convierten en enlaces simples
            megaMenus.forEach(menu => {
                menu.classList.remove('has-mega-menu');
            });
        } else {
            // En desktop, restaurar mega menus
            megaMenus.forEach(menu => {
                menu.classList.add('has-mega-menu');
            });
        }
    }

    toggleDrawer() {
        if (this.isDrawerOpen) {
            this.closeDrawer();
        } else {
            this.openDrawer();
        }
    }

    openDrawer() {
        const drawer = document.getElementById('mobileDrawer');
        const overlay = document.getElementById('drawerOverlay');
        const toggle = document.getElementById('mobileToggle');
        
        if (!drawer || !overlay) return;
        
        drawer.setAttribute('aria-hidden', 'false');
        drawer.removeAttribute('hidden');
        overlay.classList.add('active');
        this.isDrawerOpen = true;
        
        // Animar hamburguer icon
        if (toggle) {
            toggle.classList.add('active');
            const spans = toggle.querySelectorAll('span');
            if (spans.length === 3) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            }
        }
        
        // Trap focus inside drawer
        this.trapFocus(drawer);
        
        // Desplazamiento suave al abrir
        setTimeout(() => {
            drawer.style.transform = 'translateX(0)';
        }, 10);
        
        // Emitir evento personalizado
        this.emitEvent('drawer:open');
    }

    closeDrawer() {
        const drawer = document.getElementById('mobileDrawer');
        const overlay = document.getElementById('drawerOverlay');
        const toggle = document.getElementById('mobileToggle');
        
        if (!drawer || !overlay) return;
        
        drawer.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('active');
        this.isDrawerOpen = false;
        
        // Restaurar hamburguer icon
        if (toggle) {
            toggle.classList.remove('active');
            const spans = toggle.querySelectorAll('span');
            if (spans.length === 3) {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        }
        
        // Liberar focus trap
        this.releaseFocus();
        
        // Desplazamiento suave al cerrar
        drawer.style.transform = 'translateX(100%)';
        
        setTimeout(() => {
            drawer.setAttribute('hidden', '');
        }, 300);
        
        // Emitir evento personalizado
        this.emitEvent('drawer:close');
    }

    toggleUserMenu() {
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        
        if (!userMenuBtn || !userDropdown) return;
        
        if (this.isUserMenuOpen) {
            this.closeUserMenu();
        } else {
            this.openUserMenu();
        }
    }

    openUserMenu() {
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        
        userMenuBtn.setAttribute('aria-expanded', 'true');
        userDropdown.style.opacity = '1';
        userDropdown.style.visibility = 'visible';
        userDropdown.style.transform = 'translateY(0)';
        this.isUserMenuOpen = true;
        
        // Trap focus en el dropdown
        this.trapFocus(userDropdown);
    }

    closeUserMenu() {
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        
        userMenuBtn.setAttribute('aria-expanded', 'false');
        userDropdown.style.opacity = '0';
        userDropdown.style.visibility = 'hidden';
        userDropdown.style.transform = 'translateY(-10px)';
        this.isUserMenuOpen = false;
        
        // Liberar focus
        this.releaseFocus();
    }

    toggleSearch() {
        // Implementar lógica de búsqueda aquí
        console.log('Search functionality to be implemented');
        this.emitEvent('search:toggle');
    }

    setupMegaMenu() {
        const megaMenuItems = document.querySelectorAll('.has-mega-menu');
        
        megaMenuItems.forEach(item => {
            const link = item.querySelector('.nav-link');
            const megaMenu = item.querySelector('.mega-menu');
            
            if (!link || !megaMenu) return;
            
            // Hover para desktop
            item.addEventListener('mouseenter', () => {
                if (!this.isMobile) {
                    this.openMegaMenu(item);
                }
            });
            
            item.addEventListener('mouseleave', () => {
                if (!this.isMobile) {
                    this.closeMegaMenu(item);
                }
            });
            
            // Focus para accesibilidad
            link.addEventListener('focus', () => {
                if (!this.isMobile) {
                    this.openMegaMenu(item);
                }
            });
            
            item.addEventListener('focusout', (e) => {
                if (!this.isMobile && !item.contains(e.relatedTarget)) {
                    this.closeMegaMenu(item);
                }
            });
        });
    }

    openMegaMenu(item) {
        const link = item.querySelector('.nav-link');
        const megaMenu = item.querySelector('.mega-menu');
        
        if (link && megaMenu) {
            link.setAttribute('aria-expanded', 'true');
            megaMenu.style.opacity = '1';
            megaMenu.style.visibility = 'visible';
            megaMenu.style.transform = 'translateY(0)';
        }
    }

    closeMegaMenu(item) {
        const link = item.querySelector('.nav-link');
        const megaMenu = item.querySelector('.mega-menu');
        
        if (link && megaMenu) {
            link.setAttribute('aria-expanded', 'false');
            megaMenu.style.opacity = '0';
            megaMenu.style.visibility = 'hidden';
            megaMenu.style.transform = 'translateY(-10px)';
        }
    }

    handleScroll() {
        const scrollY = window.scrollY;
        const header = document.getElementById('mainHeader');
        
        if (!header) return;
        
        if (scrollY > 100 && !this.isScrolled) {
            header.classList.add('scrolled');
            this.isScrolled = true;
        } else if (scrollY <= 100 && this.isScrolled) {
            header.classList.remove('scrolled');
            this.isScrolled = false;
        }
    }

    setupClickOutside() {
        document.addEventListener('click', (e) => {
            // Cerrar user menu si se hace clic fuera
            const userMenu = document.querySelector('.user-menu');
            if (this.isUserMenuOpen && userMenu && !userMenu.contains(e.target)) {
                this.closeUserMenu();
            }
            
            // Cerrar mega menus si se hace clic fuera (solo desktop)
            if (!this.isMobile) {
                const megaMenus = document.querySelectorAll('.has-mega-menu');
                megaMenus.forEach(menu => {
                    if (!menu.contains(e.target)) {
                        this.closeMegaMenu(menu);
                    }
                });
            }
        });
    }

    handleGlobalKeydown(e) {
        // Escape key cierra todos los menús
        if (e.key === 'Escape') {
            if (this.isDrawerOpen) {
                this.closeDrawer();
            }
            if (this.isUserMenuOpen) {
                this.closeUserMenu();
            }
            if (this.isSearchOpen) {
                this.closeSearch();
            }
        }
    }

    handleToggleKeydown(e) {
        // Enter o Space para toggle
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.toggleDrawer();
        }
    }

    handleUserMenuKeydown(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.toggleUserMenu();
        } else if (e.key === 'ArrowDown' && !this.isUserMenuOpen) {
            e.preventDefault();
            this.openUserMenu();
        } else if (e.key === 'Escape' && this.isUserMenuOpen) {
            this.closeUserMenu();
        }
    }

    trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length === 0) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        element.addEventListener('keydown', function trapHandler(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            } else if (e.key === 'Escape') {
                this.closeDrawer();
                element.removeEventListener('keydown', trapHandler);
            }
        }.bind(this));
        
        firstElement.focus();
    }

    releaseFocus() {
        // Restaurar focus al elemento que lo tenía antes
        const mobileToggle = document.getElementById('mobileToggle');
        const userMenuBtn = document.getElementById('userMenuBtn');
        
        if (this.isDrawerOpen && mobileToggle) {
            mobileToggle.focus();
        } else if (this.isUserMenuOpen && userMenuBtn) {
            userMenuBtn.focus();
        }
    }

    setupFocusManagement() {
        // Guardar el último elemento enfocado
        let lastFocusedElement;
        
        document.addEventListener('focusin', (e) => {
            lastFocusedElement = e.target;
        });
        
        // Restaurar focus cuando se cierran modales
        this.on('drawer:close', () => {
            if (lastFocusedElement) {
                lastFocusedElement.focus();
            }
        });
    }

    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }

    emitEvent(name, detail = {}) {
        const event = new CustomEvent(name, { 
            detail,
            bubbles: true 
        });
        document.dispatchEvent(event);
    }

    on(eventName, callback) {
        document.addEventListener(eventName, callback);
    }

    off(eventName, callback) {
        document.removeEventListener(eventName, callback);
    }

    // Métodos públicos para control externo
    openMobileMenu() {
        this.openDrawer();
    }

    closeMobileMenu() {
        this.closeDrawer();
    }

    destroy() {
        // Cleanup si es necesario
        window.removeEventListener('scroll', this.handleScroll);
        document.removeEventListener('keydown', this.handleGlobalKeydown);
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.menuManager = new MenuManager();
    
    // Prevenir el comportamiento por defecto de los enlaces vacíos
    document.addEventListener('click', function(e) {
        if (e.target.matches('a[href="#"]') || e.target.closest('a[href="#"]')) {
            e.preventDefault();
        }
    });
});

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MenuManager;
}