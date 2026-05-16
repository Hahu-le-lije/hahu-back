<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Child Microservice — Ha-hu Lä-Ləje</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --sky: #E8F4FD;
    --sky-mid: #B8DCF5;
    --sky-deep: #378ADD;
    --leaf: #EAF3DE;
    --leaf-mid: #97C459;
    --leaf-deep: #3B6D11;
    --sun: #FAEEDA;
    --sun-mid: #EF9F27;
    --sun-deep: #854F0B;
    --coral: #FAECE7;
    --coral-mid: #D85A30;
    --coral-deep: #4A1B0C;
    --purple: #EEEDFE;
    --purple-mid: #7F77DD;
    --purple-deep: #3C3489;
    --teal: #E1F5EE;
    --teal-mid: #1D9E75;
    --teal-deep: #04342C;
    --bg: #FEFDF9;
    --ink: #2C2C2A;
    --muted: #5F5E5A;
    --border: rgba(44,44,42,0.12);
  }

  body {
    font-family: 'Fredoka', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 3rem 1.5rem 5rem;
    position: relative;
    overflow-x: hidden;
  }

  .bg-dots {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    background-image: radial-gradient(circle, rgba(55,138,221,0.08) 1.5px, transparent 1.5px);
    background-size: 28px 28px;
  }

  .wrap {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 780px;
  }

  /* ── HEADER ── */
  .eyebrow {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 1rem;
  }

  .pill-tag {
    font-family: 'Fredoka', sans-serif;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.04em;
    padding: 4px 14px;
    border-radius: 999px;
    background: var(--sky-mid);
    color: #0C447C;
  }

  .service-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--leaf-mid);
    box-shadow: 0 0 0 3px var(--leaf);
    display: inline-block;
  }

  h1 {
    font-family: 'DM Serif Display', serif;
    font-size: clamp(2.4rem, 6vw, 3.6rem);
    line-height: 1.1;
    color: var(--ink);
    margin-bottom: 0.4rem;
  }

  h1 em {
    font-style: italic;
    color: var(--coral-mid);
  }

  .subtitle {
    font-family: 'Fredoka', sans-serif;
    font-size: 1.05rem;
    font-weight: 400;
    color: var(--muted);
    max-width: 520px;
    line-height: 1.6;
    margin-bottom: 2.5rem;
  }

  /* ── CARDS GRID ── */
  .cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .card {
    background: white;
    border: 1.5px solid var(--border);
    border-radius: 20px;
    padding: 1.4rem 1.3rem;
    position: relative;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }

  .card:hover {
    transform: translateY(-3px);
  }

  .card-icon {
    width: 44px; height: 44px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    margin-bottom: 0.85rem;
  }

  .icon-sky    { background: var(--sky);    }
  .icon-leaf   { background: var(--leaf);   }
  .icon-sun    { background: var(--sun);    }
  .icon-purple { background: var(--purple); }
  .icon-coral  { background: var(--coral);  }
  .icon-teal   { background: var(--teal);   }

  .card h3 {
    font-family: 'Fredoka', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: var(--ink);
  }

  .card p {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.875rem;
    font-weight: 400;
    color: var(--muted);
    line-height: 1.55;
  }

  /* ── PERMISSIONS PANEL ── */
  .perm-panel {
    background: white;
    border: 1.5px solid var(--border);
    border-radius: 20px;
    padding: 1.6rem 1.5rem;
    margin-bottom: 2rem;
  }

  .perm-panel h2 {
    font-family: 'DM Serif Display', serif;
    font-size: 1.3rem;
    margin-bottom: 1.1rem;
    color: var(--ink);
  }

  .perm-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 0.6rem 0;
    border-bottom: 1px solid var(--border);
  }

  .perm-row:last-child { border-bottom: none; }

  .badge {
    font-family: 'Fredoka', sans-serif;
    font-size: 12px;
    font-weight: 500;
    padding: 3px 11px;
    border-radius: 999px;
    flex-shrink: 0;
    margin-top: 1px;
  }

  .badge-yes  { background: var(--leaf);   color: var(--leaf-deep);   }
  .badge-no   { background: var(--coral);  color: var(--coral-deep);  }

  .perm-text {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.9rem;
    font-weight: 400;
    color: var(--muted);
    line-height: 1.5;
  }

  /* ── SERVICE MAP ── */
  .svc-map {
    background: white;
    border: 1.5px solid var(--border);
    border-radius: 20px;
    padding: 1.6rem 1.5rem;
    margin-bottom: 2rem;
  }

  .svc-map h2 {
    font-family: 'DM Serif Display', serif;
    font-size: 1.3rem;
    margin-bottom: 1.2rem;
    color: var(--ink);
  }

  .svc-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .svc-chip {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.88rem;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 12px;
    border: 1.5px solid;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 180px;
    flex: 1;
  }

  .svc-chip-label {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    opacity: 0.65;
  }

  .chip-this {
    background: var(--sky);
    border-color: var(--sky-mid);
    color: #0C447C;
  }

  .chip-parent {
    background: var(--leaf);
    border-color: var(--leaf-mid);
    color: var(--leaf-deep);
  }

  .chip-sub {
    background: var(--purple);
    border-color: var(--purple-mid);
    color: var(--purple-deep);
  }

  /* ── FOOTER ── */
  footer {
    font-family: 'Fredoka', sans-serif;
    font-size: 0.82rem;
    color: var(--muted);
    text-align: center;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
    width: 100%;
    max-width: 780px;
    position: relative;
    z-index: 1;
  }

  .status-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 0.3rem;
  }
