<?php

/**
 * Welcome-package copy and file lookup for membership approval emails.
 * Links to existing public pages when PDFs are not in this local copy.
 */

function eca_welcome_pack_search_dirs(): array
{
    return [
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'downloads',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'documents',
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'eca-pages' . DIRECTORY_SEPARATOR . 'downloads',
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'eca-pages' . DIRECTORY_SEPARATOR . 'documents',
    ];
}

function eca_welcome_pack_find_pdf(array $names): ?string
{
    foreach (eca_welcome_pack_search_dirs() as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        foreach ($names as $name) {
            $safe = str_replace(['../', '..\\', "\0"], '', basename((string) $name));
            if ($safe === '' || strtolower(pathinfo($safe, PATHINFO_EXTENSION)) !== 'pdf') {
                continue;
            }
            $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $safe;
            if (is_file($path)) {
                return $path;
            }
        }
    }
    return null;
}

function eca_welcome_pack_attachments(): array
{
    $groups = [
        'about' => [
            'About-ECA.pdf',
            'about.pdf',
            'ECA-Welcome.pdf',
            'Welcoming-Package.pdf',
        ],
        'conduct' => [
            'Code-of-Conduct.pdf',
            'code-of-conduct.pdf',
            'ECA-Code-of-Conduct.pdf',
        ],
        'privacy' => [
            'privacy.pdf',
            'Data-Privacy.pdf',
            'privacy-policy.pdf',
            'ECA-Data-Privacy.pdf',
        ],
    ];
    $found = [];
    foreach ($groups as $names) {
        $path = eca_welcome_pack_find_pdf($names);
        if ($path !== null) {
            $found[] = $path;
        }
    }
    return $found;
}

function eca_welcome_pack_page_paths(): array
{
    return [
        'about' => '/about.php',
        'conduct' => '/code-of-conduct.php',
        'privacy' => '/privacy.php',
        'login' => '/client/',
        'forgot' => '/client/page-forgot-password.html',
        'bylaws' => '/about-by-laws.php',
    ];
}

function eca_about_eca_blurb_html(): string
{
    return '<p>The Eswatini Contractors Association (ECA), formerly the Swaziland Contractors Association, '
        . 'is the industry trade association representing construction companies and allied trades in Eswatini. '
        . 'Founded in 1991, ECA promotes and protects the interests of contractors and works for a fairer, '
        . 'more professional construction industry.</p>';
}

function eca_code_of_conduct_html(): string
{
    return '<p><strong>Eswatini Contractors Association (ECA) – Code of Conduct for Contractors</strong></p>'
        . '<p>By this Code of Conduct, Eswatini Contractors Association (ECA) expect contractors to act socially and '
        . 'environmentally responsible and actively work for the implementation of the standards and principles set out forth.</p>'
        . '<ol>'
        . '<li><strong>Health &amp; Safety:</strong> Provide safe and hygienic working environments; prioritize worker safety and prevent accidents/injury.</li>'
        . '<li><strong>Anti-Corruption:</strong> Avoid corruption; ensure integrity, accountability, fairness, and professional conduct.</li>'
        . '<li><strong>Sexual Harassment, Exploitation and Abuse:</strong> Must not sexually harass, exploit, or sexually abuse any individual.</li>'
        . '<li><strong>Fairness:</strong> Be fair in business relationships, pricing, and contracts to give clients best possible value.</li>'
        . '<li><strong>Law:</strong> Comply with local laws and the Association’s Constitution, Codes of Conduct, and by-laws.</li>'
        . '<li><strong>Insurance:</strong> Maintain proper insurance coverage for business, employees and clients.</li>'
        . '<li><strong>Quality:</strong> Perform work in good workmanship aligned with industry standards.</li>'
        . '<li><strong>Professionalism:</strong> Meet professional standards; continue learning and share in healthy competitive spirit.</li>'
        . '<li><strong>Scheduling:</strong> Provide realistic schedules and make every effort to meet them.</li>'
        . '<li><strong>Warranty:</strong> Acknowledge defects and correct them in a mutually agreeable and timely manner.</li>'
        . '<li><strong>Training and Education:</strong> Support training activities developed and provided by the Association.</li>'
        . '</ol>'
        . '<p><strong>Complaints:</strong> Report suspected breaches to <a href="mailto:info@eca.co.sz">info@eca.co.sz</a>.</p>';
}

function eca_privacy_notice_html(): string
{
    return '<p>ECA stores and uses personal and company data provided in membership applications and member records '
        . 'in accordance with the Data Protection Act and its amendments. This includes contact details, ownership '
        . 'information, uploaded supporting documents, and membership standing needed to administer membership, '
        . 'certificates, the member hub, and related association services.</p>'
        . '<p>Applicants consent to this use when they sign the membership declaration. ECA does not publish passwords '
        . 'or identity-document files on public pages. For questions about your data, email '
        . '<a href="mailto:info@eca.co.sz">info@eca.co.sz</a> or <a href="mailto:support@eca.co.sz">support@eca.co.sz</a>.</p>';
}
