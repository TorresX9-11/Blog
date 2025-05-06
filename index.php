<?php
// Inicia la sesión para manejar datos del usuario
session_start();

// Incluye el archivo de configuración para la conexión a la base de datos
require_once 'config.php';

// Verifica si el usuario ha iniciado sesión, redirige si no lo ha hecho
requireLogin();

// Obtiene el ID y el nombre de usuario del usuario desde la sesión
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Consulta para obtener las publicaciones del usuario actual
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.profile_image 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
// Asocia el ID del usuario a la consulta y la ejecuta
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
// Obtiene todas las publicaciones como un arreglo asociativo
$posts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos básicos del documento -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaBlog - Mi Blog</title>
    <!-- Enlaces a hojas de estilo -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- Incluye el encabezado del sitio -->
    <?php include 'header.php'; ?>
    
    <div class="container">
        <!-- Barra lateral con información del usuario -->
        <div class="sidebar">
            <div class="user-profile">
                <!-- Muestra la imagen de perfil del usuario o una imagen predeterminada -->
                <img src="uploads/profiles/<?php echo $_SESSION['profile_image'] ?? 'default_profile.jpg'; ?>" alt="Perfil" class="profile-image">
                <h3><?php echo htmlspecialchars($username); ?></h3>
            </div>
            <!-- Menú de navegación lateral -->
            <ul class="sidebar-menu">
                <li class="active"><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="create_post.php"><i class="fas fa-plus-square"></i> Crear Post</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Perfil</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
            </ul>
        </div>
        
        <!-- Contenido principal -->
        <div class="main-content">
            <!-- Botón para crear un nuevo post -->
            <div class="create-post-btn">
                <a href="create_post.php" class="btn btn-primary"><i class="fas fa-plus"></i> Crear Nuevo Post</a>
            </div>
            
            <!-- Contenedor de publicaciones -->
            <div class="posts-container">
                <?php if (count($posts) > 0): ?>
                    <!-- Itera sobre las publicaciones y las muestra -->
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card">
                            <!-- Encabezado de la publicación -->
                            <div class="post-header">
                                <!-- Imagen de perfil del autor -->
                                <img src="uploads/profiles/<?php echo $post['profile_image']; ?>" alt="Perfil" class="post-profile-img">
                                <!-- Nombre de usuario del autor -->
                                <span class="post-username"><?php echo htmlspecialchars($post['username']); ?></span>
                                <!-- Acciones para editar o eliminar la publicación -->
                                <div class="post-actions">
                                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete_post.php?id=<?php echo $post['id']; ?>" class="btn-delete" onclick="return confirm('¿Estás seguro de que deseas eliminar este post?');"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </div>
                            
                            <!-- Imagen de la publicación (si existe) -->
                            <?php if ($post['image_path']): ?>
                                <div class="post-image">
                                    <img src="uploads/posts/<?php echo $post['image_path']; ?>" alt="Imagen del post">
                                </div>
                            <?php endif; ?>
                            
                            <!-- Contenido de la publicación -->
                            <div class="post-content">
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            </div>
                            
                            <!-- Pie de la publicación con la fecha -->
                            <div class="post-footer">
                                <span class="post-date"><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Mensaje si no hay publicaciones -->
                    <div class="no-posts">
                        <p>Aún no has creado ningún post. ¡Crea tu primer post ahora!</p>
                        <a href="create_post.php" class="btn btn-primary">Crear Post</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Incluye el pie de página -->
    <?php include 'footer.php'; ?>
    
    <!-- Archivo de JavaScript -->
    <script src="assets/js/script.js"></script>
</body>
</html>