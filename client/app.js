const apiBase = '../api/index.php?action=';

const state = {
  me: null,
  client: null,
  suppliers: [],
  changeRequests: [],
  activities: [],
  labels: [],
  supplierTypes: [],
  selectedSupplierId: null,
};

const q = (id) => document.getElementById(id);

async function api(action, method = 'GET', body = null, query = {}) {
  const params = new URLSearchParams({ action, ...query });
  const opts = { method, credentials: 'include', headers: {} };
  if (body) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }

  const resp = await fetch('../api/index.php?' + params.toString(), opts);
  const data = await resp.json().catch(() => ({}));
  if (!resp.ok || data.ok === false) {
    throw new Error(data.error || ('HTTP ' + resp.status));
  }
  return data;
}

function showAuth(loggedIn) {
  q('loginView').classList.toggle('hidden', loggedIn);
  q('appView').classList.toggle('hidden', !loggedIn);
}

function safe(v) {
  return String(v || '').trim();
}

function parseList(value) {
  return safe(value)
    .split(/[;,|/]+/)
    .map((item) => item.trim())
    .filter(Boolean);
}

function escapeHtml(value) {
  return safe(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function selectedValues(selectEl) {
  return Array.from(selectEl?.selectedOptions || [])
    .map((opt) => safe(opt.value))
    .filter(Boolean);
}

function joinSelectedValues(selectEl) {
  return selectedValues(selectEl).join('; ');
}

function ensureOption(selectEl, value) {
  const normalized = safe(value);
  if (!normalized) return;
  if (Array.from(selectEl.options).some((opt) => safe(opt.value) === normalized)) return;
  const opt = document.createElement('option');
  opt.value = normalized;
  opt.textContent = normalized;
  selectEl.appendChild(opt);
}

function setMultiSelectValues(selectEl, values) {
  const list = Array.isArray(values) ? values.map((v) => safe(v)).filter(Boolean) : [];
  list.forEach((value) => ensureOption(selectEl, value));
  Array.from(selectEl.options).forEach((opt) => {
    opt.selected = list.includes(safe(opt.value));
  });
  refreshMultiPickerChips(selectEl);
}

function refreshMultiPickerChips(selectEl) {
  const chipTargetId = selectEl?.getAttribute('data-chip-target');
  if (!chipTargetId) return;
  const host = q(chipTargetId);
  if (!host) return;

  const values = selectedValues(selectEl);
  if (!values.length) {
    host.innerHTML = '';
    return;
  }

  host.innerHTML = values.map((value) => [
    '<span class="multi-chip">',
    escapeHtml(value),
    '<button type="button" class="multi-chip-remove" data-chip-select="' + escapeHtml(selectEl.id) + '" data-chip-value="' + escapeHtml(value) + '">x</button>',
    '</span>',
  ].join('')).join('');
}

function bindMultiPicker(selectId) {
  const selectEl = q(selectId);
  if (!selectEl) return;
  selectEl.addEventListener('change', () => refreshMultiPickerChips(selectEl));
  const chipTargetId = selectEl.getAttribute('data-chip-target');
  const chipHost = chipTargetId ? q(chipTargetId) : null;
  chipHost?.addEventListener('click', (event) => {
    const btn = event.target.closest('button[data-chip-select][data-chip-value]');
    if (!btn) return;
    const value = safe(btn.getAttribute('data-chip-value'));
    Array.from(selectEl.options).forEach((opt) => {
      if (safe(opt.value) === value) {
        opt.selected = false;
      }
    });
    refreshMultiPickerChips(selectEl);
  });
}

function populateSelect(selectEl, values, { includeEmpty = false, emptyLabel = '' } = {}) {
  const items = Array.isArray(values) ? values.map((v) => safe(v)).filter(Boolean) : [];
  const options = [];
  if (includeEmpty) {
    options.push('<option value="">' + escapeHtml(emptyLabel) + '</option>');
  }
  items.forEach((item) => {
    options.push('<option value="' + escapeHtml(item) + '">' + escapeHtml(item) + '</option>');
  });
  selectEl.innerHTML = options.join('');
}

function fieldLabel(field) {
  const labels = {
    name: 'Nom',
    address: 'Adresse',
    city: 'Ville',
    postal_code: 'Code postal',
    country: 'Pays',
    phone: 'Téléphone',
    email: 'Email',
    website: 'Site web',
    supplier_type: 'Type fournisseur',
    activity_text: 'Activités globales',
    labels: 'Labels globaux',
  };
  return labels[safe(field)] || safe(field);
}

function currentSupplier() {
  return state.suppliers.find((row) => Number(row.id) === Number(state.selectedSupplierId)) || null;
}

function renderReferenceSelects() {
  populateSelect(q('profileActivitySelect'), state.activities);
  populateSelect(q('profileLabelsSelect'), state.labels);
  populateSelect(q('changeRequestSupplierType'), state.supplierTypes, { includeEmpty: true, emptyLabel: 'Sélectionner un type' });
  populateSelect(q('changeRequestActivities'), state.activities);
  populateSelect(q('changeRequestLabels'), state.labels);
  refreshMultiPickerChips(q('profileActivitySelect'));
  refreshMultiPickerChips(q('profileLabelsSelect'));
  refreshMultiPickerChips(q('changeRequestActivities'));
  refreshMultiPickerChips(q('changeRequestLabels'));
}

function renderChangeRequestInput() {
  const field = safe(q('changeRequestField')?.value);
  const supplier = currentSupplier();
  const textEl = q('changeRequestNewValue');
  const typeEl = q('changeRequestSupplierType');
  const activitiesWrap = q('changeRequestActivitiesWrap');
  const activitiesEl = q('changeRequestActivities');
  const labelsWrap = q('changeRequestLabelsWrap');
  const labelsEl = q('changeRequestLabels');

  textEl.classList.toggle('hidden', field === 'supplier_type' || field === 'activity_text' || field === 'labels');
  typeEl.classList.toggle('hidden', field !== 'supplier_type');
  activitiesWrap.classList.toggle('hidden', field !== 'activity_text');
  labelsWrap.classList.toggle('hidden', field !== 'labels');

  if (!supplier) {
    textEl.value = '';
    typeEl.value = '';
    setMultiSelectValues(activitiesEl, []);
    setMultiSelectValues(labelsEl, []);
    return;
  }

  if (field === 'supplier_type') {
    ensureOption(typeEl, supplier.supplier_type);
    typeEl.value = safe(supplier.supplier_type);
    return;
  }
  if (field === 'activity_text') {
    setMultiSelectValues(activitiesEl, parseList(supplier.global_activity_text));
    return;
  }
  if (field === 'labels') {
    setMultiSelectValues(labelsEl, parseList(supplier.global_labels));
    return;
  }

  const valueMap = {
    name: supplier.name,
    address: supplier.address,
    city: supplier.city,
    postal_code: supplier.postal_code,
    country: supplier.country,
    phone: supplier.phone,
    email: supplier.email,
    website: supplier.website,
  };
  textEl.value = safe(valueMap[field]);
}

function showMsg(id, text, isError = false) {
  const el = q(id);
  el.textContent = text || '';
  el.classList.toggle('error', !!isError);
}

function renderClientHeader() {
  const client = state.client || {};
  q('clientTitleText').textContent = safe(client.name) || 'Mon espace client';
  const meta = [safe(client.city), safe(client.client_type)].filter(Boolean).join(' - ');
  q('clientMeta').textContent = meta || 'Gestion des profils fournisseurs';

  const pendingCount = state.changeRequests.filter((r) => safe(r.status) === 'pending').length;
  const pendingBadge = q('pendingBadge');
  if (pendingCount > 0) {
    pendingBadge.textContent = `${pendingCount} demande(s) en attente`;
    pendingBadge.classList.remove('hidden');
  } else {
    pendingBadge.textContent = '';
    pendingBadge.classList.add('hidden');
  }

  if (state.me?.is_client_user) {
    q('helloUser').textContent = 'Connecte: ' + (state.me.client_username || 'client');
  } else if (state.me?.is_admin) {
    q('helloUser').textContent = 'Mode admin';
  } else {
    q('helloUser').textContent = '';
  }
}

function formatGlobalSupplier(s) {
  const labels = safe(s.global_labels) || '-';
  const acts = safe(s.global_activity_text) || '-';
  const type = safe(s.supplier_type) || '-';
  const contact = [safe(s.phone), safe(s.email)].filter(Boolean).join(' / ') || '-';

  return [
    '<strong>Fiche globale</strong>',
    '<div><b>Type:</b> ' + type + '</div>',
    '<div><b>Activites:</b> ' + acts + '</div>',
    '<div><b>Labels:</b> ' + labels + '</div>',
    '<div><b>Contact:</b> ' + contact + '</div>',
  ].join('');
}

function renderSupplierList() {
  const host = q('suppliersList');
  const query = safe(q('searchSupplier').value).toLowerCase();
  const rows = state.suppliers.filter((s) => {
    if (!query) return true;
    const text = [s.name, s.city, s.supplier_type, s.global_activity_text, s.profile_activity_text, s.profile_labels_text]
      .map((v) => safe(v).toLowerCase())
      .join(' ');
    return text.includes(query);
  });

  if (!rows.length) {
    host.innerHTML = '<div class="list-item"><div class="list-sub">Aucun fournisseur</div></div>';
    return;
  }

  host.innerHTML = rows
    .map((s) => {
      const isActive = Number(state.selectedSupplierId) === Number(s.id);
      const rel = safe(s.relationship_status) || 'active';
      const city = safe(s.city) || '-';
      return [
        '<div class="list-item ' + (isActive ? 'active' : '') + '" data-id="' + Number(s.id) + '">',
        '<div class="list-main">' + (safe(s.name) || '(sans nom)') + '</div>',
        '<div class="list-sub">' + city + ' - statut: ' + rel + '</div>',
        '</div>',
      ].join('');
    })
    .join('');

  host.querySelectorAll('.list-item[data-id]').forEach((el) => {
    el.addEventListener('click', () => {
      state.selectedSupplierId = Number(el.getAttribute('data-id'));
      renderSupplierList();
      renderEditor();
    });
  });
}

function renderEditor() {
  const s = currentSupplier();
  const form = q('profileForm');
  const global = q('supplierGlobal');
  const reqSection = q('changeRequestSection');

  if (!s) {
    q('editorTitle').textContent = 'Profil client fournisseur';
    q('editorHint').textContent = 'Selectionnez un fournisseur a gauche.';
    form.classList.add('hidden');
    global.classList.add('hidden');
    reqSection.classList.add('hidden');
    q('changeRequestsList').innerHTML = '';
    return;
  }

  q('editorTitle').textContent = safe(s.name) || 'Fournisseur';
  q('editorHint').textContent = safe(s.address) || safe(s.city) || '';

  global.innerHTML = formatGlobalSupplier(s);
  global.classList.remove('hidden');

  q('profileSupplierId').value = String(Number(s.id));
  setMultiSelectValues(q('profileActivitySelect'), parseList(s.profile_activity_text));
  setMultiSelectValues(q('profileLabelsSelect'), parseList(s.profile_labels_text));
  q('profileNotes').value = safe(s.profile_notes);
  q('profileRelationshipStatus').value = safe(s.relationship_status) || 'active';
  form.classList.remove('hidden');
  reqSection.classList.remove('hidden');
  showMsg('profileMsg', '');
  showMsg('changeRequestMsg', '');
  renderChangeRequestInput();
  renderChangeRequestsForSelectedSupplier();
}

function statusLabel(status) {
  if (status === 'approved') return 'Approuvée';
  if (status === 'rejected') return 'Refusée';
  return 'En attente';
}

function renderChangeRequestsForSelectedSupplier() {
  const host = q('changeRequestsList');
  const supplierId = Number(state.selectedSupplierId || 0);
  const statusFilter = safe(q('changeRequestStatusFilter')?.value || 'all');
  const rows = state.changeRequests.filter((r) => {
    if (Number(r.supplier_id) !== supplierId) return false;
    if (statusFilter === 'all') return true;
    return safe(r.status) === statusFilter;
  });

  const supplierAllRows = state.changeRequests.filter((r) => Number(r.supplier_id) === supplierId);
  const counts = {
    pending: supplierAllRows.filter((r) => safe(r.status) === 'pending').length,
    approved: supplierAllRows.filter((r) => safe(r.status) === 'approved').length,
    rejected: supplierAllRows.filter((r) => safe(r.status) === 'rejected').length,
  };
  q('changeRequestSummary').textContent = `En attente: ${counts.pending} | Approuvées: ${counts.approved} | Refusées: ${counts.rejected}`;

  if (!supplierId || !rows.length) {
    host.innerHTML = '<div class="list-item"><div class="list-sub">Aucune demande pour ce fournisseur.</div></div>';
    return;
  }

  host.innerHTML = rows.map((r) => {
    const review = safe(r.review_note);
    return [
      '<div class="list-item">',
      '<div class="list-main">' + fieldLabel(r.field_name) + ' - ' + statusLabel(safe(r.status)) + '</div>',
      '<div class="list-sub">Ancien: ' + safe(r.old_value) + '</div>',
      '<div class="list-sub">Nouveau: ' + safe(r.new_value) + '</div>',
      '<div class="list-sub">Le ' + safe(r.created_at) + (review ? ' - Note admin: ' + review : '') + '</div>',
      '</div>',
    ].join('');
  }).join('');
}

async function loadChangeRequests() {
  const query = {};
  const statusFilter = safe(q('changeRequestStatusFilter')?.value || 'all');
  if (statusFilter !== 'all') {
    query.status = statusFilter;
  }

  if (state.me?.is_admin) {
    const adminClientId = Number(new URLSearchParams(window.location.search).get('client_id') || 0);
    if (adminClientId > 0) {
      query.client_id = String(adminClientId);
    }
  }

  const res = await api('client/change-request/list', 'GET', null, query);
  state.changeRequests = Array.isArray(res.requests) ? res.requests : [];
}

async function loadBootstrap() {
  const query = {};
  if (state.me?.is_admin) {
    const maybeId = Number(new URLSearchParams(window.location.search).get('client_id') || 0);
    if (maybeId > 0) query.client_id = String(maybeId);
  }

  const res = await api('client/bootstrap', 'GET', null, query);
  state.client = res.client || null;
  state.suppliers = Array.isArray(res.suppliers) ? res.suppliers : [];
  state.activities = Array.isArray(res.activities) ? res.activities.map((item) => safe(item.name || item)).filter(Boolean) : [];
  state.labels = Array.isArray(res.labels) ? res.labels.map((item) => safe(item.name || item)).filter(Boolean) : [];
  state.supplierTypes = Array.isArray(res.supplier_types) ? res.supplier_types.map((item) => safe(item.name || item)).filter(Boolean) : [];
  renderReferenceSelects();

  if (!state.suppliers.some((x) => Number(x.id) === Number(state.selectedSupplierId))) {
    state.selectedSupplierId = state.suppliers.length ? Number(state.suppliers[0].id) : null;
  }

  renderClientHeader();
  renderSupplierList();
  renderEditor();

  try {
    await loadChangeRequests();
  } catch (e) {
    console.error('loadChangeRequests error:', e);
  }
  renderClientHeader();
  renderChangeRequestsForSelectedSupplier();
}

async function onLogin() {
  showMsg('loginMsg', '');
  try {
    const user = safe(q('loginUser').value);
    const pass = q('loginPass').value || '';
    await api('auth/login', 'POST', { username: user, password: pass });

    const me = await api('auth/me');
    if (!me.is_client_user && !me.is_admin) {
      throw new Error('Compte non autorise pour cet espace');
    }
    state.me = me;

    showAuth(true);
    await loadBootstrap();
  } catch (e) {
    showMsg('loginMsg', e.message, true);
  }
}

async function onLogout() {
  await api('auth/logout', 'POST');
  state.me = null;
  state.client = null;
  state.suppliers = [];
  state.selectedSupplierId = null;
  showAuth(false);
}

async function onSaveProfile() {
  showMsg('profileMsg', '');
  try {
    const supplierId = Number(q('profileSupplierId').value || 0);
    if (!supplierId) throw new Error('Aucun fournisseur selectionne');

    const payload = {
      supplier_id: supplierId,
      activity_text: joinSelectedValues(q('profileActivitySelect')),
      labels_text: joinSelectedValues(q('profileLabelsSelect')),
      notes: safe(q('profileNotes').value),
      relationship_status: safe(q('profileRelationshipStatus').value) || 'active',
    };

    if (state.me?.is_admin) {
      const adminClientId = Number(new URLSearchParams(window.location.search).get('client_id') || 0);
      if (adminClientId > 0) payload.client_id = adminClientId;
    }

    await api('client/supplier/profile/save', 'POST', payload);
    showMsg('profileMsg', 'Profil enregistre');
    await loadBootstrap();
  } catch (e) {
    showMsg('profileMsg', e.message, true);
  }
}

async function onSaveChangeRequest() {
  showMsg('changeRequestMsg', '');
  try {
    const supplierId = Number(q('profileSupplierId').value || 0);
    if (!supplierId) throw new Error('Aucun fournisseur selectionne');

    const payload = {
      supplier_id: supplierId,
      field_name: safe(q('changeRequestField').value),
      new_value: '',
    };

    if (payload.field_name === 'supplier_type') {
      payload.new_value = safe(q('changeRequestSupplierType').value);
    } else if (payload.field_name === 'activity_text') {
      payload.new_value = joinSelectedValues(q('changeRequestActivities'));
    } else if (payload.field_name === 'labels') {
      payload.new_value = joinSelectedValues(q('changeRequestLabels'));
    } else {
      payload.new_value = safe(q('changeRequestNewValue').value);
    }

    if (!payload.new_value) {
      throw new Error('La nouvelle valeur est requise');
    }

    if (state.me?.is_admin) {
      const adminClientId = Number(new URLSearchParams(window.location.search).get('client_id') || 0);
      if (adminClientId > 0) payload.client_id = adminClientId;
    }

    await api('client/change-request/save', 'POST', payload);
    q('changeRequestNewValue').value = '';
    q('changeRequestSupplierType').value = '';
    setMultiSelectValues(q('changeRequestActivities'), []);
    setMultiSelectValues(q('changeRequestLabels'), []);
    showMsg('changeRequestMsg', 'Demande envoyee');
    await loadBootstrap();
  } catch (e) {
    showMsg('changeRequestMsg', e.message, true);
  }
}

function bindEvents() {
  q('btnLogin').addEventListener('click', onLogin);
  q('btnLogout').addEventListener('click', onLogout);
  q('btnSaveProfile').addEventListener('click', onSaveProfile);
  q('btnSaveChangeRequest').addEventListener('click', onSaveChangeRequest);
  q('searchSupplier').addEventListener('input', renderSupplierList);
  q('changeRequestField').addEventListener('change', renderChangeRequestInput);
  bindMultiPicker('profileActivitySelect');
  bindMultiPicker('profileLabelsSelect');
  bindMultiPicker('changeRequestActivities');
  bindMultiPicker('changeRequestLabels');
  q('changeRequestStatusFilter').addEventListener('change', async () => {
    await loadChangeRequests();
    renderChangeRequestsForSelectedSupplier();
  });
  q('loginPass').addEventListener('keydown', (ev) => {
    if (ev.key === 'Enter') onLogin();
  });
}

async function init() {
  bindEvents();

  try {
    const me = await api('auth/me');
    if (me.is_client_user || me.is_admin) {
      if (me.is_client_user && !Number(me.client_id || 0) && !Number(me.client_user_id || 0)) {
        showAuth(false);
        return;
      }
      state.me = me;
      showAuth(true);
      try {
        await loadBootstrap();
      } catch (bootErr) {
        console.error('loadBootstrap error:', bootErr);
      }
      return;
    }
  } catch (e) {
    console.error('init auth/me error:', e);
  }

  showAuth(false);
}

init();
