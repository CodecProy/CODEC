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
      line-height: 1.5;
      position: relative;
      z-index: 0;
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem 2.5rem;
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
      width: 140px;
      height: auto;
      background-color: var(--white);
      padding: 0.75rem;
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
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--primary-light);
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
      letter-spacing: 0.5px;
    }

    .brand-subtitle {
      font-size: 0.75rem;
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
      border-radius: var(--border-radius);
      cursor: pointer;
      font-size: 0.875rem;
      font-weight: 600;
      text-decoration: none;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
      position: relative;
      overflow: visible;
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
      transition: left 0.5s ease;
    }

    .nav-button:hover::before {
      left: 100%;
    }

    .nav-button:hover {
      background: linear-gradient(135deg, var(--primary-light), var(--accent));
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
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
      height: 2px;
      background: var(--primary-light);
      border-radius: 2px;
    }

    .nav-button.logout {
      background: linear-gradient(135deg, #d32f2f, #f44336);
    }

    .nav-button.logout:hover {
      background: linear-gradient(135deg, #f44336, #ff5722);
      box-shadow: var(--shadow-md);
    }

    .nav-separator {
      width: 1px;
      height: 1.5rem;
      background: linear-gradient(180deg, 
        transparent 0%, 
        var(--accent) 50%, 
        transparent 100%);
      margin: 0 0.5rem;
    }

    .main-container {
      max-width: 1400px;
      margin: 2rem auto;
      padding: 0 1.5rem;
      position: relative;
      z-index: 2;
      background-color: rgba(13, 29, 31, 0.95);
      backdrop-filter: blur(8px);
      border-radius: 12px;
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
      margin-top: 1.5rem;
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
      min-height: 500px;
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

    @media (min-width: 768px) {
      .filters-row {
        justify-content: flex-start;
      }
      .filters-actions {
        margin-top: 0;
      }
      .filter-button {
        margin-left: auto;
      }
    }

    @media (max-width: 767px) {
      .filters-row {
        flex-direction: column;
      }
      .filters-actions {
        justify-content: stretch;
      }
      .filter-button {
        width: 100%;
      }
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
    }

    .info-panel {
      flex: 1;
      max-width: 400px;
    }

    .location-header {
      margin-bottom: 2rem;
    }

    .location-title {
      font-size: 2rem;
      font-weight: 700;
      margin: 0 0 0.5rem 0;
      color: var(--primary-light);
      line-height: 1.2;
    }

    .location-subtitle {
      font-size: 1.125rem;
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
      margin: 2rem 0;
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
      margin-top: 2rem;
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
    }

    .map-container {
      flex: 1;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow-md);
      background-color: var(--gray-light);
      min-height: 400px;
      position: relative;
      z-index: 0;
    }

    #map {
      width: 100%;
      height: 100%;
      border: none;
      transition: opacity 0.3s ease;
      position: relative;
      z-index: 0;
    }

    #largeMap {
      width: 100%;
      height: 100%;
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
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      max-width: 1200px;
      margin: 0 auto;
    }

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
    }

    .source-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
      border-color: var(--primary-light);
    }

    .source-logo {
      width: 60px;
      height: 60px;
      margin: 0 auto 1rem;
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--white);
      font-weight: 700;
      font-size: 1.25rem;
      box-shadow: var(--shadow-sm);
    }

    .source-name {
      font-size: 1.125rem;
      font-weight: 600;
      color: var(--primary-light);
      margin: 0 0 0.5rem 0;
    }

    .source-price {
      font-size: 0.875rem;
      color: var(--gray-light);
      margin: 0;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.6px;
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

    /* Nuevos estilos para contenedor de colonias */
    .colonias-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 20px;
      width: 100%;
    }

    .categoria-card {
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .categoria-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .card-header {
      padding: 18px 20px;
      color: white;
      font-weight: 600;
      font-size: 1.1rem;
    }

    .caliente .card-header {
      background: linear-gradient(to right, #ff416c, #ff4b2b);
    }

    .tibia .card-header {
      background: linear-gradient(to right, #36d1dc, #5b86e5);
    }

    .fria .card-header {
      background: linear-gradient(to right, #8e9eab, #eef2f3);
    }

    .card-body {
      padding: 20px;
    }

    .colonia-item {
      padding: 10px 0;
      border-bottom: 1px solid #eee;
      display: flex;
      align-items: center;
    }

    .colonia-item:last-child {
      border-bottom: none;
    }

    .colonia-item::before {
      content: "•";
      color: #3498db;
      font-weight: bold;
      margin-right: 10px;
    }

    .legend {
      background: white;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      position: absolute;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
      max-width: 220px;
    }

    .legend h4 {
      margin-bottom: 10px;
      color: #2c3e50;
      font-size: 1.1rem;
    }

    .legend-item {
      display: flex;
      align-items: center;
      margin-bottom: 8px;
    }

    .legend-color {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      margin-right: 10px;
    }

    .caliente-color {
      background: linear-gradient(to right, #ff416c, #ff4b2b);
    }

    .tibia-color {
      background: linear-gradient(to right, #36d1dc, #5b86e5);
    }

    .fria-color {
      background: linear-gradient(to right, #8e9eab, #eef2f3);
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
        font-size: 1.75rem;
      }

      .price-value {
        font-size: 2rem;
      }
    }

    @media (max-width: 480px) {
      .sources-grid {
        grid-template-columns: 1fr;
      }

      .location-title {
        font-size: 1.5rem;
      }

      .price-value {
        font-size: 1.75rem;
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
      <a href="hon.html" class="nav-button active">
        <span>HONORARIOS</span>
        <div class="page-indicator"></div>
      </a>
      <div class="nav-separator"></div>
      <a href="menu.html" class="nav-button">
        <span>Menú</span>
      </a>
      <div class="nav-separator"></div>
      <a href="index.html" class="nav-button logout">
        <span>Cerrar sesión</span>
      </a>
    </nav>
  </header>

  <main class="main-container">
    <div class="tabs-container">
      <nav class="tabs-nav">
        <button class="tab-button active" id="specificTab">ESPECÍFICA</button>
        <button class="tab-button" id="generalTab">GENERAL</button>
      </nav>
      <div class="filters-container" id="filtersContainer">
        <div class="filters-row">
          <div class="filter-group">
            <label class="filter-label" for="entidadSelect">Entidad</label>
            <select class="filter-select" id="entidadSelect" aria-label="Seleccione una entidad">
              <option value="">Seleccione una entidad</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label" for="alcaldiaSelect">Alcaldía</label>
            <select class="filter-select" id="alcaldiaSelect" aria-label="Seleccione una alcaldía" disabled>
              <option value="">Seleccione una alcaldía</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="filter-label" for="tipoSelect">Tipo</label>
            <select class="filter-select" id="tipoSelect" aria-label="Seleccione un tipo" disabled>
              <option value="">Seleccione un tipo</option>
              <option value="Caliente">Caliente</option>
              <option value="Tibia">Tibia</option>
              <option value="Fria">Fría</option>
            </select>
          </div>
          <button class="filter-button" id="filterButton">
            <span>FILTRAR</span>
          </button>
        </div>
      </div>
      <div class="search-section" id="searchSection">
        <div class="search-wrapper">
          <div class="search-container">
            <label class="filter-label" for="searchInput">Buscar colonia o alcaldía</label>
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
          <div class="amenities-section">
            <button class="amenities-button" id="amenitiesBtn" aria-label="Ver amenidades">AMENIDADES</button>
          </div>
          <div class="price-section">
            <p class="price-label">Precio por m²</p>
            <p class="price-value" id="precioValue">$--</p>
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
        
        <!-- Nuevo contenedor para colonias -->
        <div class="colonias-container" id="coloniasContainer">
          <!-- Las colonias se mostrarán aquí -->
        </div>
        
        <!-- Leyenda de categorías -->
        <div class="legend">
          <h4>Leyenda de Categorías</h4>
          <div class="legend-item">
            <div class="legend-color caliente-color"></div>
            <span>Caliente</span>
          </div>
          <div class="legend-item">
            <div class="legend-color tibia-color"></div>
            <span>Tibia</span>
          </div>
          <div class="legend-item">
            <div class="legend-color fria-color"></div>
            <span>Fría</span>
          </div>
        </div>
      </div>
      
      <section class="sources-section">
        <h2 class="section-title">Fuentes de Información</h2>
        <div class="sources-grid" id="sourcesGrid">
          <div class="source-card" data-source="propiedades">
            <div class="source-logo">P</div>
            <div class="source-content">
              <h3 class="source-name">Propiedades.com</h3>
              <p class="source-price">$--</p>
            </div>
          </div>
          <div class="source-card" data-source="heyhome">
            <div class="source-logo">H</div>
            <div class="source-content">
              <h3 class="source-name">Hey Home</h3>
              <p class="source-price">$--</p>
            </div>
          </div>
          <div class="source-card" data-source="clau">
            <div class="source-logo">C</div>
            <div class="source-content">
              <h3 class="source-name">Clau</h3>
              <p class="source-price">$--</p>
            </div>
          </div>
          <div class="source-card" data-source="mudafy">
            <div class="source-logo">M</div>
            <div class="source-content">
              <h3 class="source-name">Mudafy</h3>
              <p class="source-price">$--</p>
            </div>
          </div>
          <div class="source-card" data-source="altaltium">
            <div class="source-logo">A</div>
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
        <h3 class="modal-title">Amenidades de la Zona</h3>
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
      coloniasContainer: document.getElementById('coloniasContainer')
    };

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
        { source: 'propiedades', price: colonia.colonia_prom_prop, name: 'Propiedades.com' },
        { source: 'heyhome', price: colonia.colonia_prom_hey, name: 'Hey Home' },
        { source: 'clau', price: colonia.colonia_prom_clau, name: 'Clau' },
        { source: 'mudafy', price: colonia.colonia_prom_mud, name: 'Mudafy' },
        { source: 'altaltium', price: colonia.colonia_prom_altal, name: 'Altaltium' }
      ];
      
      refs.sourcesGrid.innerHTML = '';
      
      sources.forEach(item => {
        if (item.price && item.price > 0) {
          const card = document.createElement('div');
          card.className = 'source-card';
          card.setAttribute('data-source', item.source);
          card.innerHTML = `
            <div class="source-logo">${item.name.charAt(0)}</div>
            <div class="source-content">
              <h3 class="source-name">${item.name}</h3>
              <p class="source-price">${formatPrice(item.price)}</p>
            </div>
          `;
          refs.sourcesGrid.appendChild(card);
        }
      });
    }

    function updateMapWithQuery(query, mapElement, loadingElement) {
      loadingElement.style.display = 'flex';
      mapElement.style.opacity = '0.5';
      
      const encodedQuery = encodeURIComponent(query);
      const mapUrl = `https://maps.google.com/maps?q=${encodedQuery}&output=embed`;
      
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

      // Actualizar el mapa grande con la alcaldía
      updateMapWithQuery(`${alcaldiaNombre}, ${estadoNombre}`, refs.largeMapFrame, refs.loadingLargeMap);

      // Obtener las colonias filtradas
      const colonias = await fetchColoniasPorFiltros(entidadId, alcaldiaId, tipo);
      if (colonias.length > 0) {
        // Mostrar colonias en el contenedor
        showColonias(colonias);
      } else {
        refs.coloniasContainer.innerHTML = '<p>No se encontraron colonias con los filtros seleccionados.</p>';
      }
    }

    function showColonias(colonias) {
      const container = refs.coloniasContainer;
      container.innerHTML = '';
      
      // Agrupar colonias por categoría
      const coloniasPorCategoria = {};
      colonias.forEach(colonia => {
        if (!coloniasPorCategoria[colonia.categoria]) {
          coloniasPorCategoria[colonia.categoria] = [];
        }
        coloniasPorCategoria[colonia.categoria].push(colonia);
      });
      
      // Crear tarjetas por categoría
      Object.entries(coloniasPorCategoria).forEach(([categoria, colonias]) => {
        const card = document.createElement('div');
        card.className = `categoria-card ${categoria.toLowerCase()}`;
        
        const header = document.createElement('div');
        header.className = 'card-header';
        header.textContent = `Categoría: ${categoria}`;
        
        const body = document.createElement('div');
        body.className = 'card-body';
        
        colonias.forEach(colonia => {
          const item = document.createElement('div');
          item.className = 'colonia-item';
          item.textContent = colonia.colonia_nombre;
          body.appendChild(item);
        });
        
        card.appendChild(header);
        card.appendChild(body);
        container.appendChild(card);
      });
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

    async function init() {
      refs.loadingMap.style.display = 'none';
      refs.mapFrame.style.opacity = '1';
      refs.loadingLargeMap.style.display = 'none';
      refs.largeMapFrame.style.opacity = '1';
      
      // Inicializar el mapa específico
      updateMapWithQuery('Paseo de las Palmas 215, Ciudad de México', refs.mapFrame, refs.loadingMap);
      
      // Inicializar el mapa general con toda la CDMX
      updateMapWithQuery('Ciudad de México', refs.largeMapFrame, refs.loadingLargeMap);
      
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