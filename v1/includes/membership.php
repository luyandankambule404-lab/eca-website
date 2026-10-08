<?php

function eca_application_statuses(): array
{
    return [
        'SUBMITTED',
        'UNDER REVIEW',
        'ADDITIONAL INFORMATION REQUIRED',
        'APPROVED',
        'REJECTED',
    ];
}

function eca_member_standing_values(): array
{
    return ['Active', 'Pending', 'Suspended', 'Declined', 'Inprogress'];
}

function eca_member_type_values(): array
{
    return ['Joining', 'Renewal', 'Active'];
}

function eca_specialisation_options(): array
{
    return [
        'Building' => 'Building',
        'Civil' => 'Civil',
        'Electrical/Mechanical' => 'Electrical/Mechanical',
        'Specialist' => 'Specialist',
    ];
}

function eca_resolve_specialisation(string $specialisation, string $other = ''): array
{
    $specialisation = trim($specialisation);
    $other = trim($other);

    if ($specialisation === '') {
        return [
            'canonical' => '',
            'other' => $other,
            'classification' => '',
            'error' => 'Please select a specialisation.',
        ];
    }

    if (preg_match('/^(other|specialist)\s*[:\-]\s*(.*)$/i', $specialisation, $m)) {
        if (trim($m[2]) !== '') {
            $other = trim($m[2]);
        }
        $specialisation = 'Specialist';
    }

    $key = strtolower(trim(preg_replace('/\s+/', ' ', str_replace(['&', '_'], ['and', ' '], $specialisation)) ?? $specialisation));
    $key = str_replace(['mechenical', 'mecanical'], 'mechanical', $key);

    $electricalKeys = [
        'electrical',
        'mechanical',
        'electrical/mechanical',
        'electrical / mechanical',
        'electrical and mechanical',
        'electrical mechanical',
        'electrical installation',
        'mechanical works',
    ];

    if ($key === 'building') {
        $canonical = 'Building';
    } elseif ($key === 'civil') {
        $canonical = 'Civil';
    } elseif (in_array($key, $electricalKeys, true)) {
        $canonical = 'Electrical/Mechanical';
    } elseif (in_array($key, ['specialist', 'other'], true)) {
        $canonical = 'Specialist';
    } else {
        if ($other === '') {
            $other = $specialisation;
        }
        $canonical = 'Specialist';
    }

    $error = '';
    if ($canonical === 'Specialist') {
        if ($other === '') {
            $error = 'Please specify your specialisation.';
        }
        $classification = $other !== '' ? ('Specialist: ' . $other) : 'Specialist';
    } else {
        $classification = $canonical;
    }

    return [
        'canonical' => $canonical,
        'other' => $other,
        'classification' => $classification,
        'error' => $error,
    ];
}

function eca_next_application_reference(mysqli $conn): string
{
    $year = date('Y');
    $prefix = 'ECA-APP-' . $year . '-';
    $next = 1;
    $stmt = $conn->prepare(
        'SELECT application_reference FROM tbl_client
         WHERE application_reference LIKE ? ORDER BY application_reference DESC LIMIT 1'
    );
    $like = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($row['application_reference']) && preg_match('/-(\d+)$/', $row['application_reference'], $m)) {
        $next = ((int) $m[1]) + 1;
    }
    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

function eca_public_membership_status(array $client, ?array $year = null): string
{
    $active = strtolower(trim((string) ($client['active'] ?? '')));
    $status = strtolower(trim((string) ($client['Status'] ?? '')));
    $app = strtoupper(trim((string) ($client['application_status'] ?? '')));
    $blob = $active . ' ' . $status;
    if (str_contains($blob, 'suspend')) {
        return 'Suspended';
    }
    if (str_contains($blob, 'decline') || $app === 'REJECTED') {
        return 'Not found';
    }
    $expiry = (string) ($year['expiry_date'] ?? '');
    if ($expiry !== '' && strtotime($expiry) !== false && strtotime($expiry) < strtotime('today')) {
        return 'Expired';
    }
    if ($active === 'active' || $status === 'renewal') {
        return 'Active';
    }
    if (
        $active === 'pending'
        || $status === 'joining'
        || in_array($app, ['SUBMITTED', 'UNDER REVIEW', 'ADDITIONAL INFORMATION REQUIRED'], true)
    ) {
        return 'Pending';
    }
    if ($active === '' && $status === '') {
        return 'Pending';
    }
    return 'Pending';
}

