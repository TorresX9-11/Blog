<?php
// Inicia la sesión para manejar datos del usuario
session_start();
// Incluye el archivo de configuración para la conexión a la base de datos
require_once 'config.php';

// Comprobar si ya se ha iniciado sesión
// Si el usuario ya está autenticado, redirige a la página principal
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Variables para almacenar mensajes de error y éxito
$error = "";
$success = "";

// Procesar el formulario de registro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitiza las entradas del formulario
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } else {
        // Comprobar si el nombre de usuario o el correo electrónico ya existen
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Si ya existe un usuario con el mismo nombre o correo
            $error = "El nombre de usuario o correo electrónico ya está en uso";
        } else {
            // Encriptar la contraseña antes de almacenarla
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar el nuevo usuario en la base de datos
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                // Registro exitoso
                $success = "Registro exitoso. Ahora puedes iniciar sesión.";
            } else {
                // Error al registrar al usuario
                $error = "Error al registrar: " . $conn->error;
            }
        }
        
        // Cierra la consulta preparada
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos básicos del documento -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - AquaBlog</title>
    <!-- Enlaces a hojas de estilo -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="register-container">
            <!-- Título del formulario de registro -->
            <h1>AquaBlog</h1>
            
            <!-- Muestra un mensaje de error si existe -->
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Muestra un mensaje de éxito si el registro fue exitoso -->
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
                <p class="redirect-message">Redirigiendo a la página de inicio de sesión...</p>
                <script>
                    // Redirige automáticamente a la página de inicio de sesión después de 3 segundos
                    setTimeout(function() {
                        window.location.href = "login.php";
                    }, 3000);
                </script>
            <?php endif; ?>
            
            <!-- Formulario de registro (solo se muestra si no hay éxito aún) -->
            <?php if (empty($success)): ?>
                <form action="register.php" method="post">
                    <!-- Campo para el nombre de usuario -->
                    <div class="form-group">
                        <input type="text" id="username" name="username" placeholder="Nombre de usuario" required>
                    </div>
                    <!-- Campo para el correo electrónico -->
                    <div class="form-group">
                        <input type="email" id="email" name="email" placeholder="Correo electrónico" required>
                    </div>
                    <!-- Campo para la contraseña -->
                    <div class="form-group">
                        <input type="password" id="password" name="password" placeholder="Contraseña" required minlength="6">
                    </div>
                    <!-- Campo para confirmar la contraseña -->
                    <div class="form-group">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar contraseña" required minlength="6">
                    </div>
                    <!-- Botón para enviar el formulario -->
                    <button type="submit" class="btn btn-primary btn-block">Registrarse</button>
                </form>
            <?php endif; ?>
            
            <!-- Enlace para iniciar sesión si ya se tiene una cuenta -->
            <div class="login-link">
                ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a>
            </div>
        </div>
    </div>

    <!-- Archivo de JavaScript -->
    <script src="assets/js/script.js"></script>
</body>
</html>
