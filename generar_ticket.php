<?php
session_start();
require_once 'conexion.php';

// CLASE CUSTOM PHPMAILER SOCKET CON SOPORTE CID (MULTIPART/RELATED)
class LocalPHPMailer {
    public $Host = 'smtp.gmail.com';
    public $Port = 465;
    public $Username = 'brianrubengb@gmail.com'; // <-- TU CORREO DE GMAIL AQUÍ
    public $Password = 'rmur aqja wwvj hpeh';     // <-- TU CLAVE DE APLICACIÓN DE 16 DÍGITOS AQUÍ
    public $FromName = 'Mi Tienda POS';

    private $to = [];
    public $Subject = '';
    public $Body = '';
    private $attachments = [];

    public function addAddress($address) { 
        $this->to[] = $address; 
    }

    public function addEmbeddedImage($blob_data, $cid, $mime_type = 'image/jpeg') {
        $this->attachments[] = [
            'data' => $blob_data,
            'cid'  => $cid,
            'mime' => $mime_type
        ];
    }

    public function send() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $remote_socket = 'ssl://' . $this->Host . ':' . $this->Port;
        $socket = @stream_socket_client($remote_socket, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            throw new Exception("No se pudo conectar al servidor SMTP de Gmail: $errstr ($errno)");
        }

        $this->read($socket);
        $this->write($socket, "EHLO " . gethostname());
        $this->write($socket, "AUTH LOGIN");
        $this->write($socket, base64_encode($this->Username));
        $this->write($socket, base64_encode($this->Password));

        $this->write($socket, "MAIL FROM: <" . $this->Username . ">");
        foreach ($this->to as $recipient) {
            $this->write($socket, "RCPT TO: <" . $recipient . ">");
        }

        $this->write($socket, "DATA");

        $boundary = "----=_NextPart_" . md5(time());

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: " . $this->FromName . " <" . $this->Username . ">\r\n";
        $headers .= "To: " . implode(',', $this->to) . "\r\n";
        $headers .= "Subject: " . $this->Subject . "\r\n";
        $headers .= "Content-Type: multipart/related; boundary=\"" . $boundary . "\"\r\n";

        // Cuerpo HTML
        $message  = $headers . "\r\n";
        $message .= "--" . $boundary . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $this->Body . "\r\n\r\n";

        // Adjuntos embebidos CID
        foreach ($this->attachments as $att) {
            $message .= "--" . $boundary . "\r\n";
            $message .= "Content-Type: " . $att['mime'] . "; name=\"img_" . $att['cid'] . "\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-ID: <" . $att['cid'] . ">\r\n";
            $message .= "Content-Disposition: inline; filename=\"img_" . $att['cid'] . "\"\r\n\r\n";
            $message .= chunk_split(base64_encode($att['data'])) . "\r\n";
        }

        $message .= "--" . $boundary . "--\r\n.";

        $this->write($socket, $message);
        $this->write($socket, "QUIT");
        fclose($socket);

        return true;
    }

    private function write($socket, $cmd) { 
        fputs($socket, $cmd . "\r\n"); 
        return $this->read($socket); 
    }

    private function read($socket) {
        $res = '';
        while ($str = fgets($socket, 512)) {
            $res .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $res;
    }
}

// CONTROL DE ACCESO
if (!isset($_SESSION['usuario_id']) && !isset($_POST['enviar_correo'])) {
    if (empty($_SESSION['carrito'])) {
        header("Location: carrito.php");
        exit;
    }
}

$mensaje_mail = '';
$error_mail = '';

