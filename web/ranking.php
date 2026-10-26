<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$defaultWorkerBase = 'https://php2shell-hard-verifier.php2shell.workers.dev';
$rankingUrl = trim((string) getenv('PHP2SHELL_RANKING_URL'));
$workerOverride = trim((string) ($_GET['worker'] ?? ''));
$workerParts = $workerOverride !== '' ? parse_url($workerOverride) : false;
if (
    is_array($workerParts)
    && isset($workerParts['scheme'], $workerParts['host'])
    && in_array(strtolower((string) $workerParts['scheme']), ['http', 'https'], true)
) {
    $rankingUrl = rtrim($workerOverride, '/') . '/leaderboard';
} else {
    $configuredParts = $rankingUrl !== '' ? parse_url($rankingUrl) : false;
    $configuredHost = is_array($configuredParts) ? strtolower((string) ($configuredParts['host'] ?? '')) : '';
    if ($rankingUrl === '' || in_array($configuredHost, ['127.0.0.1', 'localhost', 'host.docker.internal', 'php2shell-verifier.php2shell.workers.dev'], true)) {
        $rankingUrl = $defaultWorkerBase . '/leaderboard';
    }
}
$entries = [];
$error = '';
$localMode = false;
$parts = parse_url($rankingUrl);
$scheme = is_array($parts) ? ($parts['scheme'] ?? '') : '';
if (!in_array($scheme, ['http', 'https'], true)) {
    $error = 'The ranking URL must use HTTP or HTTPS.';
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 3,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\nCache-Control: no-cache\r\n",
        ],
    ]);
    $raw = @file_get_contents($rankingUrl, false, $context);
    $payload = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($payload) || !is_array($payload['entries'] ?? null)) {
        $error = 'The remote ranking service did not return a valid leaderboard.';
    } else {
        foreach ($payload['entries'] as $entry) {
            if (!is_array($entry) || !is_string($entry['nickname'] ?? null)) {
                continue;
            }
            $entries[] = [
                'rank' => (int) ($entry['rank'] ?? (count($entries) + 1)),
                'nickname' => (string) $entry['nickname'],
                'solved_at' => (int) ($entry['solved_at'] ?? 0),
            ];
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PHP2Shell — HARD — Ranking</title>
  <style>
    :root { color-scheme: dark; }
    body { font: 16px/1.55 system-ui, sans-serif; max-width: 860px; margin: 32px auto; padding: 0 18px; background: #10131a; color: #edf2f7; }
    nav { display: flex; gap: 8px; border-bottom: 1px solid #303746; margin-bottom: 28px; }
    nav a { color: #aab7c4; text-decoration: none; padding: 10px 14px; border-radius: 8px 8px 0 0; }
    nav a:hover, nav a.active { color: #10131a; background: #9fe870; }
    main { background: #171c26; border: 1px solid #303746; border-radius: 10px; padding: 22px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 11px; border-bottom: 1px solid #303746; }
    th { color: #9fe870; }
    .notice { color: #aab7c4; }
  </style>
</head>
<body>
  <nav aria-label="Challenge navigation">
    <a href="/">Index</a>
    <a class="active" href="/ranking.php" aria-current="page">Ranking</a>
  </nav>
  <main>
    <h1>PHP2Shell ranking — HARD</h1>
<?php if ($localMode): ?>
    <p class="notice">Local demo ranking. It is stored inside the challenge container and is not a production leaderboard.</p>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <p class="notice"><?= $esc($error) ?></p>
<?php else: ?>
    <table>
      <thead><tr><th>Rank</th><th>Nickname</th><th>Solved at (UTC)</th></tr></thead>
      <tbody>
<?php if ($entries === []): ?>
        <tr><td colspan="3">No ranked solves yet.</td></tr>
<?php else: foreach ($entries as $entry): ?>
        <tr>
          <td><?= $entry['rank'] ?></td>
          <td><?= $esc($entry['nickname']) ?></td>
          <td><?= $entry['solved_at'] > 0 ? $esc(gmdate('Y-m-d H:i:s', $entry['solved_at'])) . ' UTC' : 'unknown' ?></td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
<?php endif; ?>
  </main>
</body>
</html>
