<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <a href="index.php">AquaBlog</a> <!-- Logo del sitio -->
        </div>
        <div class="user-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Si el usuario ha iniciado sesión -->
                <div class="user-dropdown">
                    <img src="uploads/profiles/<?php echo $_SESSION['profile_image'] ?? 'default_profile.jpg'; ?>" alt="Perfil" class="nav-profile-img">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                    
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user"></i> Perfil</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Si el usuario no ha iniciado sesión -->
                <nav>
                    <a href="login.php" class="btn btn-login">Iniciar Sesión</a>
                    <a href="register.php" class="btn btn-register">Registrarse</a>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php
// Mostrar mensajes de sesión si existen
if (isset($_SESSION['error'])) {
    echo '<div class="message-container"><div class="error-message">' . $_SESSION['error'] . '</div></div>';
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    echo '<div class="message-container"><div class="success-message">' . $_SESSION['success'] . '</div></div>';
    unset($_SESSION['success']);
}
?>
