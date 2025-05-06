<?php
// Inicia la sesión para manejar datos del usuario
session_start();
require_once 'config.php';

// Comprobar si el usuario ha iniciado sesión
requireLogin();

// Variables para almacenar mensajes de error y éxito
$error = "";
$success = "";
$user_id = $_SESSION['user_id'];

// Obtener los datos del usuario desde la base de datos
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Procesar el formulario de actualización de perfil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitiza las entradas del formulario
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Comprobar si el nombre de usuario o correo ya están en uso por otro usuario
    if ($username != $user['username'] || $email != $user['email']) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Si el nombre de usuario o correo ya están en uso
            $error = "El nombre de usuario o correo electrónico ya está en uso por otro usuario";
            $stmt->close();
        } else {
            $stmt->close();
            
            // Actualizar el nombre de usuario y correo electrónico
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $email, $user_id);
            
            if ($stmt->execute()) {
                // Actualización exitosa
                $_SESSION['username'] = $username;
                $success = "Perfil actualizado correctamente";
            } else {
                $error = "Error al actualizar el perfil: " . $conn->error;
            }
            
            $stmt->close();
        }
    }
    
    // Manejar el cambio de contraseña si se solicita
    if (!empty($current_password) && !empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error = "Las nuevas contraseñas no coinciden";
        } elseif (strlen($new_password) < 6) {
            $error = "La nueva contraseña debe tener al menos 6 caracteres";
        } else {
            // Verificar la contraseña actual
            if (password_verify($current_password, $user['password'])) {
                // Encriptar la nueva contraseña
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Actualizar la contraseña
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $success = "Contraseña actualizada correctamente";
                } else {
                    $error = "Error al actualizar la contraseña: " . $conn->error;
                }
                
                $stmt->close();
            } else {
                $error = "Contraseña actual incorrecta";
            }
        }
    }
    
    // Manejar la subida de la imagen de perfil
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Validar la imagen
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $error = "Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG y GIF.";
        } elseif ($_FILES['profile_image']['size'] > $max_size) {
            $error = "La imagen es demasiado grande. El tamaño máximo es 2MB.";
        } else {
            // Crear el directorio de subida si no existe
            if (!file_exists('uploads/profiles')) {
                mkdir('uploads/profiles', 0777, true);
            }
            
            // Generar un nombre de archivo único
            $image_path = uniqid() . '_' . basename($_FILES['profile_image']['name']);
            $upload_path = 'uploads/profiles/' . $image_path;
            
            // Mover el archivo subido
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Eliminar la imagen antigua si existe y no es la predeterminada
                if (!empty($user['profile_image']) && $user['profile_image'] != 'default_profile.jpg' && 
                   file_exists('uploads/profiles/' . $user['profile_image'])) {
                    unlink('uploads/profiles/' . $user['profile_image']);
                }
                
                // Actualizar la imagen de perfil en la base de datos
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->bind_param("si", $image_path, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['profile_image'] = $image_path;
                    $success = "Imagen de perfil actualizada correctamente";
                } else {
                    $error = "Error al actualizar la imagen de perfil: " . $conn->error;
                }
                
                $stmt->close();
            } else {
                $error = "Error al subir la imagen. Inténtalo de nuevo.";
            }
        }
    }
    
    // Refrescar los datos del usuario
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos básicos del documento -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - AquaBlog</title>
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
                <img src="uploads/profiles/<?php echo $user['profile_image'] ?? 'default_profile.jpg'; ?>" alt="Perfil" class="profile-image">
                <h3><?php echo htmlspecialchars($user['username']); ?></h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="create_post.php"><i class="fas fa-plus-square"></i> Crear Post</a></li>
                <li class="active"><a href="profile.php"><i class="fas fa-user"></i> Perfil</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </div>
        
        <!-- Contenido principal -->
        <div class="main-content">
            <div class="profile-container">
                <h2>Mi Perfil</h2>
                
                <!-- Mensajes de error o éxito -->
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Formulario de actualización de perfil -->
                <form action="profile.php" method="post" enctype="multipart/form-data">
                    <!-- Imagen de perfil -->
                    <div class="profile-image-section">
                        <div class="current-profile-image">
                            <img src="uploads/profiles/<?php echo $user['profile_image'] ?? 'default_profile.jpg'; ?>" alt="Perfil" id="profile-img-preview">
                        </div>
                        
                        <div class="profile-image-upload">
                            <label for="profile_image" class="file-upload-label">
                                <i class="fas fa-camera"></i> Cambiar foto de perfil
                            </label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" class="file-upload-input">
                        </div>
                    </div>
                    
                    <!-- Campos de texto -->
                    <div class="form-group">
                        <label for="username">Nombre de usuario</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <!-- Cambio de contraseña -->
                    <h3>Cambiar contraseña</h3>
                    <div class="form-group">
                        <label for="current_password">Contraseña actual</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Nueva contraseña</label>
                        <input type="password" id="new_password" name="new_password" minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmar nueva contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                    </div>
                    
                    <!-- Botón para guardar cambios -->
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Incluye el pie de página -->
    <?php include 'footer.php'; ?>

    <script>
    // Script para previsualizar la imagen de perfil antes de subir
    document.getElementById('profile_image').addEventListener('change', function(e) {
        const preview = document.getElementById('profile-img-preview');
        
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.src = event.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    </script>
    
    <script src="assets/js/script.js"></script>
</body>
</html>
