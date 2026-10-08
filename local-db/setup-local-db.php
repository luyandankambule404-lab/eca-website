<?php
/**
 * Create local MySQL databases for the ECA website.
 *
 * Usage:
 *   php local-db/setup-local-db.php
 *   php local-db/setup-local-db.php --reset
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this from the command line.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/v1/includes/env.php';
eca_load_env_file($root . '/.env');

$reset = in_array('--reset', $argv, true);
$host = eca_env('ECA_DB_HOST', '127.0.0.1');
$user = eca_env('ECA_DB_USER', 'root');
$pass = eca_env('ECA_DB_PASS', '');
$hubName = eca_env('ECA_DB_NAME', 'eca_local');
$portalHost = eca_env('ECA_PORTAL_DB_HOST', $host);
$portalUser = eca_env('ECA_PORTAL_DB_USER', $user);
$portalPass = eca_env('ECA_PORTAL_DB_PASS', $pass);
$portalName = eca_env('ECA_PORTAL_DB_NAME', 'eca_portal_local');
$password = eca_env('ECA_LOCAL_ADMIN_PASSWORD', '');
if ($password === '') {
    $password = eca_env('ECA_LOCAL_PORTAL_PASSWORD', '');
}
if ($password === '') {
    $password = 'EcaLocal!2026';
}
$hash = password_hash($password, PASSWORD_DEFAULT);

function eca_sql_connect(string $host, string $user, string $pass, ?string $dbname = null): PDO
{
    $dsn = 'mysql:host=' . $host . ';charset=utf8mb4';
    if ($dbname) {
        $dsn .= ';dbname=' . $dbname;
    }
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
}

function eca_run_sql_file(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Could not read ' . $path);
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    foreach (preg_split('/;\s*$/m', $sql) ?: [] as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

function eca_has_row(PDO $pdo, string $sql, array $params = []): bool
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool) $stmt->fetchColumn();
}

echo "Connecting to MySQL at {$host}...\n";
try {
    $server = eca_sql_connect($host, $user, $pass);
} catch (Throwable $e) {
    fwrite(STDERR, "Could not connect to MySQL as {$user}@{$host}.\n");
    fwrite(STDERR, $e->getMessage() . "\n\n");
    fwrite(STDERR, "Start MySQL first, then run this again. With XAMPP:\n");
    fwrite(STDERR, "  C:\\xampp\\mysql_start.bat\n");
    fwrite(STDERR, "or open XAMPP Control Panel and start MySQL.\n");
    exit(1);
}

$ident = static function (string $name): string {
    return '`' . str_replace('`', '``', $name) . '`';
};

if ($reset) {
    echo "Dropping existing databases (--reset)...\n";
    $server->exec('DROP DATABASE IF EXISTS ' . $ident($hubName));
    $server->exec('DROP DATABASE IF EXISTS ' . $ident($portalName));
}

echo "Creating {$hubName} and {$portalName}...\n";
$server->exec('CREATE DATABASE IF NOT EXISTS ' . $ident($hubName) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$server->exec('CREATE DATABASE IF NOT EXISTS ' . $ident($portalName) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

$hub = eca_sql_connect($host, $user, $pass, $hubName);
$portal = eca_sql_connect($portalHost, $portalUser, $portalPass, $portalName);

echo "Applying hub schema...\n";
eca_run_sql_file($hub, __DIR__ . '/schema-hub.sql');
if (is_file(__DIR__ . '/schema-wellness.sql')) {
    echo "Applying wellness schema (idempotent)...\n";
    eca_run_sql_file($hub, __DIR__ . '/schema-wellness.sql');
}
if (is_file(__DIR__ . '/schema-education.sql')) {
    echo "Applying education schema (idempotent)...\n";
    eca_run_sql_file($hub, __DIR__ . '/schema-education.sql');
}
echo "Applying portal schema...\n";
eca_run_sql_file($portal, __DIR__ . '/schema-portal.sql');
try {
    $portal->exec("ALTER TABLE `user` MODIFY role VARCHAR(32) NOT NULL DEFAULT 'CONTRACTOR'");
} catch (Throwable $exception) {
    // Already compatible.
}

echo "Seeding test data...\n";

$roles = [
    ['hub.super@eca.co.sz', 'Super Admin', 'super_admin', 1],
    ['admin@eca.co.sz', 'Local Administrator', 'admin', 1],
    ['officer@eca.co.sz', 'Membership Officer', 'membership_officer', 0],
];
$insertUser = $hub->prepare(
    'INSERT INTO users (name, email, password, role, is_admin, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, NOW(), NOW())
     ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role), name = VALUES(name)'
);
$linkRole = $hub->prepare(
    'INSERT IGNORE INTO user_roles (user_id, role_id)
     SELECT ?, id FROM roles WHERE slug = ? LIMIT 1'
);
foreach ($roles as [$email, $name, $role, $isAdmin]) {
    $insertUser->execute([$name, $email, $hash, $role, $isAdmin]);
    $userId = (int) $hub->query('SELECT id FROM users WHERE email = ' . $hub->quote($email) . ' LIMIT 1')->fetchColumn();
    if ($userId > 0) {
        $linkRole->execute([$userId, $role]);
    }
}

$companies = [
    ['Banner Electrical', 'ECA-1001', 'banner@example.test', '+268 2404 1001', 'Manzini', 'Electrical', 'Building and electrical contractor based in Manzini.'],
    ['B4L Civils', 'ECA-1002', 'b4l@example.test', '+268 2404 1002', 'Hhohho', 'Civil', 'Civil engineering and earthworks.'],
    ['Blue Ridge Building', 'ECA-1003', 'blueridge@example.test', '+268 2404 1003', 'Mbabane', 'Building', 'General building contractor.'],
    ['Mbabane Mechanical Works', 'ECA-1004', 'mmw@example.test', '+268 2404 1004', 'Hhohho', 'Mechanical', 'Mechanical plant and HVAC.'],
    ['Lubombo Specialist Services', 'ECA-1005', 'lss@example.test', '+268 2404 1005', 'Lubombo', 'Specialist', 'Specialist trades and fit-out.'],
    ['Shiselweni Civil Projects', 'ECA-1006', 'scp@example.test', '+268 2404 1006', 'Shiselweni', 'Civil', 'Roads and civil infrastructure.'],
];
$insertCompany = $hub->prepare(
    'INSERT INTO companies (name, registration_number, email, phone, address, industry, status, website, description)
     VALUES (?, ?, ?, ?, ?, ?, \'active\', \'\', ?)'
);
foreach ($companies as $row) {
    if (!eca_has_row($hub, 'SELECT id FROM companies WHERE registration_number = ?', [$row[1]])) {
        $insertCompany->execute($row);
    }
}

if (!eca_has_row($hub, 'SELECT id FROM companies1 LIMIT 1')) {
    $hub->prepare(
        'INSERT INTO companies1 (name, registration_number, email, phone, address, industry, status, description)
         VALUES (?, ?, ?, ?, ?, ?, \'active\', ?)'
    )->execute([
        'Balingani Building Co',
        'ECA-2001',
        'balingani@example.test',
        '+268 2404 2001',
        'Manzini',
        'Building',
        'Women-owned building contractor for the Balingani directory.',
    ]);
}

$newsRows = [
    ['[LOCAL DEMO] Local ECA site is connected to MySQL', 'LOCAL DEMONSTRATION / SAMPLE CONTENT: This test article confirms the local database is serving news on the public site. Not an official live ECA announcement.', 'Industry News', '2026-09-01'],
    ['[LOCAL DEMO] CPD training window is open', 'LOCAL DEMONSTRATION / SAMPLE CONTENT: A sample OPEN course is seeded so the CPD application flow can be tested locally. Not an official live ECA announcement.', 'Training', '2026-09-10'],
];
$insertNews = function (PDO $pdo) use ($newsRows): void {
    $stmt = $pdo->prepare(
        'INSERT INTO news (title, summary, author, categories, image, video, link, date, status, count, created_at)
         VALUES (?, ?, \'ECA Communications\', ?, \'img/ecalogo.png\', \'\', \'/news.php\', ?, \'Active\', 0, CURDATE())'
    );
    foreach ($newsRows as $row) {
        if (!eca_has_row($pdo, 'SELECT id FROM news WHERE title = ?', [$row[0]])) {
            $stmt->execute($row);
        }
    }
};
$insertNews($hub);
$insertNews($portal);

if (!eca_has_row($hub, 'SELECT id FROM events LIMIT 1')) {
    $hub->exec(
        "INSERT INTO events (title, summary, venue, starts_at, ends_at, capacity, status)
         VALUES ('ECA members briefing', 'A local test event so registration can be tried.', 'ECA offices, Mbabane', DATE_ADD(NOW(), INTERVAL 14 DAY), DATE_ADD(NOW(), INTERVAL 14 DAY) + INTERVAL 3 HOUR, 80, 'PUBLISHED')"
    );
}
if (!eca_has_row($hub, 'SELECT id FROM tenders LIMIT 1')) {
    $hub->exec(
        "INSERT INTO tenders (title, summary, body, status, published_at, closes_at)
         VALUES ('Sample public tender', 'A published local tender for testing the tenders page.', 'Use this record to confirm the admin and public tender flow.', 'PUBLISHED', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))"
    );
}

if (!eca_has_row($portal, 'SELECT client_id FROM tbl_client WHERE MembershipNumber = ? OR application_reference = ?', ['ECA-1001', 'ECA-APP-2026-0001'])) {
    $portal->prepare(
        'INSERT INTO tbl_client
         (CompanyRegistrationName, TradingName, EmailAddress, Cellphone, telephone, address, Region, businesstype, Enterprise, Status, Clasification, DateOfRegistration, active, MembershipNumber, application_reference, application_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)'
    )->execute([
        'Banner Electrical (Pty) Ltd',
        'Banner Electrical',
        'member@eca.co.sz',
        '+268 2404 1001',
        '+268 2404 1001',
        'Manzini',
        'Manzini',
        'Company',
        'Mixed',
        'Active',
        'Electrical',
        'Active',
        'ECA-1001',
        'ECA-APP-2026-0001',
        'APPROVED',
    ]);
    $clientId = (int) $portal->lastInsertId();
    $portal->prepare(
        'INSERT INTO membership_years (client_id, type, status, year, expiry_date) VALUES (?, \'Membership\', \'Active\', ?, DATE_ADD(CURDATE(), INTERVAL 1 YEAR))'
    )->execute([$clientId, (string) date('Y')]);
    $portal->prepare(
        'INSERT INTO membership_certificates (client_id, membership_number, certificate_number, classification, company_name, issued_at, expiry_date, status, qr_token)
         VALUES (?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), \'ACTIVE\', ?)'
    )->execute([$clientId, 'ECA-1001', 'CERT-ECA-1001', 'Electrical', 'Banner Electrical', bin2hex(random_bytes(8))]);
}

$bannerStmt = $portal->prepare('SELECT client_id FROM tbl_client WHERE MembershipNumber = ? ORDER BY client_id DESC LIMIT 1');
$bannerStmt->execute(['ECA-1001']);
$bannerId = (int) $bannerStmt->fetchColumn();
if ($bannerId > 0 && !eca_has_row($portal, 'SELECT id FROM owners WHERE clientid = ? OR application_id = ?', [$bannerId, $bannerId])) {
    $ownerInsert = $portal->prepare(
        'INSERT INTO owners (clientid, name, citizen, gender, shares, application_id) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ownerInsert->execute([$bannerId, 'Sipho Dlamini', 'Swazi', 'Male', '60', $bannerId]);
    $ownerInsert->execute([$bannerId, 'Nomcebo Dlamini', 'Swazi', 'Female', '40', $bannerId]);
}

if (!eca_has_row($portal, 'SELECT client_id FROM tbl_client WHERE application_reference = ?', ['ECA-APP-2026-0002'])) {
    $portal->prepare(
        'INSERT INTO tbl_client
         (CompanyRegistrationName, TradingName, EmailAddress, Cellphone, address, Region, businesstype, Enterprise, Status, Clasification, DateOfRegistration, active, application_reference, application_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)'
    )->execute([
        'New Build Contractors',
        'New Build Contractors',
        'applicant@example.test',
        '+268 2404 3001',
        'Mbabane',
        'Hhohho',
        'Company',
        'Mixed',
        'Joining',
        'Building',
        'Pending',
        'ECA-APP-2026-0002',
        'SUBMITTED',
    ]);
}

$portal->prepare(
    'INSERT INTO userss (membership_number, full_name, email, password, role, status)
     VALUES (?, ?, ?, ?, \'MEMBER\', \'ACTIVE\')
     ON DUPLICATE KEY UPDATE password = VALUES(password), status = \'ACTIVE\', role = \'MEMBER\''
)->execute(['ECA-1001', 'Banner Electrical', 'member@eca.co.sz', $hash]);

$cpdUsers = [
    ['SUPPERADMIN', 'Super Admin', 'cpd.super@eca.co.sz', 'ACTIVE'],
    ['ADMIN', 'CPD Administrator', 'cpd.admin@eca.co.sz', 'ACTIVE'],
    ['OFFICER', 'CPD Officer', 'cpd.officer@eca.co.sz', 'ACTIVE'],
    ['CONTRACTOR', 'Test Contractor', 'contractor@eca.co.sz', 'ACTIVE'],
    ['CONTRACTOR', 'Test Learner', 'learner@eca.co.sz', 'ACTIVE'],
    ['CONTRACTOR', 'Banner Electrical', 'member@eca.co.sz', 'ACTIVE'],
];
$insertCpd = $portal->prepare(
    'INSERT INTO `user` (role, company_name, full_name, email, phone, password_hash, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role), status = VALUES(status), full_name = VALUES(full_name)'
);
foreach ($cpdUsers as [$role, $name, $email, $status]) {
    $insertCpd->execute([$role, 'ECA', $name, $email, '+268 2404 9851', $hash, $status]);
}

if (!eca_has_row($portal, 'SELECT id FROM courses LIMIT 1')) {
    $createdBy = (int) $portal->query("SELECT id FROM `user` WHERE email = 'cpd.admin@eca.co.sz' LIMIT 1")->fetchColumn();
    $portal->prepare(
        'INSERT INTO courses (title, description, start_date, end_date, venue, capacity, points, commitment_fee, fee, status, created_by)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 21 DAY), DATE_ADD(NOW(), INTERVAL 23 DAY), ?, 40, 4, 350.00, 350.00, \'OPEN\', ?)'
    )->execute([
        'Construction contract administration',
        'Sample OPEN course so CPD applications can be tested locally.',
        'ECA Training Room, Mbabane',
        $createdBy ?: null,
    ]);
}

if (!eca_has_row($portal, 'SELECT id FROM cpd_applications LIMIT 1')) {
    $courseId = (int) $portal->query('SELECT id FROM courses ORDER BY id DESC LIMIT 1')->fetchColumn();
    $portal->prepare(
        'INSERT INTO cpd_applications (course_id, company_name, membership_number, discipline, full_name, email, phone, id_number, gender, position, learning_objectives, qualification_level, qualification_name, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $courseId,
        'Banner Electrical',
        'ECA-1001',
        'Electrical',
        'Test Contractor',
        'contractor@eca.co.sz',
        '+268 2404 1001',
        '123456789',
        'Male',
        'Director',
        'Improve contract administration skills.',
        'Diploma',
        'Electrical Engineering',
        'Pending',
    ]);
}

echo "\nLocal MySQL is ready.\n";
echo "  Hub:    {$hubName}\n";
echo "  Portal: {$portalName}\n\n";
echo "Test logins (password: {$password})\n";
echo "  Super Admin      hub.super@eca.co.sz       /admin/login.php\n";
echo "  Officer          admin@eca.co.sz           /admin/login.php\n";
echo "  Officer (CPD)    cpd.admin@eca.co.sz       /admin/login.php\n";
echo "  Officer (CPD)    cpd.super@eca.co.sz       /admin/login.php\n";
echo "  Member           ECA-1001 or member@eca.co.sz  /client/\n";
echo "  Learner          contractor@eca.co.sz      /cpd/login.php\n";
echo "  Learner          learner@eca.co.sz         /cpd/login.php\n";
echo "\nOpen http://127.0.0.1:8765/ after MySQL and the PHP server are running.\n";
echo "To wipe and recreate: php local-db/setup-local-db.php --reset\n";
