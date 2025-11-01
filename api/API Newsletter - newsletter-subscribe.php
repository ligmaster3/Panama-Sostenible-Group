<?php
/**
 * Newsletter Subscription API
 * Panamá Sostenible Group
 */

// Headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validar email
if (!isset($data['email']) || empty($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email requerido']);
    exit;
}

$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email inválido']);
    exit;
}

// Conectar a la base de datos
require_once '../config/database.php';

try {
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'error' => 'Este email ya está suscrito',
            'message' => 'Ya estás recibiendo nuestras actualizaciones'
        ]);
        exit;
    }

    // Insertar nuevo suscriptor
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("
        INSERT INTO newsletter_subscribers (email, token, subscribed_at, ip_address, status) 
        VALUES (?, ?, NOW(), ?, 'pending')
    ");
    
    $stmt->execute([
        $email,
        $token,
        $_SERVER['REMOTE_ADDR']
    ]);

    // Enviar email de confirmación (opcional)
    sendConfirmationEmail($email, $token);

    // Respuesta exitosa
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => '¡Gracias por suscribirte! Revisa tu correo para confirmar.'
    ]);

} catch (PDOException $e) {
    error_log('Newsletter subscription error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al procesar la suscripción',
        'message' => 'Por favor, intenta nuevamente más tarde'
    ]);
}

/**
 * Enviar email de confirmación
 */
function sendConfirmationEmail($email, $token) {
    $confirmUrl = "https://" . $_SERVER['HTTP_HOST'] . "/confirm-newsletter.php?token=" . $token;
    
    $subject = "Confirma tu suscripción - Panamá Sostenible Group";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2d7a3e; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background: #f8fafc; }
            .button { display: inline-block; padding: 12px 30px; background: #2d7a3e; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #64748b; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>¡Bienvenido a Panamá Sostenible Group!</h1>
            </div>
            <div class='content'>
                <p>¡Gracias por suscribirte a nuestro newsletter!</p>
                <p>Para completar tu suscripción, por favor confirma tu correo electrónico haciendo clic en el siguiente botón:</p>
                <p style='text-align: center;'>
                    <a href='$confirmUrl' class='button'>Confirmar Suscripción</a>
                </p>
                <p>O copia y pega este enlace en tu navegador:</p>
                <p style='word-break: break-all; color: #0284c7;'>$confirmUrl</p>
                <p>Si no solicitaste esta suscripción, puedes ignorar este mensaje.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Panamá Sostenible Group. Todos los derechos reservados.</p>
                <p>Ciudad de Panamá, Panamá</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Panamá Sostenible Group <noreply@panamasostenible.com>" . "\r\n";

    return mail($email, $subject, $message, $headers);
}
?>