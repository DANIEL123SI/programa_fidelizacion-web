<?php
session_start();
// 1) Verificar sesión de cliente
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
$id_cliente = $_SESSION['user_id'];

// Conexión
$pdo = new PDO(
    "mysql:host=localhost;dbname=fidelizacion;charset=utf8mb4",
    "root","", [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("
    SELECT empresa, descripcion, descuento, vigente_desde, vigente_hasta, imagen
      FROM beneficios
     WHERE id_beneficio = ? AND activo = 1
");
$stmt->execute([$id]);
$b = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$b) {
    echo "<p class='text-center mt-5'>Beneficio no encontrado o ya no está disponible.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle del Beneficio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .beneficio-header {
      background: linear-gradient(135deg,#667eea,#764ba2);
      color: #fff;
      padding: 2rem;
      border-radius: .5rem;
      text-align: center;
    }
    .beneficio-header img {
      max-width: 150px;
      border: 4px solid #fff;
      border-radius: .5rem;
      margin-bottom: 1rem;
    }
    .detalle {
      max-width: 700px;
      margin: 2rem auto;
    }
    .detalle dt {
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="beneficio-header">
    <img src="<?= htmlspecialchars($b['imagen']) ?>" alt="<?= htmlspecialchars($b['empresa']) ?>">
    <h1><?= htmlspecialchars($b['empresa']) ?></h1>
  </div>

  <div class="detalle container">
    <dl class="row">
      <dt class="col-sm-3">Descripción:</dt>
      <dd class="col-sm-9"><?= nl2br(htmlspecialchars($b['descripcion'])) ?></dd>

      <dt class="col-sm-3">Descuento:</dt>
      <dd class="col-sm-9"><?= htmlspecialchars($b['descuento']) ?></dd>

      <dt class="col-sm-3">Vigencia:</dt>
      <dd class="col-sm-9">
        Desde <?= date('d/m/Y', strtotime($b['vigente_desde'])) ?>
        hasta <?= date('d/m/Y', strtotime($b['vigente_hasta'])) ?>
      </dd>
    </dl>
    <div class="text-center mt-4">
      <a href="beneficio_usuario.php" class="btn btn-outline-primary">← Volver a Beneficios</a>
    </div>
  </div>
</body>
</html>