// PROCESAR COMPRA Y REGISTRAR EN BD
if (!empty($_SESSION['carrito'])) {
    $pdo->beginTransaction();

    try {
        $total_general_compra = 0;

        foreach ($_SESSION['carrito'] as $id_producto => $item) {
            $stmt_check = $pdo->prepare("SELECT stock FROM productos WHERE id = :id FOR UPDATE");
            $stmt_check->execute(['id' => $id_producto]);
            $prod_db = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if (!$prod_db || $prod_db['stock'] < $item['cantidad']) {
                throw new Exception("Stock insuficiente para: " . $item['producto']);
            }

            $stmt_update = $pdo->prepare("UPDATE productos SET stock = stock - :cant WHERE id = :id");
            $stmt_update->execute([
                'cant' => $item['cantidad'],
                'id'   => $id_producto
            ]);

            $subtotal_item = $item['precio'] * $item['cantidad'];
            $total_general_compra += ($subtotal_item * 1.16);
        }

        $stmt_venta = $pdo->prepare("INSERT INTO ventas (usuario_id, usuario_nombre, total, fecha) VALUES (:uid, :unom, :tot, NOW())");
        $stmt_venta->execute([
            'uid'  => $_SESSION['usuario_id'],
            'unom' => $_SESSION['usuario'],
            'tot'  => $total_general_compra
        ]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al procesar la compra: " . $e->getMessage());
    }

    $_SESSION['ultimo_ticket'] = [
        'usuario' => $_SESSION['usuario'],
        'fecha'   => date('Y-m-d H:i:s'),
        'carrito' => $_SESSION['carrito']
    ];

    $_SESSION['carrito'] = [];
}

$ticket_data = $_SESSION['ultimo_ticket'] ?? null;

if (!$ticket_data) {
    header("Location: carrito.php");
    exit;
}

// PROCESAR ENVÍO DE CORREO
if (isset($_POST['enviar_correo'])) {
    $email_destino = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

    if ($email_destino) {
        try {
            $mail = new LocalPHPMailer();

            // =========================================================
            // VERIFICA AQUÍ TUS CREDENCIALES DE GMAIL
            // =========================================================
            // $mail->Username = 'tu_correo@gmail.com';
            // $mail->Password = 'tu_clave_de_16_digitos';

            $mail->addAddress($email_destino);

            $html_content = "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; padding: 20px; }
                    .ticket-box { width: 100%; max-width: 620px; border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin: 0 auto; }
                    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 13px; }
                    th { background-color: #0275d8; color: white; }
                    .img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
                    .total { text-align: right; margin-top: 15px; font-size: 16px; font-weight: bold; color: #28a745; }
                </style>
            </head>
            <body>
                <div class='ticket-box'>
                    <h2>TICKET DE COMPRA</h2>
                    <p><strong>Cliente:</strong> " . htmlspecialchars($ticket_data['usuario']) . "<br>
                    <strong>Fecha:</strong> " . $ticket_data['fecha'] . "</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Producto</th>
                                <th>Cant.</th>
                                <th>P. Unit.</th>
                                <th>Subtotal</th>
                                <th>Total (+16% IVA)</th>
                            </tr>
                        </thead>
                        <tbody>";

            $subtotal_gen = 0;
            $iva_gen = 0;
            $total_gen = 0;

            foreach ($ticket_data['carrito'] as $id_prod => $item) {
                $subtotal_item = $item['precio'] * $item['cantidad'];
                $iva_item = $subtotal_item * 0.16;
                $total_item = $subtotal_item + $iva_item;

                $subtotal_gen += $subtotal_item;
                $iva_gen += $iva_item;
                $total_gen += $total_item;

                $stmt_img = $pdo->prepare("SELECT imagen FROM productos WHERE id = :id");
                $stmt_img->execute(['id' => $id_prod]);
                $prod_img = $stmt_img->fetch(PDO::FETCH_ASSOC);

                $img_html = '<span style="font-size: 11px; color: #888;">Sin foto</span>';
                
                if ($prod_img && !empty($prod_img['imagen'])) {
                    $blob_data = $prod_img['imagen'];
                    
                    $mime_type = 'image/jpeg';
                    if (function_exists('getimagesizefromstring')) {
                        $info = @getimagesizefromstring($blob_data);
                        if ($info && !empty($info['mime'])) {
                            $mime_type = $info['mime'];
                        }
                    }

                    $cid = 'prod_img_' . $id_prod;
                    $mail->addEmbeddedImage($blob_data, $cid, $mime_type);
                    $img_html = '<img src="cid:' . $cid . '" class="img-thumb" alt="' . htmlspecialchars($item['producto']) . '">';
                }

                $html_content .= "
                    <tr>
                        <td>" . $img_html . "</td>
                        <td>" . htmlspecialchars($item['producto']) . "</td>
                        <td>" . $item['cantidad'] . "</td>
                        <td>$" . number_format($item['precio'], 2) . "</td>
                        <td>$" . number_format($subtotal_item, 2) . "</td>
                        <td>$" . number_format($total_item, 2) . "</td>
                    </tr>";
            }

            $html_content .= "
                        </tbody>
                    </table>
                    <div class='total'>
                        <p>Subtotal: $" . number_format($subtotal_gen, 2) . "</p>
                        <p>IVA (16%): $" . number_format($iva_gen, 2) . "</p>
                        <p>TOTAL COMPRA: $" . number_format($total_gen, 2) . "</p>
                    </div>
                </div>
            </body>
            </html>";

            $mail->Subject = "Ticket de Compra - " . $ticket_data['usuario'];
            $mail->Body    = $html_content;

            if ($mail->send()) {
                $mensaje_mail = "📧 <strong>¡Ticket con imágenes enviado con éxito!</strong> Revisa la bandeja de entrada de: <strong>" . htmlspecialchars($email_destino) . "</strong>";
            }
        } catch (Exception $e) {
            $error_mail = "Error al enviar el correo: " . $e->getMessage();
        }
    } else {
        $error_mail = "Ingresa una dirección de correo válida.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Compra</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f4f4f9; display: flex; justify-content: center; }
        .ticket-box { background: white; width: 620px; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: 1px solid #ddd; }
        .header-ticket { text-align: center; border-bottom: 2px dashed #bbb; padding-bottom: 10px; margin-bottom: 15px; }
        .info-compra { font-size: 14px; color: #444; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; font-size: 14px; }
        th { background: #0275d8; color: white; }
        .img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .totales-box { margin-top: 15px; text-align: right; font-size: 15px; }
        .total-destacado { color: #28a745; font-size: 18px; font-weight: bold; }
        .btn-actions { margin-top: 25px; text-align: center; }
        .btn { padding: 8px 15px; border-radius: 4px; text-decoration: none; color: white; font-weight: bold; display: inline-block; font-size: 14px; border: none; cursor: pointer; }
        .btn-blue { background: #0275d8; }
        .btn-green { background: #28a745; }
        .email-form { background: #eef6ff; padding: 15px; border-radius: 6px; border: 1px solid #b6d4fe; margin-top: 20px; }
        .email-form input[type="email"] { padding: 8px; width: 250px; border-radius: 4px; border: 1px solid #ccc; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-top: 15px; text-align: center; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-top: 15px; text-align: center; }
        @media print { .btn-actions, .email-form, .alert-success, .alert-error { display: none; } body { background: white; margin: 0; } .ticket-box { box-shadow: none; border: none; width: 100%; } }
    </style>
</head>
<body>

    <div class="ticket-box">
        <div class="header-ticket">
            <h2 style="margin: 0; color: #333;">TICKET DE COMPRA</h2>
            <small>Sistema de Ventas - Mi Tienda</small>
        </div>

        <div class="info-compra">
            <strong>Cliente:</strong> <?= htmlspecialchars($ticket_data['usuario']) ?><br>
            <strong>Fecha y Hora:</strong> <?= $ticket_data['fecha'] ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>P. Unit.</th>
                    <th>Subtotal</th>
                    <th>Total (+16% IVA)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal_general = 0;
                $iva_general      = 0;
                $total_general    = 0;

                foreach ($ticket_data['carrito'] as $id_prod => $item): 
                    $subtotal_item = $item['precio'] * $item['cantidad'];
                    $iva_item      = $subtotal_item * 0.16;
                    $total_item    = $subtotal_item + $iva_item;

                    $subtotal_general += $subtotal_item;
                    $iva_general      += $iva_item;
                    $total_general    += $total_item;

                    $stmt_img = $pdo->prepare("SELECT (imagen IS NOT NULL AND LENGTH(imagen) > 0) AS tiene_imagen FROM productos WHERE id = :id");
                    $stmt_img->execute(['id' => $id_prod]);
                    $has_img = $stmt_img->fetch(PDO::FETCH_ASSOC);
                ?>
                    <tr>
                        <td>
                            <?php if ($has_img && $has_img['tiene_imagen']): ?>
                                <img src="ver_imagen.php?id=<?= $id_prod ?>" class="img-thumb" alt="prod">
                            <?php else: ?>
                                <span style="font-size: 11px; color: #888;">Sin foto</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($item['producto']) ?></strong></td>
                        <td><?= $item['cantidad'] ?></td>
                        <td>$<?= number_format($item['precio'], 2) ?></td>
                        <td>$<?= number_format($subtotal_item, 2) ?></td>
                        <td>$<?= number_format($total_item, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totales-box">
            <p style="margin: 4px 0;">Subtotal: <strong>$<?= number_format($subtotal_general, 2) ?></strong></p>
            <p style="margin: 4px 0;">IVA (16%): <strong>$<?= number_format($iva_general, 2) ?></strong></p>
            <p class="total-destacado" style="margin: 6px 0;">TOTAL: $<?= number_format($total_general, 2) ?></p>
        </div>

        <div class="email-form">
            <h4 style="margin: 0 0 10px 0; color: #0275d8;">📩 Enviar Ticket a un Correo Real</h4>
            <form method="POST" action="generar_ticket.php">
                <input type="email" name="email" placeholder="correo_cliente@ejemplo.com" required>
                <button type="submit" name="enviar_correo" class="btn btn-blue">Enviar por Correo</button>
            </form>
        </div>

        <?php if ($mensaje_mail): ?>
            <div class="alert-success"><?= $mensaje_mail ?></div>
        <?php endif; ?>

        <?php if ($error_mail): ?>
            <div class="alert-error"><?= $error_mail ?></div>
        <?php endif; ?>

        <div class="btn-actions">
            <button onclick="window.print();" class="btn btn-green">🖨️ Imprimir / Guardar PDF</button>
            <a href="carrito.php" class="btn btn-blue" style="margin-left: 10px;">🛒 Volver a la Tienda</a>
        </div>
    </div>

</body>
</html>