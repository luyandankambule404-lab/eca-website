<?php
/**
 * Public enquiry type routing via contact subject prefixes.
 * No database schema changes — types are encoded in subject text
 * already stored on contact_messages.subject.
 */

/**
 * @return array<string,string> value => label
 */
function eca_enquiry_types(): array
{
    return [
        '' => 'General enquiry',
        'advocacy' => 'Advocacy / raise an industry issue',
        'technical' => 'Technical Support & Advisory',
        'wellness' => 'Wellness / counselling referral request',
        'membership' => 'Membership',
        'digital' => 'Directory / digital services',
        'cpd' => 'Training / CPD',
    ];
}

/**
 * Subject prefixes staff can search in Admin → Tickets.
 *
 * @return array<string,string>
 */
function eca_enquiry_subject_prefixes(): array
{
    return [
        'advocacy' => '[Advocacy]',
        'technical' => '[Technical Advisory]',
        'wellness' => '[Wellness Referral]',
        'membership' => '[Membership]',
        'digital' => '[Digital Services]',
        'cpd' => '[Training / CPD]',
    ];
}

function eca_enquiry_normalize_type(string $type): string
{
    $type = strtolower(trim($type));
    return array_key_exists($type, eca_enquiry_subject_prefixes()) ? $type : '';
}

/**
 * Apply enquiry type to subject without inventing new DB columns.
 */
function eca_enquiry_apply_subject(string $subject, string $type): string
{
    $subject = trim($subject);
    $type = eca_enquiry_normalize_type($type);
    if ($type === '') {
        return $subject;
    }
    $prefix = eca_enquiry_subject_prefixes()[$type];
    if ($subject === '') {
        $labels = eca_enquiry_types();
        return $prefix . ' ' . ($labels[$type] ?? 'Enquiry');
    }
    if (str_starts_with($subject, $prefix)) {
        return $subject;
    }
    // Avoid double-prefix if GET already set a long subject.
    foreach (eca_enquiry_subject_prefixes() as $p) {
        if (str_starts_with($subject, $p)) {
            return $subject;
        }
    }
    return $prefix . ' ' . $subject;
}

function eca_enquiry_type_from_request(): string
{
    $type = (string) ($_POST['enquiry_type'] ?? $_GET['enquiry_type'] ?? '');
    return eca_enquiry_normalize_type($type);
}
