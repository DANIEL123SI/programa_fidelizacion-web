<?php
session_start();

// Configuración de la conexión a la base de datos
$host = 'localhost';
$db   = 'fidelizacion';
$user = 'root';
$pass = ''; // Ajusta según tu configuración
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $telefono           = trim($_POST['telefono']);
    $password           = trim($_POST['password']);
    $recaptcha_response = $_POST['g-recaptcha-response'];

    // Verificación de reCAPTCHA
    $secret = '6LcFLMsqAAAAAMMmKCNOan23g4-5xjADqBnfF2-q';
    $verify = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$recaptcha_response}"
    );
    $response_data = json_decode($verify);

    if (empty($telefono) || empty($password)) {
        $error = 'Ingresa teléfono y contraseña.';
    } elseif (! $response_data->success) {
        $error = 'Error de reCAPTCHA. Inténtalo de nuevo.';
    } else {
        // Intentar login como admin (comparación en texto plano)
        $stmt = $pdo->prepare('SELECT id_admin AS id, nombre, password_hash FROM admin WHERE telefono = ?');
        $stmt->execute([$telefono]);
        $admin = $stmt->fetch();
        if ($admin && $password === $admin['password_hash']) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['nombre']  = $admin['nombre'];
            $_SESSION['rol']     = 'admin';
            echo "<script>
                    alert('Bienvenido, {$admin['nombre']}');
                    window.location.href='admin.php';
                  </script>";
            exit;
        }

        // Intentar login como cliente (comparación en texto plano)
        $stmt = $pdo->prepare('SELECT id_cliente AS id, nombre, password_hash FROM clientes WHERE telefono = ?');
        $stmt->execute([$telefono]);
        $cliente = $stmt->fetch();
        if ($cliente && $password === $cliente['password_hash']) {
            $_SESSION['user_id'] = $cliente['id'];
            $_SESSION['nombre']  = $cliente['nombre'];
            $_SESSION['rol']     = 'cliente';
            echo "<script>
                    alert('Bienvenido, {$cliente['nombre']}');
                    window.location.href='user_home.php';
                  </script>";
            exit;
        }

        // Credenciales inválidas
        $error = 'Teléfono o contraseña incorrectos.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Programa de Fidelización</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            height: 100vh;
            background: url('img/fon.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .overlay {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.3);
        }
        .login-form {
            width: 380px;
            padding: 25px;
            border-radius: 12px;
            background: white;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.3);
            position: relative; z-index: 1;
        }
        .login-form:hover {
            box-shadow: 0px 15px 40px rgba(0, 0, 0, 0.4);
        }
        .card-title {
            font-weight: bold;
            color: #333;
        }
        .btn {
            font-size: 16px;
            margin-top: 20px;
            font-weight: bold;
            border-radius: 8px;
        }
        .sign-up, .forgot-password {
            text-align: center;
            padding-top: 15px;
            font-size: 14px;
        }
        .forgot-password a {
            cursor: pointer;
        }
        .error-alert {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
    <script>
        function validarFormulario(event) {
            var response = grecaptcha.getResponse();
            if (response.length === 0) {
                event.preventDefault();
                alert("Por favor, completa el reCAPTCHA antes de continuar.");
            }
        }
        $(document).ready(function(){
            $("#linkForgot").click(function(){
                $("#modalEmail").modal("show");
            });
            // ... (scripts para recuperación idénticos) ...
        });
    </script>
</head>
<body>
    <div class="overlay"></div>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="login-form">
                    <h3 class="text-center card-title">Iniciar sesión</h3>
                    <?php if (!empty($error)): ?>
                        <div class="error-alert"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="POST" onsubmit="validarFormulario(event)">
                        <div class="mb-3">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" class="form-control" pattern="\d{10,15}" title="Sólo números, entre 10 y 15 dígitos" required>
                        </div>
                        <div class="mb-3">
                            <label>Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="g-recaptcha mb-3" data-sitekey="6LcFLMsqAAAAAO5WlI_bGH3Dyd-Isf_4Raoh9QPP"></div>
                        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>