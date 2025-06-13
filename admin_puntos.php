<?php
session_start();
// Verificar rol admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Conexión a BD
$host = 'localhost';
$db   = 'fidelizacion';
$user = 'root';
$pass = '';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

$action = $_GET['action'] ?? 'list';
$error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form_type'] === 'register_purchase') {
        // Registrar compra y bonificar puntos
        $id_cliente = $_POST['id_cliente'];
        $monto = (float)$_POST['monto_compra'];
        $pts = floor($monto / 100) * 5;
        $stmt = $pdo->prepare("INSERT INTO transacciones_puntos(id_cliente,monto_compra,puntos_acreditados) VALUES(?,?,?)");
        $stmt->execute([$id_cliente, $monto, $pts]);
        // Actualizar puntos en clientes
        $pdo->prepare("UPDATE clientes SET puntos_actuales = puntos_actuales + ? WHERE id_cliente = ?")
            ->execute([$pts, $id_cliente]);
        header('Location: admin_puntos.php'); exit;
    }

    if ($_POST['form_type'] === 'redeem_points') {
        // Redimir puntos
        $id_cliente = $_POST['id_cliente'];
        $id_premio  = $_POST['id_premio'];
        $pts_req    = (int)$_POST['puntos_usados'];

        // 1) Obtener puntos actuales
        $stmt = $pdo->prepare("SELECT puntos_actuales FROM clientes WHERE id_cliente = ?");
        $stmt->execute([$id_cliente]);
        $current = (int)$stmt->fetchColumn();

        // 2) Calcular puntos restantes
        $remaining = $current - $pts_req;

        // 3) Insertar redención incluyendo puntos_restantes
        $stmt = $pdo->prepare("
          INSERT INTO redenciones (id_cliente, id_premio, puntos_usados, puntos_restantes)
          VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$id_cliente, $id_premio, $pts_req, $remaining]);

        // 4) Actualizar puntos en clientes
        $pdo->prepare("UPDATE clientes SET puntos_actuales = ? WHERE id_cliente = ?")
            ->execute([$remaining, $id_cliente]);

        header('Location: admin_puntos.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puntos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/tablas.css">
    <style>
        body{background:#f5f7fa;}
        .header{padding:1rem;display:flex;align-items:center;}
        .header a{margin-right:1rem;font-size:1.5rem;text-decoration:none;color:#556;}
        .card{margin-top:1rem;border-radius:8px;}
        .btn-action{margin-right:.5rem;}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="admin.php">&larr;</a>
        <h2>Módulo de Puntos</h2>
    </div>

    <?php if ($action === 'list'): ?>
        <a href="admin_puntos.php?action=add" class="btn btn-primary">Registrar Compra</a>
        <a href="admin_puntos.php?action=redeem" class="btn btn-success">Redimir Puntos</a>

        <!-- Listado de transacciones -->
        <h4 class="mt-4">Transacciones de Puntos</h4>
        <table class="table table-striped card">
            <thead>
              <tr>
                <th>#</th><th>Cliente</th><th>Monto (MXN)</th>
                <th>Puntos</th><th>Fecha</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $stmt = $pdo->query("
                SELECT t.id_transaccion,
                       CONCAT(c.nombre,' ',c.apellidos) AS cliente,
                       t.monto_compra,
                       t.puntos_acreditados,
                       t.fecha
                  FROM transacciones_puntos t
                  JOIN clientes c ON t.id_cliente=c.id_cliente
                  ORDER BY t.fecha DESC
            ");
            while($t = $stmt->fetch()): ?>
                <tr>
                    <td><?= $t['id_transaccion'] ?></td>
                    <td><?= htmlspecialchars($t['cliente']) ?></td>
                    <td><?= number_format($t['monto_compra'],2) ?></td>
                    <td><?= $t['puntos_acreditados'] ?></td>
                    <td><?= $t['fecha'] ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Listado de redenciones -->
        <h4 class="mt-4">Redenciones de Puntos</h4>
        <table class="table table-striped card">
            <thead>
              <tr>
                <th>#</th><th>Cliente</th><th>Premio</th>
                <th>Puntos Usados</th><th>Puntos Restantes</th><th>Fecha</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $stmt = $pdo->query("
                SELECT r.id_redencion,
                       CONCAT(c.nombre,' ',c.apellidos) AS cliente,
                       p.nombre AS premio,
                       r.puntos_usados,
                       r.puntos_restantes,
                       r.fecha
                  FROM redenciones r
                  JOIN clientes c ON r.id_cliente=c.id_cliente
                  JOIN premios p ON r.id_premio=p.id_premio
                  ORDER BY r.fecha DESC
            ");
            while($r = $stmt->fetch()): ?>
                <tr>
                    <td><?= $r['id_redencion'] ?></td>
                    <td><?= htmlspecialchars($r['cliente']) ?></td>
                    <td><?= htmlspecialchars($r['premio']) ?></td>
                    <td><?= $r['puntos_usados'] ?></td>
                    <td><?= $r['puntos_restantes'] ?></td>
                    <td><?= $r['fecha'] ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

    <?php elseif ($action === 'add'): ?>
        <div class="card p-4 mt-4">
            <h4>Registrar Compra</h4>
            <form method="POST">
                <input type="hidden" name="form_type" value="register_purchase">
                <div class="mb-3">
                    <label class="form-label">Cliente</label>
                    <select name="id_cliente" class="form-select" required>
                        <option value="">Selecciona...</option>
                        <?php
                        $stmt = $pdo->query("SELECT id_cliente,nombre,apellidos FROM clientes");
                        while($c = $stmt->fetch()): ?>
                            <option value="<?= $c['id_cliente'] ?>">
                                <?= htmlspecialchars($c['nombre'].' '.$c['apellidos']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Monto de Compra (MXN)</label>
                    <input type="number" step="0.01" name="monto_compra" id="monto_compra" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Puntos a Acreditar</label>
                    <input type="number" name="puntos_acreditados" id="puntos_acreditados" class="form-control" readonly>
                </div>
                <button class="btn btn-primary">Registrar</button>
                <a href="admin_puntos.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
        <script>
            document.getElementById('monto_compra').addEventListener('input', function(){
                const v = parseFloat(this.value) || 0;
                document.getElementById('puntos_acreditados').value = Math.floor(v/100)*5;
            });
        </script>

    <?php elseif ($action === 'redeem'): ?>
        <div class="card p-4 mt-4">
            <h4>Redimir Puntos</h4>
            <form method="POST">
                <input type="hidden" name="form_type" value="redeem_points">
                <div class="mb-3">
                    <label class="form-label">Cliente</label>
                    <select name="id_cliente" id="cliente_sel" class="form-select" required>
                        <option value="">Selecciona...</option>
                        <?php
                        $stmt = $pdo->query("SELECT id_cliente,nombre,apellidos,puntos_actuales FROM clientes");
                        while($c = $stmt->fetch()): ?>
                            <option value="<?= $c['id_cliente'] ?>"
                                    data-puntos="<?= $c['puntos_actuales'] ?>">
                                <?= htmlspecialchars($c['nombre'].' '.$c['apellidos'].' ('.$c['puntos_actuales'].' pts)') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Premio</label>
                    <select name="id_premio" id="premio_sel" class="form-select" required>
                        <option value="">Selecciona...</option>
                        <?php
                        $stmt = $pdo->query("SELECT id_premio,nombre,puntos_requeridos FROM premios WHERE activo=1");
                        while($p = $stmt->fetch()): ?>
                            <option value="<?= $p['id_premio'] ?>"
                                    data-ptsreq="<?= $p['puntos_requeridos'] ?>">
                                <?= htmlspecialchars($p['nombre'].' ('.$p['puntos_requeridos'].' pts)') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Puntos a Usar</label>
                    <input type="number" name="puntos_usados" id="puntos_usados" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Puntos Restantes</label>
                    <input type="number" name="puntos_restantes" id="puntos_restantes" class="form-control" readonly>
                </div>
                <button class="btn btn-success" id="btnRedeem" disabled>Redimir</button>
                <a href="admin_puntos.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
        <script>
            const clienteSel = document.getElementById('cliente_sel');
            const premioSel = document.getElementById('premio_sel');
            const puntosUs   = document.getElementById('puntos_usados');
            const puntosRest = document.getElementById('puntos_restantes');
            const btnRedeem  = document.getElementById('btnRedeem');

            function updateRedeem(){
                const cl = parseInt(clienteSel.selectedOptions[0]?.dataset.puntos || 0);
                const pr = parseInt(premioSel.selectedOptions[0]?.dataset.ptsreq || 0);
                const ok = cl >= pr;
                puntosUs.value   = ok ? pr : 0;
                puntosRest.value = ok ? (cl - pr) : cl;
                btnRedeem.disabled = !ok;
            }
            clienteSel.onchange = updateRedeem;
            premioSel.onchange  = updateRedeem;
        </script>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
