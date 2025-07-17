<?php
// ======================================================================
// SECCIÓN PHP: MANEJADOR DE PETICIONES PARA FUNCIONALIDADES AJAX
// ======================================================================

// Configuración de conexión a la base de datos
$servername = "sql302.infinityfree.com";
$username = "if0_39478618";
$password = "C3rrEiwVe81HS1";
$dbname = "if0_39478618_altaltium";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Manejar peticiones para obtener amenidades
if (isset($_GET['action']) && $_GET['action'] == 'obtenerAmenidades') {
    $coloniaId = isset($_GET['coloniaId']) ? $_GET['coloniaId'] : '';
    $coloniaNombre = isset($_GET['coloniaNombre']) ? $_GET['coloniaNombre'] : '';

    if (empty($coloniaId) && empty($coloniaNombre)) {
        echo json_encode(['error' => 'Se requiere ID o nombre de colonia']);
        exit;
    }

    if (!empty($coloniaId)) {
        $sql = "SELECT * FROM amenidades_codec WHERE id_amenidad = :coloniaId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':coloniaId', $coloniaId);
    } else {
        $sql = "SELECT am.* FROM amenidades_codec am
                JOIN colonia_codec c ON am.id_amenidad = c.id_colonia
                WHERE c.colonia_nombre = :coloniaNombre";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':coloniaNombre', $coloniaNombre);
    }

    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $amenidades = [
            ['icon' => '🛣️', 'name' => 'Vialidad: ' . $result['amenidad_vialidad']],
            ['icon' => '🏫', 'name' => 'Escuelas: ' . $result['amenidad_escuelas']],
            ['icon' => '🏥', 'name' => 'Hospitales: ' . $result['amenidad_hospitales']],
            ['icon' => '🛒', 'name' => 'Comercio: ' . $result['amenidad_comercio']],
            ['icon' => '🧭', 'name' => 'Otros: ' . $result['amenidad_otros']]
        ];
        echo json_encode($amenidades);
    } else {
        echo json_encode([]);
    }
    exit;
}

