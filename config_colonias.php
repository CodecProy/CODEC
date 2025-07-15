<?php
// ======================================================================
// CONFIGURACIÓN DE CONEXIÓN A LA BASE DE DATOS
// ======================================================================
$servername = "sql302.infinityfree.com";
$username = "if0_39478618";
$password = "C3rrEiwVe81HS1";
$dbname = "if0_39478618_altaltium";
// Desactivar visualización de errores para evitar interferencia con JSON
error_reporting(0);
ini_set('display_errors', 0);

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Manejar búsqueda de colonias
if (isset($_GET['action']) && $_GET['action'] == 'buscarColonias') {
    $query = isset($_GET['q']) ? $_GET['q'] : '';
    $parts = explode(',', $query);
    $coloniaQuery = trim($parts[0] ?? '');
    $alcaldiaQuery = trim($parts[1] ?? '');

    $sql = "SELECT c.id_colonia, c.colonia_nombre, a.alcaldia_nombre, e.estado_nombre, c.colonia_cp, 
                   c.colonia_promedio, c.colonia_prom_prop, c.colonia_prom_hey, c.colonia_prom_clau, 
                   c.colonia_prom_mud, c.colonia_prom_altal
            FROM colonia_codec c
            JOIN alcaldia_codec a ON c.colonia_alcaldia = a.id_alcaldia
            JOIN estado_codec e ON a.alcaldia_estado = e.id_estado
            WHERE c.colonia_nombre LIKE CONCAT('%', :coloniaQuery, '%')";
    
    $params = [':coloniaQuery' => $coloniaQuery];
    
    if (!empty($alcaldiaQuery)) {
        $sql .= " AND a.alcaldia_nombre LIKE CONCAT('%', :alcaldiaQuery, '%')";
        $params[':alcaldiaQuery'] = $alcaldiaQuery;
    }
    
    $sql .= " ORDER BY c.colonia_nombre ASC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Manejar obtención de colonia por ID
if (isset($_GET['action']) && $_GET['action'] == 'obtenerColonia') {
    $coloniaId = isset($_GET['coloniaId']) ? $_GET['coloniaId'] : '';
    
    $sql = "SELECT c.id_colonia, c.colonia_nombre, a.alcaldia_nombre, e.estado_nombre, c.colonia_cp, 
                   c.colonia_promedio, c.colonia_prom_prop, c.colonia_prom_hey, c.colonia_prom_clau, 
                   c.colonia_prom_mud, c.colonia_prom_altal
            FROM colonia_codec c
            JOIN alcaldia_codec a ON c.colonia_alcaldia = a.id_alcaldia
            JOIN estado_codec e ON a.alcaldia_estado = e.id_estado
            WHERE c.id_colonia = :coloniaId";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':coloniaId', $coloniaId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($result ? $result : []);
    exit;
}

// Manejar obtención de amenidades
if (isset($_GET['action']) && $_GET['action'] == 'obtenerAmenidades') {
    $coloniaId = isset($_GET['coloniaId']) ? $_GET['coloniaId'] : '';
    
    if (empty($coloniaId)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Se requiere ID de colonia']);
        exit;
    }

    // Consulta corregida usando colonia_id
    $sql = "SELECT * FROM amenidades_codec WHERE colonia_id = :coloniaId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':coloniaId', $coloniaId);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode([]);
    }
    exit;
}

