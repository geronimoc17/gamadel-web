<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// =========================================================================================
// ARCHIVO: procesar_cv.php
// UBICACIÓN: assets/
// FUNCIÓN: Procesa la postulación, valida y envía el correo con el CV adjunto.
// =========================================================================================

// =======================================================
// === AJUSTES OBLIGATORIOS ===
// =======================================================
// ¡ATENCIÓN! Reemplaza este email por la dirección real de RR. HH. de Gamadel.
$to_email = "gerocepeda@gmail.com"; 
$from_email = "no-reply@gamadel.com.ar"; // Usa un email del dominio si es posible para evitar spam
// La ruta es relativa a la ubicación de este script (assets/) y sube un nivel (../)
// para buscar la carpeta 'uploads' en la raíz del proyecto.
$upload_directory = "../uploads/"; 
// =======================================================

$subject = "Nueva Postulación de CV | Web Gamadel S.A.";
$from_name = "Sistema Web Gamadel";
$allowed_extensions = array('pdf', 'doc', 'docx');

// Respuesta por defecto (JSON)
$response = array('success' => false, 'message' => 'Hubo un error desconocido en el servidor.');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ----------------------------------------
    // A. MEDIDA ANTI-SPAM: HONEYPOT
    // ----------------------------------------
    if (!empty($_POST['spam_check'])) {
        $response['message'] = "Error de validación anti-spam (honeypot).";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // ----------------------------------------
    // B. VALIDACIÓN DE CAMPOS REQUERIDOS
    // ----------------------------------------
    $required_fields = ['nombre', 'ciudad', 'telefono', 'email'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $response['message'] = "Falta completar el campo requerido: " . $field;
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
    
    // ----------------------------------------
    // C. VALIDACIÓN Y MANEJO DEL ARCHIVO
    // ----------------------------------------
    if (empty($_FILES['cv_file']) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = "No se subió ningún archivo o hubo un error en la subida.";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    $file_name = $_FILES['cv_file']['name'];
    $file_tmp_name = $_FILES['cv_file']['tmp_name'];
    $file_size = $_FILES['cv_file']['size'];
    
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Verificar extensión del archivo
    if (!in_array($file_ext, $allowed_extensions)) {
        $response['message'] = "Solo se permiten archivos PDF, DOC o DOCX.";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Verificar tamaño (máximo 5MB)
    if ($file_size > 5 * 1024 * 1024) { 
        $response['message'] = "El archivo es demasiado grande. Máximo 5MB.";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Asegurar que el directorio de subida exista
    if (!is_dir($upload_directory)) {
        // En un entorno de producción, es mejor que este directorio ya exista y tenga permisos
        // En algunos servidores, intentar crear el directorio aquí puede fallar
        // @mkdir($upload_directory, 0777, true); 
        $response['message'] = "Error de configuración del servidor: Falta el directorio de subida ('uploads').";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Mover archivo subido al directorio temporal
    $new_file_name = uniqid('cv_') . '.' . $file_ext;
    $upload_path = $upload_directory . $new_file_name;
    
    if (!move_uploaded_file($file_tmp_name, $upload_path)) {
        $response['message'] = "Error al mover el archivo subido. Verifique los permisos de la carpeta 'uploads'.";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // ----------------------------------------
    // D. PREPARACIÓN DEL CONTENIDO DEL CORREO
    // ----------------------------------------
    
    $nombre = htmlspecialchars($_POST['nombre']);
    $ciudad = htmlspecialchars($_POST['ciudad']);
    $telefono = htmlspecialchars($_POST['telefono']);
    $email = htmlspecialchars($_POST['email']);
    $message_body = htmlspecialchars($_POST['motivacion']);

    $body = "
    <html>
    <head>
        <title>{$subject}</title>
    </head>
    <body>
        <h2>Nueva Postulación Recibida</h2>
        <p><strong>Nombre y Apellido:</strong> {$nombre}</p>
        <p><strong>Ciudad / Provincia:</strong> {$ciudad}</p>
        <p><strong>Teléfono:</strong> {$telefono}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Aviso Legal Aceptado:</strong> Sí</p>
        <hr>
        <h3>Mensaje de Motivación:</h3>
        <p>" . nl2br($message_body) . "</p>
        <hr>
        <p>El Curriculum Vitae se adjunta a este correo.</p>
        <p><small>Enviado desde el formulario de Trabaja con Nosotros de Gamadel S.A.</small></p>
    </body>
    </html>
    ";

    // ----------------------------------------
    // E. ENVÍO DEL CORREO CON ADJUNTO (Función nativa mail())
    // ----------------------------------------
    
    $semi_rand = md5(time());
    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 
    
    $headers = "From: {$from_name} <{$from_email}>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed;\r\n" . " boundary=\"{$mime_boundary}\""; 
    
    $email_message = "This is a multi-part message in MIME format.\r\n\r\n" . "--{$mime_boundary}\r\n";
    $email_message .= "Content-Type: text/html; charset=\"UTF-8\"\r\n" . "Content-Transfer-Encoding: 8bit\r\n\r\n" . $body . "\r\n\r\n";
    
    // Adjuntar archivo
    $data = chunk_split(base64_encode(file_get_contents($upload_path)));
    $email_message .= "--{$mime_boundary}\r\n";
    $email_message .= "Content-Type: application/octet-stream; name=\"{$file_name}\"\r\n";
    $email_message .= "Content-Disposition: attachment; filename=\"{$file_name}\"\r\n";
    $email_message .= "Content-Transfer-Encoding: base64\r\n\r\n" . $data . "\r\n\r\n";
    $email_message .= "--{$mime_boundary}--\r\n";
    
    // Intentar enviar
    if (mail($to_email, $subject, $email_message, $headers)) {
        $response['success'] = true;
        $response['message'] = "Postulación enviada con éxito. ¡Gracias!";
    } else {
        $response['message'] = "Error al enviar el correo. Verifique la configuración 'mail()' del servidor.";
    }

    // ----------------------------------------
    // F. LIMPIEZA
    // ----------------------------------------
    unlink($upload_path); // Eliminar el archivo temporal del servidor

}

// Devolver la respuesta al JavaScript
header('Content-Type: application/json');
echo json_encode($response);
?>