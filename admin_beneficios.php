<?php
// beneficios.php
session_start();
// 1) Verificar rol admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 2) Conexión a BD (poner aquí tu db.php o conexión inline)
$host='localhost'; $db='fidelizacion'; $user='root'; $pass='';
$pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION
]);

$action = $_GET['action'] ?? 'list';
$uploadDir = __DIR__ . '/img/beneficios/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

// 3) Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Borrado
    if ($_POST['form_type']==='delete_beneficio') {
        // Opcional: borrar archivo físico
        $stmt = $pdo->prepare("SELECT imagen FROM beneficios WHERE id_beneficio=?");
        $stmt->execute([$_POST['id']]);
        $old = $stmt->fetchColumn();
        if ($old && file_exists(__DIR__.'/'.$old)) unlink(__DIR__.'/'.$old);

        $pdo->prepare("DELETE FROM beneficios WHERE id_beneficio=?")
            ->execute([$_POST['id']]);
        header('Location: beneficios.php');
        exit;
    }

    // Guardar (insert/update)
    if ($_POST['form_type']==='save_beneficio') {
        $id            = $_POST['id'] ?: null;
        $empresa       = $_POST['empresa'];
        $descripcion   = $_POST['descripcion'];
        $descuento     = $_POST['descuento'];
        $desde         = $_POST['vigente_desde'];
        $hasta         = $_POST['vigente_hasta'];
        $activo        = isset($_POST['activo'])?1:0;

        // 3.1) Procesar subida de imagen
        $imagenPath = null;
        if (!empty($_FILES['imagen']['name'])) {
            $tmp  = $_FILES['imagen']['tmp_name'];
            $ext  = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $name = uniqid('ben_').'.'.$ext;
            move_uploaded_file($tmp, $uploadDir.$name);
            $imagenPath = 'img/beneficios/'.$name;
        }

        if ($id) {
            // UPDATE
            $sql = "UPDATE beneficios 
                       SET empresa=?, descripcion=?, descuento=?, vigente_desde=?, vigente_hasta=?, activo=?"
                  .($imagenPath? ", imagen=?" : "")
                  ." WHERE id_beneficio=?";
            $stmt = $pdo->prepare($sql);
            $params = [$empresa,$descripcion,$descuento,$desde,$hasta,$activo];
            if ($imagenPath) $params[] = $imagenPath;
            $params[] = $id;
            $stmt->execute($params);
        } else {
            // INSERT
            $sql = "INSERT INTO beneficios 
                      (empresa, descripcion, descuento, vigente_desde, vigente_hasta, activo, imagen)
                    VALUES (?,?,?,?,?,?,?)";
            $pdo->prepare($sql)
                ->execute([$empresa,$descripcion,$descuento,$desde,$hasta,$activo,$imagenPath]);
        }

        header('Location: beneficios.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Admin – Beneficios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/tablas.css">
  <style>
    body { background: #f5f7fa; }
    .img-thumb {
      width: 100px;
      height: 70px;
      object-fit: cover;
      border-radius: 4px;
    }
    .header { padding: 1rem; display: flex; align-items: center; }
    .header a { margin-right: 1rem; font-size: 1.5rem; color: #556; text-decoration: none; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="header">
    <a href="admin.php">&larr;</a>
    <h2>Gestión de Beneficios</h2>
  </div>

  <?php if ($action==='list'): ?>
    <a href="?action=add" class="btn btn-primary mb-3">+ Nuevo Beneficio</a>
    <table class="table table-striped bg-white rounded shadow-sm">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Imagen</th>
          <th>Empresa</th>
          <th>Descuento</th>
          <th>Desde</th>
          <th>Hasta</th>
          <th>Activo</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php
        $stmt = $pdo->query("SELECT * FROM beneficios ORDER BY empresa");
        while($b = $stmt->fetch()):
      ?>
        <tr>
          <td><?= $b['id_beneficio'] ?></td>
          <td>
            <?php if($b['imagen'] && file_exists(__DIR__.'/'.$b['imagen'])): ?>
              <img src="<?= htmlspecialchars($b['imagen']) ?>" class="img-thumb">
            <?php else: ?>
              — sin imagen —
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($b['empresa']) ?></td>
          <td><?= htmlspecialchars($b['descuento']) ?></td>
          <td><?= $b['vigente_desde'] ?></td>
          <td><?= $b['vigente_hasta'] ?></td>
          <td><?= $b['activo']? 'Sí':'No' ?></td>
          <td>
            <a href="?action=edit&id=<?= $b['id_beneficio'] ?>" class="btn btn-sm btn-warning">Editar</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar?')">
              <input type="hidden" name="form_type" value="delete_beneficio">
              <input type="hidden" name="id" value="<?= $b['id_beneficio'] ?>">
              <button class="btn btn-sm btn-danger">Borrar</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>

  <?php elseif(in_array($action, ['add','edit'])):
    $id = $_GET['id'] ?? null;
    $b = ['empresa'=>'','descripcion'=>'','descuento'=>'','vigente_desde'=>'','vigente_hasta'=>'','activo'=>1,'imagen'=>''];
    if ($id) {
      $stmt = $pdo->prepare("SELECT * FROM beneficios WHERE id_beneficio=?");
      $stmt->execute([$id]);
      $b = $stmt->fetch();
    }
  ?>
    <div class="card p-4 shadow-sm">
      <h4><?= $id? 'Editar':'Nuevo' ?> Beneficio</h4>
      <form method="POST" enctype="multipart/form-data" class="mt-3">
        <input type="hidden" name="form_type" value="save_beneficio">
        <?php if($id):?><input type="hidden" name="id" value="<?= $id ?>"><?php endif;?>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Empresa</label>
            <input type="text" name="empresa" class="form-control" required
                   value="<?= htmlspecialchars($b['empresa']) ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Descuento</label>
            <input type="text" name="descuento" class="form-control"
                   value="<?= htmlspecialchars($b['descuento']) ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($b['descripcion']) ?></textarea>
        </div>

        <div class="row mb-3">
          <div class="col">
            <label class="form-label">Vigente Desde</label>
            <input type="date" name="vigente_desde" class="form-control"
                   value="<?= $b['vigente_desde'] ?>">
          </div>
          <div class="col">
            <label class="form-label">Vigente Hasta</label>
            <input type="date" name="vigente_hasta" class="form-control"
                   value="<?= $b['vigente_hasta'] ?>">
          </div>
        </div>

        <div class="form-check mb-3">
          <input type="checkbox" name="activo" id="activo" class="form-check-input"
                 <?= $b['activo']? 'checked':'' ?>>
          <label for="activo" class="form-check-label">Activo</label>
        </div>

        <div class="mb-4">
          <label class="form-label">Imagen (100×70px)</label>
          <?php if($b['imagen'] && file_exists(__DIR__.'/'.$b['imagen'])): ?>
            <div class="mb-2">
              <img src="<?= htmlspecialchars($b['imagen']) ?>" class="img-thumb">
            </div>
          <?php endif; ?>
          <input type="file" name="imagen" accept="image/*" class="form-control">
        </div>

        <button class="btn btn-success"><?= $id? 'Actualizar' : 'Guardar' ?></button>
        <a href="beneficios.php" class="btn btn-secondary">Cancelar</a>
      </form>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
