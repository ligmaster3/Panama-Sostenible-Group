<?php
/**
 * Newsletter Confirmation Page
 * Panamá Sostenible Group
 */

session_start();
require_once 'config/database.php';

$pageTitle = "Confirmación de Suscripción";
$message = '';
$messageType = '';

// Verificar token
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Buscar suscriptor por token
        $stmt = $pdo->prepare("
            SELECT id, email, status 
            FROM newsletter_subscribers 
            WHERE token = ? 
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $subscriber = $stmt->fetch();
        
        if ($subscriber) {
            if ($subscriber['status'] === 'active') {
                $message = '¡Tu email ya estaba confirmado! Gracias por ser parte de nuestra comunidad.';
                $messageType = 'info';
            } else {
                // Confirmar suscripción
                $updateStmt = $pdo->prepare("
                    UPDATE newsletter_subscribers 
                    SET status = 'active', 
                        confirmed_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$subscriber['id']]);
                
                $message = '¡Suscripción confirmada! Ahora recibirás nuestras actualizaciones y ofertas exclusivas.';
                $messageType = 'success';
                
                // Log para analytics
                error_log("Newsletter confirmed: " . $subscriber['email']);
            }
        } else {
            $message = 'Token inválido o expirado. Por favor, intenta suscribirte nuevamente.';
            $messageType = 'error';
        }
    } catch (PDOException $e) {
        error_log('Newsletter confirmation error: ' . $e->getMessage());
        $message = 'Ocurrió un error. Por favor, intenta nuevamente más tarde.';
        $messageType = 'error';
    }
} else {
    $message = 'No se proporcionó un token de confirmación válido.';
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Panamá Sostenible Group</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
    .confirmation-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #e8f5e9 0%, #f8fafc 100%);
        padding: 2rem;
    }

    .confirmation-card {
        max-width: 600px;
        width: 100%;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        padding: 3rem;
        text-align: center;
    }

    .icon-wrapper {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
    }

    .icon-wrapper.success {
        background: #dcfce7;
        color: #16a34a;
    }

    .icon-wrapper.error {
        background: #fee2e2;
        color: #dc2626;
    }

    .icon-wrapper.info {
        background: #dbeafe;
        color: #2563eb;
    }

    .confirmation-card h1 {
        font-size: 2rem;
        color: #1e293b;
        margin-bottom: 1rem;
    }

    .confirmation-card p {
        font-size: 1.125rem;
        color: #64748b;
        line-height: 1.8;
        margin-bottom: 2rem;
    }

    .btn-group {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.875rem 1.75rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: #2d7a3e;
        color: white;
    }

    .btn-primary:hover {
        background: #245e31;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(45, 122, 62, 0.3);
    }

    .btn-secondary {
        background: white;
        color: #2d7a3e;
        border: 2px solid #2d7a3e;
    }

    .btn-secondary:hover {
        background: #e8f5e9;
    }

    @media (max-width: 768px) {
        .confirmation-card {
            padding: 2rem 1.5rem;
        }

        .confirmation-card h1 {
            font-size: 1.5rem;
        }

        .btn-group {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }
    </style>
</head>

<body>
    <div class="confirmation-page">
        <div class="confirmation-card">
            <div class="icon-wrapper <?php echo $messageType; ?>">
                <?php if ($messageType === 'success'): ?>
                ✓
                <?php elseif ($messageType === 'error'): ?>
                ✕
                <?php else: ?>
                ℹ
                <?php endif; ?>
            </div>

            <h1>
                <?php if ($messageType === 'success'): ?>
                ¡Confirmación Exitosa!
                <?php elseif ($messageType === 'error'): ?>
                Error de Confirmación
                <?php else: ?>
                Ya estás suscrito
                <?php endif; ?>
            </h1>

            <p><?php echo htmlspecialchars($message); ?></p>

            <?php if ($messageType === 'success' || $messageType === 'info'): ?>
            <div class="benefits">
                <p style="font-size: 1rem; color: #475569; margin-bottom: 1rem;">
                    <strong>¿Qué puedes esperar?</strong>
                </p>
                <ul style="text-align: left; max-width: 400px; margin: 0 auto 2rem; color: #64748b;">
                    <li style="margin-bottom: 0.5rem;">🌿 Ofertas exclusivas de viajes sostenibles</li>
                    <li style="margin-bottom: 0.5rem;">📍 Descubre destinos únicos en Panamá</li>
                    <li style="margin-bottom: 0.5rem;">💡 Tips y consejos de turismo responsable</li>
                    <li style="margin-bottom: 0.5rem;">🎁 Descuentos especiales para suscriptores</li>
                </ul>
            </div>
            <?php endif; ?>

            <div class="btn-group">
                <a href="index.php" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor"
                            stroke-width="2" />
                    </svg>
                    Ir al Inicio
                </a>
                <a href="servicios.php" class="btn btn-secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" />
                        <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    Ver Servicios
                </a>
            </div>
        </div>
    </div>

    <script>
    // Confetti animation para éxito (opcional)
    <?php if ($messageType === 'success'): ?>
    setTimeout(() => {
        console.log('🎉 ¡Bienvenido a Panamá Sostenible Group!');
    }, 100);
    <?php endif; ?>
    </script>
</body>

</html>