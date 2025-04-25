<footer class="footer mt-auto py-4 bg-secondary"> <!-- Changed to bg-secondary -->
    <div class="container-fluid px-5"> <!-- Changed to container-fluid and added px-5 -->
        <!-- Keep existing footer content the same -->
    </div>
</footer>

<style>
/* Remove any conflicting padding */
body {
    padding-right: 0 !important;
    padding-left: 0 !important;
    overflow-x: hidden;
}

.footer {
    width: 100vw;
    position: relative;
    left: 50%;
    right: 50%;
    margin-left: -50vw;
    margin-right: -50vw;
    border-top: 3px solid var(--primary);
    background: var(--secondary) !important;
}

/* Ensure content alignment */
.container-fluid {
    padding-right: var(--bs-gutter-x, 1.5rem);
    padding-left: var(--bs-gutter-x, 1.5rem);
}
</style>