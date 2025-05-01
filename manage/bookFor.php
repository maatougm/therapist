<?php
/**
 * Appointment Booking Page
 * 
 * Allows therapists to create new appointments for clients.
 * Includes form validation, client selection, and appointment scheduling.
 */

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is either admin or kine
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'therapist'])) {
    header('Location: ' . url('login.php'));
    exit;
}

$therapist_id = $_SESSION['id'];
$selected_date = $_GET['date'] ?? date('Y-m-d');

try {
    /**
     * Fetch Available Clients
     * 
     * Retrieves all clients (users with role 'client') for the dropdown selection
     */
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM users 
        WHERE role = 'client' 
        ORDER BY name
    ");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /**
     * Fetch Available Locations
     * 
     * Retrieves all locations for the dropdown selection
     */
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM locations 
        ORDER BY name
    ");
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /**
     * Fetch Appointments for Selected Date
     * 
     * Retrieves appointments for the current therapist:
     * - Today's appointments
     * - Upcoming appointments (next 7 days)
     */
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as client_name, l.name as location_name
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN locations l ON a.location_id = l.id
        WHERE a.therapist_id = ? AND a.date = ?
        ORDER BY a.hour
    ");
    $stmt->execute([$therapist_id, $selected_date]);
    $day_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log error and display error message
    error_log("Error fetching data: " . $e->getMessage());
    $error = "Une erreur est survenue lors du chargement des données";
}

// Force dark mode
echo '<script>document.documentElement.setAttribute("data-bs-theme", "dark");</script>';

// Include header
include __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <?php include '../partials/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Créer un rendez-vous</h1>    
            </div>

            <!-- Appointment Creation Form -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <form id="appointmentForm" action="controllers/appointmentController.php" method="POST">
                                <!-- Client Selection with Search -->
                                <div class="mb-3 position-relative">
                                    <label for="clientSearch" class="form-label">Rechercher un patient</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="clientSearch" 
                                               placeholder="Tapez le nom du patient..." autocomplete="off">
                                        <span class="input-group-text">
                                            <i class="bi bi-search"></i>
                                        </span>
                                    </div>
                                    <input type="hidden" id="client_id" name="client_id" required>
                                    <div id="clientSuggestions" class="list-group mt-1" style="display: none;"></div>
                                </div>

                                <!-- Location Selection -->
                                <div class="mb-3">
                                    <label for="location_id" class="form-label">Lieu</label>
                                    <select class="form-select" id="location_id" name="location_id" required>
                                        <option value="">Sélectionner un lieu</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?php echo $location['id']; ?>">
                                                <?php echo htmlspecialchars($location['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Date Selection -->
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" required 
                                           min="<?php echo date('Y-m-d'); ?>" value="<?php echo $selected_date; ?>">
                                </div>

                                <!-- Time Selection -->
                                <div class="mb-3">
                                    <label for="hour" class="form-label">Heure</label>
                                    <input type="time" class="form-control" id="hour" name="hour" required 
                                           min="08:00" max="20:00" step="1800">
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-primary">Créer le rendez-vous</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Selected Day's Appointments -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Rendez-vous du <?php echo date('d/m/Y', strtotime($selected_date)); ?></h5>
                            <?php if (!empty($day_appointments)): ?>
                                <div class="list-group">
                                    <?php foreach ($day_appointments as $appointment): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($appointment['client_name']); ?></h6>
                                                <small><?php echo htmlspecialchars($appointment['hour']); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($appointment['location_name']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Aucun rendez-vous ce jour</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Form Validation and Client Search Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('appointmentForm');
    const dateInput = document.getElementById('date');
    const clientSearch = document.getElementById('clientSearch');
    const clientIdInput = document.getElementById('client_id');
    const clientSuggestions = document.getElementById('clientSuggestions');
    let clients = <?php echo json_encode($clients); ?>;
    let selectedClient = null;

    // Update appointments when date changes
    dateInput.addEventListener('change', function() {
        window.location.href = '?date=' + this.value;
    });

    // Client search functionality
    clientSearch.addEventListener('input', function() {
        const searchText = this.value.toLowerCase().trim();
        let suggestions = [];

        if (searchText.length === 0) {
            suggestions = clients;
        } else {
            suggestions = clients.filter(client => 
                client.name.toLowerCase().includes(searchText)
            );
        }

        if (suggestions.length > 0) {
            clientSuggestions.innerHTML = suggestions.map(client => `
                <a href="#" class="list-group-item list-group-item-action" 
                   data-id="${client.id}" 
                   data-name="${client.name}">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-circle me-2"></i>
                        <span>${client.name}</span>
                    </div>
                </a>
            `).join('');
            clientSuggestions.style.display = 'block';
        } else {
            clientSuggestions.innerHTML = `
                <div class="list-group-item text-muted">
                    Aucun patient trouvé
                </div>
            `;
            clientSuggestions.style.display = 'block';
        }
    });

    // Handle suggestion click
    clientSuggestions.addEventListener('click', function(e) {
        e.preventDefault();
        const item = e.target.closest('.list-group-item');
        if (item) {
            const clientId = item.getAttribute('data-id');
            const clientName = item.getAttribute('data-name');
            clientSearch.value = clientName;
            clientIdInput.value = clientId;
            selectedClient = { id: clientId, name: clientName };
            clientSuggestions.style.display = 'none';
        }
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!clientSearch.contains(e.target) && !clientSuggestions.contains(e.target)) {
            clientSuggestions.style.display = 'none';
        }
    });

    // Clear selection when search is cleared
    clientSearch.addEventListener('blur', function() {
        if (this.value === '' && selectedClient) {
            clientIdInput.value = '';
            selectedClient = null;
        }
    });

    // Form validation
    form.addEventListener('submit', function(e) {
        if (!clientIdInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un patient');
            return;
        }
    });
});
</script>

<style>
#clientSuggestions {
    max-height: 300px;
    overflow-y: auto;
    position: absolute;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 0.25rem;
}

#clientSuggestions .list-group-item {
    cursor: pointer;
    border: none;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 0.75rem 1rem;
}

#clientSuggestions .list-group-item:last-child {
    border-bottom: none;
}

#clientSuggestions .list-group-item:hover {
    background-color: var(--bs-primary);
    color: white;
}

#clientSuggestions .list-group-item i {
    font-size: 1.2rem;
}

.input-group-text {
    background-color: transparent;
    border-left: none;
}

#clientSearch {
    border-right: none;
}

#clientSearch:focus {
    box-shadow: none;
    border-color: #ced4da;
}

#clientSearch:focus + .input-group-text {
    border-color: #ced4da;
}
</style>

<?php include __DIR__ . '/../partials/footer.php'; ?>