</style>
</head>
<body>

<div class="bg-dots"></div>

<div class="wrap">

  <div class="eyebrow">
    <span class="pill-tag">child-service</span>
    <span class="service-dot"></span>
    <span style="font-family:'Fredoka',sans-serif;font-size:13px;color:var(--muted);">Ha-hu Lä-Ləje Platform</span>
  </div>

  <h1>Child <em>Microservice</em></h1>
  <p class="subtitle">
Owns child profiles, generated login credentials, and child-scoped authentication for the Ha-hu Lä-Ləje literacy platform. Children can read — the rest is up to the grown-ups.
  </p>

  <!-- capabilities -->
  <div class="cards">
    <div class="card">
      <div class="card-icon icon-sky">🧒</div>
      <h3>Child profile management</h3>
      <p>Create, store, and retrieve child profiles scoped to a parent household.</p>
    </div>
    <div class="card">
      <div class="card-icon icon-sun">🔑</div>
      <h3>Credential generation</h3>
      <p>Issues child-friendly login credentials — no email required. Simple, safe access.</p>
    </div>
    <div class="card">
      <div class="card-icon icon-teal">🛡️</div>
      <h3>Child-scoped auth</h3>
      <p>Authenticates child sessions with intentionally narrow token permissions.</p>
    </div>
    <div class="card">
      <div class="card-icon icon-leaf">📖</div>
      <h3>Read-only self-view</h3>
      <p>Children can see their own profile. Nothing more. By design.</p>
    </div>
    <div class="card">
      <div class="card-icon icon-purple">🏠</div>
      <h3>Household linkage</h3>
      <p>Each child is linked to a household owned by the Parent Service.</p>
    </div>
    <div class="card">
      <div class="card-icon icon-coral">🚫</div>
      <h3>Intentional limits</h3>
      <p>No billing. No subscription changes. No leaving a household. That's a feature.</p>
    </div>
  </div>

  <!-- permissions panel -->
  <div class="perm-panel">
    <h2>What children can (and can't) do</h2>

    <div class="perm-row">
      <span class="badge badge-yes">✓ yes</span>
      <span class="perm-text">Log in using their generated credentials</span>
    </div>
    <div class="perm-row">
      <span class="badge badge-yes">✓ yes</span>
      <span class="perm-text">Read their own profile (name, avatar, progress)</span>
    </div>
    <div class="perm-row">
      <span class="badge badge-no">✗ no</span>
      <span class="perm-text">Edit their own account details</span>
    </div>
    <div class="perm-row">
      <span class="badge badge-no">✗ no</span>
      <span class="perm-text">Leave or change their household</span>
    </div>
    <div class="perm-row">
      <span class="badge badge-no">✗ no</span>
      <span class="perm-text">Access billing or subscription information</span>
    </div>
    <div class="perm-row">
      <span class="badge badge-no">✗ no</span>
      <span class="perm-text">View or modify other children in the household</span>
    </div>
  </div>

  <!-- service map -->
  <div class="svc-map">
    <h2>Platform service boundaries</h2>
    <div class="svc-row">
      <div class="svc-chip chip-parent">
        <span class="svc-chip-label">parent service</span>
Parent users · billing authority · household administration
</div>
      <div class="svc-chip chip-sub">
        <span class="svc-chip-label">subscription service</span>
Purchased plans · assignment rules
</div>
      <div class="svc-chip chip-this">
        <span class="svc-chip-label">child service ← you are here</span>
Child profiles · credentials · child-scoped auth
</div>
    </div>
  </div>

</div>

<footer>
  <div class="status-row">
    <span class="service-dot"></span>
    <span>child-microservice · Ha-hu Lä-Ləje literacy platform</span>
  </div>
  <span>Laravel · child-scoped auth · read-only by design</span>
</footer>

</body>
</html>
