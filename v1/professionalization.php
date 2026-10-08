<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/organization.php';

eca_public_page_start(
    'Professionalization & Capacity Building',
    'What ECA does',
    'Professionalization & Capacity Building',
    'Training, CPD and professional development that strengthen contractor capability across Eswatini\'s construction industry.',
    'Professionalization'
);
eca_org_assets();
?>
<div class="org-page">
    <div class="org-wrap">
        <?php eca_org_pillar_nav('professionalization'); ?>

        <section class="org-panel">
            <p class="org-kicker">Develop</p>
            <h2>Professionalization &amp; Capacity Building</h2>
            <p class="org-lead">
                ECA supports skills development and continuous professional development so contractors can compete,
                comply and deliver to higher professional standards.
            </p>
            <?php eca_org_note('Course registration, CPD records and learner dashboards use the existing CPD and education systems. This page does not create a second CPD platform.'); ?>
            <div class="org-actions">
                <a class="org-btn" href="/education-training.php">Training &amp; CPD</a>
                <a class="org-btn-ghost" href="/education-learner.php">Learner Portal</a>
            </div>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Learning pathways</p>
            <h2>Reuse existing education &amp; CPD services</h2>
            <div class="org-grid-3">
                <?php
                eca_org_link_card('Education Hub', 'Overview of membership development and learning pathways.', '/education.php', 'Open →', 'fa-university');
                eca_org_link_card('Training Programmes', 'Upcoming and completed ECA courses and training information.', '/education-training.php', 'View courses →', 'fa-chalkboard-teacher');
                eca_org_link_card('CPD / Learner Portal', 'Access learner login for courses, progress and certificates.', '/education-learner.php', 'Learner access →', 'fa-laptop');
                eca_org_link_card('Knowledge Centre', 'Guides, articles and practical contractor resources.', '/education-knowledge.php', 'Browse →', 'fa-book');
                eca_org_link_card('Contractor Development', 'Programmes focused on longer-term contractor development.', '/education-development.php', 'Explore →', 'fa-hard-hat');
                eca_org_link_card('Resources', 'Downloadable learning and reference materials.', '/education-resources.php', 'Open resources →', 'fa-folder-open');
                ?>
            </div>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Focus areas</p>
            <h2>Capacity-building priorities</h2>
            <div class="org-grid-2">
                <?php
                eca_org_topic_card('CPD Points', 'Continuous professional development pathways through the existing CPD system.', 'fa-award');
                eca_org_topic_card('Technical Excellence', 'Strengthening delivery quality and professional practice.', 'fa-cogs');
                eca_org_topic_card('Skills Development', 'Building practical skills for contractors and their teams.', 'fa-tools');
                eca_org_topic_card('Training Calendar', 'Course schedules are published through the existing training pages.', 'fa-calendar');
                ?>
            </div>
        </section>
    </div>
</div>
<?php eca_public_page_end(); ?>
