<?php

function eca_project_folder_column(PDO $conn, string $table, string $column, string $ddl): void
{
    static $cache = [];
    $table = str_replace('`', '', $table);
    if (!isset($cache[$table])) {
        $cache[$table] = [];
        try {
            $rows = $conn->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                $cache[$table][strtolower((string) ($row['Field'] ?? ''))] = true;
            }
        } catch (Throwable $e) {
            $cache[$table] = [];
        }
    }
    if (isset($cache[$table][strtolower($column)])) {
        return;
    }
    try {
        $conn->exec('ALTER TABLE `' . $table . '` ADD COLUMN ' . $ddl);
        $cache[$table][strtolower($column)] = true;
    } catch (Throwable $e) {
        // Column already present or table is mid-migrate.
    }
}

function eca_project_folder_schema(PDO $conn): void
{
    eca_project_folder_column($conn, 'member_projects', 'contract_value', 'contract_value DECIMAL(14,2) NOT NULL DEFAULT 0.00');
    eca_project_folder_column($conn, 'member_projects', 'location', 'location VARCHAR(255) DEFAULT NULL');
    eca_project_folder_column($conn, 'member_projects', 'current_stage', 'current_stage VARCHAR(190) DEFAULT NULL');
    eca_project_folder_column($conn, 'member_projects', 'manager_name', 'manager_name VARCHAR(190) DEFAULT NULL');
    eca_project_folder_column($conn, 'member_projects', 'manager_phone', 'manager_phone VARCHAR(64) DEFAULT NULL');
    eca_project_folder_column($conn, 'member_projects', 'manager_email', 'manager_email VARCHAR(190) DEFAULT NULL');
    eca_project_folder_column($conn, 'member_projects', 'health_status', "health_status VARCHAR(32) NOT NULL DEFAULT 'on_track'");
    eca_project_folder_column($conn, 'member_projects', 'current_challenge', 'current_challenge VARCHAR(64) DEFAULT NULL');
    eca_project_folder_column($conn, 'member_projects', 'issue_type', 'issue_type VARCHAR(190) DEFAULT NULL');
    eca_project_folder_column($conn, 'member_projects', 'issue_amount', 'issue_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00');
    eca_project_folder_column($conn, 'member_projects', 'issue_days', 'issue_days INT NOT NULL DEFAULT 0');
    eca_project_folder_column($conn, 'member_projects', 'issue_impact', 'issue_impact VARCHAR(190) DEFAULT NULL');
    eca_project_folder_column($conn, 'member_projects', 'eca_action', 'eca_action VARCHAR(190) DEFAULT NULL');
    eca_project_folder_column($conn, 'member_projects', 'eca_action_status', 'eca_action_status VARCHAR(64) DEFAULT NULL');

    $conn->exec(
        'CREATE TABLE IF NOT EXISTS member_project_packages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id INT UNSIGNED NOT NULL,
            membership_number VARCHAR(64) NOT NULL,
            name VARCHAR(190) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT \'not_started\',
            sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_member_project_packages_project (project_id),
            KEY idx_member_project_packages_member (membership_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $conn->exec(
        'CREATE TABLE IF NOT EXISTS member_project_timeline (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id INT UNSIGNED NOT NULL,
            membership_number VARCHAR(64) NOT NULL,
            event_date VARCHAR(32) DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT \'done\',
            sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_member_project_timeline_project (project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $conn->exec(
        'CREATE TABLE IF NOT EXISTS member_contractor_capacity (
            membership_number VARCHAR(64) NOT NULL,
            employees INT UNSIGNED NOT NULL DEFAULT 0,
            technical_staff INT UNSIGNED NOT NULL DEFAULT 0,
            skilled_workers INT UNSIGNED NOT NULL DEFAULT 0,
            administrative INT UNSIGNED NOT NULL DEFAULT 0,
            excavators INT UNSIGNED NOT NULL DEFAULT 0,
            tlb INT UNSIGNED NOT NULL DEFAULT 0,
            trucks INT UNSIGNED NOT NULL DEFAULT 0,
            other_equipment INT UNSIGNED NOT NULL DEFAULT 0,
            spec_building TINYINT UNSIGNED NOT NULL DEFAULT 0,
            spec_roads TINYINT UNSIGNED NOT NULL DEFAULT 0,
            spec_civil TINYINT UNSIGNED NOT NULL DEFAULT 0,
            spec_water TINYINT UNSIGNED NOT NULL DEFAULT 0,
            completed_projects INT UNSIGNED NOT NULL DEFAULT 0,
            total_project_value VARCHAR(64) DEFAULT NULL,
            private_projects INT UNSIGNED NOT NULL DEFAULT 0,
            years_operating INT UNSIGNED NOT NULL DEFAULT 0,
            active_projects INT UNSIGNED NOT NULL DEFAULT 0,
            active_value VARCHAR(64) DEFAULT NULL,
            attention_projects INT UNSIGNED NOT NULL DEFAULT 0,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (membership_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function eca_project_health_options(): array
{
    return [
        'on_track' => 'On track',
        'needs_attention' => 'Needs attention',
        'critical' => 'Critical',
    ];
}

function eca_project_challenge_options(): array
{
    return [
        'payment_delay' => 'Payment delay',
        'variation_approval' => 'Variation approval',
        'certificate_unpaid' => 'Certificate not paid',
        'material_supply' => 'Material supply',
        'client_issue' => 'Client issue',
        'design_issue' => 'Design/consultant issue',
        'labour_issue' => 'Labour issue',
        'cashflow' => 'Cashflow issue',
        'other' => 'Other',
    ];
}

function eca_project_package_status_options(): array
{
    return [
        'completed' => 'Completed',
        'in_progress' => 'In progress',
        'not_started' => 'Not started',
    ];
}

function eca_project_default_package_names(): array
{
    return [
        'Site establishment',
        'Foundations',
        'Structural works',
        'Roofing',
        'Electrical',
        'Finishes',
    ];
}

function eca_project_action_status_options(): array
{
    return ['Under review', 'In progress', 'Resolved', 'Not required'];
}

function eca_project_folder_decimal($value): string
{
    $raw = preg_replace('/[^0-9.]/', '', (string) $value);
    return $raw === '' ? '0.00' : number_format((float) $raw, 2, '.', '');
}

function eca_project_folder_int($value, int $min = 0, int $max = 1000000): int
{
    return max($min, min($max, (int) $value));
}

function eca_project_save_folder_fields(PDO $conn, int $id, string $membership, array $input): void
{
    if ($id < 1 || $membership === '') {
        return;
    }
    eca_project_folder_schema($conn);
    $health = trim((string) ($input['health_status'] ?? 'on_track'));
    if (!isset(eca_project_health_options()[$health])) {
        $health = 'on_track';
    }
    $challenge = trim((string) ($input['current_challenge'] ?? ''));
    if ($challenge !== '' && !isset(eca_project_challenge_options()[$challenge])) {
        $challenge = '';
    }
    $actionStatus = trim((string) ($input['eca_action_status'] ?? ''));
    if ($actionStatus !== '' && !in_array($actionStatus, eca_project_action_status_options(), true)) {
        $actionStatus = '';
    }
    $stmt = $conn->prepare(
        'UPDATE member_projects SET
            contract_value = ?, location = ?, current_stage = ?, manager_name = ?,
            manager_phone = ?, manager_email = ?, health_status = ?, current_challenge = ?,
            issue_type = ?, issue_amount = ?, issue_days = ?, issue_impact = ?,
            eca_action = ?, eca_action_status = ?
         WHERE id = ? AND membership_number = ?'
    );
    $stmt->execute([
        eca_project_folder_decimal($input['contract_value'] ?? '0'),
        trim((string) ($input['location'] ?? '')),
        trim((string) ($input['current_stage'] ?? '')),
        trim((string) ($input['manager_name'] ?? '')),
        trim((string) ($input['manager_phone'] ?? '')),
        trim((string) ($input['manager_email'] ?? '')),
        $health,
        $challenge !== '' ? $challenge : null,
        trim((string) ($input['issue_type'] ?? '')),
        eca_project_folder_decimal($input['issue_amount'] ?? '0'),
        eca_project_folder_int($input['issue_days'] ?? 0, 0, 3650),
        trim((string) ($input['issue_impact'] ?? '')),
        trim((string) ($input['eca_action'] ?? '')),
        $actionStatus !== '' ? $actionStatus : null,
        $id,
        $membership,
    ]);
}

function eca_project_packages(PDO $conn, int $projectId, string $membership): array
{
    if ($projectId < 1 || $membership === '') {
        return [];
    }
    eca_project_folder_schema($conn);
    $stmt = $conn->prepare(
        'SELECT * FROM member_project_packages
         WHERE project_id = ? AND membership_number = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$projectId, $membership]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function eca_project_timeline(PDO $conn, int $projectId, string $membership): array
{
    if ($projectId < 1 || $membership === '') {
        return [];
    }
    eca_project_folder_schema($conn);
    $stmt = $conn->prepare(
        'SELECT * FROM member_project_timeline
         WHERE project_id = ? AND membership_number = ?
         ORDER BY sort_order ASC, id ASC'
    );
    $stmt->execute([$projectId, $membership]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function eca_project_ensure_packages(PDO $conn, int $projectId, string $membership, array $preset = []): void
{
    if ($projectId < 1 || $membership === '' || eca_project_packages($conn, $projectId, $membership)) {
        return;
    }
    $names = eca_project_default_package_names();
    $insert = $conn->prepare(
        'INSERT INTO member_project_packages (project_id, membership_number, name, status, sort_order)
         VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($names as $i => $name) {
        $status = $preset[$name] ?? 'not_started';
        if (!isset(eca_project_package_status_options()[$status])) {
            $status = 'not_started';
        }
        $insert->execute([$projectId, $membership, $name, $status, $i]);
    }
}

function eca_project_ensure_timeline(PDO $conn, int $projectId, string $membership, array $events = []): void
{
    if ($projectId < 1 || $membership === '' || eca_project_timeline($conn, $projectId, $membership)) {
        return;
    }
    if (!$events) {
        $events = [
            ['Mar 2026', 'Contract awarded', 'done'],
            ['Apr 2026', 'Site established', 'done'],
            ['May 2026', 'Works commenced', 'done'],
        ];
    }
    $insert = $conn->prepare(
        'INSERT INTO member_project_timeline (project_id, membership_number, event_date, title, status, sort_order)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($events as $i => $event) {
        $insert->execute([$projectId, $membership, $event[0], $event[1], $event[2], $i]);
    }
}

function eca_project_save_packages(PDO $conn, int $projectId, string $membership, array $statuses, ?int $progress = null): void
{
    $rows = eca_project_packages($conn, $projectId, $membership);
    $upd = $conn->prepare(
        'UPDATE member_project_packages SET status = ? WHERE id = ? AND project_id = ? AND membership_number = ?'
    );
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $status = trim((string) ($statuses[$id] ?? $row['status'] ?? 'not_started'));
        if (!isset(eca_project_package_status_options()[$status])) {
            $status = 'not_started';
        }
        $upd->execute([$status, $id, $projectId, $membership]);
    }
    if ($progress !== null) {
        $stmt = $conn->prepare(
            'UPDATE member_projects SET progress = ? WHERE id = ? AND membership_number = ?'
        );
        $stmt->execute([max(0, min(100, $progress)), $projectId, $membership]);
    }
}

function eca_project_save_health(PDO $conn, int $projectId, string $membership, array $input): void
{
    eca_project_save_folder_fields($conn, $projectId, $membership, $input);
}

function eca_project_capacity_blank(array $member = [], array $stats = []): array
{
    $class = strtolower((string) ($member['classification'] ?? ''));
    return [
        'employees' => 24,
        'technical_staff' => 7,
        'skilled_workers' => 14,
        'administrative' => 3,
        'excavators' => 2,
        'tlb' => 1,
        'trucks' => 3,
        'other_equipment' => 1,
        'spec_building' => (int) (str_contains($class, 'build') || $class === ''),
        'spec_roads' => (int) str_contains($class, 'road'),
        'spec_civil' => (int) (str_contains($class, 'civil') || str_contains($class, 'electric')),
        'spec_water' => (int) (str_contains($class, 'water') || str_contains($class, 'sanit')),
        'completed_projects' => (int) ($stats['completed'] ?? 0),
        'total_project_value' => 'E130m',
        'private_projects' => 8,
        'years_operating' => 6,
        'active_projects' => max(1, (int) ($stats['total'] ?? 1) - (int) ($stats['completed'] ?? 0)),
        'active_value' => 'E32m',
        'attention_projects' => 1,
    ];
}

function eca_project_capacity_get(PDO $conn, string $membership): ?array
{
    if ($membership === '') {
        return null;
    }
    eca_project_folder_schema($conn);
    $stmt = $conn->prepare('SELECT * FROM member_contractor_capacity WHERE membership_number = ? LIMIT 1');
    $stmt->execute([$membership]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function eca_project_capacity_ensure(PDO $conn, array $member, array $stats = []): array
{
    $membership = trim((string) ($member['membership'] ?? ''));
    if ($membership === '') {
        return eca_project_capacity_blank($member, $stats);
    }
    $row = eca_project_capacity_get($conn, $membership);
    if ($row) {
        return $row;
    }
    $blank = eca_project_capacity_blank($member, $stats);
    $stmt = $conn->prepare(
        'INSERT INTO member_contractor_capacity (
            membership_number, employees, technical_staff, skilled_workers, administrative,
            excavators, tlb, trucks, other_equipment, spec_building, spec_roads, spec_civil, spec_water,
            completed_projects, total_project_value, private_projects, years_operating,
            active_projects, active_value, attention_projects
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $membership,
        $blank['employees'],
        $blank['technical_staff'],
        $blank['skilled_workers'],
        $blank['administrative'],
        $blank['excavators'],
        $blank['tlb'],
        $blank['trucks'],
        $blank['other_equipment'],
        $blank['spec_building'],
        $blank['spec_roads'],
        $blank['spec_civil'],
        $blank['spec_water'],
        $blank['completed_projects'],
        $blank['total_project_value'],
        $blank['private_projects'],
        $blank['years_operating'],
        $blank['active_projects'],
        $blank['active_value'],
        $blank['attention_projects'],
    ]);
    return eca_project_capacity_get($conn, $membership) ?: $blank;
}

function eca_project_capacity_save(PDO $conn, string $membership, array $input): void
{
    if ($membership === '') {
        return;
    }
    eca_project_folder_schema($conn);
    $fields = [
        'employees', 'technical_staff', 'skilled_workers', 'administrative',
        'excavators', 'tlb', 'trucks', 'other_equipment',
        'completed_projects', 'private_projects', 'years_operating',
        'active_projects', 'attention_projects',
    ];
    $row = [
        $membership,
    ];
    foreach ($fields as $key) {
        $row[] = eca_project_folder_int($input[$key] ?? 0, 0, 100000);
    }
    $row[] = (int) !empty($input['spec_building']);
    $row[] = (int) !empty($input['spec_roads']);
    $row[] = (int) !empty($input['spec_civil']);
    $row[] = (int) !empty($input['spec_water']);
    $row[] = trim((string) ($input['total_project_value'] ?? ''));
    $row[] = trim((string) ($input['active_value'] ?? ''));
    $stmt = $conn->prepare(
        'INSERT INTO member_contractor_capacity (
            membership_number, employees, technical_staff, skilled_workers, administrative,
            excavators, tlb, trucks, other_equipment, completed_projects, private_projects,
            years_operating, active_projects, attention_projects, spec_building, spec_roads,
            spec_civil, spec_water, total_project_value, active_value
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            employees = VALUES(employees),
            technical_staff = VALUES(technical_staff),
            skilled_workers = VALUES(skilled_workers),
            administrative = VALUES(administrative),
            excavators = VALUES(excavators),
            tlb = VALUES(tlb),
            trucks = VALUES(trucks),
            other_equipment = VALUES(other_equipment),
            completed_projects = VALUES(completed_projects),
            private_projects = VALUES(private_projects),
            years_operating = VALUES(years_operating),
            active_projects = VALUES(active_projects),
            attention_projects = VALUES(attention_projects),
            spec_building = VALUES(spec_building),
            spec_roads = VALUES(spec_roads),
            spec_civil = VALUES(spec_civil),
            spec_water = VALUES(spec_water),
            total_project_value = VALUES(total_project_value),
            active_value = VALUES(active_value)'
    );
    $stmt->execute($row);
}

function eca_project_folder_money($value): string
{
    $amount = (float) $value;
    if ($amount <= 0) {
        return '—';
    }
    if (abs($amount - round($amount)) < 0.001) {
        return 'E' . number_format($amount, 0, '.', ',');
    }
    return 'E' . number_format($amount, 2, '.', ',');
}

function eca_project_format_month($value): string
{
    $value = trim((string) $value);
    if ($value === '' || $value === '0000-00-00') {
        return '—';
    }
    $ts = strtotime($value);
    return $ts ? date('M Y', $ts) : '—';
}

function eca_project_industry_overview(PDO $conn): array
{
    $overview = [
        'registered' => 0,
        'active_contractors' => 0,
        'executing' => 0,
        'active_value' => 0.0,
        'active_projects' => 0,
        'on_track' => 0,
        'needs_attention' => 0,
        'critical' => 0,
        'challenges' => 0,
    ];
    try {
        $overview['registered'] = (int) $conn->query(
            "SELECT COUNT(*) FROM tbl_client
             WHERE UPPER(COALESCE(Status, '')) IN ('ACTIVE', 'APPROVED')
                OR UPPER(COALESCE(active, '')) IN ('ACTIVE', '1', 'YES')"
        )->fetchColumn();
    } catch (Throwable $e) {
        try {
            $overview['registered'] = (int) $conn->query('SELECT COUNT(*) FROM tbl_client')->fetchColumn();
        } catch (Throwable $e2) {
            $overview['registered'] = 0;
        }
    }
    try {
        $overview['active_contractors'] = (int) $conn->query(
            "SELECT COUNT(*) FROM userss WHERE UPPER(status) = 'ACTIVE' AND UPPER(role) = 'MEMBER'"
        )->fetchColumn();
    } catch (Throwable $e) {
        $overview['active_contractors'] = $overview['registered'];
    }
    try {
        $row = $conn->query(
            "SELECT
                COUNT(*) AS total,
                COUNT(DISTINCT membership_number) AS executing,
                COALESCE(SUM(contract_value), 0) AS value_sum,
                SUM(CASE WHEN health_status = 'on_track' THEN 1 ELSE 0 END) AS on_track,
                SUM(CASE WHEN health_status = 'needs_attention' THEN 1 ELSE 0 END) AS needs_attention,
                SUM(CASE WHEN health_status = 'critical' THEN 1 ELSE 0 END) AS critical,
                SUM(CASE WHEN current_challenge IS NOT NULL AND current_challenge <> '' THEN 1 ELSE 0 END) AS challenges
             FROM member_projects
             WHERE UPPER(status) = 'ACTIVE'"
        )->fetch(PDO::FETCH_ASSOC);
        $overview['active_projects'] = (int) ($row['total'] ?? 0);
        $overview['executing'] = (int) ($row['executing'] ?? 0);
        $overview['active_value'] = (float) ($row['value_sum'] ?? 0);
        $overview['on_track'] = (int) ($row['on_track'] ?? 0);
        $overview['needs_attention'] = (int) ($row['needs_attention'] ?? 0);
        $overview['critical'] = (int) ($row['critical'] ?? 0);
        $overview['challenges'] = (int) ($row['challenges'] ?? 0);
    } catch (Throwable $e) {
        // Keep zeros when the folder columns are not ready.
    }
    $total = max(1, $overview['active_projects']);
    $overview['on_track_pct'] = (int) round(($overview['on_track'] / $total) * 100);
    $overview['needs_pct'] = (int) round(($overview['needs_attention'] / $total) * 100);
    $overview['critical_pct'] = (int) round(($overview['critical'] / $total) * 100);
    return $overview;
}

function eca_project_demo_payload(array $member): array
{
    $region = trim((string) ($member['region'] ?? '')) ?: 'Manzini';
    return [
        'title' => 'Construction of Community Health Centre',
        'description' => 'Construction of a 25-bed community health centre including consultation rooms, maternity ward, pharmacy and administration block.',
        'client_entity' => 'Government',
        'contract_type' => 'Lump Sum',
        'start_date' => '2026-03-01',
        'finish_date' => '2026-12-31',
        'status' => 'ACTIVE',
        'progress' => 62,
        'pending_vo' => 1,
        'unapproved_vo_value' => '850000.00',
        'eot_status' => 'NOT REQUESTED',
        'intervention' => 'No',
        'assigned_staff' => 'Sipho Dlamini',
        'contract_value' => '12500000.00',
        'location' => $region,
        'current_stage' => 'Structural works',
        'manager_name' => 'Sipho Dlamini',
        'manager_phone' => trim((string) ($member['phone'] ?? '')) ?: '+268 760 123 456',
        'manager_email' => trim((string) ($member['email'] ?? '')) ?: 'sipho@example.test',
        'health_status' => 'needs_attention',
        'current_challenge' => 'certificate_unpaid',
        'issue_type' => 'Payment certificate outstanding',
        'issue_amount' => '850000.00',
        'issue_days' => 74,
        'issue_impact' => 'Cash flow pressure',
        'eca_action' => 'Engagement required',
        'eca_action_status' => 'Under review',
    ];
}

function eca_project_ensure_demo(PDO $conn, array $member): void
{
    $membership = trim((string) ($member['membership'] ?? ''));
    if ($membership === '' || eca_project_records($conn, $membership)) {
        return;
    }
    $id = eca_project_save($conn, $member, eca_project_demo_payload($member));
    if ($id < 1) {
        return;
    }
    eca_project_ensure_packages($conn, $id, $membership, [
        'Site establishment' => 'completed',
        'Foundations' => 'completed',
        'Structural works' => 'in_progress',
        'Roofing' => 'in_progress',
        'Electrical' => 'not_started',
        'Finishes' => 'not_started',
    ]);
    eca_project_ensure_timeline($conn, $id, $membership, [
        ['Mar 2026', 'Contract awarded', 'done'],
        ['Apr 2026', 'Site established', 'done'],
        ['May 2026', 'Works commenced', 'done'],
        ['Jul 2026', 'First payment received', 'done'],
        ['Aug 2026', 'Variation submitted', 'done'],
        ['Sep 2026', 'Variation awaiting approval', 'current'],
        ['Current', 'Cashflow pressure', 'alert'],
    ]);
}

function eca_project_hydrate_folder(PDO $conn, array $member): void
{
    $membership = trim((string) ($member['membership'] ?? ''));
    if ($membership === '') {
        return;
    }
    eca_project_folder_schema($conn);
    eca_project_ensure_demo($conn, $member);
    $stats = eca_project_stats($conn, $membership);
    eca_project_capacity_ensure($conn, $member, $stats);
    foreach (eca_project_records($conn, $membership) as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id < 1) {
            continue;
        }
        $genericTitle = in_array(strtolower(trim((string) ($row['title'] ?? ''))), ['', 'untitled project', 'project portfolio'], true);
        if ($genericTitle && trim((string) ($row['description'] ?? '')) === '') {
            $demo = eca_project_demo_payload($member);
            eca_project_save($conn, $member, array_merge($demo, ['id' => $id]));
            $row = eca_project_get($conn, $id, $membership) ?: array_merge($row, $demo);
        }
        $progress = (int) ($row['progress'] ?? 0);
        $preset = [];
        if ($progress >= 50) {
            $preset = [
                'Site establishment' => 'completed',
                'Foundations' => 'completed',
                'Structural works' => 'in_progress',
                'Roofing' => 'in_progress',
                'Electrical' => 'not_started',
                'Finishes' => 'not_started',
            ];
        }
        eca_project_ensure_packages($conn, $id, $membership, $preset);
        $events = [];
        if (!empty($row['start_date'])) {
            $events[] = [eca_project_format_month($row['start_date']), 'Works commenced', 'done'];
        }
        if (!empty($row['finish_date'])) {
            $events[] = [eca_project_format_month($row['finish_date']), 'Expected completion', 'upcoming'];
        }
        if ((int) ($row['pending_vo'] ?? 0) > 0) {
            $events[] = ['Current', 'Variation awaiting approval', 'current'];
        }
        eca_project_ensure_timeline($conn, $id, $membership, $events);
        $needsFill = trim((string) ($row['location'] ?? '')) === ''
            || (float) ($row['contract_value'] ?? 0) <= 0
            || trim((string) ($row['manager_name'] ?? '')) === '';
        if ($needsFill && strtoupper((string) ($row['status'] ?? '')) === 'ACTIVE') {
            $demo = eca_project_demo_payload($member);
            $fill = [
                'contract_value' => (float) ($row['contract_value'] ?? 0) > 0 ? $row['contract_value'] : $demo['contract_value'],
                'location' => trim((string) ($row['location'] ?? '')) ?: $demo['location'],
                'current_stage' => trim((string) ($row['current_stage'] ?? '')) ?: $demo['current_stage'],
                'manager_name' => trim((string) ($row['manager_name'] ?? $row['assigned_staff'] ?? '')) ?: $demo['manager_name'],
                'manager_phone' => trim((string) ($row['manager_phone'] ?? '')) ?: $demo['manager_phone'],
                'manager_email' => trim((string) ($row['manager_email'] ?? '')) ?: $demo['manager_email'],
                'health_status' => trim((string) ($row['health_status'] ?? '')) ?: $demo['health_status'],
                'current_challenge' => trim((string) ($row['current_challenge'] ?? '')) ?: $demo['current_challenge'],
                'issue_type' => trim((string) ($row['issue_type'] ?? '')) ?: $demo['issue_type'],
                'issue_amount' => (float) ($row['issue_amount'] ?? 0) > 0 ? $row['issue_amount'] : $demo['issue_amount'],
                'issue_days' => (int) ($row['issue_days'] ?? 0) > 0 ? $row['issue_days'] : $demo['issue_days'],
                'issue_impact' => trim((string) ($row['issue_impact'] ?? '')) ?: $demo['issue_impact'],
                'eca_action' => trim((string) ($row['eca_action'] ?? '')) ?: $demo['eca_action'],
                'eca_action_status' => trim((string) ($row['eca_action_status'] ?? '')) ?: $demo['eca_action_status'],
            ];
            eca_project_save_folder_fields($conn, $id, $membership, $fill);
        }
    }
}

function eca_project_folder_tabs(): array
{
    return [
        'overview' => 'Project Overview',
        'progress' => 'Progress',
        'capacity' => 'Capacity Profile',
        'challenges' => 'Challenges & Support',
        'timeline' => 'Timeline',
        'documents' => 'Documents',
    ];
}

function eca_project_folder_nav(): array
{
    return [
        'dashboard' => ['/client/dashboard.php', 'fa-house', 'Dashboard'],
        'profile' => ['/client/profile.php', 'fa-user', 'My Profile'],
        'projects' => ['/client/projects.php', 'fa-folder', 'Projects Folder'],
        'cpd' => ['/client/cpd.php', 'fa-graduation-cap', 'Training & Development'],
        'wellness' => ['/client/wellness/', 'fa-heart-pulse', 'Wellness'],
        'payments' => ['/client/payments.php', 'fa-coins', 'Loans & Finance'],
        'notifications' => ['/client/notifications.php', 'fa-comments', 'Messages'],
        'support' => ['/contact.php', 'fa-headset', 'Support'],
        'settings' => ['/client/profile.php', 'fa-gear', 'Settings'],
    ];
}

function eca_project_folder_tab(string $tab): string
{
    $tabs = eca_project_folder_tabs();
    return isset($tabs[$tab]) ? $tab : 'overview';
}

function eca_project_folder_url(int $id = 0, string $tab = 'overview', array $extra = []): string
{
    $query = $extra;
    if ($id > 0) {
        $query['id'] = $id;
    }
    if ($tab !== '' && $tab !== 'overview') {
        $query['tab'] = $tab;
    }
    return '/client/projects.php' . ($query ? '?' . http_build_query($query) : '');
}

function eca_project_hero_src(?array $cover): string
{
    if ($cover) {
        return '/client/project-file.php?id=' . (int) $cover['id'];
    }
    return '/img/construction-site-f-compressed.jpg';
}

function eca_project_health_label(string $status): string
{
    return eca_project_health_options()[$status] ?? 'On track';
}
