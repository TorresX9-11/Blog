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

// Variable para almacenar mensajes de error
$error = "";

// Procesar el formulario de inicio de sesión
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitiza el nombre de usuario para evitar inyecciones
    $username = sanitize($_POST['username']);
    // Obtiene la contraseña sin modificarla (será verificada más adelante)
    $password = $_POST['password'];
    
    // Prepara una consulta para buscar al usuario en la base de datos
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Verifica si se encontró un usuario con el nombre proporcionado
    if ($result->num_rows == 1) {
        // Obtiene los datos del usuario
        $user = $result->fetch_assoc();
        // Verifica si la contraseña ingresada coincide con la almacenada (hash)
        if (password_verify($password, $user['password'])) {
            // Contraseña correcta, crea la sesión del usuario
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirige a la página principal
            header("Location: index.php");
            exit();
        } else {
            // Contraseña incorrecta
            $error = "Contraseña incorrecta";
        }
    } else {
        // Usuario no encontrado
        $error = "Usuario no encontrado";
    }
    
    // Cierra la consulta preparada
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos básicos del documento -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AquaBlog</title>
    <!-- Enlaces a hojas de estilo -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <!-- Título del formulario de inicio de sesión -->
            <h1>AquaBlog</h1>
            
            <!-- Muestra un mensaje de error si existe -->
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Formulario de inicio de sesión -->
            <form action="login.php" method="post">
                <!-- Campo para el nombre de usuario -->
                <div class="form-group">
                    <input type="text" id="username" name="username" placeholder="Nombre de usuario" required>
                </div>
                <!-- Campo para la contraseña -->
                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder="Contraseña" required>
                </div>
                <!-- Botón para enviar el formulario -->
                <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
            </form>
            
            <!-- Enlace para registrarse si no se tiene una cuenta -->
            <div class="signup-link">
                ¿No tienes una cuenta? <a href="register.php">Regístrate</a>
            </div>
        </div>
    </div>

    <!-- Archivo de JavaScript -->
    <script src="assets/js/script.js"></script>
</body>
</html>