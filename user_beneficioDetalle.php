<?php
session_start();
// 1) Verificar sesión de cliente
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit;
}
$id_cliente = $_SESSION['user_id'];

// 2) Conexión a la BD
$pdo = new PDO(
    "mysql:host=localhost;dbname=fidelizacion;charset=utf8mb4",
    "root", "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 3) Obtener detalle del beneficio
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("
    SELECT empresa, descripcion, descuento, vigente_desde, vigente_hasta, imagen
      FROM beneficios
     WHERE id_beneficio = ? AND activo = 1
");
$stmt->execute([$id]);
$b = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$b) {
    echo "<p class='no-found'>Beneficio no encontrado o ya no está disponible.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Detalle del Beneficio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body {
      background:rgb(219, 252, 224);
      font-family: 'Inter', sans-serif;
      color: #444;
      margin: 0; padding: 0;
    }
    .navbar {
      background: #ffffff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      padding: 0.75rem 1.5rem;
    }
    .nav-back {
      background: linear-gradient(135deg, #6a11cb, #2575fc);
      color: #fff;
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      display: inline-flex;
      align-items: center;
      font-weight: 600;
      transition: transform 0.2s ease;
    }
    .nav-back:hover { transform: translateY(-2px); }

    .no-found {
      text-align: center;
      margin: 4rem 1rem;
      font-size: 1.25rem;
      color: #777;
    }

    .card-detalle {
      max-width: 800px;
      margin: 2rem auto 4rem;
      background: #fff;
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    .card-body {
      padding: 2rem;
    }
    .card-body h1 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      text-align: center;
      background: linear-gradient(90deg, #6a11cb, #2575fc);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .card-image {
      width: 100%;
      max-height: 300px;
      overflow: hidden;
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .card-image img {
      width: auto;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .card-image:hover img {
      transform: scale(1.05);
    }
    .badge-descuento {
      display: inline-block;
      background: linear-gradient(135deg, #ff5f6d, #ffc371);
      color: #fff;
      padding: 0.5rem 1rem;
      border-radius: 1rem;
      font-weight: 600;
      margin: 0 auto 1rem;
      text-align: center;
      font-size: 1.1rem;
    }
    .detalle-list {
      margin: 2rem 0;
      row-gap: 1rem;
    }
    .detalle-list dt {
      font-weight: 600;
      color: #555;
    }
    .detalle-list dd {
      margin: 0;
      color: #333;
    }

    @media (max-width: 576px) {
      .card-body h1 { font-size: 2rem; }
      .detalle-list dt, .detalle-list dd { font-size: 0.95rem; }
    }
  </style>
</head>
<body>

<nav class="navbar d-flex justify-content-between align-items-center">
  <a href="user_beneficio.php" class="nav-back">← Volver</a>
  <span class="h5 m-0">Detalle del Beneficio</span>
  <div style="width:3.5rem;"></div>
</nav>

<article class="card-detalle">
  <div class="card-body">
    <!-- Etiqueta de descuento corregida -->
    <div class="badge-descuento">
      <?= htmlspecialchars($b['descuento']) ?> de descuento
    </div>
    <!-- Título empresa -->
    <h1><?= htmlspecialchars($b['empresa']) ?></h1>
    <!-- Imagen principal -->
    <div class="card-image">
      <img
        src="<?= htmlspecialchars($b['imagen'] ?: 'https://via.placeholder.com/800x450?text=Beneficio') ?>"
        alt="<?= htmlspecialchars($b['empresa']) ?>"
      >
    </div>
    <!-- Detalles -->
    <dl class="row detalle-list">
      <dt class="col-sm-4">Descripción:</dt>
      <dd class="col-sm-8"><?= nl2br(htmlspecialchars($b['descripcion'])) ?></dd>

      <dt class="col-sm-4">Vigencia:</dt>
      <dd class="col-sm-8">
        Desde <strong><?= date('d/m/Y', strtotime($b['vigente_desde'])) ?></strong>
        hasta <strong><?= date('d/m/Y', strtotime($b['vigente_hasta'])) ?></strong>
      </dd>
    </dl>
  </div>
</article>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
