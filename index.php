<?php
// Start session and include required files
session_start();
include 'partials/header.php';
require 'config/db.php';

// Get featured therapists
$stmt = $pdo->query("
    SELECT u.*, COUNT(a.id) as appointment_count 
    FROM users u 
    JOIN appointments a ON u.id = a.user_id 
    WHERE u.role = 'therapist' 
    GROUP BY u.id 
    ORDER BY appointment_count DESC 
    LIMIT 3
");
$featured_therapists = $stmt->fetchAll();
?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden">
    <div class="container">
        <div class="row min-vh-100 align-items-center">
            <div class="col-lg-6">
                <h1 class="display-3 fw-bold text-primary mb-4 animate__animated animate__fadeInUp">
                    Votre bien-être commence ici
                </h1>
                <p class="lead text-muted mb-4 animate__animated animate__fadeInUp animate__delay-1s">
                    Des soins de kinésithérapie personnalisés et professionnels pour votre santé et votre confort.
                </p>
                
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-image position-relative animate__animated animate__fadeIn parallax-element">
                    <img src="assets/images/hero-image.jpg" alt="Kinésithérapie" class="img-fluid rounded-4 shadow-lg">
                    <div class="hero-stats position-absolute bottom-0 start-0 bg-white p-4 rounded-4 shadow-lg floating-card">
                        <div class="row g-4">
                            <div class="col-6">
                                <div class="text-center counter-animation">
                                    <h3 class="text-primary mb-0" data-count="50">0+</h3>
                                    <small class="text-muted">Professionnels</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center counter-animation">
                                    <h3 class="text-primary mb-0" data-count="1000">0+</h3>
                                    <small class="text-muted">Patients satisfaits</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-shape position-absolute top-0 end-0 w-50 h-100 bg-primary-light opacity-10"></div>
</section>

<!-- Services Section -->
<section id="services" class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold text-primary mb-3 animate-on-scroll">Nos Services</h2>
            <p class="lead text-muted animate-on-scroll">Des soins adaptés à vos besoins spécifiques</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="service-card card h-100 border-0 shadow-sm hover-shadow animate-on-scroll">
                    <div class="card-body p-4">
                        <div class="icon-circle bg-primary-light mb-4 rotate-icon">
                            <i class="fas fa-bone text-primary"></i>
                        </div>
                        <h3 class="h5 mb-3">Rééducation</h3>
                        <p class="text-muted mb-0">
                            Traitement des troubles musculo-squelettiques et rééducation fonctionnelle.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card card h-100 border-0 shadow-sm hover-shadow animate-on-scroll">
                    <div class="card-body p-4">
                        <div class="icon-circle bg-success-light mb-4 rotate-icon">
                            <i class="fas fa-running text-success"></i>
                        </div>
                        <h3 class="h5 mb-3">Kinésithérapie Sportive</h3>
                        <p class="text-muted mb-0">
                            Prévention et traitement des blessures liées à la pratique sportive.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card card h-100 border-0 shadow-sm hover-shadow animate-on-scroll">
                    <div class="card-body p-4">
                        <div class="icon-circle bg-info-light mb-4 rotate-icon">
                            <i class="fas fa-baby text-info"></i>
                        </div>
                        <h3 class="h5 mb-3">Kinésithérapie Pédiatrique</h3>
                        <p class="text-muted mb-0">
                            Prise en charge des troubles du développement chez l'enfant.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Therapists Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold text-primary mb-3 animate-on-scroll">Nos Kinésithérapeutes</h2>
            <p class="lead text-muted animate-on-scroll">Des professionnels qualifiés à votre service</p>
        </div>
        <div class="row g-4">
            <?php foreach ($featured_therapists as $therapist): ?>
                <div class="col-md-4">
                    <div class="therapist-card card h-100 border-0 shadow-sm hover-shadow animate-on-scroll">
                        <div class="card-body p-4 text-center">
                            <div class="therapist-avatar mb-3 scale-on-hover">
                                <i class="fas fa-user-md fa-3x text-primary"></i>
                            </div>
                            <h3 class="h5 mb-2"><?= htmlspecialchars($therapist['name']) ?></h3>
                            <p class="text-muted mb-3">
                                <?= $therapist['appointment_count'] ?> rendez-vous effectués
                            </p>
                            <a href="book_appointment.php?therapist_id=<?= $therapist['id'] ?>" 
                               class="btn btn-outline-primary hover-scale">
                                <i class="fas fa-calendar-plus me-2"></i>
                                Prendre RDV
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold text-primary mb-3 animate-on-scroll">Témoignages</h2>
            <p class="lead text-muted animate-on-scroll">Ce que nos patients disent de nous</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card card h-100 border-0 shadow-sm animate-on-scroll">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="testimonial-avatar me-3 scale-on-hover">
                                <i class="fas fa-user fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Marie D.</h5>
                                <small class="text-muted">Patient depuis 2 ans</small>
                            </div>
                        </div>
                        <p class="text-muted mb-0">
                            "Un suivi personnalisé et des résultats rapides. Je recommande vivement !"
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card card h-100 border-0 shadow-sm animate-on-scroll">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="testimonial-avatar me-3 scale-on-hover">
                                <i class="fas fa-user fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Jean P.</h5>
                                <small class="text-muted">Patient depuis 1 an</small>
                            </div>
                        </div>
                        <p class="text-muted mb-0">
                            "Des professionnels à l'écoute et des soins de qualité. Très satisfait !"
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card card h-100 border-0 shadow-sm animate-on-scroll">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="testimonial-avatar me-3 scale-on-hover">
                                <i class="fas fa-user fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Sophie L.</h5>
                                <small class="text-muted">Patient depuis 3 ans</small>
                            </div>
                        </div>
                        <p class="text-muted mb-0">
                            "Une équipe compétente et un accueil chaleureux. Je ne vais nulle part ailleurs !"
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="display-4 fw-bold mb-3 animate-on-scroll">Prêt à prendre soin de votre santé ?</h2>
                <p class="lead mb-0 animate-on-scroll">
                    Réservez votre consultation en ligne en quelques clics.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="book_appointment.php" class="btn btn-light btn-lg px-4 pulse-animation">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Prendre RDV
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'partials/footer.php'; ?>