<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-sections">
                <div class="weather-widget">
                    <h4>El Clima</h4>
                    <div id="weather-info">
                        <div class="weather-loading">Cargando...</div>
                    </div>
                </div>
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> AquaBlog.</p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Modal para explicar la solicitud de ubicación -->
<div id="locationModal" class="modal">
    <div class="modal-content">
        <h3>Permiso de Ubicación</h3>
        <p>Para mostrarte información meteorológica precisa de tu zona, necesitamos acceder a tu ubicación.</p>
        <p>Esta información solo se utiliza para mostrar el clima local y no se almacena en ningún lugar.</p>
        <div class="modal-buttons">
            <button id="acceptLocation" class="btn btn-primary">Aceptar</button>
            <button id="declineLocation" class="btn">Usar ubicación predeterminada</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_KEY = 'a839636e166f9e09e2892740ac386486';
    const weatherInfo = document.getElementById('weather-info');
    const modal = document.getElementById('locationModal');
    const locationPreference = localStorage.getItem('weatherLocationPreference');
    const lastLocationCheck = localStorage.getItem('lastLocationUpdate');
    const ONE_DAY = 24 * 60 * 60 * 1000; // 24 horas en milisegundos

    // Función para obtener el clima usando coordenadas
    function getWeatherByCoords(lat, lon) {
        fetch(`https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${API_KEY}&units=metric&lang=es`)
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta de la API');
                return response.json();
            })
            .then(data => {
                weatherInfo.innerHTML = `
                    <div class="weather-data">
                        <div class="location-info">
                            <span class="city">${data.name}</span>
                        </div>
                        <img src="https://openweathermap.org/img/wn/${data.weather[0].icon}@2x.png" 
                             alt="${data.weather[0].description}"
                             onerror="this.onerror=null; this.src='assets/images/weather-default.png';">
                        <div class="weather-details">
                            <span class="temperature">${Math.round(data.main.temp)}°C</span>
                            <span class="description">${data.weather[0].description}</span>
                            <span class="humidity">Humedad: ${data.main.humidity}%</span>
                        </div>
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
                weatherInfo.innerHTML = '<p class="weather-error">No se pudo cargar la información del clima</p>';
            });
    }

    // Función para obtener el clima por ciudad (fallback)
    function getWeatherByCity(city = 'Santiago,CL') {
        fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${API_KEY}&units=metric&lang=es`)
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta de la API');
                return response.json();
            })
            .then(data => {
                weatherInfo.innerHTML = `
                    <div class="weather-data">
                        <div class="location-info">
                            <span class="city">${data.name}</span>
                        </div>
                        <img src="https://openweathermap.org/img/wn/${data.weather[0].icon}@2x.png" 
                             alt="${data.weather[0].description}"
                             onerror="this.onerror=null; this.src='assets/images/weather-default.png';">
                        <div class="weather-details">
                            <span class="temperature">${Math.round(data.main.temp)}°C</span>
                            <span class="description">${data.weather[0].description}</span>
                            <span class="humidity">Humedad: ${data.main.humidity}%</span>
                        </div>
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
                weatherInfo.innerHTML = '<p class="weather-error">No se pudo cargar la información del clima</p>';
            });
    }

    // Nueva función para manejar la solicitud de ubicación
    function requestLocation() {
        // Si ya existe una preferencia guardada y no ha pasado más de un día
        if (locationPreference && lastLocationCheck && (Date.now() - parseInt(lastLocationCheck) < ONE_DAY)) {
            if (locationPreference === 'accepted') {
                // Si el usuario aceptó anteriormente, usar geolocalización
                if ("geolocation" in navigator) {
                    navigator.geolocation.getCurrentPosition(
                        position => getWeatherByCoords(position.coords.latitude, position.coords.longitude),
                        error => getWeatherByCity(),
                        { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                    );
                }
            } else {
                // Si el usuario rechazó anteriormente, usar ubicación predeterminada
                getWeatherByCity();
            }
            return;
        }

        // Si no hay preferencia guardada o ha pasado más de un día, mostrar el modal
        modal.style.display = 'block';

        document.getElementById('acceptLocation').onclick = function() {
            modal.style.display = 'none';
            localStorage.setItem('weatherLocationPreference', 'accepted');
            localStorage.setItem('lastLocationUpdate', Date.now().toString());
            
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    position => getWeatherByCoords(position.coords.latitude, position.coords.longitude),
                    error => getWeatherByCity(),
                    { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                );
            }
        };

        document.getElementById('declineLocation').onclick = function() {
            modal.style.display = 'none';
            localStorage.setItem('weatherLocationPreference', 'declined');
            localStorage.setItem('lastLocationUpdate', Date.now().toString());
            getWeatherByCity();
        };
    }

    // Iniciar el proceso
    requestLocation();
});
</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #ffffff;
    margin: 15% auto;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.modal-content h3 {
    color: #333;
    font-size: 24px;
    margin-bottom: 15px;
}

.modal-content p {
    color: #555;
    font-size: 16px;
    line-height: 1.5;
    margin-bottom: 10px;
}

.modal-buttons {
    margin-top: 25px;
    display: flex;
    justify-content: center;
    gap: 15px;
}

.modal-buttons button {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #2196F3;
    color: white;
    box-shadow: 0 2px 5px rgba(33, 150, 243, 0.3);
}

.btn-primary:hover {
    background-color: #1976D2;
    transform: translateY(-2px);
}

.btn {
    background-color: #e0e0e0;
    color: #333;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn:hover {
    background-color: #d5d5d5;
    transform: translateY(-2px);
}
</style>

