<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$checkUrl = trim((string) getenv('PHP2SHELL_CHECK_URL'));
$keyUrl = trim((string) getenv('PHP2SHELL_KEY_URL'));
$sessionToken = trim((string) getenv('PHP2SHELL_SESSION_TOKEN'));
$workerOverride = trim((string) ($_GET['worker'] ?? ''));
$workerParts = $workerOverride !== '' ? parse_url($workerOverride) : false;
if (
    is_array($workerParts)
    && isset($workerParts['scheme'], $workerParts['host'])
    && in_array(strtolower((string) $workerParts['scheme']), ['http', 'https'], true)
) {
    $workerBase = rtrim($workerOverride, '/');
    $keyUrl = $workerBase . '/key';
    $checkUrl = $workerBase . '/check';
}
$sessionOverride = trim((string) ($_GET['session'] ?? ''));
if ($sessionOverride !== '') {
    $sessionToken = $sessionOverride;
}

$connectSources = ["'self'"];
foreach ([$keyUrl, $checkUrl] as $remoteUrl) {
    $parts = parse_url($remoteUrl);
    if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }
        $connectSources[] = $origin;
    }
}
$connectSources = implode(' ', array_values(array_unique($connectSources)));
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; script-src 'unsafe-inline'; connect-src $connectSources");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PHP2Shell — HARD — submit solve</title>
  <style>
    :root { color-scheme: dark; }
    body { font: 16px/1.55 system-ui, sans-serif; max-width: 680px; margin: 32px auto; padding: 0 18px; background: #10131a; color: #edf2f7; }
    nav { display: flex; gap: 8px; border-bottom: 1px solid #303746; margin-bottom: 28px; }
    nav a { color: #aab7c4; text-decoration: none; padding: 10px 14px; border-radius: 8px 8px 0 0; }
    nav a:hover, nav a.active { color: #10131a; background: #9fe870; }
    main { background: #171c26; border: 1px solid #303746; border-radius: 10px; padding: 22px; }
    label { display: block; margin-top: 16px; color: #aab7c4; }
    input { display: block; box-sizing: border-box; width: 100%; margin-top: 6px; padding: 10px; border: 1px solid #586174; border-radius: 6px; background: #10131a; color: #edf2f7; }
    button { margin-top: 18px; padding: 10px 16px; border: 0; border-radius: 6px; background: #9fe870; color: #10131a; cursor: pointer; }
    #result { margin-top: 18px; white-space: pre-wrap; }
    .notice { color: #aab7c4; }
  </style>
</head>
<body>
  <nav aria-label="Challenge navigation">
    <a href="/">Index</a>
    <a href="/ranking.php">Ranking</a>
    <a class="active" href="/nickname.php" aria-current="page">Submit solve</a>
  </nav>
  <main>
    <h1>Native RCE proven — HARD</h1>
    <p class="notice">After proof, the server returns a session-bound solve key. Enter a nickname and submit it; the server then returns the rank and demo flag. A production verifier is optional.</p>
    <form id="claim-form">
      <label for="nickname">Nickname</label>
      <input id="nickname" name="nickname" minlength="2" maxlength="24" pattern="[A-Za-z0-9_.-]+" required autocomplete="nickname">
      <label for="solve-key">Solve key <span class="notice">(tự điền hoặc dán thủ công)</span></label>
      <input id="solve-key" name="solve-key" required autocomplete="off" spellcheck="false" placeholder="Paste solve key if the field is empty">
      <button type="submit">Claim flag and join ranking</button>
    </form>
    <div id="result" role="status"></div>
  </main>
  <script>
    const keyUrl = <?= json_encode($keyUrl, JSON_UNESCAPED_SLASHES) ?>;
    const checkUrl = <?= json_encode($checkUrl, JSON_UNESCAPED_SLASHES) ?>;
    const sessionToken = <?= json_encode($sessionToken, JSON_UNESCAPED_SLASHES) ?>;
    const form = document.getElementById('claim-form');
    const result = document.getElementById('result');
    const keyInput = document.getElementById('solve-key');

    async function loadSolveKey() {
      if (!keyUrl || !sessionToken) return;
      for (let attempt = 0; attempt < 30; attempt += 1) {
        try {
          const response = await fetch(keyUrl, {
            cache: 'no-store',
            headers: { 'X-CTF-Session': sessionToken },
          });
          const payload = await response.json();
          if (payload.key) {
            keyInput.value = payload.key;
            result.textContent = 'Solve key received. Enter a nickname and claim the flag.';
            return;
          }
        } catch (error) {
          // Keep retrying while the verifier propagates proof acceptance.
        }
        await new Promise((resolve) => window.setTimeout(resolve, 1000));
      }
      result.textContent = 'Solve key is not available yet. Check the verifier configuration.';
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!checkUrl || !sessionToken) {
        result.textContent = 'Verifier is not configured for this target.';
        return;
      }
      result.textContent = 'Submitting...';
      const body = {
        nickname: document.getElementById('nickname').value.trim(),
        key: document.getElementById('solve-key').value.trim(),
      };
      try {
        const response = await fetch(checkUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CTF-Session': sessionToken },
          body: JSON.stringify(body),
        });
        const payload = await response.json();
        result.textContent = response.ok
          ? `Solved. Rank: ${payload.rank}\n${payload.flag || ''}`
          : `Claim failed: ${payload.status || 'unknown_error'}`;
      } catch (error) {
        result.textContent = 'Verifier is unreachable.';
      }
    });
    loadSolveKey();
  </script>
</body>
</html>
