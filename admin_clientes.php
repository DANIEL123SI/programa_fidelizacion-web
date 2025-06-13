<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

// Verificar rol admin
if (!isset($_SESSION['rol']) || $_SESSION['rol']!=='admin') {
    header('Location: login.php');
    exit;
}

// Conexión a BD
$dsn = "mysql:host=localhost;dbname=fidelizacion;charset=utf8mb4";
try {
    $pdo = new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Error BD: ".$e->getMessage());
}

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Obtener metadatos de columnas
$cols = [];
$stmt = $pdo->query("DESCRIBE clientes");
while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = $col;
}

// Columna PK y lista de campos editables
$pk = null;
$fields = [];
foreach ($cols as $col) {
    if ($col['Key']==='PRI') {
        $pk = $col['Field'];
        continue;
    }
    // omitimos creado_en automático y tarjeta_digital (si lo quieres)
    if (in_array($col['Field'], ['creado_en'])) {
        continue;
    }
    $fields[] = $col;
}

// Procesar POST (add/edit)
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $data = [];
    foreach ($fields as $col) {
        $name = $col['Field'];
        $data[$name] = $_POST[$name] ?? null;
    }
    if ($_POST['form_type']==='save') {
        if (!empty($_POST[$pk])) {
            // UPDATE
            $sets = [];
            foreach ($fields as $col) {
                $sets[] = $col['Field']." = ?";
            }
            $sql = "UPDATE clientes SET ".implode(', ',$sets)." WHERE $pk = ?";
            $stmt= $pdo->prepare($sql);
            $stmt->execute(array_merge(array_values($data), [$_POST[$pk]]));
        } else {
            // INSERT
            $names = array_map(fn($c)=>$c['Field'],$fields);
            $ph    = array_fill(0, count($names),'?');
            $sql   = "INSERT INTO clientes (".implode(', ',$names).") VALUES (".implode(', ',$ph).")";
            $stmt  = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
        }
    }
    if ($_POST['form_type']==='delete') {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE $pk = ?");
        $stmt->execute([$_POST[$pk]]);
    }
    header('Location: admin_clientes.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CRUD Clientes</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet">
  <link rel="stylesheet" href="css/tablas.css">
</head>
<body>
  <div class="container">
    <div class="page-header">
      <a href="admin.php">&larr;</a>
      <h2>Clientes</h2>
    </div>

    <?php if ($action==='list'): ?>
      <a href="?action=add" class="btn btn-primary mb-3">+ Nuevo Cliente</a>
      <div class="card">
        <div class="table-container">
          <table class="table table-striped">
            <thead>
              <tr>
                <?php foreach ($cols as $col): ?>
                  <th><?= htmlspecialchars($col['Field']) ?></th>
                <?php endforeach ?>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $rows = $pdo->query("SELECT * FROM clientes ORDER BY $pk")
                        ->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row): ?>
              <tr>
                <?php foreach ($cols as $col): ?>
                  <td><?= htmlspecialchars($row[$col['Field']]) ?></td>
                <?php endforeach ?>
                <td>
                  <a href="?action=edit&id=<?= $row[$pk] ?>"
                     class="btn btn-sm btn-warning">Editar</a>
                  <form method="POST" class="d-inline"
                        onsubmit="return confirm('¿Eliminar?');">
                    <input type="hidden" name="form_type" value="delete">
                    <input type="hidden" name="<?= $pk ?>"
                           value="<?= $row[$pk] ?>">
                    <button class="btn btn-sm btn-danger">Borrar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php else:
      // cargar datos existentes si edit
      $record = [];
      if ($action==='edit' && $id) {
          $stmt = $pdo->prepare("SELECT * FROM clientes WHERE $pk = ?");
          $stmt->execute([$id]);
          $record = $stmt->fetch(PDO::FETCH_ASSOC);
      }
    ?>
      <div class="card card--padded">
        <h4><?= $action==='add' ? 'Nuevo' : 'Editar' ?> Cliente</h4>
        <form method="POST">
          <input type="hidden" name="form_type" value="save">
          <?php if ($action==='edit'): ?>
            <input type="hidden" name="<?= $pk ?>"
                   value="<?= $record[$pk] ?>">
          <?php endif ?>

          <?php foreach ($fields as $col): 
               $name = $col['Field'];
               $val  = $record[$name] ?? '';
          ?>
            <div class="mb-3">
              <label class="form-label">
                <?= ucfirst($name) ?>
              </label>
              <input
                type="<?= $name==='correo' ? 'email' : 'text' ?>"
                name="<?= $name ?>"
                class="form-control"
                value="<?= htmlspecialchars($val) ?>"
                <?= $col['Null']==='NO' ? 'required' : '' ?>>
            </div>
          <?php endforeach ?>

          <button type="submit" class="btn btn-primary">
            <?= $action==='add' ? 'Guardar' : 'Actualizar' ?>
          </button>
          <a href="admin_clientes.php" class="btn btn-secondary">Cancelar</a>
        </form>
      </div>
    <?php endif ?>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
  </script>
</body>
</html>
