<?php
require_once dirname(__DIR__) . '/includes/http.php';
require_once dirname(__DIR__) . '/includes/wellness-hub.php';

$id = (int) ($_GET['id'] ?? 0);
$conn = eca_wellness_db();
eca_wellness_hub_ensure_schema($conn);
$item = eca_wellness_hub_item($conn, $id);
if (!$item) {
    eca_not_found('That wellness resource was not found.');
}

$kind = (string) ($item['kind'] ?? '');
$section = (string) ($item['hub_section'] ?? '');
$back = [
    'mental-health' => ['/wellness/mental-health.php', 'mental'],
    'holistic' => ['/wellness/holistic.php', 'holistic'],
    'library' => ['/wellness/library.php', 'library'],
    'toolbox' => ['/wellness/toolbox.php', 'toolbox'],
    'support' => ['/wellness/support.php', 'support'],
    'groups' => ['/wellness/groups.php', 'groups'],
][$section] ?? ['/wellness/library.php', 'library'];

$title = (string) ($item['title'] ?? 'Wellness resource');
$intro = (string) ($item['description'] ?? '');
eca_wellness_page_start($title, $title, $intro !== '' ? $intro : 'ECA Wellness Hub resource.', $back[1]);

$url = eca_wellness_safe_url((string) ($item['external_url'] ?? ''));
$file = trim((string) ($item['file_path'] ?? ''));
$body = trim((string) ($item['body_text'] ?? ''));
$kindLabel = eca_wellness_hub_kinds()[$kind] ?? ((string) ($item['category_name'] ?? 'Resource'));
?>
<p class="edu-kicker"><?= eca_wellness_h($kindLabel) ?></p>
<div class="edu-article">
    <?php if ($body !== ''): ?>
        <p><?= eca_wellness_prose($body) ?></p>
    <?php elseif ($intro !== ''): ?>
        <p><?= eca_wellness_prose($intro) ?></p>
    <?php endif; ?>
    <?php if ($url !== ''): ?>
        <?= eca_wellness_video_embed($url) ?>
        <?php if (eca_wellness_youtube_id($url) === ''): ?>
            <p><a class="edu-btn" href="<?= eca_wellness_h($url) ?>" rel="noopener noreferrer">Open linked resource</a></p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($file !== '' && eca_wellness_is_video_file($file)): ?>
        <div class="wh-video">
            <video controls preload="metadata" src="/wellness-download.php?id=<?= $id ?>&amp;inline=1">
                Your browser cannot play this video. Use Download instead.
            </video>
        </div>
        <p><a class="edu-btn-ghost" href="/wellness-download.php?id=<?= $id ?>">Download video</a></p>
    <?php elseif ($file !== ''): ?>
        <p><a class="edu-btn" href="/wellness-download.php?id=<?= $id ?>">Download resource</a></p>
    <?php endif; ?>
    <p><a class="edu-btn-ghost" href="<?= eca_wellness_h($back[0]) ?>">Back</a></p>
</div>
<?php eca_wellness_page_end(); ?>
