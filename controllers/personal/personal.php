<?php
require_once '../../database/conexion.php'; // $pdo listo

// Traer datos con JOIN
$sql = "SELECT p.id, p.nombre, p.cedula, p.telefono, c.nombre AS cargo
        FROM personal p
        LEFT JOIN p_cargo c ON p.cargo_id = c.id
        ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$personal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener cargos únicos para el filtro
$sql_cargos = "SELECT DISTINCT nombre FROM p_cargo ORDER BY nombre";
$stmt_cargos = $pdo->prepare($sql_cargos);
$stmt_cargos->execute();
$cargos = $stmt_cargos->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Profesionales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --border: #dee2e6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fb 0%, #e3e8f5 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
        }

        .header h1 {
            color: var(--primary);
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px 25px;
        }

        .card-header h2 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-filter-container {
            padding: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            border-bottom: 1px solid var(--border);
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--light);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background-color: white;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .filter-select {
            padding: 14px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--light);
            min-width: 200px;
            transition: var(--transition);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background-color: white;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #f1f5fd;
        }

        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--primary);
            border-bottom: 2px solid var(--border);
            position: relative;
            cursor: pointer;
            user-select: none;
            transition: var(--transition);
        }

        th:hover {
            background-color: #e6edfc;
        }

        th i {
            margin-left: 5px;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: var(--transition);
        }

        tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        td {
            padding: 18px 15px;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .badge-success {
            background-color: rgba(114, 9, 183, 0.1);
            color: var(--secondary);
        }

        .badge-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .badge-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #d1d5e0;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .stats {
            display: flex;
            justify-content: space-between;
            padding: 15px 25px;
            background-color: #f8f9fa;
            border-top: 1px solid var(--border);
            color: var(--gray);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .search-filter-container {
                flex-direction: column;
            }
            
            .search-box, .filter-select {
                min-width: 100%;
            }
            
            th, td {
                padding: 12px 8px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .card-header h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Listado de Profesionales</h1>
            <p>Gestiona y visualiza la información de todos los profesionales</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-table"></i> Tabla de Profesionales</h2>
            </div>

            <div class="search-filter-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar por nombre, cédula o teléfono...">
                </div>
                <select class="filter-select" id="cargoFilter">
                    <option value="">Todos los cargos</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?= htmlspecialchars($cargo) ?>"><?= htmlspecialchars($cargo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="table-container">
                <table id="professionalsTable">
                    <thead>
                        <tr>
                            <th data-sort="id">ID <i class="fas fa-sort"></i></th>
                            <th data-sort="nombre">Nombre <i class="fas fa-sort"></i></th>
                            <th data-sort="cedula">Cédula <i class="fas fa-sort"></i></th>
                            <th data-sort="telefono">Teléfono <i class="fas fa-sort"></i></th>
                            <th data-sort="cargo">Cargo <i class="fas fa-sort"></i></th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (empty($personal)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fas fa-users-slash"></i>
                                        <h3>No hay profesionales registrados</h3>
                                        <p>No se encontraron profesionales en la base de datos</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($personal as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['id']) ?></td>
                                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                                    <td><?= htmlspecialchars($p['cedula']) ?></td>
                                    <td><?= htmlspecialchars($p['telefono']) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'badge-primary';
                                        if ($p['cargo'] === 'Enfermero') $badge_class = 'badge-success';
                                        elseif ($p['cargo'] === 'Administrativo') $badge_class = 'badge-warning';
                                        elseif ($p['cargo'] === 'Técnico') $badge_class = 'badge-info';
                                        ?>
                                        <span class="badge <?= $badge_class ?>">
                                            <?= htmlspecialchars($p['cargo'] ?? 'Sin cargo') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="stats">
                <span id="resultCount">
                    <?php if (!empty($personal)): ?>
                        Mostrando <?= count($personal) ?> de <?= count($personal) ?> profesionales
                    <?php else: ?>
                        No hay profesionales para mostrar
                    <?php endif; ?>
                </span>
                <span>Lista actualizada</span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.getElementById('tableBody');
            const rows = Array.from(tableBody.querySelectorAll('tr'));
            const searchInput = document.getElementById('searchInput');
            const cargoFilter = document.getElementById('cargoFilter');
            const resultCount = document.getElementById('resultCount');
            
            let currentSort = { column: 'id', direction: 'desc' };
            
            // Función para filtrar y ordenar filas
            function updateTable() {
                let filteredRows = rows.filter(row => {
                    // Saltar la fila de estado vacío
                    if (row.querySelector('.empty-state')) return false;
                    
                    const text = row.textContent.toLowerCase();
                    const searchTerm = searchInput.value.toLowerCase();
                    const cargo = row.cells[4].textContent.trim();
                    const selectedCargo = cargoFilter.value;
                    
                    return text.includes(searchTerm) && 
                           (selectedCargo === '' || cargo === selectedCargo);
                });
                
                // Ordenar filas
                filteredRows.sort((a, b) => {
                    const aValue = a.cells[getColumnIndex(currentSort.column)].textContent;
                    const bValue = b.cells[getColumnIndex(currentSort.column)].textContent;
                    
                    if (currentSort.column === 'id') {
                        return currentSort.direction === 'asc' 
                            ? parseInt(aValue) - parseInt(bValue)
                            : parseInt(bValue) - parseInt(aValue);
                    } else {
                        return currentSort.direction === 'asc'
                            ? aValue.localeCompare(bValue)
                            : bValue.localeCompare(aValue);
                    }
                });
                
                // Actualizar tabla
                tableBody.innerHTML = '';
                if (filteredRows.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-search"></i>
                                    <h3>No se encontraron profesionales</h3>
                                    <p>Intenta ajustar los términos de búsqueda o el filtro de cargo</p>
                                </div>
                            </td>
                        </tr>
                    `;
                } else {
                    filteredRows.forEach(row => tableBody.appendChild(row));
                }
                
                // Actualizar contador de resultados
                const totalRows = rows.filter(row => !row.querySelector('.empty-state')).length;
                resultCount.textContent = `Mostrando ${filteredRows.length} de ${totalRows} profesionales`;
            }
            
            function getColumnIndex(columnName) {
                const headers = Array.from(document.querySelectorAll('th'));
                return headers.findIndex(header => header.dataset.sort === columnName);
            }
            
            // Eventos de búsqueda y filtro
            searchInput.addEventListener('input', updateTable);
            cargoFilter.addEventListener('change', updateTable);
            
            // Eventos de ordenamiento
            document.querySelectorAll('th[data-sort]').forEach(header => {
                header.addEventListener('click', () => {
                    const column = header.dataset.sort;
                    
                    if (currentSort.column === column) {
                        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentSort.column = column;
                        currentSort.direction = 'asc';
                    }
                    
                    // Actualizar iconos de ordenamiento
                    document.querySelectorAll('th i').forEach(icon => {
                        icon.className = 'fas fa-sort';
                    });
                    
                    header.querySelector('i').className = 
                        currentSort.direction === 'asc' 
                            ? 'fas fa-sort-up' 
                            : 'fas fa-sort-down';
                    
                    updateTable();
                });
            });
            
            // Inicializar tabla solo si hay datos
            if (rows.length > 0 && !rows[0].querySelector('.empty-state')) {
                updateTable();
            }
        });
    </script>
</body>
</html>