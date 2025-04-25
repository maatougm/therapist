<div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Délai minimum pour annuler (en heures)</h5>
            <form method="POST" action="controllers/adminController.php">
                <input type="number" name="cancel_limit" class="form-control mb-2" value="<?= $cancelLimit ?>" min="1" max="48">
                <button type="submit" name="update_cancel_limit" class="btn btn-primary">Mettre à jour</button>
            </form>
        </div>
    </div>  