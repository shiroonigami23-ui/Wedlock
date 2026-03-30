const page = document.body.dataset.page || 'home';
const $ = (s) => document.querySelector(s);
const api = (q, method = 'GET', body = null) =>
  fetch(`api.php?api=${q}`, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: body ? JSON.stringify(body) : null
  }).then((r) => r.json());

function esc(x = '') {
  return String(x).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

async function loadDashboard() {
  const stats = $('#dashboardStats');
  const sk = $('#matchesSkeleton');
  const grid = $('#matchesGrid');
  if (stats) {
    const d = await api('dashboard');
    if (d.ok) stats.innerHTML = `Active Plan: <strong>${esc(d.active_plan)}</strong> | Pending Requests: <strong>${d.pending_requests}</strong>`;
    else stats.textContent = 'Could not load stats.';
  }
  if (grid) {
    const m = await api('matches');
    if (sk) sk.style.display = 'none';
    if (!m.ok || !m.items?.length) { grid.innerHTML = '<p class="muted">No matches yet. Complete profile for better suggestions.</p>'; return; }
    grid.innerHTML = m.items.map((x) => `
      <article class="match">
        <div class="row"><h4>${esc(x.full_name)}</h4><span class="score">${x.score}% Match</span></div>
        <p class="muted">${esc(x.age || 'N/A')} yrs • ${esc(x.city || '')} • ${esc(x.education || '')}</p>
        <p>${esc(x.bio || x.occupation || 'Profile looks promising.')}</p>
        <button class="btn small" data-interest="${x.user_id}">Send Interest</button>
      </article>`).join('');
    grid.querySelectorAll('[data-interest]').forEach((b) => b.addEventListener('click', async () => {
      const r = await api('send_interest', 'POST', { to_user_id: Number(b.dataset.interest) });
      b.textContent = r.ok ? 'Interest Sent' : 'Failed';
      b.disabled = true;
    }));
  }
}

async function loadRequests() {
  const box = $('#requestsBox');
  if (!box) return;
  const r = await api('requests');
  if (!r.ok || !r.items?.length) { box.innerHTML = '<p class="muted">No incoming requests.</p>'; return; }
  box.innerHTML = r.items.map((x) => `
    <article class="match">
      <div class="row"><h4>${esc(x.full_name)}</h4><span class="muted">${esc(x.status)}</span></div>
      <p class="muted">${esc(x.city || 'Unknown city')}</p>
      ${x.status === 'pending' ? `<div class="row"><button class="btn small" data-acc="${x.id}">Accept</button><button class="btn small ghost" data-dec="${x.id}">Decline</button></div>` : ''}
    </article>`).join('');
  box.querySelectorAll('[data-acc]').forEach((b) => b.addEventListener('click', async () => { await api('respond_interest', 'POST', { interest_id: Number(b.dataset.acc), status: 'accepted' }); loadRequests(); }));
  box.querySelectorAll('[data-dec]').forEach((b) => b.addEventListener('click', async () => { await api('respond_interest', 'POST', { interest_id: Number(b.dataset.dec), status: 'declined' }); loadRequests(); }));
}

async function loadPlans(target = '#plansGrid') {
  const box = $(target);
  if (!box) return;
  const r = await api('plans');
  if (!r.ok || !r.items?.length) { box.innerHTML = '<p class="muted">No plans available.</p>'; return; }
  box.innerHTML = r.items.map((p) => `
    <article class="match">
      <h4>${esc(p.plan_name)}</h4>
      <p class="muted">Rs ${esc(p.price_inr)} • ${esc(p.duration_days)} days</p>
      <p class="muted">Contacts: ${esc(p.max_contact_views)}</p>
      <button class="btn small" data-sub="${p.id}">Activate</button>
    </article>`).join('');
  box.querySelectorAll('[data-sub]').forEach((b) => b.addEventListener('click', async () => {
    const r2 = await api('subscribe', 'POST', { plan_id: Number(b.dataset.sub) });
    b.textContent = r2.ok ? 'Activated' : 'Failed';
  }));
}

function wireProfileSave() {
  const f = $('#profileForm');
  if (!f) return;
  f.addEventListener('submit', async (e) => {
    e.preventDefault();
    const d = Object.fromEntries(new FormData(f).entries());
    const r = await api('save_profile', 'POST', d);
    alert(r.ok ? 'Profile saved' : 'Save failed');
  });
}

async function loadAdminPending() {
  const box = $('#pendingBox');
  if (!box) return;
  const r = await api('admin_pending');
  if (!r.ok || !r.items?.length) { box.innerHTML = '<p class="muted">No pending users.</p>'; return; }
  box.innerHTML = r.items.map((u) => `
    <article class="match">
      <div class="row"><h4>${esc(u.full_name)}</h4><span class="muted">${esc(u.email)}</span></div>
      <p class="muted">${esc(u.gender || '')} • ${esc(u.city || '')} • ${esc(u.education || '')}</p>
      <div class="row"><button class="btn small" data-ap="${u.id}">Approve</button><button class="btn small ghost" data-rj="${u.id}">Reject</button></div>
    </article>`).join('');
  box.querySelectorAll('[data-ap]').forEach((b) => b.addEventListener('click', async () => { await api('admin_approve', 'POST', { user_id: Number(b.dataset.ap) }); loadAdminPending(); }));
  box.querySelectorAll('[data-rj]').forEach((b) => b.addEventListener('click', async () => {
    const reason = prompt('Rejection reason', 'Incomplete profile') || 'Incomplete profile';
    await api('admin_reject', 'POST', { user_id: Number(b.dataset.rj), reason });
    loadAdminPending();
  }));
}

async function loadAdminPlans() {
  const box = $('#adminPlans');
  if (!box) return;
  const r = await api('admin_plans');
  if (!r.ok) { box.textContent = 'Failed to load plans'; return; }
  box.innerHTML = r.items.map((p) => `<p>${esc(p.plan_name)} - Rs ${esc(p.price_inr)} (${esc(p.duration_days)} days)</p>`).join('');
}

function wireAdminForms() {
  const plan = $('#planForm');
  if (plan) {
    plan.addEventListener('submit', async (e) => {
      e.preventDefault();
      const d = Object.fromEntries(new FormData(plan).entries());
      d.has_priority_listing = !!plan.querySelector('[name="has_priority_listing"]').checked;
      d.has_advanced_filters = !!plan.querySelector('[name="has_advanced_filters"]').checked;
      d.is_active = !!plan.querySelector('[name="is_active"]').checked;
      const r = await api('admin_save_plan', 'POST', d);
      alert(r.ok ? 'Plan saved' : 'Save failed');
      loadAdminPlans();
    });
  }
  const sf = $('#settingsForm');
  if (sf) {
    sf.addEventListener('submit', async (e) => {
      e.preventDefault();
      const d = Object.fromEntries(new FormData(sf).entries());
      for (const [k, v] of Object.entries(d)) await api('admin_save_setting', 'POST', { key: k, value: v });
      alert('Settings saved');
    });
  }
}

if (page === 'dashboard') {
  loadDashboard();
  const rb = $('#reloadMatches');
  if (rb) rb.addEventListener('click', loadDashboard);
}
if (page === 'connections') loadRequests();
if (page === 'packages') loadPlans();
if (page === 'profile') wireProfileSave();
if (page === 'admin') {
  loadAdminPending();
  loadAdminPlans();
  wireAdminForms();
}

const menuBtn = $('#menuToggle');
const mainNav = $('#mainNav');
if (menuBtn && mainNav) {
  menuBtn.addEventListener('click', () => mainNav.classList.toggle('open'));
}
