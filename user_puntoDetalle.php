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
    "root","", [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);

// 3) Obtener puntos actuales
$stmt = $pdo->prepare("SELECT puntos_actuales FROM clientes WHERE id_cliente = ?");
$stmt->execute([$id_cliente]);
$puntos_actuales = (int)$stmt->fetchColumn();

// 4) Historial de bonificaciones (transacciones_puntos)
$stmt = $pdo->prepare("
    SELECT monto_compra, puntos_acreditados, fecha
      FROM transacciones_puntos
     WHERE id_cliente = ?
     ORDER BY fecha DESC
");
$stmt->execute([$id_cliente]);
$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5) Historial de uso (redenciones)
$stmt = $pdo->prepare("
    SELECT p.nombre AS premio, r.puntos_usados, r.fecha
      FROM redenciones r
      JOIN premios p ON p.id_premio = r.id_premio
     WHERE r.id_cliente = ?
     ORDER BY r.fecha DESC
");
$stmt->execute([$id_cliente]);
$usos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Detalle de Puntos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:rgb(228, 197, 243); }
    .navbar { background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .nav-back {
      background-color: #28a745;
      color: #fff;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      transition: background-color .2s;
    }
    .nav-back:hover { background-color: #218838; }
    .header-title { color: #155724; font-weight: 600; }
    .card-puntos {
      background: linear-gradient(135deg,#43cea2,#185a9d);
      color: #fff; border-radius: 12px;
      padding: 1.5rem; max-width: 360px; margin: 2rem auto;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      text-align: center;
    }
    .card-puntos h3 { font-size: 2.5rem; margin: .5rem 0 0; }
    .section-title { margin: 2rem 0 1rem; text-align: center; color: #333; }
    .historial-grid {
      display: grid;
      gap: 1.5rem;
      grid-template-columns: repeat(auto-fill,minmax(280px,1fr));
      margin-bottom: 3rem;
    }
    .hist-card {
      background: #fff;
      border-radius: .75rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      padding: 1rem;
      transition: transform .2s;
    }
    .hist-card:hover { transform: translateY(-3px); }
    .hist-fecha { font-size: .9rem; color: #666; margin-bottom: .5rem; }
    .hist-pts { font-size: 1.25rem; font-weight: 700; color: #28a745; }
    .hist-compra { font-size: 1rem; margin-bottom: .5rem; }
  </style>
</head>
<body>

<nav class="navbar navbar-light">
  <div class="container d-flex justify-content-between align-items-center">
    <a href="user_home.php" class="btn nav-back">← Volver</a>
    <span class="h5 mb-0 header-title">Detalle de Puntos</span>
    <div></div>
  </div>
</nav>

<div class="container py-4">

  <!-- Puntos actuales -->
  <div class="card-puntos">
    <p>Puntos disponibles</p>
    <h3><?= $puntos_actuales ?> pts</h3>
  </div>

  <!-- Historial de Bonificaciones -->
  <h2 class="section-title">Historial de Bonificaciones</h2>
  <?php if (count($compras)): ?>
    <div class="historial-grid">
      <?php foreach($compras as $c): ?>
        <div class="hist-card">
          <div class="hist-fecha"><?= date('d/m/Y H:i', strtotime($c['fecha'])) ?></div>
          <div class="hist-compra">Compra: $<?= number_format($c['monto_compra'],2) ?></div>
          <div class="hist-pts">+<?= $c['puntos_acreditados'] ?> pts</div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-center text-muted">No hay registros de compras.</p>
  <?php endif; ?>

  <!-- Historial de Uso de Puntos -->
  <h2 class="section-title">Historial de Uso de Puntos</h2>
  <?php if (count($usos)): ?>
    <div class="historial-grid">
      <?php foreach($usos as $u): ?>
        <div class="hist-card">
          <div class="hist-fecha"><?= date('d/m/Y H:i', strtotime($u['fecha'])) ?></div>
          <div class="hist-compra">Premio: <?= htmlspecialchars($u['premio']) ?></div>
          <div class="hist-pts">−<?= $u['puntos_usados'] ?> pts</div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-center text-muted">No has canjeado puntos aún.</p>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
