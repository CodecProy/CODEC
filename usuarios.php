<?php
// Configuración de la base de datos
$servername = "sql302.infinityfree.com";
$username = "if0_39478618";
$password = "C3rrEiwVe81HS1";
$dbname = "if0_39478618_altaltium";
// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['agregar'])) {
        // Agregar nuevo usuario
        $usuario = $_POST['nuevo_usuario'];
        $contrasena = $_POST['nueva_contrasena']; // No se encripta
        $estatus = isset($_POST['nuevo_super']) ? 'super' : 'normal';
        
        // Obtener el próximo ID
        $sql = "SELECT MAX(id_usuario) as max_id FROM usuarios_codec";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $nuevo_id = $row['max_id'] + 1;
        
        $stmt = $conn->prepare("INSERT INTO usuarios_codec (id_usuario, usuario, contrasena, estatus) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $nuevo_id, $usuario, $contrasena, $estatus);
        $stmt->execute();
    } elseif (isset($_POST['actualizar'])) {
        // Actualizar usuario existente
        $id = $_POST['id'];
        $usuario = $_POST['usuario'];
        $estatus = isset($_POST['super']) ? 'super' : 'normal';
        
        $stmt = $conn->prepare("UPDATE usuarios_codec SET usuario = ?, estatus = ? WHERE id_usuario = ?");
        $stmt->bind_param("ssi", $usuario, $estatus, $id);
        $stmt->execute();
        
        // Actualizar contraseña si se proporcionó una nueva
        if (!empty($_POST['contrasena'])) {
            $contrasena = $_POST['contrasena']; // No se encripta
            $stmt = $conn->prepare("UPDATE usuarios_codec SET contrasena = ? WHERE id_usuario = ?");
            $stmt->bind_param("si", $contrasena, $id);
            $stmt->execute();
        }
    } elseif (isset($_POST['eliminar'])) {
        // Eliminar usuario
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM usuarios_codec WHERE id_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

// Obtener todos los usuarios
$sql = "SELECT id_usuario, usuario, contrasena, estatus FROM usuarios_codec";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Usuarios</title>
  <style>
    :root {
      --admin-dark: #001a1a;
      --admin-primary: #006666;
      --admin-light: #009999;
      --admin-accent: #3d8b8b;
      --admin-bg: #0a1515;
      --primary-dark: #003035;
      --primary: #008a8a;
      --primary-light: #00cccc;
      --accent: #52b3c0;
      --dark-bg: #0d1d1f;
      --darker-bg: #000000;
      --light-bg: #fefcef;
      --white: #ffffff;
      --gray-light: #bfc6c7;
      --gray: #949797;
    }

    /* Navbar de administrador */
    .admin-navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 40px;
      background: linear-gradient(135deg, 
        var(--admin-dark) 0%, 
        var(--admin-bg) 100%);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
      border-bottom: 2px solid var(--admin-accent);
      position: relative;
      z-index: 1000;
    }

    .admin-navbar::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, 
        transparent 0%, 
        var(--admin-light) 50%, 
        transparent 100%);
    }

    .admin-logo {
      width: 150px;
      height: auto;
      background-color: var(--white);
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }

    .admin-logo:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 153, 153, 0.3);
      border-color: var(--admin-light);
    }

    .admin-nav-menu {
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
    }

    .admin-nav-button {
      background-color: var(--admin-primary);
      color: var(--white);
      border: none;
      padding: 10px 22px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      white-space: nowrap;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      letter-spacing: 0.5px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .admin-nav-button:hover {
      background-color: var(--admin-light);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 153, 153, 0.3);
    }

    .admin-nav-button.active {
      background-color: var(--admin-accent);
      box-shadow: 0 4px 15px rgba(61, 139, 139, 0.4);
    }

    .admin-nav-button.logout {
      background: linear-gradient(135deg, #8b0000, #a52a2a);
      margin-left: 10px;
    }

    .admin-nav-button.logout:hover {
      background: linear-gradient(135deg, #a52a2a, #c04040);
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-image: url('img/fondo_admin2.png');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      color: #ffffff;
      position: relative;
      min-height: 100vh;
    }

    .banner-container {
      width: 100%;
      padding: 0;
      margin: 0 auto;
      max-width: 1400px;
    }

    .banner {
      width: 100%;
      max-height: 180px;
      object-fit: cover;
      display: block;
      margin: 0 auto;
      border-radius: 0 0 10px 10px;
    }

    .main-container {
      max-width: 1400px;
      margin: 20px auto;
      padding: 0 20px;
    }

    .content-card {
      background-color: rgba(13, 29, 31, 0.92);
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 0 25px rgba(0, 0, 0, 0.7);
      margin-top: 20px;
    }

    .section-title {
      font-size: 28px;
      color: var(--primary-light);
      margin: 0 0 30px 0;
      text-align: center;
      position: relative;
      padding-bottom: 15px;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 3px;
      background-color: var(--accent);
    }

    /* Estilos para el formulario de agregar usuario */
    .add-user-form {
      background-color: rgba(0, 138, 138, 0.1);
      border-left: 4px solid var(--accent);
      padding: 25px;
      border-radius: 0 5px 5px 0;
      margin-bottom: 30px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--white);
    }

    .form-control {
      width: 100%;
      background-color: rgba(0, 0, 0, 0.4);
      border: 1px solid var(--accent);
      border-radius: 4px;
      padding: 10px;
      color: var(--white);
      font-size: 16px;
    }

    .form-control:focus {
      outline: none;
      box-shadow: 0 0 0 2px rgba(82, 179, 192, 0.3);
    }

    .switch-container {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 34px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 34px;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 26px;
      width: 26px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }

    input:checked + .slider {
      background-color: var(--accent);
    }

    input:checked + .slider:before {
      transform: translateX(26px);
    }

    .btn {
      background-color: var(--primary);
      color: var(--white);
      border: none;
      padding: 10px 25px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .btn:hover {
      background-color: var(--primary-light);
    }

    .btn-primary {
      background-color: var(--accent);
    }

    .btn-primary:hover {
      background-color: #3d9ca8;
    }

    .btn-danger {
      background-color: #d9534f;
    }

    .btn-danger:hover {
      background-color: #c9302c;
    }

    /* Estilos para la tabla de usuarios */
    .users-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
    }

    .users-table th {
      background-color: rgba(0, 138, 138, 0.3);
      color: var(--white);
      padding: 15px;
      text-align: left;
      font-weight: 600;
      border-bottom: 2px solid var(--accent);
    }

    .users-table td {
      padding: 15px;
      border-bottom: 1px solid rgba(82, 179, 192, 0.2);
      vertical-align: middle;
    }

    .users-table tr:last-child td {
      border-bottom: none;
    }

    .users-table tr:hover {
      background-color: rgba(0, 138, 138, 0.1);
    }

    .action-buttons {
      display: flex;
      gap: 10px;
    }

    .no-users {
      text-align: center;
      padding: 30px;
      color: var(--gray-light);
      font-style: italic;
    }

    @media (max-width: 1440px) {
      .banner-container, .main-container {
        max-width: 95%;
      }
    }

    @media (max-width: 768px) {
      .admin-navbar {
        flex-wrap: wrap;
        gap: 15px;
        padding: 15px 20px;
      }
      
      .main-container {
        padding: 0 10px;
      }
      
      .content-card {
        padding: 20px;
      }
      
      .users-table {
        display: block;
        overflow-x: auto;
      }

      .action-buttons {
        flex-direction: column;
      }

      .banner {
        max-height: 120px;
        border-radius: 0;
      }
    }

    @media (max-width: 480px) {
      .admin-nav-menu {
        flex-wrap: wrap;
        justify-content: center;
      }
      
      .btn {
        width: 100%;
      }
      
      .banner {
        max-height: 100px;
      }
      
      .admin-nav-button {
        padding: 8px 16px;
        font-size: 13px;
      }
    }
  </style>
</head>
<body>
  
  <div class="admin-navbar">
    <img src="img/logo.png" alt="Logo" class="admin-logo">
    
    <div class="admin-nav-menu">
      <a href="inicio.html" class="admin-nav-button">INICIO</a>
      <a href="config.html" class="admin-nav-button">PRECIOS</a>
      <a href="ajustes.html" class="admin-nav-button">VALORES</a>
      <a href="menu.html" class="admin-nav-button">MENÚ</a>
      <a href="index.html" class="admin-nav-button logout">CERRAR SESIÓN</a>
    </div>
  </div>

  <!-- Banner -->
  <div class="banner-container">
    <img src="img/banner.jpg" alt="Banner" class="banner">
  </div>

  <div class="main-container">
    <div class="content-card">
      <h1 class="section-title">GESTIÓN DE USUARIOS</h1>
      
      <!-- Formulario para agregar nuevo usuario -->
      <div class="add-user-form">
        <h2 style="color: var(--accent); margin-top: 0; margin-bottom: 20px;">Agregar Nuevo Usuario</h2>
        <form method="post">
          <div class="form-group">
            <label for="nuevo_usuario">Nombre de Usuario:</label>
            <input type="text" id="nuevo_usuario" name="nuevo_usuario" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="nueva_contrasena">Contraseña:</label>
            <input type="text" id="nueva_contrasena" name="nueva_contrasena" class="form-control" required>
          </div>
          <div class="form-group">
            <div class="switch-container">
              <label for="nuevo_super" style="margin-bottom: 0;">Super Usuario:</label>
              <label class="switch">
                <input type="checkbox" name="nuevo_super">
                <span class="slider"></span>
              </label>
            </div>
          </div>
          <button type="submit" name="agregar" class="btn btn-primary">Agregar Usuario</button>
        </form>
      </div>
      
      <!-- Tabla de usuarios existentes -->
      <h2 style="color: var(--accent);">Usuarios Registrados</h2>
      <table class="users-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Contraseña</th>
            <th>Estatus</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                  echo "<tr>";
                  echo "<form method='post'>";
                  echo "<input type='hidden' name='id' value='" . $row["id_usuario"] . "'>";
                  echo "<td>" . $row["id_usuario"] . "</td>";
                  echo "<td><input type='text' name='usuario' value='" . htmlspecialchars($row["usuario"]) . "' class='form-control' required></td>";
                  echo "<td><input type='text' name='contrasena' value='" . htmlspecialchars($row["contrasena"]) . "' class='form-control'></td>";
                  
                  // Switch para super usuario
                  echo "<td>";
                  echo "<div class='switch-container'>";
                  $checked = ($row["estatus"] == 'super') ? 'checked' : '';
                  echo "<label class='switch'>";
                  echo "<input type='checkbox' name='super' " . $checked . ">";
                  echo "<span class='slider'></span>";
                  echo "</label>";
                  echo "<span>" . ($row["estatus"] == 'super' ? 'Super' : 'Normal') . "</span>";
                  echo "</div>";
                  echo "</td>";
                  
                  // Botones de acción
                  echo "<td class='action-buttons'>";
                  echo "<button type='submit' name='actualizar' class='btn'>Actualizar</button>";
                  echo "<button type='submit' name='eliminar' class='btn btn-danger'>Eliminar</button>";
                  echo "</td>";
                  echo "</form>";
                  echo "</tr>";
              }
          } else {
              echo "<tr><td colspan='5' class='no-users'>No hay usuarios registrados</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>

<?php
$conn->close();
?>