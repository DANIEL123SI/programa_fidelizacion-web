<?php
session_start();
// 1) Verificar sesión de cliente
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
$id_cliente = $_SESSION['user_id'];

// 2) Conexión a la BD
$host = 'localhost';
$db   = 'fidelizacion';
$user = 'root';
$pass = '';
$pdo  = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// 3) Procesar creación de tarjeta (solo desde el modal 2)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'create_card') {
    $telefono_input = trim($_POST['telefono']);

    // 3.1) Validar teléfono contra la BD
    $stmt = $pdo->prepare("SELECT telefono FROM clientes WHERE id_cliente = ?");
    $stmt->execute([$id_cliente]);
    $telefono_bd = $stmt->fetchColumn();

    if ($telefono_input !== $telefono_bd) {
        $error = "El número de teléfono no coincide.";
    } else {
        // 3.2) Generar número de tarjeta único
        do {
            $numero = '';
            for ($i = 0; $i < 16; $i++) {
                $numero .= rand(0, 9);
            }
            $chk = $pdo->prepare("SELECT 1 FROM tarjetas WHERE numero = ?");
            $chk->execute([$numero]);
        } while ($chk->fetch());

        // Generar CVV único
        do {
            $cvv = str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT);
            $chk = $pdo->prepare("SELECT 1 FROM tarjetas WHERE cvv = ?");
            $chk->execute([$cvv]);
        } while ($chk->fetch());

        // Fecha de vencimiento a +4 años
        $fecha_vto = date('Y-m-d', strtotime('+4 years'));

        // 3.3) Insertar en tarjetas y actualizar al cliente
        $pdo->beginTransaction();
        $pdo->prepare("
            INSERT INTO tarjetas (id_cliente, numero, fecha_vencimiento, cvv)
            VALUES (?, ?, ?, ?)
        ")->execute([$id_cliente, $numero, $fecha_vto, $cvv]);
        $pdo->prepare("
            UPDATE clientes
               SET tarjeta_digital = 'si'
             WHERE id_cliente = ?
        ")->execute([$id_cliente]);
        $pdo->commit();

        header('Location: cliente.php');
        exit;
    }
}

