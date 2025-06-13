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
    body { background: #f0f4f8; }
    .header { margin: 2rem 0; text-align: center; }
    .card-puntos {
      background: linear-gradient(135deg,#43cea2,#185a9d);
      color: #fff; border-radius: 12px;
      padding: 1.5rem; max-width: 400px; margin: 0 auto 2rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .card-puntos h3 { font-size: 2.5rem; margin: 0; }
    .section-title { margin: 2rem 0 1rem; text-align: center; }
    .table thead { background: #185a9d; color: #fff; }
    .table-striped tbody tr:nth-of-type(odd) { background: rgba(24,90,157,0.1); }
    .btn-back { margin-top: 2rem; }
  </style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1>Detalle de Puntos</h1>
    <a href="user_home.php" class="btn btn-outline-secondary btn-back">← Volver</a>
  </div>

  <!-- Puntos actuales -->
  <div class="card-puntos text-center">
    <p>Puntos disponibles</p>
    <h3><?= $puntos_actuales ?> pts</h3>
  </div>

  <!-- Historial de bonificaciones -->
  <h2 class="section-title">Historial de Bonificaciones</h2>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Compra (MXN)</th>
          <th>Puntos Acreditados</th>
        </tr>
      </thead>
      <tbody>
      <?php if (count($compras)): ?>
        <?php foreach($compras as $c): ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($c['fecha'])) ?></td>
            <td>$<?= number_format($c['monto_compra'],2) ?></td>
            <td>+<?= $c['puntos_acreditados'] ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="3" class="text-center">No hay registros de compras.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Historial de uso de puntos -->
  <h2 class="section-title">Historial de Uso de Puntos</h2>
  <div class="table-responsive mb-4">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Premio</th>
          <th>Puntos Usados</th>
        </tr>
      </thead>
      <tbody>
      <?php if (count($usos)): ?>
        <?php foreach($usos as $u): ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($u['fecha'])) ?></td>
            <td><?= htmlspecialchars($u['premio']) ?></td>
            <td>-<?= $u['puntos_usados'] ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="3" class="text-center">No has canjeado puntos aún.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
