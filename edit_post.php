<?php
// Inicia la sesión para manejar datos del usuario
session_start();
require_once 'config.php';

// Comprobar si el usuario ha iniciado sesión
requireLogin();

// Variables para almacenar mensajes de error y éxito
$error = "";
$success = "";

// Comprobar si se proporciona el ID de la publicación
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Si no se proporciona un ID válido, redirige al inicio
    header("Location: index.php");
    exit();
}

$post_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Obtener los datos de la publicación
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Si la publicación no existe o no pertenece al usuario, redirige al inicio
    $_SESSION['error'] = "No tienes permiso para editar este post o el post no existe.";
    header("Location: index.php");
    exit();
}

$post = $result->fetch_assoc();
$stmt->close();

// Procesar el formulario de actualización de publicación
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitiza el contenido del post
    $content = sanitize($_POST['content']);
    $keep_image = isset($_POST['keep_image']) ? true : false;
    $current_image = $post['image_path'];
    $new_image_path = $current_image;
    
    // Validar que el contenido no esté vacío
    if (empty($content) && (!$keep_image && !isset($_FILES['image']) || 
                           ($_FILES['image']['error'] == UPLOAD_ERR_NO_FILE))) {
        $error = "Debes agregar texto o una imagen a tu post";
    } else {
        // Manejar la subida de imágenes si está presente
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            // Validar la imagen
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error = "Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG y GIF.";
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error = "La imagen es demasiado grande. El tamaño máximo es 5MB.";
            } else {
                // Crear el directorio de subida si no existe
                if (!file_exists('uploads/posts')) {
                    mkdir('uploads/posts', 0777, true);
                }
                
                // Generar un nombre de archivo único
                $new_image_path = uniqid() . '_' . basename($_FILES['image']['name']);
                $upload_path = 'uploads/posts/' . $new_image_path;
                
                // Mover el archivo subido
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error = "Error al subir la imagen. Inténtalo de nuevo.";
                    $new_image_path = $current_image;
                } else {
                    // Eliminar la imagen antigua si existe
                    if (!empty($current_image) && file_exists('uploads/posts/' . $current_image)) {
                        unlink('uploads/posts/' . $current_image);
                    }
                }
            }
        } elseif (!$keep_image && !empty($current_image)) {
            // Eliminar la imagen antigua si el usuario desmarcó "mantener imagen"
            if (file_exists('uploads/posts/' . $current_image)) {
                unlink('uploads/posts/' . $current_image);
            }
            $new_image_path = null;
        }
        
        // Si no hay errores, actualizar la publicación
        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE posts SET content = ?, image_path = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssii", $content, $new_image_path, $post_id, $user_id);
            
            if ($stmt->execute()) {
                $success = "Post actualizado correctamente";
                // Redirigir después de 2 segundos
                header("refresh:2;url=index.php");
            } else {
                $error = "Error al actualizar el post: " . $conn->error;
            }
            
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos básicos del documento -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Post - AquaBlog</title>
    <!-- Enlaces a hojas de estilo -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- Incluye el encabezado -->
    <?php include 'header.php'; ?>
    
    <div class="container">
        <!-- Barra lateral -->
        <div class="sidebar">
            <div class="user-profile">
                <img src="uploads/profiles/<?php echo $_SESSION['profile_image'] ?? 'default_profile.jpg'; ?>" alt="Perfil" class="profile-image">
                <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="create_post.php"><i class="fas fa-plus-square"></i> Crear Post</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Perfil</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </div>
        
        <!-- Contenido principal -->
        <div class="main-content">
            <div class="edit-post-container">
                <h2>Editar Post</h2>
                
                <!-- Mensajes de error o éxito -->
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Formulario de edición de post -->
                <form action="edit_post.php?id=<?php echo $post_id; ?>" method="post" enctype="multipart/form-data">
                    <!-- Campo de texto para el contenido -->
                    <div class="form-group">
                        <textarea name="content" id="content" placeholder="¿Qué estás pensando?" rows="5"><?php echo htmlspecialchars($post['content']); ?></textarea>
                    </div>
                    
                    <!-- Imagen actual (si existe) -->
                    <?php if (!empty($post['image_path'])): ?>
                    <div class="current-image">
                        <img src="uploads/posts/<?php echo $post['image_path']; ?>" alt="Imagen actual">
                        <div class="image-options">
                            <label>
                                <input type="checkbox" name="keep_image" value="1" checked> Mantener esta imagen
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Campo para subir una nueva imagen -->
                    <div class="form-group">
                        <label for="image" class="file-upload-label">
                            <i class="fas fa-image"></i> <?php echo !empty($post['image_path']) ? 'Cambiar imagen' : 'Agregar imagen'; ?>
                        </label>
                        <input type="file" id="image" name="image" accept="image/*" class="file-upload-input">
                        <div id="image-preview" class="image-preview"></div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Incluye el pie de página -->
    <?php include 'footer.php'; ?>

    <script>
    // Script para previsualizar la imagen antes de subir
    document.getElementById('image').addEventListener('change', function(e) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';
        
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = document.createElement('img');
                img.src = event.target.result;
                preview.appendChild(img);
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
    </script>
    
    <script src="assets/js/script.js"></script>
</body>
</html>