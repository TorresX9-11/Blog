document.addEventListener('DOMContentLoaded', function() {
    // Ocultar automáticamente los mensajes flash después de 5 segundos
    const flashMessages = document.querySelectorAll('.message-container');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0'; // Reduce la opacidad a 0
            setTimeout(() => {
                message.style.display = 'none'; // Oculta el mensaje completamente
            }, 500); // Espera 0.5 segundos para ocultarlo después de reducir la opacidad
        }, 5000); // Espera 5 segundos antes de iniciar el proceso
    });

    // Vista previa de la imagen para la creación y edición de publicaciones
    const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const previewId = this.getAttribute('data-preview') || 'image-preview'; // Obtiene el ID del contenedor de vista previa
            const preview = document.getElementById(previewId);
            
            if (!preview) return; // Si no existe el contenedor, no hace nada
            
            preview.innerHTML = ''; // Limpia cualquier contenido previo
            
            const file = e.target.files[0]; // Obtiene el archivo seleccionado
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = document.createElement('img'); // Crea un elemento de imagen
                    img.src = event.target.result; // Asigna la URL de la imagen cargada
                    preview.appendChild(img); // Agrega la imagen al contenedor de vista previa
                    preview.style.display = 'block'; // Muestra el contenedor
                }
                reader.readAsDataURL(file); // Lee el archivo como una URL de datos
            } else {
                preview.style.display = 'none'; // Oculta el contenedor si no hay archivo
            }
        });
    });

    // Alternar el menú desplegable en dispositivos móviles
    const userDropdown = document.querySelector('.user-dropdown');
    if (userDropdown) {
        userDropdown.addEventListener('click', function(e) {
            const dropdown = this.querySelector('.dropdown-menu');
            if (window.innerWidth <= 768) { // Solo aplica en pantallas pequeñas
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block'; // Alterna la visibilidad
                e.stopPropagation(); // Evita que el evento se propague
            }
        });

        // Cerrar el menú desplegable al hacer clic fuera de él
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const dropdown = userDropdown.querySelector('.dropdown-menu');
                if (!userDropdown.contains(e.target)) { // Si el clic no es dentro del menú
                    dropdown.style.display = 'none'; // Oculta el menú
                }
            }
        });
    }

    // Confirmar antes de eliminar publicaciones
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que deseas eliminar este post?')) { // Muestra un cuadro de confirmación
                e.preventDefault(); // Cancela la acción si el usuario no confirma
            }
        });
    });

    // Manejar la casilla de verificación "Mantener imagen" en la edición de publicaciones
    const keepImageCheckbox = document.querySelector('input[name="keep_image"]');
    const imageInput = document.querySelector('#image');
    
    if (keepImageCheckbox && imageInput) {
        keepImageCheckbox.addEventListener('change', function() {
            if (!this.checked && imageInput.files.length === 0) { // Si no se selecciona "Mantener imagen" y no hay nueva imagen
                alert('Debes subir una nueva imagen o mantener la actual'); // Muestra una alerta
                this.checked = true; // Restablece la casilla de verificación
            }
        });
        
        imageInput.addEventListener('change', function() {
            if (this.files.length > 0 && keepImageCheckbox) { // Si se selecciona una nueva imagen
                keepImageCheckbox.checked = false; // Desmarca la casilla de "Mantener imagen"
            }
        });
    }

    // Validación de la confirmación de contraseña
    const passwordForm = document.querySelector('form:has(input[name="new_password"])');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) { // Si las contraseñas no coinciden
                e.preventDefault(); // Cancela el envío del formulario
                alert('Las contraseñas no coinciden'); // Muestra una alerta
            }
        });
    }

    // Contador de caracteres para el contenido de la publicación
    const contentTextarea = document.querySelector('textarea[name="content"]');
    if (contentTextarea) {
        const maxLength = 500; // Define el número máximo de caracteres
        
        // Crear elemento contador
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.innerHTML = '0/' + maxLength; // Inicializar el contador
        counter.style.textAlign = 'right';
        counter.style.marginTop = '5px';
        counter.style.fontSize = '12px';
        counter.style.color = '#888';
        
        contentTextarea.parentNode.appendChild(counter); // Agrega el contador al DOM
        
        contentTextarea.addEventListener('input', function() {
            const count = this.value.length; // Obtiene la longitud del texto
            counter.innerHTML = count + '/' + maxLength; // Actualiza el contador
            
            if (count > maxLength) {
                counter.style.color = 'var(--error-color)'; // Cambia el color si se excede el límite
            } else {
                counter.style.color = '#888'; // Color normal si está dentro del límite
            }
        });
        
        // Activar el conteo inicial
        const event = new Event('input');
        contentTextarea.dispatchEvent(event); // Dispara el evento para inicializar el contador
    }
});
