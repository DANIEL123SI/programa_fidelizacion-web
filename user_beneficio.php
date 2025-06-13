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

// 3) Obtener todos los beneficios activos
$stmt = $pdo->query("SELECT id_beneficio, empresa, imagen FROM beneficios WHERE activo = 1");
$beneficios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Beneficios Disponibles</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:rgb(238, 244, 209); }

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

    .header-title {
      color: #155724;
      font-weight: 600;
      font-size: 1.5rem;
    }

    .card-beneficio {
      border: none;
      border-radius: .75rem;
      overflow: hidden;
      transition: transform .2s, box-shadow .2s;
      background: #fff;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      cursor: pointer;
    }
    .card-beneficio:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .card-beneficio img {
      width: 100%;
      height: 160px;
      object-fit: cover;
    }
    .card-beneficio .card-title {
      font-weight: 700;
      font-size: 1.1rem;
      color: #333;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-light">
  <div class="container d-flex justify-content-between align-items-center">
    <a href="user_home.php" class="btn nav-back">← Volver</a>
    <span class="header-title">Beneficios para Ti</span>
    <div></div>
  </div>
</nav>

<div class="container py-4">
  <div class="row g-4">
    <?php if(count($beneficios)): ?>
      <?php foreach($beneficios as $b): ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
          <div class="card-beneficio h-100" onclick="location.href='user_beneficioDetalle.php?id=<?= $b['id_beneficio'] ?>'">
            <img src="<?= htmlspecialchars($b['imagen'] ?: 'https://via.placeholder.com/300x160?text=Beneficio') ?>"
                 alt="<?= htmlspecialchars($b['empresa']) ?>">
            <div class="card-body text-center">
              <h5 class="card-title"><?= htmlspecialchars($b['empresa']) ?></h5>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-12">
        <p class="text-center text-muted">No hay beneficios disponibles en este momento.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
