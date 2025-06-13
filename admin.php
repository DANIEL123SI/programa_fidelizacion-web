<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Panel de Administración</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <style>
    :root {
      /* paleta armoniosa */
      --rosa:rgb(255, 255, 255);
      --menta:   #C8FAD8;
      --cielo:   #C8D7FA;
      --durazno: #FAE0C8;
    }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      /* fondo de imagen */
      background: url('img/fondo4.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    header {
      background: rgba(255,255,255,0.9);
      padding: 1rem 2rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    header h1 {
      font-size: 1.75rem;
      margin: 0;
      color: #333;
    }
    .btn-header {
      margin-left: .5rem;
    }

    main {
      flex: 1;
      display: flex;
      align-items: center;      /* centrar vertical */
      justify-content: center;  /* centrar horizontal */
      padding: 2rem;
    
    }
    .dashboard-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 2rem;
      max-width: 800px;
      width: 100%;
      justify-content: space-between;
    }
    .dashboard-item {
      flex: 1 1 calc(50% - 1rem);
      max-width: calc(50% - 1rem);
    }

    .card-dashboard {
      height: 220px;
      border: none;
      border-radius: 1.25rem;
      display: flex;
      align-items: center;       /* centrar texto vertical */
      justify-content: center;   /* centrar texto horizontal */
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: transform .2s, box-shadow .2s;
      position: relative;
      overflow: hidden;
    }
    .card-dashboard:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    .card-title {
      font-size: 2rem;   /* más grande */
      color: #333;
      margin: 0;
      z-index: 2;
    }

    /* fondos armonizados */
    .bg-clientes   { background: var(--rosa);    }
    .bg-puntos     { background: var(--menta);   }
    .bg-premios    { background: var(--cielo);   }
    .bg-beneficios { background: var(--durazno); }
  </style>
</head>
<body>
  <?php
    session_start();
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
      header('Location: login.php');
      exit;
    }
  ?>
  <header>
    <h1>Dashboard Administrador</h1>
    <div>
      <a href="logout.php" class="btn btn-danger btn-header">Cerrar Sesión</a>
    </div>
  </header>

  <main>
    <div class="dashboard-grid">
      <div class="dashboard-item">
        <a href="admin_clientes.php" class="text-decoration-none">
          <div class="card card-dashboard bg-clientes">
            <h5 class="card-title">Clientes</h5>
          </div>
        </a>
      </div>
      <div class="dashboard-item">
        <a href="admin_puntos.php" class="text-decoration-none">
          <div class="card card-dashboard bg-puntos">
            <h5 class="card-title">Puntos</h5>
          </div>
        </a>
      </div>
      <div class="dashboard-item">
        <a href="admin_premios.php" class="text-decoration-none">
          <div class="card card-dashboard bg-premios">
            <h5 class="card-title">Premios</h5>
          </div>
        </a>
      </div>
      <div class="dashboard-item">
        <a href="admin_beneficios.php" class="text-decoration-none">
          <div class="card card-dashboard bg-beneficios">
            <h5 class="card-title">Beneficios</h5>
          </div>
        </a>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
