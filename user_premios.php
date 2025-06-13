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
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO redenciones (id_cliente,id_premio,puntos_usados) VALUES (?,?,?)")
            ->execute([$id_cliente, $id_premio, $premio['puntos_requeridos']]);
        $pdo->prepare("UPDATE clientes SET puntos_actuales = puntos_actuales - ? WHERE id_cliente = ?")
            ->execute([$premio['puntos_requeridos'], $id_cliente]);
        $pdo->prepare("UPDATE premios SET stock = stock - 1 WHERE id_premio = ?")
            ->execute([$id_premio]);
        $pdo->commit();
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
  <title>Premios - Fidelización</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:rgb(209, 243, 244); }
    .navbar { background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .nav-back { 
      background: #fff; 
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      color: #333;
    }
    .user-card {
      background: #fff;
      border-radius: .75rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      padding: 1rem;
      text-align: center;
    }
    .user-card .numero { font-size: 1.1rem; letter-spacing: 2px; }
    .user-card .pts { font-size: 1.5rem; font-weight: 700; color: #4e73df; }
    .premio-card {
      border: none;
      border-radius: .75rem;
      overflow: hidden;
      transition: transform .2s;
    }
    .premio-card:hover { transform: translateY(-4px); }
    .premio-card img { height: 160px; object-fit: cover; }
    .premio-body { padding: 1rem; }
    .premio-name { font-size: 1.1rem; font-weight: 600; margin-bottom: .5rem; }
    .premio-pts { color: #1cc88a; font-weight: 700; margin-bottom: .75rem; }
  </style>
</head>
<body>

<nav class="navbar navbar-light">
  <div class="container d-flex justify-content-between align-items-center">
    <a href="user_home.php" class="btn nav-back">← Volver</a>
    <span class="h5 mb-0 title-nav">Premios Disponibles</span>
    <div></div>
  </div>
</nav>

<style>
  .nav-back {
    background-color: #28a745;    /* verde Bootstrap “success” */
    color: #ffffff;               /* texto blanco */
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: background-color 0.2s ease;
  }
  .nav-back:hover {
    background-color: #218838;    /* un poco más oscuro al pasar el ratón */
    color: #fff;
  }
  .title-nav {
    color: #155724;               /* verde oscuro para el título */
    font-weight: 600;
  }
</style>

<div class="container py-4">

  <?php if($message): ?>
    <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="row mb-4 justify-content-center">
    <div class="col-md-4 text-center">
      <div class="mb-2 fw-semibold">Tarjeta que se está usando:</div>
      <div class="user-card mx-auto">
        <div class="numero"><?= chunk_split($u['numero'] ?? '**** **** **** ****', 4, ' ') ?></div>
        <div class="pts"><?= $u['puntos_actuales'] ?> pts</div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <?php foreach($premios as $p): ?>
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card premio-card shadow-sm">
          <img src="<?= htmlspecialchars($p['imagen']?:'https://via.placeholder.com/300x160?text=Premio') ?>"
               class="card-img-top" alt="<?= htmlspecialchars($p['nombre']) ?>">
          <div class="card-body premio-body">
            <div class="premio-name"><?= htmlspecialchars($p['nombre']) ?></div>
            <div class="premio-pts"><?= $p['puntos_requeridos'] ?> pts</div>
            <form method="POST">
              <input type="hidden" name="action" value="buy">
              <input type="hidden" name="id_premio" value="<?= $p['id_premio'] ?>">
              <button class="btn btn-primary w-100"
                      <?= ($u['puntos_actuales'] < $p['puntos_requeridos'] || $p['stock']<1)
                          ? 'disabled' : '' ?>>
                <?= $p['stock']<1 ? 'Agotado' : 'Canjear' ?>
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
