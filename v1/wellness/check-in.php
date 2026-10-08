<?php
require_once dirname(__DIR__) . '/includes/wellness-hub.php';

$conn = eca_wellness_db();
eca_wellness_hub_ensure_schema($conn);

$questions = eca_wellness_checkin_questions();
$scale = [
    1 => 'Rarely',
    2 => 'Sometimes',
    3 => 'Often',
    4 => 'Very often',
    5 => 'Almost always',
];
$error = '';
$result = null;
$posted = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    eca_session_start();
    if (!eca_public_csrf_ok($_POST['csrf_token'] ?? null)) {
        $error = 'This form expired. Please try again.';
    } elseif (eca_rate_limit_exceeded('wellness-checkin', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 8, 900)) {
        $error = 'Please wait a moment before another check-in.';
    } else {
        $sum = 0;
        $ok = true;
        foreach (array_keys($questions) as $key) {
            $n = (int) ($_POST[$key] ?? 0);
            if ($n < 1 || $n > 5) {
                $ok = false;
                break;
            }
            $posted[$key] = $n;
            $sum += $n;
        }
        if (!$ok) {
            $error = 'Please answer every question.';
        } else {
            $average = $sum / count($questions);
            $band = eca_wellness_checkin_band($average);
            $result = eca_wellness_checkin_copy($band);
            $result[] = $band;
            eca_wellness_save_checkin($conn, $posted, $band);
        }
    }
}

$csrf = eca_public_csrf_token();
eca_wellness_page_start(
    'Contractor Check-In',
    'Contractor Check-In',
    'A short, confidential self-assessment for awareness and early support — not a diagnosis.',
    'checkin'
);
?>
<div class="edu-prose">
    <p>Five questions. No name, membership number or company is collected. ECA stores only anonymous scores so administrators can see general usage — not who answered.</p>
    <p>Use the result as a prompt to rest, talk or seek support. It is not a medical or psychological diagnosis.</p>
</div>
<?php eca_wellness_disclaimer(); ?>

<?php if ($error !== ''): ?>
    <p class="wh-alert" role="alert"><?= eca_wellness_h($error) ?></p>
<?php endif; ?>

<?php if ($result): ?>
    <section class="wh-result" aria-labelledby="wh-result-title">
        <p class="edu-kicker">Your check-in</p>
        <h2 id="wh-result-title"><?= eca_wellness_h($result[0]) ?></h2>
        <p><?= eca_wellness_h($result[1]) ?></p>
        <div class="edu-actions">
            <a class="edu-btn" href="/wellness/support.php">Support &amp; referrals</a>
            <a class="edu-btn-ghost" href="/wellness/mental-health.php">Mental health</a>
            <a class="edu-btn-ghost" href="/wellness/check-in.php">Start again</a>
        </div>
    </section>
<?php else: ?>
    <form class="wh-checkin eca-form-panel" method="post" action="/wellness/check-in.php">
        <input type="hidden" name="csrf_token" value="<?= eca_wellness_h($csrf) ?>">
        <?php foreach ($questions as $key => $label): ?>
            <fieldset>
                <legend><?= eca_wellness_h($label) ?></legend>
                <div class="wh-scale">
                    <?php foreach ($scale as $value => $name): ?>
                        <label>
                            <input type="radio" name="<?= eca_wellness_h($key) ?>" value="<?= (int) $value ?>" required
                                <?= (int) ($posted[$key] ?? 0) === $value ? ' checked' : '' ?>>
                            <span><?= (int) $value ?> · <?= eca_wellness_h($name) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        <?php endforeach; ?>
        <button class="edu-btn" type="submit">See my check-in</button>
    </form>
<?php endif; ?>
<?php eca_wellness_page_end(); ?>