function eca_find_client_for_verify(PDO $conn, string $membership = '', string $cert = ''): ?array
{
    $membership = trim($membership);
    $cert = trim($cert);
    try {
        if ($membership !== '') {
            $stmt = $conn->prepare('SELECT * FROM tbl_client WHERE MembershipNumber = ? LIMIT 1');
            $stmt->execute([$membership]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }
        if ($cert !== '') {
            $revoked = $conn->prepare(
                'SELECT status FROM membership_certificates WHERE certificate_number = ? ORDER BY id DESC LIMIT 1'
            );
            $revoked->execute([$cert]);
            $certStatus = (string) $revoked->fetchColumn();
            if ($certStatus !== '') {
                if (strtoupper($certStatus) === 'REVOKED') {
                    return null;
                }
                $stmt = $conn->prepare(
                    'SELECT c.* FROM tbl_client c
                     INNER JOIN membership_certificates mc ON mc.client_id = c.client_id
                     WHERE mc.certificate_number = ? AND UPPER(mc.status) = ? LIMIT 1'
                );
                $stmt->execute([$cert, 'ACTIVE']);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ?: null;
            }
            // No membership_certificates row for this number — do not fall back to
            // tbl_client.CertificateNumber (that can bypass revocation state).
            return null;
        }
    } catch (Throwable $e) {
        return null;
    }
    return null;
}

function eca_latest_membership_year(PDO $conn, array $client): ?array
{
    $number = trim((string) ($client['MembershipNumber'] ?? ''));
    $clientId = (int) ($client['client_id'] ?? 0);
    try {
        if ($number !== '') {
            $stmt = $conn->prepare(
                'SELECT * FROM membership_years WHERE membership_number = ? ORDER BY year DESC, id DESC LIMIT 1'
            );
            $stmt->execute([$number]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }
        if ($clientId > 0) {
            $stmt = $conn->prepare(
                'SELECT * FROM membership_years WHERE client_id = ? ORDER BY year DESC, id DESC LIMIT 1'
            );
            $stmt->execute([$clientId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        }
    } catch (Throwable $e) {
        return null;
    }
    return null;
}

function eca_display_date(string $value, string $empty = '—'): string
{
    $value = trim($value);
    if ($value === '') {
        return $empty;
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }
    return date('d M Y', $ts);
}

function eca_year_expiry_state(?array $year): string
{
    if (!$year) {
        return '';
    }
    $status = strtolower(trim((string) ($year['status'] ?? '')));
    $type = strtolower(trim((string) ($year['type'] ?? '')));
    $expiry = trim((string) ($year['expiry_date'] ?? ''));
    $ts = $expiry !== '' ? strtotime($expiry) : false;
    if ($status === 'expired' || ($ts !== false && $ts < strtotime('today'))) {
        return 'expired';
    }
    if ($status === 'pending' && $type === 'renewal') {
        return 'renewal_pending';
    }
    if ($ts !== false && $ts >= strtotime('today') && $ts <= strtotime('+90 days')) {
        return 'near';
    }
    if ($status === 'active' || $ts !== false) {
        return 'current';
    }
    return $status;
}

function eca_membership_year_state_values(): array
{
    return [
        'expired' => 'Expired year',
        'near' => 'Expiring within 90 days',
        'renewal_pending' => 'Pending renewal',
        'current' => 'Current year (not expired)',
    ];
}

function eca_membership_year_state_sql(string $state): ?string
{
    $inner = [
        'expired' => "status = 'Expired' OR (expiry_date IS NOT NULL AND expiry_date < CURDATE())",
        'near' => "status = 'Active' AND expiry_date IS NOT NULL AND expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)",
        'renewal_pending' => "status = 'Pending' AND type = 'Renewal'",
        'current' => "status = 'Active' AND (expiry_date IS NULL OR expiry_date >= CURDATE())",
    ][$state] ?? null;
    if ($inner === null) {
        return null;
    }
    return 'client_id IN (SELECT client_id FROM membership_years WHERE client_id IS NOT NULL AND client_id > 0 AND (' . $inner . '))';
}

function eca_member_lifecycle(?PDO $conn, array $member): array
{
    $result = [
        'standing' => trim((string) ($member['standing'] ?? $member['active'] ?? '')),
        'membership_type' => trim((string) ($member['membership_type'] ?? $member['Status'] ?? '')),
        'application_status' => trim((string) ($member['application_status'] ?? '')),
        'application_reference' => trim((string) ($member['application_reference'] ?? '')),
        'year' => '',
        'year_status' => '',
        'year_type' => '',
        'expiry' => '',
        'expiry_source' => '',
        'year_state' => '',
        'certificate_number' => trim((string) ($member['CertificateNumber'] ?? $member['certificate_number'] ?? '')),
        'certificate_status' => '',
        'certificate_issued' => '',
        'certificate_id' => 0,
        'certificate_expiry' => '',
        'client_id' => (int) ($member['client_id'] ?? 0),
        'membership' => trim((string) ($member['membership'] ?? $member['MembershipNumber'] ?? '')),
    ];
    if (!$conn) {
        return $result;
    }
    try {
        if ($result['client_id'] > 0) {
            $stmt = $conn->prepare(
                'SELECT client_id, active, Status, application_status, application_reference, CertificateNumber, MembershipNumber
                 FROM tbl_client WHERE client_id = ? LIMIT 1'
            );
            $stmt->execute([$result['client_id']]);
        } elseif ($result['membership'] !== '') {
            $stmt = $conn->prepare(
                'SELECT client_id, active, Status, application_status, application_reference, CertificateNumber, MembershipNumber
                 FROM tbl_client WHERE MembershipNumber = ? LIMIT 1'
            );
            $stmt->execute([$result['membership']]);
        } else {
            $stmt = null;
        }
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($row) {
            $result['client_id'] = (int) ($row['client_id'] ?? $result['client_id']);
            $result['standing'] = trim((string) ($row['active'] ?? ''));
            $result['membership_type'] = trim((string) ($row['Status'] ?? ''));
            $result['application_status'] = trim((string) ($row['application_status'] ?? ''));
            $result['application_reference'] = trim((string) ($row['application_reference'] ?? ''));
            $result['certificate_number'] = trim((string) ($row['CertificateNumber'] ?? $result['certificate_number']));
            if ($result['membership'] === '') {
                $result['membership'] = trim((string) ($row['MembershipNumber'] ?? ''));
            }
        }
        $year = eca_latest_membership_year($conn, [
            'MembershipNumber' => $result['membership'],
            'client_id' => $result['client_id'],
        ]);
        if ($year) {
            $result['year'] = (string) ($year['year'] ?? '');
            $result['year_status'] = (string) ($year['status'] ?? '');
            $result['year_type'] = (string) ($year['type'] ?? '');
            $exp = trim((string) ($year['expiry_date'] ?? ''));
            if ($exp !== '') {
                $result['expiry'] = $exp;
                $result['expiry_source'] = 'membership year';
            }
            $result['year_state'] = eca_year_expiry_state($year);
        }
        if ($result['client_id'] > 0) {
            $certStmt = $conn->prepare(
                'SELECT id, certificate_number, status, issued_at, expiry_date
                 FROM membership_certificates
                 WHERE client_id = ? AND status = ?
                 ORDER BY id DESC LIMIT 1'
            );
            $certStmt->execute([$result['client_id'], 'ACTIVE']);
            $cert = $certStmt->fetch(PDO::FETCH_ASSOC);
            if ($cert) {
                $result['certificate_id'] = (int) ($cert['id'] ?? 0);
                $result['certificate_number'] = (string) ($cert['certificate_number'] ?? $result['certificate_number']);
                $result['certificate_status'] = (string) ($cert['status'] ?? '');
                $result['certificate_issued'] = (string) ($cert['issued_at'] ?? '');
                $result['certificate_expiry'] = (string) ($cert['expiry_date'] ?? '');
                if ($result['expiry'] === '' && $result['certificate_expiry'] !== '') {
                    $result['expiry'] = $result['certificate_expiry'];
                    $result['expiry_source'] = 'certificate';
                }
            }
        }
    } catch (Throwable $e) {
        return $result;
    }
    return $result;
}

function eca_member_applications(?PDO $conn, array $member, int $limit = 20): array
{
    $membership = trim((string) ($member['membership'] ?? $member['MembershipNumber'] ?? ''));
    $clientId = (int) ($member['client_id'] ?? 0);
    if (!$conn || ($membership === '' && $clientId < 1)) {
        return [];
    }
    $ors = [];
    $params = [];
    if ($membership !== '') {
        $ors[] = 'MembershipNumber = ?';
        $params[] = $membership;
    }
    if ($clientId > 0) {
        $ors[] = 'client_id = ?';
        $params[] = $clientId;
    }
    try {
        $sql = 'SELECT client_id, application_reference, application_status, created_at, TradingName
                FROM tbl_client
                WHERE application_reference IS NOT NULL AND application_reference <> \'\'
                  AND (' . implode(' OR ', $ors) . ')
                ORDER BY created_at DESC LIMIT ' . max(1, min(50, $limit));
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function eca_member_certificate_history(?PDO $conn, int $clientId, int $limit = 20): array
{
    if (!$conn || $clientId < 1) {
        return [];
    }
    try {
        $stmt = $conn->prepare(
            'SELECT id, certificate_number, status, issued_at, expiry_date
             FROM membership_certificates
             WHERE client_id = ?
             ORDER BY id DESC LIMIT ' . max(1, min(50, $limit))
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function eca_member_year_history(?PDO $conn, array $member, int $limit = 20): array
{
    if (!$conn) {
        return [];
    }
    $membership = trim((string) ($member['membership'] ?? $member['MembershipNumber'] ?? ''));
    $clientId = (int) ($member['client_id'] ?? 0);
    try {
        if ($membership !== '') {
            $stmt = $conn->prepare(
                'SELECT year, type, status, payment_date, expiry_date
                 FROM membership_years
                 WHERE membership_number = ?
                 ORDER BY year DESC, id DESC LIMIT ' . max(1, min(50, $limit))
            );
            $stmt->execute([$membership]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                return $rows;
            }
        }
        if ($clientId > 0) {
            $stmt = $conn->prepare(
                'SELECT year, type, status, payment_date, expiry_date
                 FROM membership_years
                 WHERE client_id = ?
                 ORDER BY year DESC, id DESC LIMIT ' . max(1, min(50, $limit))
            );
            $stmt->execute([$clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        return [];
    }
    return [];
}

function eca_record_membership_year_from_payment(PDO $conn, int $clientId, string $membership, string $year, ?string $paymentDate = null): bool
{
    $year = preg_replace('/\D/', '', $year);
    $membership = trim($membership);
    if ($clientId < 1 || strlen($year) !== 4) {
        return false;
    }
    try {
        $exists = $conn->prepare('SELECT id FROM membership_years WHERE client_id = ? AND year = ? LIMIT 1');
        $exists->execute([$clientId, $year]);
        if ($exists->fetchColumn()) {
            return false;
        }
        $type = 'New';
        $prior = $conn->prepare('SELECT id FROM membership_years WHERE client_id = ? LIMIT 1');
        $prior->execute([$clientId]);
        if ($prior->fetchColumn()) {
            $type = 'Renewal';
        }
        $payDate = null;
        if ($paymentDate && strtotime($paymentDate) !== false) {
            $payDate = date('Y-m-d', strtotime($paymentDate));
        }
        $stmt = $conn->prepare(
            'INSERT INTO membership_years (client_id, membership_number, year, type, status, payment_date, amount)
             VALUES (?, ?, ?, ?, ?, ?, 0)'
        );
        return $stmt->execute([$clientId, $membership !== '' ? $membership : null, $year, $type, 'Active', $payDate]);
    } catch (Throwable $e) {
        error_log('membership year payment record failed: ' . $e->getMessage());
        return false;
    }
}

function eca_normalize_owner_gender(string $gender): string
{
    $key = strtolower(trim($gender));
    if (in_array($key, ['f', 'female'], true)) {
        return 'Female';
    }
    if (in_array($key, ['m', 'male'], true)) {
        return 'Male';
    }
    return '';
}

function eca_normalize_owner_citizen(string $citizen): string
{
    $key = strtolower(trim(preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', $citizen)) ?? $citizen));
    if ($key === '') {
        return '';
    }
    if (in_array($key, ['swazi', 'swati', 'liswati', 'eswatini'], true)) {
        return 'Swazi';
    }
    if (str_contains($key, 'non')) {
        return 'Non-Swazi';
    }
    return '';
}

function eca_lookup_member_by_number(PDO $conn, string $membership): array
{
    $membership = trim($membership);
    $empty = ['found' => false, 'member' => null, 'owners' => []];
    if ($membership === '') {
        return $empty;
    }

    $client = null;
    try {
        $stmt = $conn->prepare(
            'SELECT client_id, CompanyRegistrationName, TradingName, EmailAddress, Cellphone,
                    telephone, address, Region, Clasification, MembershipNumber, Status, active
             FROM tbl_client
             WHERE MembershipNumber = ? OR LOWER(MembershipNumber) = LOWER(?)
             ORDER BY
               CASE
                 WHEN LOWER(COALESCE(active, \'\')) = \'active\' THEN 0
                 WHEN LOWER(COALESCE(Status, \'\')) IN (\'active\', \'renewal\') THEN 1
                 WHEN UPPER(COALESCE(application_status, \'\')) = \'APPROVED\' THEN 2
                 ELSE 3
               END,
               client_id DESC
             LIMIT 1'
        );
        $stmt->execute([$membership, $membership]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $client = null;
    }

    $user = null;
    try {
        $stmt = $conn->prepare(
            'SELECT membership_number, full_name, email
             FROM userss
             WHERE membership_number = ? OR LOWER(membership_number) = LOWER(?)
             LIMIT 1'
        );
        $stmt->execute([$membership, $membership]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $user = null;
    }

    $company = null;
    if (!$client) {
        try {
            $stmt = $conn->prepare(
                'SELECT name, registration_number, email, phone, address, industry
                 FROM companies
                 WHERE registration_number = ? OR LOWER(registration_number) = LOWER(?)
                 LIMIT 1'
            );
            $stmt->execute([$membership, $membership]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            $company = null;
        }
    }

    if (!$client && !$user && !$company) {
        return $empty;
    }

    $client = is_array($client) ? $client : [];
    $user = is_array($user) ? $user : [];
    $company = is_array($company) ? $company : [];

    $registered = trim((string) ($client['CompanyRegistrationName'] ?? ''));
    $trading = trim((string) ($client['TradingName'] ?? ''));
    $email = trim((string) ($client['EmailAddress'] ?? ''));
    $cell = trim((string) ($client['Cellphone'] ?? ''));
    $classification = trim((string) ($client['Clasification'] ?? ''));
    $number = trim((string) ($client['MembershipNumber'] ?? ''));

    if ($company) {
        if ($registered === '') {
            $registered = trim((string) ($company['name'] ?? ''));
        }
        if ($trading === '') {
            $trading = trim((string) ($company['name'] ?? ''));
        }
        if ($email === '') {
            $email = trim((string) ($company['email'] ?? ''));
        }
        if ($cell === '') {
            $cell = trim((string) ($company['phone'] ?? ''));
        }
        if ($classification === '') {
            $classification = trim((string) ($company['industry'] ?? ''));
        }
        if ($number === '') {
            $number = trim((string) ($company['registration_number'] ?? ''));
        }
    }

    if ($user) {
        if ($email === '') {
            $email = trim((string) ($user['email'] ?? ''));
        }
        if ($trading === '') {
            $trading = trim((string) ($user['full_name'] ?? ''));
        }
        if ($registered === '') {
            $registered = trim((string) ($user['full_name'] ?? ''));
        }
        if ($number === '') {
            $number = trim((string) ($user['membership_number'] ?? ''));
        }
    }

    if ($number === '') {
        $number = $membership;
    }

    $resolved = eca_resolve_specialisation($classification);
    $member = [
        'membership_number' => $number,
        'registered_name' => $registered,
        'trading_name' => $trading,
        'email' => $email,
        'cellphone' => $cell,
        'specialisation' => $resolved['canonical'],
        'specialisation_other' => $resolved['canonical'] === 'Specialist' ? $resolved['other'] : '',
        'region' => trim((string) ($client['Region'] ?? $company['address'] ?? '')),
    ];

    $owners = [];
    $clientIds = [];
    if (!empty($client['client_id'])) {
        $clientIds[] = (int) $client['client_id'];
    }
    try {
        $idStmt = $conn->prepare(
            'SELECT client_id FROM tbl_client
             WHERE MembershipNumber = ? OR LOWER(MembershipNumber) = LOWER(?)'
        );
        $idStmt->execute([$membership, $membership]);
        foreach ($idStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $id = (int) ($row['client_id'] ?? 0);
            if ($id > 0 && !in_array($id, $clientIds, true)) {
                $clientIds[] = $id;
            }
        }
    } catch (Throwable $e) {
        // keep the primary client id only
    }

    if ($clientIds) {
        try {
            $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
            $ownerStmt = $conn->prepare(
                "SELECT name, citizen, gender, shares
                 FROM owners
                 WHERE clientid IN ({$placeholders}) OR application_id IN ({$placeholders})
                 ORDER BY id ASC"
            );
            $ownerStmt->execute([...$clientIds, ...$clientIds]);
            $seen = [];
            foreach ($ownerStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $key = strtolower($name . '|' . (string) ($row['shares'] ?? ''));
                if ($name === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $owners[] = [
                    'name' => $name,
                    'gender' => eca_normalize_owner_gender((string) ($row['gender'] ?? '')),
                    'citizen' => eca_normalize_owner_citizen((string) ($row['citizen'] ?? '')),
                    'shares' => trim((string) ($row['shares'] ?? '')),
                ];
            }
        } catch (Throwable $e) {
            $owners = [];
        }
    }

    return [
        'found' => true,
        'member' => $member,
        'owners' => $owners,
    ];
}

function eca_next_membership_number(PDO $conn): string
{
    $max = 1000;
    $scan = static function (PDO $conn, string $sql) use (&$max): void {
        try {
            $stmt = $conn->query($sql);
            if (!$stmt) {
                return;
            }
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $value) {
                if (preg_match('/^ECA-(\d+)$/', trim((string) $value), $m)) {
                    $max = max($max, (int) $m[1]);
                }
            }
        } catch (Throwable $e) {
            // keep current max
        }
    };
    $scan($conn, "SELECT MembershipNumber FROM tbl_client WHERE MembershipNumber LIKE 'ECA-%'");
    $scan($conn, "SELECT membership_number FROM userss WHERE membership_number LIKE 'ECA-%'");
    return 'ECA-' . (string) ($max + 1);
}

function eca_userss_password_usable(?array $row): bool
{
    if (!$row) {
        return false;
    }
    foreach (['password', 'password_hash'] as $key) {
        $stored = trim((string) ($row[$key] ?? ''));
        if ($stored === '') {
            continue;
        }
        $info = password_get_info($stored);
        if (!empty($info['algo'])) {
            return true;
        }
    }
    return false;
}

/**
 * Ensure an approved applicant can sign in at /client/ using the existing userss MEMBER login.
 * Generates a temporary password only when creating (or filling) that hub account — same
 * bin2hex(random_bytes(12)) + password_hash pattern used on CPD approve. Does not invent a new auth scheme.
 *
 * @return array{membership:string,password:?string,created:bool}
 */
function eca_provision_member_hub_login(PDO $conn, array $client): array
{
    $empty = ['membership' => '', 'password' => null, 'created' => false];
    $clientId = (int) ($client['client_id'] ?? 0);
    if ($clientId < 1) {
        return $empty;
    }

    $email = strtolower(trim((string) ($client['EmailAddress'] ?? '')));
    $name = trim((string) ($client['TradingName'] ?? $client['CompanyRegistrationName'] ?? 'ECA Member'));
    if ($name === '') {
        $name = 'ECA Member';
    }
    $membership = trim((string) ($client['MembershipNumber'] ?? ''));

    $byEmail = null;
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $stmt = $conn->prepare('SELECT * FROM userss WHERE LOWER(email) = LOWER(?) LIMIT 1');
            $stmt->execute([$email]);
            $byEmail = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            $byEmail = null;
        }
    }

    if ($membership === '' && $byEmail) {
        $membership = trim((string) ($byEmail['membership_number'] ?? ''));
    }
    if ($membership === '') {
        $membership = eca_next_membership_number($conn);
    }

    try {
        $conn->prepare(
            "UPDATE tbl_client SET MembershipNumber = ?
             WHERE client_id = ? AND (MembershipNumber IS NULL OR MembershipNumber = '')"
        )->execute([$membership, $clientId]);
    } catch (Throwable $e) {
        error_log('Could not save membership number on approve: ' . $e->getMessage());
    }

    $byMember = null;
    try {
        $stmt = $conn->prepare('SELECT * FROM userss WHERE membership_number = ? LIMIT 1');
        $stmt->execute([$membership]);
        $byMember = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $byMember = null;
    }

    $row = $byMember ?: $byEmail;
    if ($row && eca_userss_password_usable($row)) {
        $loginMembership = trim((string) ($row['membership_number'] ?? ''));
        if ($loginMembership === '') {
            $loginMembership = $membership;
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0 && $loginMembership !== '') {
                try {
                    $conn->prepare('UPDATE userss SET membership_number = ? WHERE id = ? AND (membership_number IS NULL OR membership_number = \'\')')
                        ->execute([$loginMembership, $id]);
                } catch (Throwable $e) {
                    error_log('Could not attach membership number to hub login: ' . $e->getMessage());
                }
            }
        }
        return ['membership' => $loginMembership !== '' ? $loginMembership : $membership, 'password' => null, 'created' => false];
    }

    $emailForInsert = ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null;
    if ($emailForInsert === null && !$row) {
        return ['membership' => $membership, 'password' => null, 'created' => false];
    }

    $plain = bin2hex(random_bytes(12));
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    if ($hash === false) {
        return ['membership' => $membership, 'password' => null, 'created' => false];
    }

    try {
        if ($row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $conn->prepare(
                    "UPDATE userss
                     SET password = ?, role = 'MEMBER', status = 'ACTIVE',
                         membership_number = CASE
                           WHEN membership_number IS NULL OR membership_number = '' THEN ?
                           ELSE membership_number
                         END
                     WHERE id = ?"
                )->execute([$hash, $membership, $id]);
                $loginMembership = trim((string) ($row['membership_number'] ?? ''));
                if ($loginMembership === '') {
                    $loginMembership = $membership;
                }
                return ['membership' => $loginMembership, 'password' => $plain, 'created' => false];
            }
        }
        $conn->prepare(
            "INSERT INTO userss (membership_number, full_name, email, password, role, status)
             VALUES (?, ?, ?, ?, 'MEMBER', 'ACTIVE')"
        )->execute([$membership, $name, $emailForInsert, $hash]);
        return ['membership' => $membership, 'password' => $plain, 'created' => true];
    } catch (Throwable $e) {
        error_log('Member hub login could not be provisioned: ' . $e->getMessage());
        return ['membership' => $membership, 'password' => null, 'created' => false];
    }
}
