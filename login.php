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
    } elseif (!$response_data->success) {
        $error = 'Error de reCAPTCHA. Inténtalo de nuevo.';
    } else {
        // Intentar login como admin
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

        // Intentar login como cliente
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
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Login - Programa de Fidelización</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <style>
    body {
      height: 100vh;
      margin: 0;
      background: url('img/fondol.png') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', Tahoma, sans-serif;
    }
    .overlay {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.35);
      z-index: 0;
    }
    .login-card {
      position: relative;
      z-index: 1;
      width: 360px;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      padding: 2rem;
      text-align: center;
    }
    .login-card h3 {
      margin-bottom: 1rem;
      font-weight: 700;
      color: #333;
    }
    .form-control {
      border-radius: 8px;
      border: 1px solid #ddd;
      padding: 0.75rem 1rem;
    }
    .form-control:focus {
      border-color: #6a11cb;
      box-shadow: 0 0 0 0.2rem rgba(106,17,203,0.25);
    }
    .btn-login {
      width: 100%;
      padding: 0.75rem;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      background: linear-gradient(135deg, #6a11cb, #2575fc);
      color: #fff;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }
    .error-alert {
      color: #c0392b;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }
    .links {
      margin-top: 1rem;
      font-size: 0.9rem;
    }
    .links a {
      color: #6a11cb;
      text-decoration: none;
      transition: color 0.2s;
    }
    .links a:hover {
      color: #2575fc;
    }
  </style>
</head>
<body>
  <div class="overlay"></div>
  <div class="login-card">
    <h3>Iniciar Sesión</h3>
    <?php if ($error): ?>
      <div class="error-alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3 text-start">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" pattern="\d{10,15}"
               title="Sólo números, entre 10 y 15 dígitos" required>
      </div>
      <div class="mb-3 text-start">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="g-recaptcha mb-3" data-sitekey="6LcFLMsqAAAAAO5WlI_bGH3Dyd-Isf_4Raoh9QPP"></div>
      <button type="submit" class="btn-login">Ingresar</button>
    </form>
  </div>
</body>
</html>