// Manejar actualización de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_colonia'])) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $coloniaId = $_POST['colonia_id'];
        
        if (empty($coloniaId)) {
            throw new Exception('ID de colonia requerido');
        }

        // Función para limpiar valores numéricos
        $cleanNumber = function($value) {
            if ($value === '' || $value === null) return null;
            $cleaned = preg_replace('/[^0-9.]/', '', $value);
            return $cleaned === '' ? null : floatval($cleaned);
        };
        
        // Actualizar precios
        $updatePrecios = "UPDATE colonia_codec SET 
            colonia_promedio = :promedio,
            colonia_prom_prop = :prop,
            colonia_prom_hey = :hey,
            colonia_prom_clau = :clau,
            colonia_prom_mud = :mud,
            colonia_prom_altal = :altal
            WHERE id_colonia = :id";
        
        $stmt = $conn->prepare($updatePrecios);
        $stmt->execute([
            ':promedio' => $cleanNumber($_POST['promedio']),
            ':prop' => $cleanNumber($_POST['propiedades']),
            ':hey' => $cleanNumber($_POST['heyhome']),
            ':clau' => $cleanNumber($_POST['clau']),
            ':mud' => $cleanNumber($_POST['mudafy']),
            ':altal' => $cleanNumber($_POST['altaltium']),
            ':id' => $coloniaId
        ]);
        
        // Actualizar amenidades
        $updateAmenidades = "INSERT INTO amenidades_codec (
            colonia_id,
            amenidad_vialidad,
            amenidad_escuelas,
            amenidad_hospitales,
            amenidad_comercio,
            amenidad_otros,
            categoria
        ) VALUES (
            :id,
            :vialidad,
            :escuelas,
            :hospitales,
            :comercio,
            :otros,
            :categoria
        ) ON DUPLICATE KEY UPDATE
            amenidad_vialidad = VALUES(amenidad_vialidad),
            amenidad_escuelas = VALUES(amenidad_escuelas),
            amenidad_hospitales = VALUES(amenidad_hospitales),
            amenidad_comercio = VALUES(amenidad_comercio),
            amenidad_otros = VALUES(amenidad_otros),
            categoria = VALUES(categoria)";
        
        $stmt = $conn->prepare($updateAmenidades);
        $stmt->execute([
            ':vialidad' => $_POST['vialidad'] ?? '',
            ':escuelas' => $_POST['escuelas'] ?? '',
            ':hospitales' => $_POST['hospitales'] ?? '',
            ':comercio' => $_POST['comercio'] ?? '',
            ':otros' => $_POST['otros'] ?? '',
            ':categoria' => $_POST['categoria'] ?? 'Tibia',
            ':id' => $coloniaId
        ]);
        
        $response['success'] = true;
        $response['message'] = 'Datos actualizados correctamente';
        $response['coloniaId'] = $coloniaId;
    } catch (Exception $e) {
        $response['message'] = 'Error al guardar los cambios: ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejar obtención de estados
if (isset($_GET['action']) && $_GET['action'] == 'obtenerEstados') {
    $sql = "SELECT id_estado, estado_nombre FROM estado_codec ORDER BY estado_nombre ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($estados);
    exit;
}

// Manejar obtención de alcaldías
if (isset($_GET['action']) && $_GET['action'] == 'obtenerAlcaldias') {
    $sql = "SELECT id_alcaldia, alcaldia_nombre FROM alcaldia_codec ORDER BY alcaldia_nombre ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $alcaldias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($alcaldias);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>C.O.D.E.C. - Configuración de Colonias</title>
  <style>
    :root {
      --admin-dark: #001a1a;
      --admin-primary: #006666;
      --admin-light: #009999;
      --admin-accent: #3d8b8b;
      --admin-bg: #0a1515;
      --white: #ffffff;
      --gray-light: #bfc6c7;
      --gray: #949797;
      --border-radius: 8px;
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.15);
      --shadow-md: 0 8px 25px rgba(0, 0, 0, 0.3);
      --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-image: url('img/fondo_admin2.png');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: var(--white);
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
      z-index: 0;
      line-height: 1.5;
    }

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
      box-shadow: 0 4px 12px rgba(0, 153, 153, 0.3);
    }

    .admin-nav-button.logout {
      background: linear-gradient(135deg, #8b0000, #a52a2a);
      margin-left: 10px;
    }

    .admin-nav-button.logout:hover {
      background: linear-gradient(135deg, #a52a2a, #c04040);
    }

    .main-container {
      max-width: 1400px;
      margin: 2rem auto;
      padding: 0 1.5rem;
      position: relative;
      z-index: 2;
      background-color: rgba(10, 21, 21, 0.95);
      backdrop-filter: blur(8px);
      border-radius: 12px;
    }

    .search-section {
      padding: 1.5rem;
      background-color: rgba(10, 21, 21, 0.95);
      border-bottom: 1px solid rgba(61, 139, 139, 0.3);
      position: relative;
      z-index: 106;
      backdrop-filter: blur(8px);
    }

    .search-wrapper {
      position: relative;
      z-index: 1001;
    }

    .search-container {
      max-width: 800px;
      margin: 0 auto;
      position: relative;
    }

    .search-bar {
      position: relative;
      display: flex;
      align-items: center;
      background-color: rgba(0, 26, 26, 0.8);
      border: 1px solid rgba(61, 139, 139, 0.4);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
    }

    .search-bar:focus-within {
      border-color: var(--admin-light);
      box-shadow: 0 0 0 3px rgba(0, 153, 153, 0.3);
    }

    .search-icon {
      padding: 0 1rem;
      color: var(--gray-light);
      font-size: 1.25rem;
    }

    .search-input {
      flex: 1;
      padding: 0.875rem 1rem;
      background: transparent;
      border: none;
      color: var(--white);
      font-size: 1rem;
      outline: none;
    }

    .search-input::placeholder {
      color: var(--gray-light);
      opacity: 0.7;
    }

    .suggestions-dropdown {
      position: absolute;
      top: calc(100% + 0.25rem);
      left: 0;
      right: 0;
      background-color: rgba(10, 21, 21, 0.95);
      border: 1px solid rgba(61, 139, 139, 0.4);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      max-height: 300px;
      overflow-y: auto;
      z-index: 10002;
      display: none;
    }

    .suggestion-item {
      padding: 0.75rem 1rem;
      cursor: pointer;
      color: var(--gray-light);
      font-size: 0.875rem;
      border-bottom: 1px solid rgba(61, 139, 139, 0.2);
      transition: var(--transition);
    }

    .suggestion-item:last-child {
      border-bottom: none;
    }

    .suggestion-item:hover {
      background-color: rgba(0, 153, 153, 0.15);
      color: var(--admin-light);
    }

    .suggestion-text {
      font-weight: 600;
      font-size: 1rem;
    }

    .suggestion-subtext {
      font-size: 0.8rem;
      color: var(--gray);
    }

    .suggestions-dropdown::-webkit-scrollbar {
      width: 6px;
    }

    .suggestions-dropdown::-webkit-scrollbar-track {
      background: rgba(0, 26, 26, 0.5);
      border-radius: 10px;
    }

    .suggestions-dropdown::-webkit-scrollbar-thumb {
      background-color: var(--admin-primary);
      border-radius: 10px;
    }

    .suggestions-dropdown::-webkit-scrollbar-thumb:hover {
      background-color: var(--admin-light);
    }

    .colonias-container {
      display: none;
      padding: 2rem;
    }

    .location-header {
      margin-bottom: 2rem;
      text-align: center;
      background: linear-gradient(135deg, rgba(0, 153, 153, 0.2) 0%, rgba(0, 153, 153, 0.1) 100%);
      padding: 1.5rem;
      border-radius: 12px;
      border-left: 4px solid var(--admin-light);
      position: relative;
    }

    .location-title {
      font-size: 2.25rem;
      font-weight: 700;
      margin: 0 0 0.5rem 0;
      color: var(--admin-light);
      line-height: 1.2;
    }

    .location-subtitle {
      font-size: 1.375rem;
      color: var(--gray-light);
      margin: 0 0 0.375rem 0;
      font-weight: 500;
    }

    .location-state {
      font-size: 1.125rem;
      color: var(--gray-light);
      margin: 0;
    }

    .tables-container {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2.5rem;
      margin-top: 2rem;
    }

    @media (min-width: 1024px) {
      .tables-container {
        grid-template-columns: 1fr 1fr;
      }
    }

    .table-section {
      background: rgba(10, 21, 21, 0.85);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--admin-accent);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid rgba(61, 139, 139, 0.3);
    }

    .section-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--admin-light);
      margin: 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }

    th, td {
      padding: 0.875rem 1rem;
      text-align: left;
      border-bottom: 1px solid rgba(61, 139, 139, 0.2);
    }

    th {
      color: var(--admin-light);
      font-weight: 600;
      font-size: 1rem;
    }

    td {
      color: var(--gray-light);
      font-size: 0.9375rem;
    }

    tr:last-child td {
      border-bottom: none;
    }

    .input-group {
      margin-bottom: 1rem;
    }

    .input-label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--admin-light);
      font-weight: 600;
    }

    .input-field {
      width: 100%;
      padding: 0.75rem;
      border-radius: var(--border-radius);
      border: 1px solid rgba(61, 139, 139, 0.4);
      background-color: rgba(0, 26, 26, 0.8);
      color: var(--white);
      font-size: 0.9375rem;
    }

    .input-field:focus {
      outline: none;
      border-color: var(--admin-light);
      box-shadow: 0 0 0 3px rgba(0, 153, 153, 0.3);
    }

    .edit-button-container {
      display: flex;
      justify-content: center;
      margin-top: 1.5rem;
    }
    
    .edit-button {
      background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-dark) 100%);
      color: var(--white);
      border: none;
      padding: 0.75rem 1.75rem;
      border-radius: var(--border-radius);
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: var(--transition);
      width: 100%;
      max-width: 200px;
    }
    
    .edit-button:hover {
      background: linear-gradient(135deg, var(--admin-light) 0%, var(--admin-primary) 100%);
      transform: translateY(-2px);
      box-shadow: var(--shadow-sm);
    }

    .update-form {
      display: none;
      background: rgba(0, 26, 26, 0.5);
      padding: 1.5rem;
      border-radius: 12px;
      margin-top: 1.5rem;
      border: 1px solid var(--admin-light);
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
    }

    @media (min-width: 1024px) {
      .form-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    .form-section {
      background: rgba(10, 21, 21, 0.85);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--admin-accent);
    }

    .form-section-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--admin-light);
      margin: 0 0 1.5rem 0;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid rgba(61, 139, 139, 0.3);
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      margin-top: 1.5rem;
    }

    .save-button {
      background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-dark) 100%);
      color: var(--white);
      border: none;
      padding: 0.75rem 1.75rem;
      border-radius: var(--border-radius);
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: var(--transition);
    }

    .save-button:hover {
      background: linear-gradient(135deg, var(--admin-light) 0%, var(--admin-primary) 100%);
      transform: translateY(-2px);
      box-shadow: var(--shadow-sm);
    }

    .cancel-button {
      background: transparent;
      color: var(--gray-light);
      border: 1px solid var(--gray-light);
      padding: 0.75rem 1.75rem;
      border-radius: var(--border-radius);
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: var(--transition);
    }

    .cancel-button:hover {
      background: rgba(255, 255, 255, 0.1);
      color: var(--admin-light);
      border-color: var(--admin-light);
    }

    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 1rem 1.5rem;
      border-radius: var(--border-radius);
      background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-dark) 100%);
      color: var(--white);
      font-weight: 600;
      box-shadow: var(--shadow-md);
      z-index: 2000;
      transform: translateX(120%);
      transition: transform 0.3s ease;
    }

    .notification.show {
      transform: translateX(0);
    }

    .notification.error {
      background: linear-gradient(135deg, #d32f2f, #f44336);
    }

    .loading {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }

    .loading-spinner {
      border: 4px solid rgba(255, 255, 255, 0.2);
      border-top: 4px solid var(--admin-light);
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
      .admin-navbar {
        padding: 1rem;
        flex-wrap: wrap;
        gap: 1rem;
      }
      
      .admin-nav-menu {
        flex-wrap: wrap;
        justify-content: center;
      }
      
      .main-container {
        padding: 1rem;
      }
      
      .tables-container {
        grid-template-columns: 1fr;
      }
      
      .location-title {
        font-size: 1.75rem;
      }
      
      .location-subtitle {
        font-size: 1.125rem;
      }
    }
  </style>
</head>
<body>
  <div class="admin-navbar">
    <img src="img/logo.png" alt="Logo C.O.D.E.C." class="admin-logo">
    <div class="admin-nav-menu">
      <a href="inicio.html" class="admin-nav-button">INICIO</a>
      <a href="usuarios.php" class="admin-nav-button">USUARIOS</a>
      <a href="ajustes.html" class="admin-nav-button">VALORES</a>
      <a href="menu.html" class="admin-nav-button">MENÚ</a>
      <a href="index.html" class="admin-nav-button logout">Cerrar sesión</a>
    </div>
  </div>

  <main class="main-container">
    <div class="search-section">
      <div class="search-wrapper">
        <div class="search-container">
          <label class="input-label" for="searchInput">Buscar colonia y alcaldía (ej: Polanco, Miguel Hidalgo)</label>
          <div class="search-bar">
            <span class="search-icon" aria-hidden="true">🔍</span>
            <input type="text" class="search-input" id="searchInput" placeholder="Buscar colonia, alcaldía (ej: Polanco, Miguel Hidalgo)" aria-label="Buscar colonia y alcaldía">
            <div class="suggestions-dropdown" id="suggestionsDropdown"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="colonias-container" id="coloniasContainer">
      <div class="location-header">
        <h1 class="location-title" id="coloniaTitle">COLONIA</h1>
        <p class="location-subtitle" id="alcaldiaCp">ALCALDÍA, CP</p>
        <p class="location-state" id="estado">ESTADO</p>
        
        <div class="edit-button-container">
          <button class="edit-button" id="editAllBtn">Modificar</button>
        </div>
      </div>

      <div class="tables-container">
        <!-- Tabla de Precios -->
        <div class="table-section">
          <div class="section-header">
            <h2 class="section-title">Precios por m²</h2>
          </div>
          
          <table id="preciosTable">
            <thead>
              <tr>
                <th>Fuente</th>
                <th>Precio</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Promedio</td>
                <td id="precioPromedio">$--</td>
              </tr>
              <tr>
                <td>Propiedades.com</td>
                <td id="precioPropiedades">$--</td>
              </tr>
              <tr>
                <td>Hey Home</td>
                <td id="precioHeyhome">$--</td>
              </tr>
              <tr>
                <td>Clau</td>
                <td id="precioClau">$--</td>
              </tr>
              <tr>
                <td>Mudafy</td>
                <td id="precioMudafy">$--</td>
              </tr>
              <tr>
                <td>Altaltium</td>
                <td id="precioAltaltium">$--</td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Tabla de Amenidades -->
        <div class="table-section">
          <div class="section-header">
            <h2 class="section-title">Amenidades</h2>
          </div>
          
          <table id="amenidadesTable">
            <thead>
              <tr>
                <th>Tipo</th>
                <th>Valor</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Vialidad</td>
                <td id="vialidadValue">--</td>
              </tr>
              <tr>
                <td>Escuelas</td>
                <td id="escuelasValue">--</td>
              </tr>
              <tr>
                <td>Hospitales</td>
                <td id="hospitalesValue">--</td>
              </tr>
              <tr>
                <td>Comercio</td>
                <td id="comercioValue">--</td>
              </tr>
              <tr>
                <td>Otros</td>
                <td id="otrosValue">--</td>
              </tr>
              <tr>
                <td>Categoría</td>
                <td id="categoriaValue">--</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Formulario de edición unificado -->
      <div class="update-form" id="fullEditForm">
        <form id="updateForm">
          <input type="hidden" name="colonia_id" id="coloniaIdInput">
          <input type="hidden" name="update_colonia" value="1">
          
          <div class="form-grid">
            <!-- Sección de Precios -->
            <div class="form-section">
              <h3 class="form-section-title">Precios por m²</h3>
              
              <div class="input-group">
                <label class="input-label" for="promedioInput">Precio Promedio</label>
                <input type="text" class="input-field" id="promedioInput" name="promedio" placeholder="Ingrese precio promedio" data-type="number">
              </div>
              
              <div class="input-group">
                <label class="input-label" for="propiedadesInput">Propiedades.com</label>
                <input type="text" class="input-field" id="propiedadesInput" name="propiedades" placeholder="Ingrese precio" data-type="number">
              </div>
              
              <div class="input-group">
                <label class="input-label" for="heyhomeInput">Hey Home</label>
                <input type="text" class="input-field" id="heyhomeInput" name="heyhome" placeholder="Ingrese precio" data-type="number">
              </div>
              
              <div class="input-group">
                <label class="input-label" for="clauInput">Clau</label>
                <input type="text" class="input-field" id="clauInput" name="clau" placeholder="Ingrese precio" data-type="number">
              </div>
              
              <div class="input-group">
                <label class="input-label" for="mudafyInput">Mudafy</label>
                <input type="text" class="input-field" id="mudafyInput" name="mudafy" placeholder="Ingrese precio" data-type="number">
              </div>
              
              <div class="input-group">
                <label class="input-label" for="altaltiumInput">Altaltium</label>
                <input type="text" class="input-field" id="altaltiumInput" name="altaltium" placeholder="Ingrese precio" data-type="number">
              </div>
            </div>
            
            <!-- Sección de Amenidades -->
            <div class="form-section">
              <h3 class="form-section-title">Amenidades</h3>
              
              <div class="input-group">
                <label class="input-label" for="vialidadInput">Vialidad</label>
                <input type="text" class="input-field" id="vialidadInput" name="vialidad" placeholder="Ingrese vialidades">
              </div>
              
              <div class="input-group">
                <label class="input-label" for="escuelasInput">Escuelas</label>
                <input type="text" class="input-field" id="escuelasInput" name="escuelas" placeholder="Ingrese escuelas">
              </div>
              
              <div class="input-group">
                <label class="input-label" for="hospitalesInput">Hospitales</label>
                <input type="text" class="input-field" id="hospitalesInput" name="hospitales" placeholder="Ingrese hospitales">
              </div>
              
              <div class="input-group">
                <label class="input-label" for="comercioInput">Comercio</label>
                <input type="text" class="input-field" id="comercioInput" name="comercio" placeholder="Ingrese comercios">
              </div>
              
              <div class="input-group">
                <label class="input-label" for="otrosInput">Otros</label>
                <input type="text" class="input-field" id="otrosInput" name="otros" placeholder="Ingrese otros">
              </div>
              
              <div class="input-group">
                <label class="input-label" for="categoriaInput">Categoría</label>
                <select class="input-field" id="categoriaInput" name="categoria">
                  <option value="Tibia">Tibia</option>
                  <option value="Calientes">Calientes</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="button" class="cancel-button" id="cancelAllBtn">Cancelar</button>
            <button type="submit" class="save-button" id="saveAllBtn">Guardar Cambios</button>
          </div>
        </form>
      </div>
    </div>
    
    <div class="loading" id="loadingSection" style="display: none;">
      <div class="loading-spinner"></div>
    </div>
    
  <script>
    const refs = {
      searchInput: document.getElementById('searchInput'),
      suggestionsDropdown: document.getElementById('suggestionsDropdown'),
      coloniasContainer: document.getElementById('coloniasContainer'),
      coloniaTitle: document.getElementById('coloniaTitle'),
      alcaldiaCp: document.getElementById('alcaldiaCp'),
      estado: document.getElementById('estado'),
      loadingSection: document.getElementById('loadingSection'),
      
      // Elementos de visualización
      precioPromedio: document.getElementById('precioPromedio'),
      precioPropiedades: document.getElementById('precioPropiedades'),
      precioHeyhome: document.getElementById('precioHeyhome'),
      precioClau: document.getElementById('precioClau'),
      precioMudafy: document.getElementById('precioMudafy'),
      precioAltaltium: document.getElementById('precioAltaltium'),
      vialidadValue: document.getElementById('vialidadValue'),
      escuelasValue: document.getElementById('escuelasValue'),
      hospitalesValue: document.getElementById('hospitalesValue'),
      comercioValue: document.getElementById('comercioValue'),
      otrosValue: document.getElementById('otrosValue'),
      categoriaValue: document.getElementById('categoriaValue'),
      
      // Elementos de formulario
      coloniaIdInput: document.getElementById('coloniaIdInput'),
      promedioInput: document.getElementById('promedioInput'),
      propiedadesInput: document.getElementById('propiedadesInput'),
      heyhomeInput: document.getElementById('heyhomeInput'),
      clauInput: document.getElementById('clauInput'),
      mudafyInput: document.getElementById('mudafyInput'),
      altaltiumInput: document.getElementById('altaltiumInput'),
      vialidadInput: document.getElementById('vialidadInput'),
      escuelasInput: document.getElementById('escuelasInput'),
      hospitalesInput: document.getElementById('hospitalesInput'),
      comercioInput: document.getElementById('comercioInput'),
      otrosInput: document.getElementById('otrosInput'),
      categoriaInput: document.getElementById('categoriaInput'),
      
      // Formulario unificado
      fullEditForm: document.getElementById('fullEditForm'),
      editAllBtn: document.getElementById('editAllBtn'),
      cancelAllBtn: document.getElementById('cancelAllBtn'),
      updateForm: document.getElementById('updateForm')
    };

    const notification = document.createElement('div');
    notification.id = 'notification';
    notification.className = 'notification';
    notification.innerHTML = '<span id="notificationMessage"></span>';
    document.body.appendChild(notification);
    refs.notification = notification;
    refs.notificationMessage = document.getElementById('notificationMessage');

    let selectedColonia = null;

    function formatNumber(number) {
      if (number === null || number === '' || isNaN(number)) return '--';
      return new Intl.NumberFormat('es-MX', { 
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(number);
    }

    function formatNumberInput(input) {
      let value = input.value.replace(/,/g, '');
      value = value.replace(/[^\d.]/g, '');
      const decimalCount = (value.match(/\./g) || []).length;
      if (decimalCount > 1) {
        value = value.substring(0, value.lastIndexOf('.'));
      }
      if (value) {
        const parts = value.split('.');
        let integerPart = parts[0];
        const decimalPart = parts.length > 1 ? `.${parts[1]}` : '';
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        input.value = integerPart + decimalPart;
      }
    }

    function parseFormattedNumber(str) {
      if (!str) return null;
      return parseFloat(str.replace(/,/g, ''));
    }

    async function fetchColonias(query) {
      try {
        const response = await fetch(`?action=buscarColonias&q=${encodeURIComponent(query)}`);
        if (!response.ok) throw new Error('Error en la búsqueda');
        const data = await response.json();
        return data.map(item => ({
          ...item,
          colonia_promedio: parseFloat(item.colonia_promedio) || 0,
          colonia_prom_prop: parseFloat(item.colonia_prom_prop) || 0,
          colonia_prom_hey: parseFloat(item.colonia_prom_hey) || 0,
          colonia_prom_clau: parseFloat(item.colonia_prom_clau) || 0,
          colonia_prom_mud: parseFloat(item.colonia_prom_mud) || 0,
          colonia_prom_altal: parseFloat(item.colonia_prom_altal) || 0
        }));
      } catch (error) {
        console.error('Error fetching colonias:', error);
        return [];
      }
    }

    async function fetchColoniaById(coloniaId) {
      try {
        const response = await fetch(`?action=obtenerColonia&coloniaId=${coloniaId}`);
        if (!response.ok) throw new Error('Error al obtener colonia');
        const data = await response.json();
        return {
          ...data,
          colonia_promedio: parseFloat(data.colonia_promedio) || 0,
          colonia_prom_prop: parseFloat(data.colonia_prom_prop) || 0,
          colonia_prom_hey: parseFloat(data.colonia_prom_hey) || 0,
          colonia_prom_clau: parseFloat(data.colonia_prom_clau) || 0,
          colonia_prom_mud: parseFloat(data.colonia_prom_mud) || 0,
          colonia_prom_altal: parseFloat(data.colonia_prom_altal) || 0
        };
      } catch (error) {
        console.error('Error fetching colonia by id:', error);
        return null;
      }
    }

    async function fetchAmenidades(coloniaId) {
      try {
        const response = await fetch(`?action=obtenerAmenidades&coloniaId=${coloniaId}`);
        if (!response.ok) throw new Error('Error al obtener amenidades');
        return await response.json();
      } catch (error) {
        console.error('Error fetching amenities:', error);
        return {};
      }
    }

    function showSuggestions(colonias) {
      refs.suggestionsDropdown.innerHTML = '';
      if (colonias.length > 0) {
        colonias.forEach(colonia => {
          const suggestionItem = document.createElement('div');
          suggestionItem.className = 'suggestion-item';
          suggestionItem.setAttribute('role', 'option');
          suggestionItem.innerHTML = `
            <div class="suggestion-text">${colonia.colonia_nombre}</div>
            <div class="suggestion-subtext">${colonia.alcaldia_nombre}, ${colonia.estado_nombre}</div>
          `;
          suggestionItem.addEventListener('click', () => selectColonia(colonia));
          refs.suggestionsDropdown.appendChild(suggestionItem);
        });
        refs.suggestionsDropdown.style.display = 'block';
      } else {
        refs.suggestionsDropdown.style.display = 'none';
      }
    }

    async function selectColonia(colonia) {
      refs.loadingSection.style.display = 'flex';
      refs.suggestionsDropdown.style.display = 'none';
      refs.fullEditForm.style.display = 'none';
      
      try {
        selectedColonia = colonia;
        
        refs.coloniaTitle.textContent = colonia.colonia_nombre.toUpperCase();
        refs.alcaldiaCp.textContent = `${colonia.alcaldia_nombre}, ${colonia.colonia_cp}`;
        refs.estado.textContent = colonia.estado_nombre;
        
        refs.precioPromedio.textContent = colonia.colonia_promedio ? `$${formatNumber(colonia.colonia_promedio)}` : '$--';
        refs.precioPropiedades.textContent = colonia.colonia_prom_prop ? `$${formatNumber(colonia.colonia_prom_prop)}` : '$--';
        refs.precioHeyhome.textContent = colonia.colonia_prom_hey ? `$${formatNumber(colonia.colonia_prom_hey)}` : '$--';
        refs.precioClau.textContent = colonia.colonia_prom_clau ? `$${formatNumber(colonia.colonia_prom_clau)}` : '$--';
        refs.precioMudafy.textContent = colonia.colonia_prom_mud ? `$${formatNumber(colonia.colonia_prom_mud)}` : '$--';
        refs.precioAltaltium.textContent = colonia.colonia_prom_altal ? `$${formatNumber(colonia.colonia_prom_altal)}` : '$--';
        
        const amenidades = await fetchAmenidades(colonia.id_colonia);
        
        refs.vialidadValue.textContent = amenidades.amenidad_vialidad || '--';
        refs.escuelasValue.textContent = amenidades.amenidad_escuelas || '--';
        refs.hospitalesValue.textContent = amenidades.amenidad_hospitales || '--';
        refs.comercioValue.textContent = amenidades.amenidad_comercio || '--';
        refs.otrosValue.textContent = amenidades.amenidad_otros || '--';
        refs.categoriaValue.textContent = amenidades.categoria || '--';
        
        refs.coloniasContainer.style.display = 'block';
        
        refs.coloniaIdInput.value = colonia.id_colonia;
        
        refs.promedioInput.value = colonia.colonia_promedio ? formatNumber(colonia.colonia_promedio) : '';
        refs.propiedadesInput.value = colonia.colonia_prom_prop ? formatNumber(colonia.colonia_prom_prop) : '';
        refs.heyhomeInput.value = colonia.colonia_prom_hey ? formatNumber(colonia.colonia_prom_hey) : '';
        refs.clauInput.value = colonia.colonia_prom_clau ? formatNumber(colonia.colonia_prom_clau) : '';
        refs.mudafyInput.value = colonia.colonia_prom_mud ? formatNumber(colonia.colonia_prom_mud) : '';
        refs.altaltiumInput.value = colonia.colonia_prom_altal ? formatNumber(colonia.colonia_prom_altal) : '';
        
        refs.vialidadInput.value = amenidades.amenidad_vialidad || '';
        refs.escuelasInput.value = amenidades.amenidad_escuelas || '';
        refs.hospitalesInput.value = amenidades.amenidad_hospitales || '';
        refs.comercioInput.value = amenidades.amenidad_comercio || '';
        refs.otrosInput.value = amenidades.amenidad_otros || '';
        refs.categoriaInput.value = amenidades.categoria || 'Tibia';
        
      } catch (error) {
        console.error('Error al cargar datos de la colonia:', error);
        showNotification('Error al cargar los datos de la colonia', true);
      } finally {
        refs.loadingSection.style.display = 'none';
      }
    }

    function showNotification(message, isError = false) {
      refs.notificationMessage.textContent = message;
      refs.notification.className = isError ? 'notification error' : 'notification';
      refs.notification.classList.add('show');
      
      setTimeout(() => {
        refs.notification.classList.remove('show');
      }, 3000);
    }

    function toggleEditMode() {
      if (refs.fullEditForm.style.display === 'block') {
        refs.fullEditForm.style.display = 'none';
        refs.editAllBtn.textContent = 'Modificar';
      } else {
        refs.fullEditForm.style.display = 'block';
        refs.editAllBtn.textContent = 'Cerrar Edición';
      }
    }

    async function saveChanges() {
      refs.loadingSection.style.display = 'flex';
      
      const formData = new FormData(refs.updateForm);
      
      // Procesar números formateados
      formData.set('promedio', parseFormattedNumber(formData.get('promedio')));
      formData.set('propiedades', parseFormattedNumber(formData.get('propiedades')));
      formData.set('heyhome', parseFormattedNumber(formData.get('heyhome')));
      formData.set('clau', parseFormattedNumber(formData.get('clau')));
      formData.set('mudafy', parseFormattedNumber(formData.get('mudafy')));
      formData.set('altaltium', parseFormattedNumber(formData.get('altaltium')));
      
      const coloniaId = formData.get('colonia_id');
      
      try {
        const response = await fetch('', {
          method: 'POST',
          body: formData
        });
        
        if (!response.ok) throw new Error('Error en la actualización');
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          const textResponse = await response.text();
          throw new Error(`Respuesta inesperada: ${textResponse.substring(0, 100)}`);
        }
        
        const result = await response.json();
        if (result.success) {
          // Recargar datos después de actualizar
          const coloniaActualizada = await fetchColoniaById(coloniaId);
          const amenidadesActualizadas = await fetchAmenidades(coloniaId);
          
          if (coloniaActualizada) {
            await selectColonia(coloniaActualizada);
          }
          showNotification('Cambios guardados correctamente');
          toggleEditMode(); // Salir del modo edición
        } else {
          throw new Error(result.message || 'Error desconocido');
        }
      } catch (error) {
        console.error('Error al guardar cambios:', error);
        showNotification('Error al guardar los cambios: ' + error.message, true);
      } finally {
        refs.loadingSection.style.display = 'none';
      }
    }

    const handleSearchInput = debounce(async function() {
      const query = refs.searchInput.value.trim();
      if (query.length > 2) {
        const colonias = await fetchColonias(query);
        showSuggestions(colonias);
      } else {
        refs.suggestionsDropdown.style.display = 'none';
      }
    }, 300);

    function debounce(func, wait) {
      let timeout;
      return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
      };
    }

    refs.searchInput.addEventListener('input', handleSearchInput);

    refs.editAllBtn.addEventListener('click', toggleEditMode);
    refs.cancelAllBtn.addEventListener('click', toggleEditMode);
    
    refs.updateForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      await saveChanges();
    });

    // Validación de números en inputs
    document.querySelectorAll('input[data-type="number"]').forEach(input => {
      input.addEventListener('input', () => formatNumberInput(input));
    });
  </script>
</body>
</html>