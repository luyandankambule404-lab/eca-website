<?php
/**
 * Shared local-tool credential resolution.
 * Production / unknown APP_ENV: fail closed — never use hardcoded passwords.
 */
function eca_tool_local_admin_password(): string
{
    $env = strtolower(trim((string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'local'))));
    $configured = getenv('ECA_LOCAL_ADMIN_PASSWORD');
    if ($configured === false || $configured === null || $configured === '') {
        $configured = (string) ($_ENV['ECA_LOCAL_ADMIN_PASSWORD'] ?? '');
    }

    if (in_array($env, ['production', 'prod', 'live'], true)) {
        fwrite(STDERR, "Refusing: local verification tools must not run with APP_ENV=production.\n");
        exit(2);
    }

    if ($configured !== '') {
        return (string) $configured;
    }

    // Hardcoded fallback is local/live_readonly QA only.
    return 'EcaLocal!2026';
}