// Manejar peticiones para buscar colonias
if (isset($_GET['action']) && $_GET['action'] == 'buscarColonias') {
    $query = isset($_GET['q']) ? $_GET['q'] : '';
    
    $sql = "SELECT c.id_colonia, c.colonia_nombre, a.alcaldia_nombre, e.estado_nombre, c.colonia_cp, 
                   c.colonia_promedio, c.colonia_prom_prop, c.colonia_prom_hey, c.colonia_prom_clau, 
                   c.colonia_prom_mud, c.colonia_prom_altal
            FROM colonia_codec c
            JOIN alcaldia_codec a ON c.colonia_alcaldia = a.id_alcaldia
            JOIN estado_codec e ON a.alcaldia_estado = e.id_estado
            WHERE c.colonia_nombre LIKE CONCAT('%', :query, '%')
            OR a.alcaldia_nombre LIKE CONCAT('%', :query, '%')
            ORDER BY c.colonia_nombre ASC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':query', $query);
    $stmt->execute();
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Manejar peticiones para obtener entidades
if (isset($_GET['action']) && $_GET['action'] == 'obtenerEntidades') {
    $sql = "SELECT * FROM estado_codec ORDER BY estado_nombre";
    $stmt = $conn->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Manejar peticiones para obtener alcaldías
if (isset($_GET['action']) && $_GET['action'] == 'obtenerAlcaldias') {
    $entidadId = isset($_GET['entidadId']) ? $_GET['entidadId'] : '';
    
    $sql = "SELECT * FROM alcaldia_codec WHERE alcaldia_estado = :entidadId ORDER BY alcaldia_nombre";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':entidadId', $entidadId);
    $stmt->execute();
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Manejar peticiones para filtrar colonias
if (isset($_GET['action']) && $_GET['action'] == 'buscarColoniasFiltros') {
    $entidadId = isset($_GET['entidadId']) ? $_GET['entidadId'] : '';
    $alcaldiaId = isset($_GET['alcaldiaId']) ? $_GET['alcaldiaId'] : '';
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
    
    $sql = "SELECT c.*, a.alcaldia_nombre, e.estado_nombre, am.categoria 
            FROM colonia_codec c
            JOIN alcaldia_codec a ON c.colonia_alcaldia = a.id_alcaldia
            JOIN estado_codec e ON a.alcaldia_estado = e.id_estado
            JOIN amenidades_codec am ON am.id_amenidad = c.id_colonia
            WHERE c.colonia_alcaldia = :alcaldiaId
            AND am.categoria = :tipo";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':alcaldiaId', $alcaldiaId);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->execute();
    
    $colonias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($colonias);
    exit;
}

// ======================================================================
// SECCIÓN HTML: INTERFAZ DE USUARIO
// ======================================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Mapa de calor interactivo para explorar precios y amenidades por colonia en México.">
  <meta name="author" content="C.O.D.E.C.">
  <title>C.O.D.E.C. - Mapa de Calor</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
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
      background-image: url('img/fondo.jpeg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: var(--white);
      min-height: 100vh;
      overflow-x: hidden;
      line-height: 1.6;
      position: relative;
      z-index: 0;
    }

    /* Capa de superposición para mejorar la legibilidad */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(13, 29, 31, 0.92);
      z-index: -1;
    }

    /* NAVBAR MEJORADO */
    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, 
        rgba(13, 29, 31, 0.95) 0%, 
        rgba(0, 48, 53, 0.92) 50%, 
        rgba(13, 29, 31, 0.95) 100%);
      backdrop-filter: blur(10px);
      box-shadow: var(--shadow-md);
      border-bottom: 2px solid var(--accent);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .top-bar::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, 
        transparent 0%, 
        var(--primary-light) 20%, 
        var(--accent) 50%, 
        var(--primary-light) 80%, 
        transparent 100%);
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .brand-logo {
      width: 120px;
      height: auto;
      background-color: var(--white);
      padding: 0.5rem;
      border-radius: 12px;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
      border: 2px solid transparent;
    }

    .brand-logo:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 204, 204, 0.3);
      border-color: var(--primary-light);
    }

    .brand-text {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }

    .brand-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--primary-light);
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
      letter-spacing: 0.5px;
    }

    .brand-subtitle {
      font-size: 0.7rem;
      color: var(--gray-light);
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .nav-menu {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .nav-button {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: var(--white);
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 50px;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 600;
      text-decoration: none;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
      min-width: 140px;
      justify-content: center;
    }

    .nav-button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, 
        transparent, 
        rgba(255, 255, 255, 0.2), 
        transparent);
      transition: left 0.6s ease;
    }

    .nav-button:hover::before {
      left: 100%;
    }

    .nav-button:hover {
      background: linear-gradient(135deg, var(--primary-light), var(--accent));
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 204, 204, 0.4);
    }

    .nav-button.active {
      background: linear-gradient(135deg, var(--accent), var(--primary-light));
      box-shadow: var(--shadow-md);
      transform: translateY(-1px);
    }

    .nav-button.active::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      right: 0;
      height: 3px;
      background: var(--primary-light);
      border-radius: 2px;
    }

    .nav-button.logout {
      background: linear-gradient(135deg, #d32f2f, #f44336);
    }

    .nav-button.logout:hover {
      background: linear-gradient(135deg, #f44336, #ff5722);
      box-shadow: 0 8px 20px rgba(244, 67, 54, 0.4);
    }

    .nav-separator {
      width: 2px;
      height: 2rem;
      background: linear-gradient(180deg, 
        transparent 0%, 
        var(--accent) 50%, 
        transparent 100%);
      margin: 0 0.5rem;
      border-radius: 2px;
    }

    .main-container {
      max-width: 1400px;
      margin: 1.5rem auto;
      padding: 0 1.5rem;
      position: relative;
      z-index: 2;
    }

    .tabs-container {
      background-color: rgba(13, 29, 31, 0.95);
      border-radius: 12px 12px 0 0;
      box-shadow: var(--shadow-md);
      backdrop-filter: blur(8px);
      overflow: visible;
      position: relative;
      z-index: 105;
    }

    .tabs-nav {
      display: flex;
      border-bottom: 1px solid rgba(82, 179, 192, 0.3);
    }

    .tab-button {
      flex: 1;
      max-width: 200px;
      padding: 1rem 1.5rem;
      background: none;
      border: none;
      font-size: 0.9375rem;
      font-weight: 600;
      cursor: pointer;
      color: var(--gray-light);
      letter-spacing: 0.6px;
      transition: var(--transition);
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    .tab-button i {
      font-size: 1.1rem;
    }

    .tab-button.active {
      color: var(--primary-light);
      background-color: rgba(0, 204, 204, 0.15);
    }

    .tab-button.active::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 4px;
      background-color: var(--primary-light);
      box-shadow: 0 2px 5px rgba(0, 204, 204, 0.4);
    }

    .tab-button:hover:not(.active) {
      background-color: rgba(0, 0, 0, 0.15);
      color: var(--white);
    }

    .filters-container {
      display: none;
      padding: 1.5rem;
      background-color: rgba(13, 29, 31, 0.95);
      border-bottom: 1px solid rgba(82, 179, 192, 0.3);
    }

    .filters-row {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: flex-end;
      justify-content: space-between;
    }

    .filter-group {
      flex: 1 1 200px;
      position: relative;
    }

    .filter-label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--primary-light);
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.6px;
    }

    .filter-select,
    .filter-input {
      width: 100%;
      padding: 0.875rem 1rem;
      border-radius: var(--border-radius);
      border: 1px solid rgba(82, 179, 192, 0.4);
      background-color: rgba(0, 48, 53, 0.8);
      color: var(--white);
      font-size: 0.875rem;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
    }

    .filter-select:focus,
    .filter-input:focus {
      outline: none;
      border-color: var(--primary-light);
      box-shadow: 0 0 0 3px rgba(0, 204, 204, 0.3);
    }

    .filter-select:hover,
    .filter-input:hover {
      border-color: var(--accent);
    }

    .filters-actions {
      display: flex;
      justify-content: flex-end;
      width: 100%;
      margin-top: 1rem;
    }

    .filter-button {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: var(--white);
      border: none;
      padding: 0.875rem 1.875rem;
      border-radius: var(--border-radius);
      cursor: pointer;
      font-size: 0.875rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .filter-button:hover {
      background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    /* Nuevos estilos para la tabla de resultados */
    .results-container {
      width: 100%;
      overflow-x: auto;
      margin-top: 20px;
      display: none;
    }

    .results-table {
      width: 100%;
      border-collapse: collapse;
      background-color: rgba(0, 48, 53, 0.8);
      border-radius: var(--border-radius);
      overflow: hidden;
    }

    .results-table th {
      background-color: var(--primary);
      color: var(--white);
      padding: 0.75rem 1rem;
      text-align: left;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.6px;
    }

    .results-table td {
      padding: 0.75rem 1rem;
      border-bottom: 1px solid rgba(82, 179, 192, 0.3);
      color: var(--gray-light);
      cursor: pointer;
    }

    .results-table tr:hover td {
      background-color: rgba(0, 204, 204, 0.15);
      color: var(--primary-light);
    }

    .results-table tr:last-child td {
      border-bottom: none;
    }

    /* Estilo para el mapa grande */
    .map-section.large {
      flex: 3;
      min-height: 500px; /* Tamaño más equilibrado */
      display: flex;
      flex-direction: column;
    }

    .general-content {
      display: flex;
      flex-direction: column;
      gap: 2rem;
      padding: 2rem;
      min-height: 500px;
      position: relative;
      z-index: 2;
      background-color: rgba(13, 29, 31, 0.95);
      backdrop-filter: blur(8px);
      border-radius: 12px;
      display: none;
    }

    .search-section {
      padding: 1.5rem;
      background-color: rgba(13, 29, 31, 0.95);
      border-bottom: 1px solid rgba(82, 179, 192, 0.3);
      position: relative;
      z-index: 106;
      backdrop-filter: blur(8px);
    }

    .search-wrapper {
      position: relative;
      z-index: 1001;
    }

    .search-container {
      max-width: 600px;
      margin: 0 auto;
      position: relative;
    }

    .search-bar {
      position: relative;
      display: flex;
      align-items: center;
      background-color: rgba(0, 48, 53, 0.8);
      border: 1px solid rgba(82, 179, 192, 0.4);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
    }

    .search-bar:focus-within {
      border-color: var(--primary-light);
      box-shadow: 0 0 0 3px rgba(0, 204, 204, 0.3);
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
      font-size: 0.875rem;
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
      background-color: rgba(13, 29, 31, 0.95);
      border: 1px solid rgba(82, 179, 192, 0.4);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      max-height: 200px;
      overflow-y: auto;
      z-index: 10002;
      display: none;
    }

    .suggestion-item {
      padding: 0.75rem 1rem;
      cursor: pointer;
      color: var(--gray-light);
      font-size: 0.875rem;
      border-bottom: 1px solid rgba(82, 179, 192, 0.2);
      transition: var(--transition);
      display: flex;
      flex-direction: column;
    }

    .suggestion-item:last-child {
      border-bottom: none;
    }

    .suggestion-item:hover {
      background-color: rgba(0, 204, 204, 0.15);
      color: var(--primary-light);
    }

    .suggestion-text {
      font-weight: 600;
    }

    .suggestion-subtext {
      font-size: 0.75rem;
      color: var(--gray);
    }

    .suggestions-dropdown::-webkit-scrollbar {
      width: 6px;
    }

    .suggestions-dropdown::-webkit-scrollbar-track {
      background: rgba(0, 48, 53, 0.5);
      border-radius: 10px;
    }

    .suggestions-dropdown::-webkit-scrollbar-thumb {
      background-color: var(--primary);
      border-radius: 10px;
    }

    .suggestions-dropdown::-webkit-scrollbar-thumb:hover {
      background-color: var(--primary-light);
    }

    .main-content {
      display: flex;
      gap: 2rem;
      padding: 2rem;
      min-height: 500px;
      position: relative;
      z-index: 2;
      background-color: rgba(13, 29, 31, 0.95);
      backdrop-filter: blur(8px);
      border-radius: 12px;
      box-shadow: var(--shadow-md);
    }

    .info-panel {
      flex: 1;
      max-width: 400px;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .location-header {
      background: linear-gradient(135deg, rgba(0, 204, 204, 0.15) 0%, rgba(0, 204, 204, 0.1) 100%);
      padding: 1.5rem;
      border-radius: var(--border-radius);
      border-left: 3px solid var(--primary-light);
    }

    .location-title {
      font-size: 1.8rem;
      font-weight: 700;
      margin: 0 0 0.5rem 0;
      color: var(--primary-light);
      line-height: 1.2;
    }

    .location-subtitle {
      font-size: 1.1rem;
      color: var(--gray-light);
      margin: 0 0 0.375rem 0;
      font-weight: 500;
    }

    .location-state {
      font-size: 1rem;
      color: var(--gray-light);
      margin: 0;
    }

    .amenities-section {
      margin: 0;
    }

    .amenities-button {
      background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
      color: var(--white);
      padding: 0.875rem 1.875rem;
      border-radius: 25px;
      border: none;
      font-weight: 600;
      font-size: 0.9375rem;
      cursor: pointer;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    .amenities-button:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
      background: linear-gradient(135deg, var(--accent) 0%, var(--primary-light) 100%);
    }

    .price-section {
      background: linear-gradient(135deg, rgba(0, 204, 204, 0.2) 0%, rgba(0, 204, 204, 0.1) 100%);
      border-radius: 12px;
      padding: 1.5rem;
      border-left: 4px solid var(--primary-light);
      text-align: center;
    }

    .price-label {
      font-size: 1rem;
      color: var(--gray-light);
      margin: 0 0 0.5rem 0;
      font-weight: 600;
    }

    .price-value {
      font-size: 2.25rem;
      font-weight: 700;
      color: var(--primary-light);
      margin: 0;
      line-height: 1;
    }

    .map-section {
      flex: 2;
      display: flex;
      flex-direction: column;
      position: relative;
      z-index: 1;
      height: 100%;
      min-height: 500px;
    }

    .map-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow-md);
      background-color: var(--gray-light);
      min-height: 500px;
      position: relative;
      z-index: 0;
      height: 100%;
      border: 1px solid rgba(82, 179, 192, 0.4);
    }

    #map {
      width: 100%;
      height: 100%;
      flex: 1;
      border: none;
      transition: opacity 0.3s ease;
      position: relative;
      z-index: 0;
    }

    #largeMap {
      width: 100%;
      height: 100%;
      flex: 1;
      border: none;
      transition: opacity 0.3s ease;
      position: relative;
      z-index: 0;
    }

    .loading-map {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: var(--gray-light);
      text-align: center;
      padding: 2rem;
      background-color: rgba(13, 29, 31, 0.85);
      z-index: 10;
      opacity: 0;
      animation: fadeIn 0.3s ease forwards;
    }

    @keyframes fadeIn {
      to { opacity: 1; }
    }

    .loading-spinner {
      border: 4px solid rgba(255, 255, 255, 0.2);
      border-top: 4px solid var(--primary-light);
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin-bottom: 1rem;
      border-radius: 50%;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .sources-section {
      background-color: rgba(13, 29, 31, 0.95);
      padding: 2rem;
      border-top: 1px solid rgba(82, 179, 192, 0.3);
      backdrop-filter: blur(8px);
      border-radius: 0 0 12px 12px;
      margin-top: 2rem;
    }

    .section-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--primary-light);
      margin: 0 0 1.5rem 0;
      text-align: center;
      letter-spacing: 0.6px;
    }

    .sources-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      max-width: 1200px;
      margin: 0 auto;
    }

    /* MEJORAS EN LAS TARJETAS DE FUENTES */
    .source-card {
      background-color: rgba(13, 29, 31, 0.85);
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
      transition: var(--transition);
      border: 1px solid var(--accent);
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 180px;
      position: relative;
      overflow: hidden;
    }

    .source-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--primary-light));
    }

    .source-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
      border-color: var(--primary-light);
    }

    .source-logo {
      width: 80px;
      height: 80px;
      margin: 0 auto 1rem;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1.25rem;
      box-shadow: var(--shadow-sm);
      overflow: hidden;
      background: white;
      padding: 5px;
    }
    
    .source-logo img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .source-name {
      font-size: 1.125rem;
      font-weight: 600;
      color: var(--primary-light);
      margin: 0 0 0.5rem 0;
    }

    .source-price {
      font-size: 1.1rem;
      color: var(--white);
      margin: 0;
      font-weight: 700;
      letter-spacing: 0.6px;
      background: rgba(0, 204, 204, 0.2);
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      display: inline-block;
    }

    .amenities-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(5px);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .amenities-modal.show {
      display: flex;
      opacity: 1;
    }

    .modal-content {
      background-color: var(--dark-bg);
      border-radius: 16px;
      width: 90%;
      max-width: 600px;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: var(--shadow-lg);
      border: 2px solid var(--accent);
      transform: scale(0.95);
      transition: transform 0.3s ease;
    }

    .amenities-modal.show .modal-content {
      transform: scale(1);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem;
      border-bottom: 1px solid rgba(82, 179, 192, 0.3);
      background-color: rgba(0, 48, 53, 0.8);
      border-radius: 16px 16px 0 0;
    }

    .modal-title {
      font-size: 1.375rem;
      color: var(--primary-light);
      margin: 0;
      font-weight: 700;
    }

    .close-modal {
      background: none;
      border: none;
      color: var(--gray-light);
      font-size: 1.75rem;
      cursor: pointer;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: var(--transition);
    }

    .close-modal:hover {
      color: var(--primary-light);
      background-color: rgba(0, 204, 204, 0.15);
    }

    .amenities-list {
      list-style-type: none;
      padding: 1.5rem;
      margin: 0;
    }

    .amenity-item {
      padding: 1rem 0;
      border-bottom: 1px solid rgba(82, 179, 192, 0.3);
      display: flex;
      align-items: center;
    }

    .amenity-item:last-child {
      border-bottom: none;
    }

    .amenity-icon {
      margin-right: 1rem;
      font-size: 1.5rem;
      width: 40px;
      text-align: center;
      color: var(--primary-light);
    }

    .amenity-name {
      font-size: 1rem;
      color: var(--gray-light);
      font-weight: 500;
    }

    .modal-content::-webkit-scrollbar {
      width: 8px;
    }

    .modal-content::-webkit-scrollbar-track {
      background: rgba(0, 48, 53, 0.5);
      border-radius: 10px;
    }

    .modal-content::-webkit-scrollbar-thumb {
      background-color: var(--primary);
      border-radius: 10px;
    }

    .modal-content::-webkit-scrollbar-thumb:hover {
      background-color: var(--primary-light);
    }

    /* Nuevos estilos para la tabla de colonias */
    .colonias-container {
      width: 100%;
      overflow-x: auto;
      margin-top: 20px;
      border-radius: var(--border-radius);
      background-color: rgba(0, 48, 53, 0.8);
      box-shadow: var(--shadow-sm);
      border: 1px solid rgba(82, 179, 192, 0.4);
    }

    .colonias-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 600px;
    }

    .colonias-table th {
      background-color: var(--primary);
      color: var(--white);
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.875rem;
      letter-spacing: 0.6px;
    }

    .colonias-table td {
      padding: 1rem;
      border-bottom: 1px solid rgba(82, 179, 192, 0.3);
      color: var(--gray-light);
    }

    .colonias-table tr:hover td {
      background-color: rgba(0, 204, 204, 0.15);
      color: var(--primary-light);
    }

    .colonias-table tr:last-child td {
      border-bottom: none;
    }

    .badge {
      display: inline-block;
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      text-align: center;
      min-width: 80px;
    }

    .caliente {
      background-color: #ffcdd2;
      color: #c62828;
    }

    .tibia {
      background-color: #fff9c4;
      color: #f57f17;
    }

    .fria {
      background-color: #c5e1a5;
      color: #33691e;
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      padding: 1rem 1rem 0;
    }

    .table-title {
      font-size: 1.25rem;
      color: var(--primary-light);
      font-weight: 600;
    }
    
    /* NUEVOS ESTILOS AGREGADOS */
    .map-header {
      background-color: rgba(13, 29, 31, 0.9);
      padding: 0.75rem 1.5rem;
      border-bottom: 1px solid rgba(82, 179, 192, 0.3);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .map-title {
      font-size: 1.1rem;
      color: var(--primary-light);
      font-weight: 600;
    }
    
    .back-button {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: var(--white);
      border: none;
      padding: 0.5rem 1rem;
      border-radius: var(--border-radius);
      cursor: pointer;
      font-size: 0.8rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: var(--transition);
    }
    
    .back-button:hover {
      background: linear-gradient(135deg, var(--primary-light), var(--accent));
    }
    
    .colonias-table tr {
      cursor: pointer;
      transition: var(--transition);
    }
    
    .colonias-table tr:hover {
      background-color: rgba(0, 204, 204, 0.1);
    }
    
    .colonias-table tr.selected {
      background-color: rgba(0, 204, 204, 0.2);
      box-shadow: inset 0 0 0 2px var(--primary-light);
    }

    @media (max-width: 1024px) {
      .main-content {
        flex-direction: column;
        gap: 1.5rem;
      }

      .info-panel {
        max-width: 100%;
      }

      .map-container {
        min-height: 350px;
      }
      
      .nav-menu {
        gap: 0.8rem;
      }
      
      .nav-button {
        padding: 0.7rem 1.2rem;
        font-size: 0.8rem;
        min-width: 120px;
      }
    }

    @media (max-width: 768px) {
      .top-bar {
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1rem;
      }

      .nav-menu {
        flex-wrap: wrap;
        justify-content: center;
      }

      .main-container {
        padding: 1rem;
      }

      .main-content {
        padding: 1.5rem;
      }

      .search-section {
        padding: 1rem;
      }

      .filters-container {
        padding: 1rem;
      }

      .filters-row {
        gap: 1rem;
      }

      .filter-group {
        min-width: 150px;
      }

      .sources-section {
        padding: 1.5rem;
      }

      .sources-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
      }

      .location-title {
        font-size: 1.5rem;
      }

      .price-value {
        font-size: 1.8rem;
      }
      
      .map-container {
        min-height: 400px;
      }
      
      .nav-button {
        min-width: auto;
        width: 100%;
      }
    }

    @media (max-width: 480px) {
      .sources-grid {
        grid-template-columns: 1fr;
      }

      .location-title {
        font-size: 1.4rem;
      }

      .price-value {
        font-size: 1.6rem;
      }

      .modal-content {
        width: 95%;
        margin: 1rem;
      }

      .modal-header {
        padding: 1rem;
      }

      .amenities-list {
        padding: 1rem;
      }

      .filters-row {
        flex-direction: column;
        gap: 1rem;
      }

      .filter-group {
        min-width: 100%;
      }

      .filter-button {
        width: 100%;
      }

      .search-container {
        max-width: 100%;
      }

      .suggestions-dropdown {
        width: 100%;
        left: 0;
      }
      
      .tab-button {
        padding: 0.8rem;
        font-size: 0.8rem;
      }
      
      .brand-logo {
        width: 100px;
      }
      
      .brand-title {
        font-size: 1.2rem;
      }
      
      .map-container {
        min-height: 350px;
      }
    }
  </style>
