</main>
<footer class="site-footer footer mt-5">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-3 col-md-6">
        <h5>SIET</h5>
        <p class="small mb-2">Placeholder professional portal for engineering technologists and technicians.</p>
        <p class="small mb-1">Placeholder address line</p>
        <p class="small mb-1">Phone: +00 0000 0000</p>
        <p class="small mb-0">Email: placeholder@example.com</p>
      </div>
      <div class="col-lg-3 col-md-6">
        <h6>Membership</h6>
        <a class="footer-link" href="<?= SITE_URL ?>/why-member.php">Why Become a Member</a>
        <a class="footer-link" href="<?= SITE_URL ?>/membership-pathways.php">Membership Pathways</a>
        <a class="footer-link" href="<?= SITE_URL ?>/mature-candidate.php">Mature Candidate Scheme</a>
        <a class="footer-link" href="<?= SITE_URL ?>/member-directory.php">Membership &amp; Certification Search</a>
      </div>
      <div class="col-lg-3 col-md-6">
        <h6>Certifications</h6>
        <a class="footer-link" href="<?= SITE_URL ?>/professionalexaminations.php">Professional Examinations</a>
        <a class="footer-link" href="<?= SITE_URL ?>/cert-vs-membership.php">Certification &amp; Progression</a>
        <a class="footer-link" href="<?= SITE_URL ?>/cert-application.php">Certification Application</a>
        <a class="footer-link" href="<?= SITE_URL ?>/cpd-renewal.php">Renewal of Professional Registration</a>
        <a class="footer-link" href="<?= SITE_URL ?>/cpd-types.php">Types of CPD</a>
      </div>
      <div class="col-lg-3 col-md-6">
        <h6>Global Networks</h6>
        <a class="footer-link" href="<?= SITE_URL ?>/global-founding.php">Founding Members and Members</a>
        <a class="footer-link" href="<?= SITE_URL ?>/global-recognitions.php">International Recognitions</a>
        <a class="footer-link" href="<?= SITE_URL ?>/global-affiliations.php">Global Recognitions &amp; Affiliations</a>
        <a class="footer-link" href="<?= SITE_URL ?>/accreditation-international.php">International Accreditation</a>
        <a class="footer-link" href="<?= SITE_URL ?>/accredited-courses.php">Accredited Courses</a>
        <a class="footer-link" href="<?= SITE_URL ?>/contact.php">Contact</a>
      </div>
    </div>
    <hr class="border-light-subtle my-4">
    <div class="d-flex flex-column flex-md-row justify-content-between small">
      <span>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All placeholder content.</span>
      <span>Developed as a local XAMPP PHP rebuild.</span>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>
