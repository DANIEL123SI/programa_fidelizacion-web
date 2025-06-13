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

// 1) Obtener datos de cliente y tarjeta
$stmt = $pdo->prepare("
  SELECT c.puntos_actuales, t.numero
    FROM clientes c
    LEFT JOIN tarjetas t ON t.id_cliente = c.id_cliente
   WHERE c.id_cliente = ?
");
$stmt->execute([$id_cliente]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

// 2) Procesar compra de premio
$message = "";
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='buy') {
    $id_premio = (int)$_POST['id_premio'];

    // 2.1) Info del premio
    $p = $pdo->prepare("SELECT puntos_requeridos, stock FROM premios WHERE id_premio = ? AND activo = 1");
    $p->execute([$id_premio]);
    $premio = $p->fetch(PDO::FETCH_ASSOC);

    if (!$premio) {
        $message = "Premio no encontrado.";
    } elseif ($u['puntos_actuales'] < $premio['puntos_requeridos']) {
        $message = "No tienes suficientes puntos.";
    } elseif ($premio['stock'] < 1) {
        $message = "Lo sentimos, este premio está agotado.";
    } else {
        // 2.2) Transacción: insertar redención, actualizar puntos y stock
        $pdo->beginTransaction();
        $inst = $pdo->prepare("
          INSERT INTO redenciones (id_cliente,id_premio,puntos_usados)
          VALUES (?,?,?)
        ");
        $inst->execute([$id_cliente, $id_premio, $premio['puntos_requeridos']]);

        $upd1 = $pdo->prepare("
          UPDATE clientes
             SET puntos_actuales = puntos_actuales - ?
           WHERE id_cliente = ?
        ");
        $upd1->execute([$premio['puntos_requeridos'], $id_cliente]);

        $upd2 = $pdo->prepare("
          UPDATE premios
             SET stock = stock - 1
           WHERE id_premio = ?
        ");
        $upd2->execute([$id_premio]);

        $pdo->commit();
        // recarga datos de puntos
        $u['puntos_actuales'] -= $premio['puntos_requeridos'];
        $message = "¡Has canjeado el premio correctamente!";
    }
}

// 3) Obtener lista de premios activos
$stmt = $pdo->query("
  SELECT id_premio, nombre, descripcion, puntos_requeridos, stock, imagen
    FROM premios
   WHERE activo = 1
   ORDER BY puntos_requeridos ASC
");
$premios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Premios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">
  <style>
    body { background: #f5f7fa; }
    .header { display:flex; align-items:center; justify-content:space-between; margin:1.5rem; }
    .card-mini {
      padding:0.75rem 1.25rem;
      border-radius:12px;
      background: linear-gradient(135deg,#667eea,#764ba2);
      color:#fff;
      font-weight:500;
    }
    .grid { display:grid; gap:1.5rem; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); }
    .premio-card { border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
    .premio-card img { width:100%; height:140px; object-fit:cover; }
    .premio-body { padding:1rem; }
    .premio-name { font-size:1.1rem; margin-bottom:0.5rem; }
    .premio-pts { font-weight:600; color:#333; margin-bottom:0.75rem; }
  </style>
</head>
<body>

<div class="container">
  <div class="header">
    <h2 style="position: relative; left: 500px;">Premios Disponibles</h2>
    <a href="user_home.php" class="btn btn-outline-secondary btn-back" style="position: relative; left: -550px; top:60px;">← Volver</a>
  </div>

<div class="container">
  <div class="header">
    <h2></h2>
    <div class="card-mini" style="position: relative; top: 70px; left: -500px;">
      <small>Tarjeta nº</small><br>
      <?= chunk_split($u['numero']??'************0000',4,' ') ?>
      <br><small><?= $u['puntos_actuales'] ?> pts</small>
    </div>
  </div>

  <?php if($message): ?>
    <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="grid">
    <?php foreach($premios as $p): ?>
      <div class="premio-card">
        <img src="<?= htmlspecialchars($p['imagen']?:'https://via.placeholder.com/300x140?text=Premio') ?>"
             alt="<?= htmlspecialchars($p['nombre']) ?>">
        <div class="premio-body">
          <div class="premio-name"><?= htmlspecialchars($p['nombre']) ?></div>
          <div class="premio-pts"><?= $p['puntos_requeridos'] ?> pts</div>
          <form method="POST">
            <input type="hidden" name="action" value="buy">
            <input type="hidden" name="id_premio" value="<?= $p['id_premio'] ?>">
            <button class="btn btn-outline-primary w-100"
                    <?= $u['puntos_actuales'] < $p['puntos_requeridos'] || $p['stock']<1 
                        ? 'disabled' : '' ?>>
              <?= $p['stock']<1 ? 'Agotado' : 'Comprar' ?>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

</body>
</html>
