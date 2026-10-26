<?php
declare(strict_types=1);

$statusUrl = trim((string) getenv('PHP2SHELL_STATUS_URL'));
if ($statusUrl === '') {
    $statusUrl = '/status.php';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PHP2Shell CTF — HARD</title>
  <style>
    :root { color-scheme: dark; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #0d1117; color: #edf2f7; font: 16px/1.6 system-ui, sans-serif; }
    .shell { width: min(980px, calc(100% - 32px)); margin: 28px auto 52px; }
    nav { display: flex; gap: 8px; border-bottom: 1px solid #303746; margin-bottom: 22px; }
    nav a { color: #aab7c4; text-decoration: none; padding: 9px 14px; border-radius: 8px 8px 0 0; }
    nav a:hover, nav a.active { color: #10131a; background: #9fe870; }
    .hero, .panel { background: #171c26; border: 1px solid #303746; border-radius: 12px; padding: 24px; margin-top: 18px; }
    .hero { border-color: #46536a; background: linear-gradient(135deg, #1b2635, #171c26 62%); }
    .eyebrow { color: #9fe870; font-size: .78rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
    h1 { margin: 4px 0 8px; font-size: clamp(2rem, 5vw, 3.2rem); line-height: 1.1; }
    h2 { margin: 0 0 10px; font-size: 1.25rem; }
    p { margin: 8px 0; }
    .muted { color: #aab7c4; }
    .hero-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
    .button { display: inline-block; padding: 9px 13px; border-radius: 7px; background: #9fe870; color: #10131a; font-weight: 700; text-decoration: none; }
    .button.secondary { background: #273342; color: #d7e2ec; }
    a { color: #9fe870; }
    code { color: #9fe870; }
    .status { display: flex; align-items: center; gap: 10px; margin-top: 14px; padding: 13px 15px; border: 1px solid #46536a; border-radius: 8px; color: #d7e2ec; }
    .dot { width: 9px; height: 9px; flex: 0 0 auto; border-radius: 50%; background: #e6b450; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-top: 18px; }
    .grid .panel { margin-top: 0; }
    .tree { overflow-x: auto; margin: 12px 0 0; padding: 15px; border: 1px solid #303746; border-radius: 8px; background: #0d1117; color: #d7e2ec; font: 14px/1.55 ui-monospace, SFMono-Regular, Consolas, monospace; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 9px 8px; border-bottom: 1px solid #303746; text-align: left; vertical-align: top; }
    th { color: #9fe870; font-weight: 650; }
    ol { margin: 10px 0 0; padding-left: 24px; }
    li { margin: 7px 0; }
    .callout { border-left: 3px solid #9fe870; padding: 10px 14px; background: #101a16; color: #d7e2ec; }
    @media (max-width: 600px) { .shell { width: min(100% - 20px, 980px); margin-top: 12px; } .hero, .panel { padding: 18px; } }
  </style>
</head>
<body>
  <div class="shell">
    <nav aria-label="Challenge navigation">
      <a class="active" href="/" aria-current="page">Challenge</a>
      <a href="/ranking.php">Ranking</a>
    </nav>

    <header class="hero">
      <span class="eyebrow">Native memory challenge · HARD</span>
      <h1>PHP2Shell — HARD</h1>
      <p class="muted">A transparent PHP runtime challenge. The web application is only the entry point; the intended target is the request lifecycle inside the configured PHP runtime.</p>
      <div class="hero-links">
        <a class="button" href="/ranking.php">View public ranking</a>
        <a class="button secondary" href="/nickname.php">Submit a solve</a>
      </div>
    </header>

    <section class="panel">
      <h2>Objective — HARD proof</h2>
      <p>Obtain native RCE and execute the exact proof command:</p>
      <p class="callout"><strong><code>cat /p&gt;/out/f</code></strong></p>
      <p>This copies the current runtime token from <code>/p</code> into <code>/out/f</code>. The checker accepts only an exact match.</p>
      <p class="muted">An OOM, a crash, an arbitrary file write, or a normal web-shell response is not a valid solve. The local Docker target does not contain the production flag.</p>
      <div id="solve-state" class="status" role="status"><span class="dot" aria-hidden="true"></span><span>Waiting for native-RCE proof...</span></div>
    </section>

    <div class="grid">
      <section class="panel">
        <h2>Repository map</h2>
        <p class="muted">These are the relevant files in the downloaded challenge package.</p>
        <pre class="tree">php2shell-ctf/
├── web/
│   ├── index.php       challenge page and proof status
│   ├── ranking.php     local or remote leaderboard
│   └── nickname.php    solve-key submission
├── docker/
│   ├── php.ini         PHP runtime profile
│   └── apache.conf     web-server configuration
├── docker-compose.yml  local target definition
└── setup.ps1           first-run setup</pre>
      </section>

      <section class="panel">
        <h2>Container paths</h2>
        <p class="muted">The proof target is intentionally explicit.</p>
        <pre class="tree">/p
└── /run/ctf/proof-token

/out/f
└── expected native-RCE proof output</pre>
        <p><a href="http://127.0.0.1:44159/status.php">Open proof status JSON</a></p>
      </section>
    </div>

    <section class="panel">
      <h2>Published runtime profile</h2>
      <table>
        <thead><tr><th>Component</th><th>Value</th><th>Why it matters</th></tr></thead>
        <tbody>
          <tr><td>PHP image</td><td><code>8.3.35-apache-bookworm</code></td><td>Hard uses a separate minor version and therefore a separate offset profile.</td></tr>
          <tr><td>Memory limit</td><td><code>16M</code></td><td>The OOM preparation must exceed the larger hard-mode limit.</td></tr>
          <tr><td>Input variables</td><td><code>1000</code></td><td>Field-count layouts must be measured against this profile.</td></tr>
          <tr><td>Upload progress</td><td><code>custom static field</code></td><td>Hard uses a fixed application field name distinct from Easy.</td></tr>
          <tr><td>Error display</td><td><code>On</code></td><td>The runtime remains observable while the larger OOM threshold adds difficulty.</td></tr>
          <tr><td>Apache model</td><td><code>prefork</code>, one worker</td><td>Worker rotation is still required between the staged requests.</td></tr>
          <tr><td>Proof check</td><td>Exact byte comparison</td><td>The output must match the current runtime proof value.</td></tr>
        </tbody>
      </table>
    </section>

    <section class="panel">
      <h2>Player route</h2>
      <ol>
        <li>Inspect the runtime profile, not only the small PHP page.</li>
        <li>Study <code>multipart/form-data</code>, PHP minor-version offsets, <code>session.upload_progress</code>, the <code>16M</code> limit, and request lifecycle.</li>
        <li>Work toward a native memory primitive rather than stopping at a crash or OOM.</li>
          <li>Execute the exact HARD proof command: <code>cat /p&gt;/out/f</code>.</li>
        <li>After the status becomes <code>rce_proven</code>, obtain the remote session-bound key from the nickname page.</li>
        <li>Submit the key and a nickname at <a href="/nickname.php">nickname submission</a>, then check <a href="/ranking.php">ranking</a>.</li>
      </ol>
    </section>

    <section class="panel">
      <h2>Trust boundary</h2>
      <div class="callout">The local container proves only the exploit state. The separate verifier issues the solve key, ranking entry and flag. No verifier secret is included.</div>
    </section>
  </div>

  <script>
    const statusUrl = <?= json_encode($statusUrl, JSON_UNESCAPED_SLASHES) ?>;
    const redirectMarker = 'php2shell-rce-redirected';
    const stateBox = document.getElementById('solve-state');

    function wasRedirected() {
      try { return window.sessionStorage.getItem(redirectMarker) === '1'; }
      catch (error) { return false; }
    }

    function markRedirected() {
      try { window.sessionStorage.setItem(redirectMarker, '1'); }
      catch (error) { /* Keep the page usable when storage is disabled. */ }
    }

    function clearRedirectMarker() {
      try { window.sessionStorage.removeItem(redirectMarker); }
      catch (error) { /* Ignore browsers that disable session storage. */ }
    }

    function hasInternalReferrer() {
      if (!document.referrer) return false;
      try { return new URL(document.referrer).origin === window.location.origin; }
      catch (error) { return false; }
    }

    function showNicknameLink(nextUrl) {
      stateBox.innerHTML = '';
      const dot = document.createElement('span');
      dot.className = 'dot';
      dot.setAttribute('aria-hidden', 'true');
      const text = document.createElement('span');
      text.append('Native RCE proven. ');
      const link = document.createElement('a');
      link.href = nextUrl || '/nickname.php';
      link.textContent = 'Open nickname submission';
      text.appendChild(link);
      stateBox.append(dot, text);
    }

    async function pollSolveState() {
      try {
        const response = await fetch(statusUrl, { cache: 'no-store' });
        const state = await response.json();
        if (state.status === 'rce_proven') {
          if (!wasRedirected() && !hasInternalReferrer()) {
            markRedirected();
            stateBox.querySelector('span:last-child').textContent = 'Native RCE proven. Redirecting to nickname submission...';
            window.location.assign(state.next || '/nickname.php');
          } else {
            showNicknameLink(state.next);
          }
          return;
        }
        clearRedirectMarker();
        stateBox.querySelector('span:last-child').textContent = 'Waiting for native-RCE proof...';
      } catch (error) {
        stateBox.querySelector('span:last-child').textContent = 'Proof status is temporarily unavailable.';
      }
    }

    pollSolveState();
    window.setInterval(pollSolveState, 2000);
  </script>
</body>
</html>
