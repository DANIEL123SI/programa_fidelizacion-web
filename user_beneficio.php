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
    .card-beneficio {
      transition: transform .2s, box-shadow .2s;
      cursor: pointer;
    }
    .card-beneficio:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .card-beneficio img {
      object-fit: cover;
      height: 180px;
      width: 100%;
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex align-items-center mb-4" style="position: relative; left:500px;">
      <a href="user_home.php" class="btn btn-outline-secondary me-3">← Volver</a>
      <h2 class="mb-0">Beneficios para Ti</h2>
    </div>
    <div class="row g-3">
      <?php foreach($beneficios as $b): ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
          <div class="card card-beneficio h-100" onclick="location.href='user_beneficioDetalle.php?id=<?= $b['id_beneficio'] ?>'">
            <img src="<?= htmlspecialchars($b['imagen']) ?>" class="card-img-top" alt="<?= htmlspecialchars($b['empresa']) ?>">
            <div class="card-body text-center">
              <h5 class="card-title mb-0"><?= htmlspecialchars($b['empresa']) ?></h5>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if(count($beneficios) === 0): ?>
        <div class="col-12">
          <p class="text-center">No hay beneficios disponibles en este momento.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
