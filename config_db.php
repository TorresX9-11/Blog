<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = ""; 
$database = "blog_system";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Establecer el conjunto de caracteres
$conn->set_charset("utf8mb4");

// Función para limpiar los datos de entrada
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

// Función para verificar si el usuario ha iniciado sesión
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para redirigir al inicio de sesión si no ha iniciado sesión
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}
?>
