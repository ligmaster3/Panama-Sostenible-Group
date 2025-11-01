-- ========================================
-- Newsletter Database Schema
-- Panamá Sostenible Group
-- ========================================

-- Tabla de suscriptores del newsletter
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    token VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('pending', 'active', 'unsubscribed') DEFAULT 'pending',
    subscribed_at DATETIME NOT NULL,
    confirmed_at DATETIME NULL,
    unsubscribed_at DATETIME NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_subscribed_at (subscribed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de campañas de newsletter
CREATE TABLE IF NOT EXISTS newsletter_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('draft', 'scheduled', 'sent', 'cancelled') DEFAULT 'draft',
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    total_recipients INT DEFAULT 0,
    opened_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at),
    FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de estadísticas de envío
CREATE TABLE IF NOT EXISTS newsletter_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    subscriber_id INT NOT NULL,
    sent_at DATETIME NOT NULL,
    opened_at DATETIME NULL,
    clicked_at DATETIME NULL,
    bounced BOOLEAN DEFAULT FALSE,
    bounce_reason TEXT NULL,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_subscriber_id (subscriber_id),
    INDEX idx_opened_at (opened_at),
    FOREIGN KEY (campaign_id) REFERENCES newsletter_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (subscriber_id) REFERENCES newsletter_subscribers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Procedimiento almacenado para obtener suscriptores activos
DELIMITER //
CREATE PROCEDURE GetActiveSubscribers()
BEGIN
    SELECT id, email, subscribed_at, confirmed_at
    FROM newsletter_subscribers
    WHERE status = 'active'
    ORDER BY subscribed_at DESC;
END //
DELIMITER ;

-- Procedimiento para dar de baja un suscriptor
DELIMITER //
CREATE PROCEDURE UnsubscribeUser(IN p_email VARCHAR(255))
BEGIN
    UPDATE newsletter_subscribers
    SET status = 'unsubscribed',
        unsubscribed_at = NOW()
    WHERE email = p_email
    AND status = 'active';
    
    SELECT ROW_COUNT() as affected_rows;
END //
DELIMITER ;

-- Vista para estadísticas de campañas
CREATE OR REPLACE VIEW newsletter_campaign_stats AS
SELECT 
    c.id,
    c.title,
    c.subject,
    c.status,
    c.sent_at,
    c.total_recipients,
    c.opened_count,
    c.clicked_count,
    ROUND((c.opened_count / c.total_recipients) * 100, 2) as open_rate,
    ROUND((c.clicked_count / c.total_recipients) * 100, 2) as click_rate
FROM newsletter_campaigns c
WHERE c.total_recipients > 0;

-- Trigger para actualizar estadísticas al abrir email
DELIMITER //
CREATE TRIGGER update_campaign_opens
AFTER UPDATE ON newsletter_stats
FOR EACH ROW
BEGIN
    IF NEW.opened_at IS NOT NULL AND OLD.opened_at IS NULL THEN
        UPDATE newsletter_campaigns
        SET opened_count = opened_count + 1
        WHERE id = NEW.campaign_id;
    END IF;
END //
DELIMITER ;

-- Trigger para actualizar estadísticas al hacer click
DELIMITER //
CREATE TRIGGER update_campaign_clicks
AFTER UPDATE ON newsletter_stats
FOR EACH ROW
BEGIN
    IF NEW.clicked_at IS NOT NULL AND OLD.clicked_at IS NULL THEN
        UPDATE newsletter_campaigns
        SET clicked_count = clicked_count + 1
        WHERE id = NEW.campaign_id;
    END IF;
END //
DELIMITER ;

-- Función para generar token único
DELIMITER //
CREATE FUNCTION generate_unique_token()
RETURNS VARCHAR(64)
DETERMINISTIC
BEGIN
    DECLARE token VARCHAR(64);
    DECLARE token_exists INT;
    
    REPEAT
        SET token = MD5(CONCAT(RAND(), NOW(), RAND()));
        SELECT COUNT(*) INTO token_exists 
        FROM newsletter_subscribers 
        WHERE token = token;
    UNTIL token_exists = 0 END REPEAT;
    
    RETURN token;
END //
DELIMITER ;

-- Datos de ejemplo (opcional - remover en producción)
INSERT INTO newsletter_subscribers (email, token, status, subscribed_at, confirmed_at) VALUES
('ejemplo1@email.com', MD5(CONCAT('token1', NOW())), 'active', NOW(), NOW()),
('ejemplo2@email.com', MD5(CONCAT('token2', NOW())), 'active', NOW(), NOW()),
('ejemplo3@email.com', MD5(CONCAT('token3', NOW())), 'pending', NOW(), NULL);

-- Índices adicionales para optimización
CREATE INDEX idx_newsletter_status_date ON newsletter_subscribers(status, subscribed_at);
CREATE INDEX idx_campaign_status_sent ON newsletter_campaigns(status, sent_at);

-- Evento para limpiar suscripciones no confirmadas (después de 30 días)
DELIMITER //
CREATE EVENT IF NOT EXISTS cleanup_pending_subscriptions
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    DELETE FROM newsletter_subscribers
    WHERE status = 'pending'
    AND subscribed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //
DELIMITER ;

-- Activar el scheduler de eventos
SET GLOBAL event_scheduler = ON;


Total de suscriptores activos
 SELECT COUNT(*) as total_activos FROM newsletter_subscribers WHERE status = 'active';

Tasa de conversión de suscripciones
SELECT 
  COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as confirmados,
    ROUND((SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as tasa_confirmacion
 FROM newsletter_subscribers;

 Nuevos suscriptores por mes
 SELECT 
     DATE_FORMAT(subscribed_at, '%Y-%m') as mes,
     COUNT(*) as nuevos_suscriptores
 FROM newsletter_subscribers
 WHERE status = 'active'
 GROUP BY DATE_FORMAT(subscribed_at, '%Y-%m')
ORDER BY mes DESC;

Rendimiento de campañas
SELECT * FROM newsletter_campaign_stats ORDER BY sent_at DESC LIMIT 10;