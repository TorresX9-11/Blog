<?php
// Inicia la sesión para manejar datos del usuario
session_start();
require_once 'config.php';

// Comprobar si el usuario ha iniciado sesión
requireLogin();

// Variables para almacenar mensajes de error y éxito
$error = "";
$success = "";

// Procesar el formulario de creación de publicación
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitiza el contenido del post
    $content = sanitize($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $image_path = null;
    
    // Comprobar si el contenido no está vacío
    if (empty($content) && (!isset($_FILES['image']) || $_FILES['image']['error'] == UPLOAD_ERR_NO_FILE)) {
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
                $image_path = uniqid() . '_' . basename($_FILES['image']['name']);
                $upload_path = 'uploads/posts/' . $image_path;
                
                // Mover el archivo subido
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error = "Error al subir la imagen. Inténtalo de nuevo.";
                    $image_path = null;
                }
            }
        }
        
        // Si no hay errores, crear la publicación
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $content, $image_path);
            
            if ($stmt->execute()) {
                $success = "Post creado correctamente";
                // Redirigir después de 2 segundos
                header("refresh:2;url=index.php");
            } else {
                $error = "Error al crear el post: " . $conn->error;
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
    <title>Crear Post - AquaBlog</title>
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
                <!-- Muestra la imagen de perfil del usuario -->
                <img src="uploads/profiles/<?php echo $_SESSION['profile_image'] ?? 'default_profile.jpg'; ?>" alt="Perfil" class="profile-image">
                <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
            </div>
            <!-- Menú de navegación lateral -->
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li class="active"><a href="create_post.php"><i class="fas fa-plus-square"></i> Crear Post</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Perfil</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </div>
        
        <!-- Contenido principal -->
        <div class="main-content">
            <div class="create-post-container">
                <h2>Crear Nuevo Post</h2>
                
                <!-- Mensajes de error o éxito -->
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Formulario de creación de post -->
                <form action="create_post.php" method="post" enctype="multipart/form-data">
                    <!-- Campo de texto para el contenido -->
                    <div class="form-group">
                        <textarea name="content" id="content" placeholder="¿Qué estás pensando?" rows="5"></textarea>
                    </div>
                    
                    <!-- Campo para subir una imagen -->
                    <div class="form-group">
                        <label for="image" class="file-upload-label">
                            <i class="fas fa-image"></i> Agregar imagen
                        </label>
                        <input type="file" id="image" name="image" accept="image/*" class="file-upload-input">
                        <div id="image-preview" class="image-preview"></div>
                    </div>
                    
                    <!-- Botón para publicar -->
                    <button type="submit" class="btn btn-primary btn-block">Publicar</button>
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

