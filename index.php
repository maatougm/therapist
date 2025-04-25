<?php include 'partials/header.php'; ?>

<!-- Hero Section -->
<section class="hero-kine">
  <div class="hero-wave"></div>
  <div class="container">
    <div class="hero-content">
      <div class="hero-text">
        <h1 class="reveal-text">Restore Your Movement<br>Regain Your Freedom</h1>
        <p class="hero-subtext">Personalized kinesitherapy treatments for complete rehabilitation</p>
        <a href="#booking" class="cta-pulse">Book Assessment</a>
      </div>
      <div class="hero-visual">
        <div class="floating-joints">
          <!-- Animated joint visualization -->
          <div class="joint-line shoulder"></div>
          <div class="joint-line spine"></div>
          <div class="joint-line knee"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Key Services -->
<section class="services-kine">
  <div class="container">
    <h2 class="section-title">Our Therapeutic Approaches</h2>
    
    <div class="service-grid">
      <div class="service-card">
        <div class="card-illustration neuro"></div>
        <h3>Neurological Rehabilitation</h3>
        <p>Recover motor functions after neurological events</p>
        <div class="card-hover">
          <a href="#" class="card-link">Learn More</a>
        </div>
      </div>

      <div class="service-card">
        <div class="card-illustration sport"></div>
        <h3>Sports Recovery</h3>
        <p>Specialized programs for athletes</p>
        <div class="card-hover">
          <a href="#" class="card-link">Learn More</a>
        </div>
      </div>

      <div class="service-card">
        <div class="card-illustration posture"></div>
        <h3>Postural Correction</h3>
        <p>Ergonomic assessment and correction</p>
        <div class="card-hover">
          <a href="#" class="card-link">Learn More</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Interactive Body Map -->
<section class="body-map">
  <div class="container">
    <h2>Targeted Treatment Areas</h2>
    <div class="interactive-body">
      <!-- SVG body map with interactive areas -->
      <img src="body-silhouette.svg" alt="Human body areas" class="body-base">
      <div class="body-point shoulder" data-area="shoulder"></div>
      <div class="body-point spine" data-area="spine"></div>
      <div class="body-point knee" data-area="knee"></div>
    </div>
  </div>
</section>

<!-- Booking CTA -->
<section class="booking-kine" id="booking">
  <div class="booking-wave"></div>
  <div class="container">
    <div class="booking-card">
      <h2>Start Your Recovery Journey</h2>
      <p>Schedule your initial assessment with our specialists</p>
      <div class="booking-actions">
        <a href="/booking" class="cta-3d">Book Now</a>
        <a href="/therapists" class="cta-outline">Meet Our Team</a>
      </div>
    </div>
  </div>
</section>

<style>
/* Color Scheme */
:root {
  --primary-teal: #2B7A78;
  --soft-blue: #DEF2F1;
  --warm-gray: #EDE7E3;
  --accent-orange: #FF7A5A;
}

/* Hero Section */
.hero-kine {
  background: linear-gradient(160deg, var(--soft-blue) 60%, #ffffff 100%);
  padding: 6rem 0 8rem;
  position: relative;
}

.hero-wave {
  position: absolute;
  bottom: -1px;
  left: 0;
  right: 0;
  height: 150px;
  background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg"><path fill="%23ffffff" d="M0,224L60,213.3C120,203,240,181,360,192C480,203,600,245,720,234.7C840,224,960,160,1080,154.7C1200,149,1320,203,1380,229.3L1440,256L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"></path></svg>');
  animation: waveAnim 12s infinite linear;
}

@keyframes waveAnim {
  0% { background-position-x: 0; }
  100% { background-position-x: 1440px; }
}

.service-card {
  background: white;
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 30px rgba(43,122,120,0.1);
  transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.service-card:hover {
  transform: translateY(-15px);
}

.card-illustration {
  height: 200px;
  background-size: contain;
  background-repeat: no-repeat;
  margin: -2rem -2rem 1rem -2rem;
  border-radius: 15px 15px 0 0;
}

.neuro { background-image: url('neuro-illustration.svg'); }
.sport { background-image: url('sports-illustration.svg'); }
.posture { background-image: url('posture-illustration.svg'); }

/* Interactive Body Map */
.interactive-body {
  position: relative;
  max-width: 600px;
  margin: 2rem auto;
}

.body-point {
  position: absolute;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: var(--accent-orange);
  cursor: pointer;
  transition: transform 0.3s ease;
}

.body-point:hover {
  transform: scale(1.2);
}

/* 3D CTA Button */
.cta-3d {
  background: var(--primary-teal);
  padding: 1rem 2rem;
  border-radius: 15px;
  color: white;
  font-weight: 600;
  box-shadow: 0 8px 0 #1B4A48,
              0 12px 20px rgba(43,122,120,0.3);
  transition: all 0.2s ease;
}

.cta-3d:active {
  transform: translateY(4px);
  box-shadow: 0 4px 0 #1B4A48;
}

/* Mobile Optimization */
@media (max-width: 768px) {
  .hero-content {
    flex-direction: column;
    text-align: center;
  }
  
  .service-grid {
    grid-template-columns: 1fr;
  }
  
  .floating-joints {
    display: none;
  }
}

/* Joint Animation */
.joint-line {
  position: absolute;
  background: rgba(43,122,120,0.1);
  border-radius: 10px;
  animation: jointFloat 4s infinite ease-in-out;
}

@keyframes jointFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}
</style>

<?php include 'partials/footer.php'; ?>