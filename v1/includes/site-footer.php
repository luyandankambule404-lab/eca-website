<footer class="eca-site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <img src="/img/ecalogo.png" alt="Eswatini Contractors Association" class="eca-footer-logo">
                <p>Suite 40, Cooper Centre, Mbabane, Eswatini</p>
                <p><a href="tel:+26824044987">+268 2404 4987</a></p>
                <p><a href="mailto:info@eca.co.sz">info@eca.co.sz</a></p>
                <div class="eca-footer-social" aria-label="Social media">
                    <a href="https://www.facebook.com/people/Eswatini-Contractors-Association/61582863643851/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                    <a href="https://www.instagram.com/eca.sz/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                    <a href="https://www.linkedin.com/company/eswatini-contractors-association-eca" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <h5>About</h5>
                <a href="/about.php">About ECA</a>
                <a href="/about-history.php">Our History</a>
                <a href="/about-mission.php">Mission &amp; purpose</a>
                <a href="/about-structure.php">Organizational structure</a>
                <a href="/about-bod.php">Leadership</a>
                <a href="/about-by-laws.php">Bylaws</a>
                <a href="/advocacy.php">Advocacy</a>
                <a href="/contact.php">Contact</a>
            </div>
            <div class="col-lg-2 col-md-4">
                <h5>Membership</h5>
                <a href="/directory.php">Member directory</a>
                <a href="/verify.php">Verify membership</a>
                <a href="/application.php">Apply</a>
                <a href="/renewal.php">Renew</a>
            </div>
            <div class="col-lg-3 col-md-4">
                <h5>What ECA does</h5>
                <a href="/digital-intelligence.php">Digital Intelligence</a>
                <a href="/professionalization.php">Professionalization</a>
                <a href="/technical-support.php">Technical Support</a>
                <a href="/wellness-inclusivity.php">Wellness &amp; Inclusivity</a>
                <a href="/education.php">Education hub</a>
                <a href="/education-training.php">Training &amp; CPD</a>
                <a href="/education-knowledge.php">Knowledge centre</a>
                <a href="/wellness/">Wellness Hub</a>
                <a href="/documents/">Documents</a>
                <a href="/faq.php">FAQs</a>
                <a href="/learner-portal.php">Learner login</a>
            </div>
            <div class="col-lg-2 col-md-4">
                <h5>Information</h5>
                <a href="/news.php">News</a>
                <a href="/events.php">Events</a>
                <a href="/tenders.php">Tenders</a>
                <a href="/code-of-conduct.php">Code of Conduct</a>
                <a href="/privacy.php">Data privacy</a>
                <a href="/sitemap.php">Sitemap</a>
            </div>
        </div>
        <div class="eca-footer-copy">
            <span>&copy; <?= date('Y') ?> Eswatini Contractors Association. All rights reserved.</span>
            <span>Serving Eswatini's construction industry since 1991.</span>
            <span>Developed by M.L.N</span>
        </div>
    </div>
</footer>
<?php
if (!function_exists('eca_session_dashboard_back_ensure')) {
    require_once __DIR__ . '/hub.php';
}
eca_session_dashboard_back_ensure();
?>
