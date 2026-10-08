<?php
$authTheme = $authTheme ?? 'unified';
$authVisual = [
    'unified' => [
        'eyebrow' => 'ECA sign-in',
        'heading' => 'Choose the right door.',
        'text' => 'Officer, member and learner each have their own login. Learners always open the Learner Portal.',
        'points' => [
            ['bi-shield-lock', 'Officer'],
            ['bi-person-badge', 'Member'],
            ['bi-mortarboard', 'Learner'],
        ],
    ],
    'admin' => [
        'eyebrow' => 'Officer access',
        'heading' => 'Officer Hub',
        'text' => 'For administrators and super administrators managing membership, training and operations.',
        'points' => [
            ['bi-people', 'Members'],
            ['bi-file-earmark-check', 'Applications'],
            ['bi-mortarboard', 'Training'],
        ],
    ],
    'officer' => [
        'eyebrow' => 'Officer access',
        'heading' => 'Officer Hub',
        'text' => 'For administrators and super administrators managing membership, training and operations.',
        'points' => [
            ['bi-people', 'Members'],
            ['bi-file-earmark-check', 'Applications'],
            ['bi-mortarboard', 'Training'],
        ],
    ],
    'member' => [
        'eyebrow' => 'Member Hub',
        'heading' => 'Your membership desk.',
        'text' => 'Certificates, payments, company profile and member services in one place.',
        'points' => [
            ['bi-award', 'Certificates'],
            ['bi-credit-card', 'Payments'],
            ['bi-building', 'Company'],
        ],
    ],
    'cpd' => [
        'eyebrow' => 'CPD',
        'heading' => 'Contractor training portal.',
        'text' => 'Courses, applications and your CPD record with Eswatini Contractors Association.',
        'points' => [
            ['bi-journal-richtext', 'Courses'],
            ['bi-clipboard-check', 'Applications'],
            ['bi-bar-chart', 'CPD points'],
        ],
    ],
    'learner' => [
        'eyebrow' => 'Learner Portal',
        'heading' => 'Training Portal.',
        'text' => 'Sign in to your ECA learner space for courses, progress, materials and certificates.',
        'points' => [
            ['bi-journal-richtext', 'Courses'],
            ['bi-play-circle', 'Materials'],
            ['bi-award', 'Certificates'],
        ],
    ],
    'staff' => [
        'eyebrow' => 'CPD staff',
        'heading' => 'Training administration.',
        'text' => 'For CPD administrators and officers managing programmes and attendance.',
        'points' => [
            ['bi-calendar-event', 'Programmes'],
            ['bi-person-check', 'Attendance'],
            ['bi-inboxes', 'Approvals'],
        ],
    ],
    'register' => [
        'eyebrow' => 'CPD registration',
        'heading' => 'Create a contractor account.',
        'text' => 'Submit your details. ECA activates your account after review.',
        'points' => [
            ['bi-person-plus', 'Register'],
            ['bi-mortarboard', 'Training'],
            ['bi-envelope-check', 'Email confirmation'],
        ],
    ],
    'forgot' => [
        'eyebrow' => 'Account help',
        'heading' => 'We will get you back in securely.',
        'text' => 'Member passwords are reset by the ECA office so your company record stays protected.',
        'points' => [
            ['bi-envelope-check', 'Email the office'],
            ['bi-telephone', 'Call during hours'],
            ['bi-shield-lock', 'Verified reset'],
        ],
    ],
];
$visual = $authVisual[$authTheme] ?? $authVisual['unified'];
$h = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};
?>
<section class="eca-auth-visual" aria-label="<?= $h($visual['eyebrow']) ?>" style="--eca-auth-hero-image: url('/img/construction-site-f-compressed.jpg');">
    <div class="eca-auth-visual-glow" aria-hidden="true"></div>
    <div class="eca-auth-visual-grid" aria-hidden="true"></div>
    <div class="eca-auth-visual-inner">
        <p class="eca-auth-visual-kicker"><?= $h($visual['eyebrow']) ?></p>
        <h2><?= $h($visual['heading']) ?></h2>
        <p class="eca-auth-visual-lead"><?= $h($visual['text']) ?></p>
        <ul class="eca-auth-points">
            <?php foreach ($visual['points'] as [$icon, $label]): ?>
                <li>
                    <i class="bi <?= $h($icon) ?>" aria-hidden="true"></i>
                    <span><?= $h($label) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
