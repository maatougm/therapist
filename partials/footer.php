<footer class="footer mt-auto py-5" style="margin-left: 280px;">
    <div class="container">
        <!-- Newsletter Section -->
        <div class="newsletter-section mb-5">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <h3 class="newsletter-title">
                        <i class="fas fa-envelope-open-text me-2"></i>
                        Restez informé
                    </h3>
                    <p class="newsletter-text">Inscrivez-vous à notre newsletter pour recevoir les dernières actualités et conseils de santé.</p>
                    <form class="newsletter-form">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Votre adresse email" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane"></i>
                                S'inscrire
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- About Section -->
            <div class="col-md-4 mb-4">
                <div class="footer-brand">
                    <i class="fas fa-heartbeat"></i>
                    <span class="ms-2">KeneTherapy</span>
                </div>
                <p class="mt-3">
                    Votre partenaire de confiance pour une rééducation complète et personnalisée. 
                    Notre équipe de kinésithérapeutes expérimentés est là pour vous accompagner 
                    dans votre parcours de guérison.
                </p>
                <div class="social-links mt-3">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-md-2 mb-4">
                <h5 class="footer-title">Liens Rapides</h5>
                <ul class="footer-links">
                    <li><a href="/pfaa/index.php"><i class="fas fa-home"></i> Accueil</a></li>
                    <li><a href="/pfaa/services.php"><i class="fas fa-hand-holding-medical"></i> Services</a></li>
                    <li><a href="/pfaa/therapists.php"><i class="fas fa-user-md"></i> Thérapeutes</a></li>
                    <li><a href="/pfaa/contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="col-md-3 mb-4">
                <h5 class="footer-title">Nos Services</h5>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-brain"></i> Rééducation Neurologique</a></li>
                    <li><a href="#"><i class="fas fa-running"></i> Rééducation Sportive</a></li>
                    <li><a href="#"><i class="fas fa-user-md"></i> Correction Posturale</a></li>
                    <li><a href="#"><i class="fas fa-procedures"></i> Soins à Domicile</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-md-3 mb-4">
                <h5 class="footer-title">Contactez-nous</h5>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Rue de la Santé, 75000 Paris</span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <span>+33 1 23 45 67 89</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>contact@kenetherapy.fr</span>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <span>Lun-Ven: 8h-20h</span>
                    </li>
                </ul>
                <!-- Emergency Contact -->
                <div class="emergency-contact mt-3">
                    <a href="tel:112" class="btn btn-danger btn-sm">
                        <i class="fas fa-phone-alt"></i>
                        Urgence: 112
                    </a>
                </div>
                <!-- Quick Appointment Button -->
                <div class="quick-appointment mt-3">
                    <a href="/pfaa/appointment.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-calendar-check"></i>
                        Prendre RDV
                    </a>
                </div>
            </div>
        </div>

        <hr class="footer-divider">

        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">
                    &copy; <?= date('Y') ?> KeneTherapy. Tous droits réservés.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <ul class="footer-legal">
                    <li><a href="/pfaa/privacy.php">Politique de confidentialité</a></li>
                    <li><a href="/pfaa/terms.php">Conditions d'utilisation</a></li>
                    <li><a href="/pfaa/cookies.php">Politique des cookies</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<style>
.footer {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    padding: 4rem 0 2rem;
    margin-top: auto;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background-color: var(--bs-border-color);
    opacity: 0.1;
}

.footer-brand {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--bs-primary);
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.footer-brand i {
    font-size: 2rem;
    margin-right: 0.5rem;
}

.footer-title {
    color: var(--bs-primary);
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.footer-title::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 40px;
    height: 2px;
    background-color: var(--bs-primary);
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.8rem;
}

.footer-links a {
    color: var(--bs-body-color);
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.footer-links a:hover {
    color: var(--bs-primary);
    transform: translateX(5px);
}

.footer-links i {
    color: var(--bs-primary);
    width: 20px;
}

.footer-contact {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-contact li {
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.footer-contact i {
    color: var(--bs-primary);
    font-size: 1.2rem;
    width: 20px;
}

.social-links {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.social-link {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--bs-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-link:hover {
    background-color: var(--bs-primary);
    opacity: 0.8;
    transform: translateY(-3px);
}

.footer-divider {
    border-color: var(--bs-border-color);
    margin: 2rem 0;
    opacity: 0.1;
}

.footer-legal {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    gap: 1.5rem;
    justify-content: flex-end;
}

.footer-legal a {
    color: var(--bs-body-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-legal a:hover {
    color: var(--bs-primary);
}

@media (max-width: 768px) {
    .footer {
        margin-left: 0 !important;
        padding: 2rem 0;
    }
    
    .footer-legal {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .footer-title::after {
        left: 50%;
        transform: translateX(-50%);
    }
}
</style>

<script>
// Newsletter form submission
document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const email = this.querySelector('input[type="email"]').value;
    
    // Here you would typically send the email to your server
    // For now, we'll just show a success message
    alert('Merci pour votre inscription à notre newsletter!');
    this.reset();
});
</script>