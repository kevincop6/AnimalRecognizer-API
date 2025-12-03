<!DOCTYPE html>
<html lang="es">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Purple Admin</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="assets/images/favicon.png" />
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth">
          <div class="row flex-grow">
            <div class="col-lg-4 mx-auto">
              <div class="auth-form-light text-center p-5">
                <div class="brand-logo">
                  <img src="assets/images/logo2.svg">
                </div>
                <h4>¡Hola! Empecemos</h4>
                <h6 class="font-weight-light">Inicia sesión para continuar.</h6>
                <form class="pt-3" id="loginForm">
  <div class="form-group">
    <input type="text" class="form-control form-control-lg" id="username" placeholder="Nombre de usuario o Correo" required>
  </div>
  <div class="form-group">
    <input type="password" class="form-control form-control-lg" id="password" placeholder="Contraseña" required>
  </div>
  <div class="mt-3 d-grid gap-2">
    <button type="submit" id="loginBtn" class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn">
      INICIAR SESIÓN
    </button>
  </div>
  <div class="my-2 d-flex justify-content-between align-items-center">
    <div class="form-check">
      <label class="form-check-label text-muted">
        <input type="checkbox" class="form-check-input" id="rememberMe"> Mantener sesión iniciada 
      </label>
    </div>
    <a href="#" class="auth-link text-primary">¿Olvidaste tu contraseña?</a>
  </div>
</form>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/misc.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/todolist.js"></script>
    <script src="assets/js/jquery.cookie.js"></script>
    <!-- endinject -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Asegúrate de que jQuery ($) esté cargado antes de este script
$(document).ready(function() {
  checkAdminSession();
    // Ruta relativa al proxy dentro de web-admin/
    const PROXY_URL = 'ajax/login_proxy.php'; 

    $('#loginForm').on('submit', function(e) {
        e.preventDefault(); 

        const username = $('#username').val();
        const password = $('#password').val();
        const rememberMe = $('#rememberMe').prop('checked'); 

        const formData = new FormData();
        formData.append('usuario_correo', username); 
        formData.append('password', password);
        formData.append('persistir', rememberMe ? '1' : '0'); 
        
        const $btn = $('#loginBtn');
        $btn.text('Validando...').prop('disabled', true);

        fetch(PROXY_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Leer el JSON, incluyendo errores HTTP (401, 500)
            return response.json().then(data => {
                if (!response.ok) {
                    // Si el estado HTTP es 4xx o 5xx, lanzamos el error del JSON para el catch
                    return Promise.reject(data);
                }
                return data;
            });
        })
        .then(data => {
            // --- LOGIN EXITOSO ---
            // data.token y data.usuario deben existir
            if (data.token && data.usuario) {
                
                // Almacenar el token y ROL (lectura del objeto anidado)
                localStorage.setItem('userToken', data.token);
                localStorage.setItem('userRole', data.usuario.rol); 
                
                Swal.fire({
                    title: '¡Bienvenido!',
                    text: data.usuario.nombre + ' (' + data.usuario.rol + ')',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'dashboard.html'; // Redirigir
                });
            } else {
                 return Promise.reject({ message: 'Respuesta de éxito inesperada.' });
            }
        })
        .catch(error => {
            // --- MANEJO DE ERRORES CON SWEETALERT2 ---
            const errorMessage = error.message || 'Error desconocido. Intenta más tarde.';
            
            Swal.fire({
                title: 'Fallo de Autenticación',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'Cerrar'
            });
            
            // Reactivar botón
            $btn.text('INICIAR SESIÓN').prop('disabled', false);
        });
    });
});

function checkAdminSession() {
    // 1. Obtener el token almacenado
    const userToken = localStorage.getItem('userToken');
    const userRole = localStorage.getItem('userRole');
    
    // Ruta de la página de login actual (Asumimos login.html o index.php)
    const loginPage = 'login.html'; 
    const isLoginPage = window.location.pathname.includes(loginPage) || window.location.pathname.endsWith('/');

    // 2. Si no hay token y no estamos en el login, redirigir.
    if (!userToken || !userRole) {
        if (!isLoginPage) {
            window.location.href = loginPage;
        }
        return;
    }

    // 3. Endpoint de validación (Ajusta la URL base si es necesario)
    const API_VALIDATE_URL = '../api/usuarios/verificar_sesion.php'; 

    // 4. Implementación con $.ajax()
    $.ajax({
        url: API_VALIDATE_URL,
        method: 'POST', 
        headers: {
            'Authorization': 'Bearer ' + userToken 
        },
        dataType: 'json', 
        
        success: function(data) {
            // Éxito (200 OK): Si estamos en el login, redirigir al dashboard.
            if (isLoginPage) {
                console.log("Sesión válida. Redirigiendo a dashboard...");
                window.location.href = 'dashboard.html'; 
            }
        },
        
        error: function(xhr, status, error) {
            // Fallo (401, 403, etc.): Sesión inválida o expirada.
            console.error("Fallo de validación AJAX:", xhr.status, error);
            
            // Limpiar tokens
            localStorage.removeItem('userToken'); 
            localStorage.removeItem('userRole');
            
            // Redirigir al login solo si no estamos ya en el login
            if (!isLoginPage) {
                window.location.href = loginPage; 
            }
        }
    });
}
</script>
  </body>
</html>