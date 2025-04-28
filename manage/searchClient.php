<?php
session_start();
include '../partials/header.php';
require '../config/db.php';

// Force dark mode
echo '<script>document.documentElement.setAttribute("data-bs-theme", "dark");</script>';

$searchResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
  $searchTerm = '%' . $_POST['search_term'] . '%';
  $therapist_id = $_SESSION['user_id'];
  
  $search_query = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone 
                  FROM users u 
                  JOIN therapist_clients tc ON u.id = tc.client_id 
                  WHERE tc.therapist_id = ? 
                  AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
  
  $stmt = $conn->prepare($search_query);
  $stmt->bind_param("issss", $therapist_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
  $stmt->execute();
  $searchResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<div class="container-fluid">
  <div class="row">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Rechercher un patient</h1>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <form method="POST" class="row g-3">
            <div class="col-md-8">
              <div class="input-group">
                <input type="text" name="search_term" class="form-control" 
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

      <?php if (!empty($searchResults)): ?>
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Résultats de la recherche</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($searchResults as $client): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></td>
                      <td><?php echo htmlspecialchars($client['email']); ?></td>
                      <td><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></td>
                      <td>
                        <a href="bookFor.php?client_id=<?php echo $client['id']; ?>" 
                           class="btn btn-sm btn-primary">
                          <i class="bi bi-calendar-plus"></i> Créer un rendez-vous
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i> Aucun patient trouvé correspondant à votre recherche.
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<?php include '../partials/footer.php'; ?>
