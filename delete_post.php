<?php
session_start();
require_once 'config.php';

// Comprobar si el usuario ha iniciado sesión
requireLogin();

$error = "";
$success = "";

// Comprobar si se proporciona el ID de la publicación
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$post_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verificar si la publicación pertenece al usuario actual
$stmt = $conn->prepare("SELECT image_path FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Publicación no encontrada o no pertenece al usuario
    $_SESSION['error'] = "No tienes permiso para eliminar este post o el post no existe.";
    header("Location: index.php");
    exit();
}

// Obtener la ruta de la imagen para eliminar
$post = $result->fetch_assoc();
$image_path = $post['image_path'];

// Eliminar la publicación de la base de datos
$stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);

if ($stmt->execute()) {
    // Eliminar la imagen de la publicación si existe
    if (!empty($image_path) && file_exists('uploads/posts/' . $image_path)) {
        unlink('uploads/posts/' . $image_path);
    }
    
    $_SESSION['success'] = "Post eliminado correctamente.";
} else {
    $_SESSION['error'] = "Error al eliminar el post.";
}

$stmt->close();
$conn->close();

// Redirigir de vuelta a la página de inicio
header("Location: index.php");
exit();
?>