// 4) Obtener datos del cliente y tarjeta
$stmt = $pdo->prepare("
    SELECT
      c.nombre,
      c.apellidos,
      c.puntos_actuales,
      c.tarjeta_digital,
      t.numero,
      t.fecha_vencimiento,
      t.cvv
    FROM clientes c
    LEFT JOIN tarjetas t ON t.id_cliente = c.id_cliente
    WHERE c.id_cliente = ?
");
$stmt->execute([$id_cliente]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mi Cuenta - Fidelización</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts: Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    /* Estilos globales */
    body {
      font-family: 'Inter', sans-serif;
      background: #f8f9fa;
      margin: 0;
      padding: 0;
    }
    .navbar {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    /* Tarjeta virtual */
    .card-virtual {
      max-width: 380px;
      margin: 2rem auto;
      padding: 2rem;
      border-radius: 20px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: #fff;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      position: relative;
    }
    .card-number {
      font-size: 1.3rem;
      letter-spacing: 2px;
      margin: 1.5rem 0;
    }
    /* Secciones de puntos, premios y beneficios */
    .section {
      text-align: center;
      margin: 3rem 0;
    }
    .section img {
      width: 80px;
      height: auto;
      margin-bottom: 1rem;
    }
    /* Botón con degradado personalizado */
    .btn-gradient {
      background: linear-gradient(135deg, #667eea, #764ba2);
      border: none;
      color: #fff;
    }
    /* Footer */
    footer {
      text-align: center;
      padding: 2rem 0;
      color: #888;
    }
  </style>

</head>
<body>

<!-- Navegación -->
<nav class="navbar navbar-expand-lg navbar-light bg-white">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Fidelización</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar Sesión</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-5">
  <?php if ($u['tarjeta_digital'] !== 'si'): ?>
    <!-- Modal 1: Confirmar creación de tarjeta -->
    <div class="modal fade" id="modalConfirm" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow-lg">
          <div class="modal-header border-0">
            <h5 class="modal-title">Crear Tarjeta Digital</h5>
          </div>
          <div class="modal-body">
            <p>Para continuar, necesitas crear tu tarjeta digital.</p>
          </div>
          <div class="modal-footer border-0">
            <button type="button" id="btnAccept" class="btn btn-gradient px-4">Aceptar</button>
            <a href="logout.php" class="btn btn-outline-secondary px-4">Salir</a>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal 2: Formulario de teléfono -->
    <div class="modal fade" id="modalPhone" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow-lg">
          <form method="POST">
            <input type="hidden" name="form_type" value="create_card">
            <div class="modal-header border-0">
              <h5 class="modal-title">Verificar Teléfono</h5>
            </div>
            <div class="modal-body">
              <p>Ingresa tu teléfono registrado:</p>
              <?php if ($error): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>
              <input type="text" name="telefono" class="form-control" required>
            </div>
            <div class="modal-footer border-0">
              <button type="submit" class="btn btn-primary px-4">Crear Tarjeta</button>
              <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- Cabecera de bienvenida -->
    <div class="text-center mb-5">
      <h1 class="fw-bold">Bienvenido, <?= htmlspecialchars($u['nombre'].' '.$u['apellidos']) ?></h1>
    </div>

    <!-- Tarjeta digital -->
    <div class="card-virtual">
      <img src="img/visa.png" alt="Logo"
           class="position-relative" style="width: 70px; top:-10px; left:270px;">
      <div class="card-number"><?= chunk_split($u['numero'], 4, ' ') ?></div>
      <button class="btn btn-light btn-sm position-absolute"
              style="bottom:1rem; right:1rem;"
              data-bs-toggle="modal" data-bs-target="#detallesCard">
        Detalles
      </button>
    </div>

    <!-- Modal: detalles de tarjeta -->
    <div class="modal fade" id="detallesCard" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
          <div class="modal-header">
            <h5 class="modal-title">Detalles de tu Tarjeta</h5>
          </div>
          <div class="modal-body text-center">
            <p><strong>Número:</strong> <?= chunk_split($u['numero'],4,' ') ?></p>
            <p><strong>Vence:</strong> <?= date('m/Y', strtotime($u['fecha_vencimiento'])) ?></p>
            <p><strong>CVV:</strong> <?= htmlspecialchars($u['cvv']) ?></p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Sección de Puntos -->
    <div class="row gx-4 gy-5">
      <div class="col-md-4 section">
        <h4>Puntos Acumulados</h4>
        <p class="display-6 text-primary fw-bold"><?= $u['puntos_actuales'] ?> pts</p>
        <a href="user_puntoDetalle.php" class="btn btn-outline-primary">Ver Detalles</a>
      </div>
      <!-- Sección de Premios -->
      <div class="col-md-4 section">
        <h4>Premios</h4>
        <img src="img/premios.jpg" alt="Premios" style="width: 200px; position: relative; left: 65px;">
        <a href="user_premios.php" class="btn btn-outline-success mt-3" style="position:relative; top: 90px; left: -90px;">Ir a Premios</a>
      </div>
      <!-- Sección de Beneficios -->
      <div class="col-md-4 section">
        <h4>Beneficios</h4>
        <img src="img/bene.jpg" alt="Beneficios" style="width: 190px; position: relative; left: 65px;">
        <a href="user_beneficio.php" class="btn btn-outline-warning mt-3" style="position:relative; top: 90px; left: -90px;">Ir a Beneficios</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Footer -->
<footer>
  &copy; <?= date('Y') ?> Fidelización. Todos los derechos reservados.
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const hasCard = <?= json_encode($u['tarjeta_digital'] === 'si') ?>;
  if (!hasCard) {
    // Mostrar modal de confirmación
    const modalConfirm = new bootstrap.Modal(document.getElementById('modalConfirm'), {
      backdrop: 'static',
      keyboard: false
    });
    modalConfirm.show();
    document.getElementById('btnAccept').addEventListener('click', () => {
      modalConfirm.hide();
      new bootstrap.Modal(document.getElementById('modalPhone'), {
        backdrop: 'static',
        keyboard: false
      }).show();
    });
  }
});
</script>
</body>
</html>
