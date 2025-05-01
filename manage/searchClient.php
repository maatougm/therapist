<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    header('Location: ' . url('login.php'));
    exit;
}

// Force dark mode
echo '<script>document.documentElement.setAttribute("data-bs-theme", "dark");</script>';

$searchResults = [];
$searchPerformed = false;
$error = null;
$debugInfo = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = trim($_POST['search_term']);
    $searchPerformed = true;
    
    // Debug session information
    $debugInfo['session'] = [
        'loggedin' => isset($_SESSION['loggedin']),
        'role' => $_SESSION['role'] ?? 'not set',
        'id' => $_SESSION['id'] ?? 'not set'
    ];
    
    // Check if therapist_id is set in session
    if (!isset($_SESSION['id'])) {
        $error = "Session invalide. Veuillez vous reconnecter.";
        $debugInfo['error'] = "Missing therapist_id in session";
    } else {
        $therapist_id = $_SESSION['id'];
        
        try {
            // Debug database connection
            $debugInfo['db_connection'] = [
                'status' => $pdo ? 'connected' : 'not connected',
                'error' => $pdo ? null : 'PDO connection failed'
            ];
            
            if (empty($searchTerm)) {
                // If search term is empty, show all clients
                $search_query = "SELECT u.id, u.name, u.email, u.phone 
                                FROM users u 
                                WHERE u.role = 'client'
                                ORDER BY u.name";
                
                $debugInfo['query'] = [
                    'type' => 'empty_search',
                    'sql' => $search_query,
                    'params' => []
                ];
                
                $stmt = $pdo->prepare($search_query);
                if (!$stmt) {
                    throw new PDOException("Erreur de préparation de la requête");
                }
                $stmt->execute();
            } else {
                // If search term is not empty, perform the search
                $searchPattern = '%' . $searchTerm . '%';
                $search_query = "SELECT u.id, u.name, u.email, u.phone 
                                FROM users u 
                                WHERE u.role = 'client'
                                AND (
                                    LOWER(u.name) LIKE LOWER(?) 
                                    OR LOWER(u.email) LIKE LOWER(?) 
                                    OR u.phone LIKE ?
                                )
                                ORDER BY u.name";
                
                $debugInfo['query'] = [
                    'type' => 'search',
                    'sql' => $search_query,
                    'params' => [$searchPattern, $searchPattern, $searchPattern]
                ];
                
                $stmt = $pdo->prepare($search_query);
                if (!$stmt) {
                    throw new PDOException("Erreur de préparation de la requête");
                }
                $stmt->execute([$searchPattern, $searchPattern, $searchPattern]);
            }
            
            $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $debugInfo['results'] = [
                'count' => count($searchResults),
                'first_result' => $searchResults[0] ?? null
            ];
            
            // Log successful search
            error_log("Search successful for therapist_id: " . $therapist_id . ", term: " . $searchTerm);
            
        } catch (PDOException $e) {
            $debugInfo['error'] = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ];
            
            error_log("Search error: " . $e->getMessage());
            error_log("SQL Query: " . $search_query);
            error_log("Parameters: " . print_r([$therapist_id, $searchPattern ?? null], true));
            error_log("Debug Info: " . print_r($debugInfo, true));
            
            $error = "Une erreur est survenue lors de la recherche. Détails: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Rechercher un patient</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $error; ?>
                </div>
                
                <!-- Debug Information (only shown if there's an error) -->
                <div class="card mt-3">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Informations de débogage</h5>
                    </div>
                    <div class="card-body">
                        <pre class="bg-light p-3 rounded"><?php 
                            echo "Session Information:\n";
                            print_r($debugInfo['session'] ?? 'No session info');
                            echo "\n\nDatabase Information:\n";
                            print_r($debugInfo['db_connection'] ?? 'No DB info');
                            echo "\n\nQuery Information:\n";
                            print_r($debugInfo['query'] ?? 'No query info');
                            echo "\n\nError Information:\n";
                            print_r($debugInfo['error'] ?? 'No error info');
                        ?></pre>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" name="search_term" id="searchInput" class="form-control" 
                                       placeholder="Rechercher par nom, email ou téléphone..." 
                                       value="<?php echo isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : ''; ?>">
                                <button class="btn btn-primary" type="submit" name="search">
                                    <i class="bi bi-search"></i> Rechercher
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div id="searchResults">
                <?php if ($searchPerformed): ?>
                    <?php if (!empty($searchResults)): ?>
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID Client</th>
                                                <th>Nom</th>
                                                <th>Email</th>
                                                <th>Téléphone</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($searchResults as $result): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($result['id'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($result['name'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($result['email'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($result['phone'] ?? ''); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-info" 
                                                                onclick="showClientInfo(<?php echo $result['id']; ?>)">
                                                            <i class="bi bi-info-circle"></i> Détails
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Aucun patient trouvé pour cette recherche.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Client Info Modal -->
            <div class="modal fade" id="clientInfoModal" tabindex="-1" aria-labelledby="clientInfoModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="clientInfoModalLabel">Informations du patient</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="clientInfoContent">
                                <!-- Client info will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let searchTimeout;

document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const searchTerm = this.value;
    
    // Show loading state
    document.getElementById('searchResults').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    // Debounce the search to avoid too many requests
    searchTimeout = setTimeout(() => {
        fetch('searchClient.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'search_term=' + encodeURIComponent(searchTerm) + '&search=1'
        })
        .then(response => response.text())
        .then(html => {
            // Extract the results table from the response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const resultsTable = doc.querySelector('#searchResults');
            if (resultsTable) {
                document.getElementById('searchResults').innerHTML = resultsTable.innerHTML;
            }
        })
        .catch(error => {
            document.getElementById('searchResults').innerHTML = '<div class="alert alert-danger">Erreur lors de la recherche</div>';
            console.error('Error:', error);
        });
    }, 300); // 300ms delay
});

function showClientInfo(clientId) {
    const modal = new bootstrap.Modal(document.getElementById('clientInfoModal'));
    const clientInfoContent = document.getElementById('clientInfoContent');
    
    // Show loading state
    clientInfoContent.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.show();
    
    // Fetch client info
    fetch(`../controllers/getClientInfo.php?client_id=${clientId}`)
        .then(response => response.text())
        .then(html => {
            // Parse the HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Find all "Voir le rapport" buttons and replace them with the actual report content
            const buttons = doc.querySelectorAll('button[onclick^="showReport"]');
            buttons.forEach(button => {
                const appointmentId = button.getAttribute('onclick').match(/\d+/)[0];
                const row = button.closest('tr');
                const reportCell = row.querySelector('td:last-child');
                
                // Fetch the report content
                fetch(`../controllers/getReport.php?appointment_id=${appointmentId}`)
                    .then(response => response.text())
                    .then(reportHtml => {
                        reportCell.innerHTML = reportHtml;
                    })
                    .catch(error => {
                        reportCell.innerHTML = '<span class="text-danger">Erreur lors du chargement du rapport</span>';
                        console.error('Error:', error);
                    });
            });
            
            // Update the modal content
            clientInfoContent.innerHTML = doc.body.innerHTML;
        })
        .catch(error => {
            clientInfoContent.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des informations</div>';
            console.error('Error:', error);
        });
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