</head>
<body>
  <header class="top-bar">
    <div class="navbar-brand">
      <img src="img/logo.png" alt="Logo C.O.D.E.C." class="brand-logo">
      <div class="brand-text">
        <div class="brand-title">C.O.D.E.C.</div>
        <div class="brand-subtitle">Mapa de Calor</div>
      </div>
    </div>
    <nav class="nav-menu">
      <a href="honorarios.html" class="nav-button active">
        <i class="fas fa-map-marked-alt"></i>
        <span>HONORARIOS</span>
      </a>
      <div class="nav-separator"></div>
      <a href="menu.html" class="nav-button">
        <i class="fas fa-bars"></i>
        <span>Menú</span>
      </a>
      <div class="nav-separator"></div>
      <a href="index.html" class="nav-button logout">
        <i class="fas fa-sign-out-alt"></i>
        <span>Cerrar sesión</span>
      </a>
    </nav>
  </header>

  <main class="main-container">
    <div class="tabs-container">
      <nav class="tabs-nav">
        <button class="tab-button active" id="specificTab">
          <i class="fas fa-search-location"></i>
          ESPECÍFICA
        </button>
        <button class="tab-button" id="generalTab">
          <i class="fas fa-globe-americas"></i>
          GENERAL
        </button>
      </nav>
      <div class="filters-container" id="filtersContainer">
        <div class="filters-row">
          <div class="filter-group">
            <label class="filter-label" for="entidadSelect"><i class="fas fa-city"></i> Entidad</label>
            <select class="filter-select" id="entidadSelect" aria-label="Seleccione una entidad">
              <option value="">Seleccione una entidad</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label" for="alcaldiaSelect"><i class="fas fa-building"></i> Alcaldía</label>
            <select class="filter-select" id="alcaldiaSelect" aria-label="Seleccione una alcaldía" disabled>
              <option value="">Seleccione una alcaldía</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label" for="tipoSelect"><i class="fas fa-thermometer-half"></i> Tipo</label>
            <select class="filter-select" id="tipoSelect" aria-label="Seleccione un tipo" disabled>
              <option value="">Seleccione un tipo</option>
              <option value="Caliente">Caliente</option>
              <option value="Tibia">Tibia</option>
              <option value="Fria">Fría</option>
            </select>
          </div>
          <button class="filter-button" id="filterButton">
            <i class="fas fa-filter"></i>
            <span>FILTRAR</span>
          </button>
        </div>
      </div>
      <div class="search-section" id="searchSection">
        <div class="search-wrapper">
          <div class="search-container">
            <label class="filter-label" for="searchInput"><i class="fas fa-search"></i> Buscar colonia o alcaldía</label>
            <div class="search-bar">
              <span class="search-icon" aria-hidden="true">🔍</span>
              <input type="text" class="search-input" id="searchInput" placeholder="Buscar colonia, alcaldía..." aria-label="Buscar colonia o alcaldía">
              <div class="suggestions-dropdown" id="suggestionsDropdown"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="content-container">
      <!-- Contenido para pestaña Específica -->
      <div class="main-content" id="specificContent">
        <section class="info-panel">
          <div class="location-header">
            <h1 class="location-title" id="coloniaTitle">COLONIA</h1>
            <p class="location-subtitle" id="alcaldiaCp">ALCALDÍA, CP</p>
            <p class="location-state" id="estado">ESTADO</p>
          </div>
          
          <div class="price-section">
            <p class="price-label">Precio por m²</p>
            <p class="price-value" id="precioValue">$--</p>
          </div>
          
          <div class="amenities-section">
            <button class="amenities-button" id="amenitiesBtn" aria-label="Ver amenidades">
              <i class="fas fa-map-marker-alt"></i>
              AMENIDADES
            </button>
          </div>
        </section>
        <section class="map-section">
          <div class="map-container">
            <div class="loading-map" id="loadingMap">
              <div class="loading-spinner"></div>
              <h3>Cargando mapa...</h3>
              <p>Por favor espera mientras se carga la ubicación</p>
            </div>
            <iframe id="map" 
                    allowfullscreen="" 
                    loading="lazy" 
                    title="Mapa de ubicación">
            </iframe>
          </div>
        </section>
      </div>
      
      <!-- Contenido para pestaña General -->
      <div class="general-content" id="generalContent">
        <section class="map-section large">
          <div class="map-header" id="mapHeader">
            <h3 class="map-title">Alcaldía: <span id="currentAlcaldia">-</span></h3>
            <button class="back-button" id="backToAlcaldiaBtn" style="display:none;">
              <i class="fas fa-arrow-left"></i>
              VOLVER A ALCALDÍA
            </button>
          </div>
          <div class="map-container">
            <div class="loading-map" id="loadingLargeMap">
              <div class="loading-spinner"></div>
              <h3>Cargando mapa...</h3>
              <p>Por favor espera mientras se carga la alcaldía</p>
            </div>
            <iframe id="largeMap" 
                    allowfullscreen="" 
                    loading="lazy" 
                    title="Mapa de alcaldía">
            </iframe>
          </div>
        </section>
        
        <!-- Tabla de colonias -->
        <div class="colonias-container" id="coloniasContainer">
          <div class="table-header">
            <h3 class="table-title"><i class="fas fa-list"></i> Colonias encontradas</h3>
          </div>
          <table class="colonias-table" id="coloniasTable">
            <thead>
              <tr>
                <th>Colonia</th>
                <th>Precio Promedio (m²)</th>
                <th>Categoría</th>
              </tr>
            </thead>
            <tbody id="coloniasTableBody">
              <!-- Las colonias se mostrarán aquí dinámicamente -->
            </tbody>
          </table>
        </div>
      </div>
      
      <section class="sources-section" id="sourcesSection">
        <h2 class="section-title"><i class="fas fa-database"></i> Fuentes de Información</h2>
        <div class="sources-grid" id="sourcesGrid">
          <div class="source-card" data-source="propiedades">
            <div class="source-logo">
              <img src="img/prop.jpg" alt="Propiedades.com">
            </div>
            <div class="source-content">
              <h3 class="source-name">Propiedades.com</h3>
              <p class="source-price">$--</p>
            </div>
          </div>
          <div class="source-card" data-source="heyhome">
            <div class="source-logo">
              <img src="img/hey.png" alt="Hey Home">
            </div>
            <div class="source-content">
              <h3 class="source-name">Hey Home</h3>
              <p class="source-price">$--</p>
            </div>
          </div>
          <div class="source-card" data-source="clau">
            <div class="source-logo">
              <img src="img/clau.png" alt="Clau">
            </div>
            <div class="source-content">
              <h3 class="source-name">Clau</h3>
              <p class="source-price">$--</p>
            </div>
          </div>
          <div class="source-card" data-source="mudafy">
            <div class="source-logo">
              <img src="img/mud.png" alt="Mudafy">
            </div>
            <div class="source-content">
              <h3 class="source-name">Mudafy</h3>
              <p class="source-price">$--</p>
            </div>
          </div>
          <div class="source-card" data-source="altaltium">
            <div class="source-logo">
              <img src="img/alta.png" alt="Altaltium">
            </div>
            <div class="source-content">
              <h3 class="source-name">Altaltium</h3>
              <p class="source-price">$--</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>

  <div class="amenities-modal" id="amenitiesModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title"><i class="fas fa-map-marked-alt"></i> Amenidades de la Zona</h3>
        <button class="close-modal" id="closeModal" aria-label="Cerrar modal">×</button>
      </div>
      <ul class="amenities-list" id="amenitiesList"></ul>
    </div>
  </div>

  <script>
    const refs = {
      searchInput: document.getElementById('searchInput'),
      suggestionsDropdown: document.getElementById('suggestionsDropdown'),
      coloniaTitle: document.getElementById('coloniaTitle'),
      alcaldiaCp: document.getElementById('alcaldiaCp'),
      precioValue: document.getElementById('precioValue'),
      estado: document.getElementById('estado'),
      mapFrame: document.getElementById('map'),
      largeMapFrame: document.getElementById('largeMap'),
      loadingMap: document.getElementById('loadingMap'),
      loadingLargeMap: document.getElementById('loadingLargeMap'),
      amenitiesBtn: document.getElementById('amenitiesBtn'),
      amenitiesModal: document.getElementById('amenitiesModal'),
      closeModal: document.getElementById('closeModal'),
      amenitiesList: document.getElementById('amenitiesList'),
      specificTab: document.getElementById('specificTab'),
      generalTab: document.getElementById('generalTab'),
      filtersContainer: document.getElementById('filtersContainer'),
      searchSection: document.getElementById('searchSection'),
      filterButton: document.getElementById('filterButton'),
      entidadSelect: document.getElementById('entidadSelect'),
      alcaldiaSelect: document.getElementById('alcaldiaSelect'),
      tipoSelect: document.getElementById('tipoSelect'),
      sourcesGrid: document.getElementById('sourcesGrid'),
      specificContent: document.getElementById('specificContent'),
      generalContent: document.getElementById('generalContent'),
      coloniasContainer: document.getElementById('coloniasContainer'),
      coloniasTableBody: document.getElementById('coloniasTableBody'),
      sourcesSection: document.getElementById('sourcesSection'),
      currentAlcaldia: document.getElementById('currentAlcaldia'),
      backToAlcaldiaBtn: document.getElementById('backToAlcaldiaBtn')
    };

    // Variables globales
    let currentAlcaldia = null;
    let currentColoniaMarker = null;

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

    async function fetchAmenidades(coloniaNombre) {
      try {
        const response = await fetch(`?action=obtenerAmenidades&coloniaNombre=${encodeURIComponent(coloniaNombre)}`);
        if (!response.ok) throw new Error('Error al obtener amenidades');
        return await response.json();
      } catch (error) {
        console.error('Error fetching amenities:', error);
        return [];
      }
    }

    async function fetchEntidades() {
      try {
        const response = await fetch(`?action=obtenerEntidades`);
        if (!response.ok) throw new Error('Error al obtener entidades');
        return await response.json();
      } catch (error) {
        console.error('Error fetching entidades:', error);
        return [];
      }
    }

    async function fetchAlcaldias(entidadId) {
      try {
        const response = await fetch(`?action=obtenerAlcaldias&entidadId=${entidadId}`);
        if (!response.ok) throw new Error('Error al obtener alcaldías');
        return await response.json();
      } catch (error) {
        console.error('Error fetching alcaldias:', error);
        return [];
      }
    }

    async function fetchColoniasPorFiltros(entidadId, alcaldiaId, tipo) {
      try {
        const response = await fetch(`?action=buscarColoniasFiltros&entidadId=${entidadId}&alcaldiaId=${alcaldiaId}&tipo=${tipo}`);
        if (!response.ok) throw new Error('Error al filtrar colonias');
        return await response.json();
      } catch (error) {
        console.error('Error fetching filtered colonias:', error);
        return [];
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
      refs.searchInput.value = colonia.colonia_nombre;
      refs.suggestionsDropdown.style.display = 'none';
      
      refs.coloniaTitle.textContent = colonia.colonia_nombre.toUpperCase();
      refs.alcaldiaCp.textContent = `${colonia.alcaldia_nombre}, ${colonia.colonia_cp}`;
      refs.estado.textContent = colonia.estado_nombre;
      refs.precioValue.textContent = colonia.colonia_promedio ? `$${colonia.colonia_promedio.toLocaleString('es-MX')}` : '$--';
      
      updateSourcePrices(colonia);
      
      updateMapWithQuery(`${colonia.colonia_nombre}, ${colonia.alcaldia_nombre}, ${colonia.estado_nombre}`, refs.mapFrame, refs.loadingMap);
    }

    function updateSourcePrices(colonia) {
      const formatPrice = (price) => price ? `$${price.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})}` : '$--';
      
      const sources = [
        { source: 'propiedades', price: colonia.colonia_prom_prop, name: 'Propiedades.com', image: 'img/prop.jpg' },
        { source: 'heyhome', price: colonia.colonia_prom_hey, name: 'Hey Home', image: 'img/hey.png' },
        { source: 'clau', price: colonia.colonia_prom_clau, name: 'Clau', image: 'img/clau.png' },
        { source: 'mudafy', price: colonia.colonia_prom_mud, name: 'Mudafy', image: 'img/mud.png' },
        { source: 'altaltium', price: colonia.colonia_prom_altal, name: 'Altaltium', image: 'img/alta.png' }
      ];
      
      refs.sourcesGrid.innerHTML = '';
      
      sources.forEach(item => {
        if (item.price && item.price > 0) {
          const card = document.createElement('div');
          card.className = 'source-card';
          card.setAttribute('data-source', item.source);
          card.innerHTML = `
            <div class="source-logo">
              <img src="${item.image}" alt="${item.name}">
            </div>
            <div class="source-content">
              <h3 class="source-name">${item.name}</h3>
              <p class="source-price">${formatPrice(item.price)}</p>
            </div>
          `;
          refs.sourcesGrid.appendChild(card);
        }
      });
    }

    function updateMapWithQuery(query, mapElement, loadingElement, zoom = 13) {
      loadingElement.style.display = 'flex';
      mapElement.style.opacity = '0.5';
      
      const encodedQuery = encodeURIComponent(query);
      const mapUrl = `https://maps.google.com/maps?q=${encodedQuery}&z=${zoom}&output=embed`;
      
      mapElement.onload = () => {
        loadingElement.style.display = 'none';
        mapElement.style.opacity = '1';
      };
      
      mapElement.src = mapUrl;
    }

    async function showAmenities() {
      refs.amenitiesList.innerHTML = '';
      const coloniaNombre = refs.coloniaTitle.textContent;
      
      if (coloniaNombre === 'COLONIA') {
        addAmenityItem('Selecciona una colonia primero');
      } else {
        try {
          const amenities = await fetchAmenidades(coloniaNombre);
          if (amenities.length > 0) {
            amenities.forEach(amenity => {
              addAmenityItem(`${amenity.icon} ${amenity.name}`);
            });
          } else {
            addAmenityItem('No se encontraron amenidades para esta colonia');
          }
        } catch (error) {
          console.error('Error fetching amenities:', error);
          addAmenityItem('Error al cargar amenidades');
        }
      }
      
      refs.amenitiesModal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }

    function addAmenityItem(text) {
      const li = document.createElement('li');
      li.className = 'amenity-item';
      li.innerHTML = `
        <span class="amenity-icon">${text.charAt(0)}</span>
        <span class="amenity-name">${text.substring(2)}</span>
      `;
      refs.amenitiesList.appendChild(li);
    }

    function closeModal() {
      refs.amenitiesModal.classList.remove('show');
      document.body.style.overflow = 'auto';
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

    function toggleTab() {
      const isSpecificTab = this === refs.specificTab;
      refs.specificTab.classList.toggle('active', isSpecificTab);
      refs.generalTab.classList.toggle('active', !isSpecificTab);
      refs.filtersContainer.style.display = isSpecificTab ? 'none' : 'block';
      refs.searchSection.style.display = isSpecificTab ? 'block' : 'none';
      refs.specificContent.style.display = isSpecificTab ? 'flex' : 'none';
      refs.generalContent.style.display = isSpecificTab ? 'none' : 'block';
      refs.sourcesSection.style.display = isSpecificTab ? 'block' : 'none';
    }

    async function handleEntidadChange() {
      const entidadId = parseInt(this.value);
      refs.alcaldiaSelect.innerHTML = '<option value="">Seleccione una alcaldía</option>';
      refs.tipoSelect.value = '';
      refs.alcaldiaSelect.disabled = !entidadId;
      refs.tipoSelect.disabled = true;

      if (entidadId) {
        const alcaldias = await fetchAlcaldias(entidadId);
        alcaldias.forEach(alcaldia => {
          const option = document.createElement('option');
          option.value = alcaldia.id_alcaldia;
          option.textContent = alcaldia.alcaldia_nombre;
          refs.alcaldiaSelect.appendChild(option);
        });
      }
    }

    function handleAlcaldiaChange() {
      const alcaldiaId = this.value;
      refs.tipoSelect.disabled = !alcaldiaId;
    }

    async function handleFilterButton() {
      const entidadId = refs.entidadSelect.value;
      const alcaldiaId = refs.alcaldiaSelect.value;
      const tipo = refs.tipoSelect.value;

      if (!entidadId || !alcaldiaId || !tipo) {
        alert('Por favor seleccione todos los filtros');
        return;
      }

      // Obtener el nombre de la alcaldía seleccionada
      const alcaldiaNombre = refs.alcaldiaSelect.options[refs.alcaldiaSelect.selectedIndex].text;
      // Obtener el nombre de la entidad (estado) seleccionada
      const estadoNombre = refs.entidadSelect.options[refs.entidadSelect.selectedIndex].text;

      // Guardar alcaldía actual
      currentAlcaldia = alcaldiaNombre;
      refs.currentAlcaldia.textContent = alcaldiaNombre;
      
      // Actualizar el título del mapa
      document.querySelector('.map-title').textContent = `Alcaldía: ${alcaldiaNombre}`;
      
      // Actualizar el mapa grande con la alcaldía
      updateMapWithQuery(`${alcaldiaNombre}, ${estadoNombre}`, refs.largeMapFrame, refs.loadingLargeMap, 13);

      // Obtener las colonias filtradas
      const colonias = await fetchColoniasPorFiltros(entidadId, alcaldiaId, tipo);
      if (colonias.length > 0) {
        // Mostrar colonias en la tabla
        showColoniasTable(colonias);
      } else {
        refs.coloniasTableBody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No se encontraron colonias con los filtros seleccionados</td></tr>';
      }
      
      // Ocultar botón de volver
      refs.backToAlcaldiaBtn.style.display = 'none';
    }

    function showColoniaOnMap(colonia) {
      if (!currentAlcaldia) return;
      
      // Actualizar el mapa con la colonia específica
      const query = `${colonia.colonia_nombre}, ${currentAlcaldia}`;
      updateMapWithQuery(query, refs.largeMapFrame, refs.loadingLargeMap, 15);
      
      // Mostrar botón para volver a la vista de alcaldía
      refs.backToAlcaldiaBtn.style.display = 'flex';
    }

    function showColoniasTable(colonias) {
      const tableBody = refs.coloniasTableBody;
      tableBody.innerHTML = '';
      
      colonias.forEach(colonia => {
        const row = document.createElement('tr');
        const categoriaClass = colonia.categoria.toLowerCase();
        
        row.innerHTML = `
          <td>${colonia.colonia_nombre}</td>
          <td>${colonia.colonia_promedio ? '$' + colonia.colonia_promedio.toLocaleString('es-MX') : 'N/A'}</td>
          <td><span class="badge ${categoriaClass}">${colonia.categoria}</span></td>
        `;
        
        // Agregar evento click a la fila
        row.addEventListener('click', () => {
          // Remover selección previa
          const selectedRow = tableBody.querySelector('.selected');
          if (selectedRow) selectedRow.classList.remove('selected');
          
          // Seleccionar fila actual
          row.classList.add('selected');
          
          // Mostrar colonia en el mapa
          showColoniaOnMap(colonia);
        });
        
        tableBody.appendChild(row);
      });
    }

    function showAlcaldiaOnMap() {
      if (!currentAlcaldia) return;
      
      // Volver a mostrar la vista de la alcaldía completa
      const estadoNombre = refs.entidadSelect.options[refs.entidadSelect.selectedIndex].text;
      updateMapWithQuery(`${currentAlcaldia}, ${estadoNombre}`, refs.largeMapFrame, refs.loadingLargeMap, 13);
      
      // Ocultar botón de volver
      refs.backToAlcaldiaBtn.style.display = 'none';
      
      // Quitar selección de fila
      const selectedRow = refs.coloniasTableBody.querySelector('.selected');
      if (selectedRow) selectedRow.classList.remove('selected');
    }

    async function populateEntidades() {
      const entidades = await fetchEntidades();
      refs.entidadSelect.innerHTML = '<option value="">Seleccione una entidad</option>';
      
      entidades.forEach(entidad => {
        const option = document.createElement('option');
        option.value = entidad.id_estado;
        option.textContent = entidad.estado_nombre;
        refs.entidadSelect.appendChild(option);
      });
    }

    refs.searchInput.addEventListener('input', handleSearchInput);
    refs.searchInput.addEventListener('keypress', async (e) => {
      if (e.key === 'Enter') {
        const query = refs.searchInput.value.trim();
        if (query) {
          const colonias = await fetchColonias(query);
          if (colonias.length > 0) {
            await selectColonia(colonias[0]);
          } else {
            updateMapWithQuery(`${query}, Ciudad de México`, refs.mapFrame, refs.loadingMap);
          }
        }
      }
    });
    
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.search-wrapper')) {
        refs.suggestionsDropdown.style.display = 'none';
      }
    });

    refs.amenitiesBtn.addEventListener('click', showAmenities);
    refs.closeModal.addEventListener('click', closeModal);
    refs.amenitiesModal.addEventListener('click', (e) => {
      if (e.target === refs.amenitiesModal) closeModal();
    });

    refs.specificTab.addEventListener('click', toggleTab);
    refs.generalTab.addEventListener('click', toggleTab);
    refs.entidadSelect.addEventListener('change', handleEntidadChange);
    refs.alcaldiaSelect.addEventListener('change', handleAlcaldiaChange);
    refs.filterButton.addEventListener('click', handleFilterButton);
    refs.backToAlcaldiaBtn.addEventListener('click', showAlcaldiaOnMap);

    async function init() {
      refs.loadingMap.style.display = 'none';
      refs.mapFrame.style.opacity = '1';
      refs.loadingLargeMap.style.display = 'none';
      refs.largeMapFrame.style.opacity = '1';
      
      // Inicializar el mapa específico
      updateMapWithQuery('Paseo de las Palmas 215, Ciudad de México', refs.mapFrame, refs.loadingMap, 15);
      
      // Inicializar el mapa general con toda la CDMX
      updateMapWithQuery('Ciudad de México', refs.largeMapFrame, refs.loadingLargeMap, 11);
      
      // Cargar entidades al iniciar
      await populateEntidades();
      
      // Activar pestaña Específica por defecto
      refs.specificTab.click();
      
      // Seleccionar Ciudad de México por defecto
      const entidadOption = Array.from(refs.entidadSelect.options).find(opt => opt.textContent === 'Ciudad de México');
      if (entidadOption) {
        refs.entidadSelect.value = entidadOption.value;
        handleEntidadChange.call(refs.entidadSelect);
      }
    }

    init();
  </script>
</body>
</html>