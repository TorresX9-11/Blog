<?php
// Iniciar la sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de configuración de la base de datos
require_once 'config_db.php';

// Definir constantes del sistema
define('SITE_NAME', 'AquaBlog'); // Nombre del sitio web
define('UPLOADS_DIR', 'uploads'); // Directorio para las subidas de archivos

// Crear directorios de subida si no existen
if (!file_exists(UPLOADS_DIR . '/posts')) {
    mkdir(UPLOADS_DIR . '/posts', 0777, true); // Crear directorio para imágenes de posts
}

if (!file_exists(UPLOADS_DIR . '/profiles')) {
    mkdir(UPLOADS_DIR . '/profiles', 0777, true); // Crear directorio para imágenes de perfil
}

// Función para mostrar mensajes de error
function showError($message) {
    echo '<div class="error-message">' . $message . '</div>'; // Muestra un mensaje de error en la vista
}

// Función para mostrar mensajes de éxito
function showSuccess($message) {
    echo '<div class="success-message">' . $message . '</div>'; // Muestra un mensaje de éxito en la vista
}

// Función para redireccionar
function redirect($url) {
    header("Location: $url");
    exit(); // Redirige a la URL especificada
}

// Función para generar nombres de archivo únicos
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION); // Obtiene la extensión del archivo
    return uniqid() . '_' . time() . '.' . $extension; // Genera un nombre de archivo único para evitar conflictos
}

// Función para validar imágenes
function validateImage($file, $maxSize = 5242880) { // 5MB por defecto
    $errors = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; // Tipos de archivo permitidos
    
    if (!in_array($file['type'], $allowedTypes)) {
        $errors[] = "Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG y GIF."; // Valida el tipo de archivo
    }
    
    if ($file['size'] > $maxSize) {
        $errors[] = "La imagen es demasiado grande. El tamaño máximo es " . ($maxSize / 1048576) . "MB."; // Valida el tamaño del archivo
    }
    
    return $errors; // Devuelve los errores encontrados
}

// Función para subir imágenes
function uploadImage($file, $directory) {
    $targetDir = UPLOADS_DIR . '/' . $directory . '/'; // Directorio de destino
    $filename = generateUniqueFilename($file['name']); // Genera un nombre único para el archivo
    $targetFile = $targetDir . $filename; // Ruta completa del archivo
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return $filename; // Sube la imagen al directorio especificado y devuelve el nombre del archivo
    }
    
    return false; // Devuelve false si la subida falla
}

// Función para eliminar imágenes
function deleteImage($filename, $directory) {
    $targetFile = UPLOADS_DIR . '/' . $directory . '/' . $filename; // Ruta completa del archivo
    if (file_exists($targetFile) && $filename != 'default_profile.jpg') {
        return unlink($targetFile); // Elimina la imagen del directorio especificado
    }
    return true; // Devuelve true si no hay nada que eliminar o si es la imagen predeterminada
}

// Función para obtener información del usuario actual
function getCurrentUser() {
    global $conn; // Usa la conexión global a la base de datos
    
    if (!isset($_SESSION['user_id'])) {
        return null; // Devuelve null si no hay un usuario autenticado
    }
    
    $user_id = $_SESSION['user_id']; // Obtiene el ID del usuario desde la sesión
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?"); // Prepara la consulta
    $stmt->bind_param("i", $user_id); // Asocia el ID del usuario a la consulta
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc(); // Devuelve la información del usuario si existe
    }
    
    return null; // Devuelve null si no se encuentra el usuario
}

// Función para obtener posts
function getPosts($limit = 10, $offset = 0, $user_id = null) {
    global $conn; // Usa la conexión global a la base de datos
    
    $query = "
        SELECT p.*, u.username, u.profile_image 
        FROM posts p
        JOIN users u ON p.user_id = u.id
    "; // Consulta base para obtener posts y datos del usuario
    
    if ($user_id !== null) {
        $query .= " WHERE p.user_id = ? "; // Filtra por usuario si se proporciona un ID
    }
    
    $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?"; // Ordena por fecha y aplica paginación
    
    if ($user_id !== null) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $user_id, $limit, $offset); // Asocia parámetros si se filtra por usuario
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $limit, $offset); // Asocia parámetros para la paginación
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC); // Devuelve los posts como un arreglo asociativo
}

// Función para formatear la fecha
function formatDate($dateString) {
    $date = new DateTime($dateString); // Convierte la cadena en un objeto DateTime
    $now = new DateTime(); // Obtiene la fecha y hora actual
    $interval = $date->diff($now); // Calcula la diferencia entre las fechas
    
    // Devuelve un formato amigable según la diferencia de tiempo
    if ($interval->y > 0) {
        return $interval->format('%y año(s) atrás');
    } elseif ($interval->m > 0) {
        return $interval->format('%m mes(es) atrás');
    } elseif ($interval->d > 0) {
        return $interval->format('%d día(s) atrás');
    } elseif ($interval->h > 0) {
        return $interval->format('%h hora(s) atrás');
    } elseif ($interval->i > 0) {
        return $interval->format('%i minuto(s) atrás');
    } else {
        return 'Hace un momento';
    }
}
?>

