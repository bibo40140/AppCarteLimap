const apiBase = '../api/index.php?action=';

const state = {
  me: null,
  client: null,
  clientGalleryDraft: [],
  clientConsent: null,
  supplierConsentHistory: [],
  suppliers: [],
  changeRequests: [],
  supplierCreateRequests: [],
  supplierLinkRequests: [],
  supplierLinkSearchResults: [],
  activities: [],
  labels: [],
  supplierTypes: [],
  selectedSupplierId: null,
};

const mapState = {
  map: null,
  marker: null,
};

const q = (id) => document.getElementById(id);

let lastUiTrackAt = 0;

function trackUiEvent(eventName, payload = {}) {
  if (!state.me || (!state.me.is_client_user && !state.me.is_admin)) {
    return;
  }

  // Avoid flooding when the same tab is re-rendered quickly.
  const now = Date.now();
  if (now - lastUiTrackAt < 350) {
    return;
  }
  lastUiTrackAt = now;

  api('audit/ui-event', 'POST', {
    event_name: eventName,
    event_type: payload.event_type || 'visit',
    app: payload.app || 'client',
    page: payload.page || 'client',
    tab: payload.tab || '',
    meta: payload.meta || {},
  }).catch(() => {});
}

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

async function apiForm(action, formData, query = {}) {
  const params = new URLSearchParams({ action, ...query });
  const resp = await fetch('../api/index.php?' + params.toString(), {
    method: 'POST',
    credentials: 'include',
    body: formData,
  });
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

function showForgotView(show) {
  q('forgotView')?.classList.toggle('hidden', !show);
  if (!show) {
    showMsg('forgotMsg', '');
  }
}

function showResetView(show) {
  q('resetView')?.classList.toggle('hidden', !show);
  if (!show) {
    showMsg('resetMsg', '');
  }
}

function setClientTab(tabName) {
  document.querySelectorAll('#clientTabs .tab-btn[data-tab]').forEach((btn) => {
    btn.classList.toggle('active', safe(btn.getAttribute('data-tab')) === tabName);
  });
  document.querySelectorAll('.client-tab-panel[id^="panel-"]').forEach((panel) => {
    const isActive = safe(panel.id) === `panel-${tabName}`;
    panel.classList.toggle('active', isActive);
  });
  if (tabName === 'my-data') {
    window.setTimeout(() => {
      mapState.map?.invalidateSize();
    }, 0);
  }

  trackUiEvent('ui_visit_tab', {
    event_type: 'visit',
    app: 'client',
    page: 'client',
    tab: tabName,
  });
}

function bindClientTabs() {
  document.querySelectorAll('#clientTabs .tab-btn[data-tab]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const tabName = safe(btn.getAttribute('data-tab'));
      if (!tabName) return;
      setClientTab(tabName);
    });
  });
}

function safe(v) {
  return String(v || '').trim();
}

function normalizeToken(value) {
  return safe(value).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
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
  if (!selectEl) return [];

  const fromData = parseList(selectEl.dataset.values || '');
  if (fromData.length) {
    const seen = new Set();
    return fromData.filter((value) => {
      const key = normalizeToken(value);
      if (!key || seen.has(key)) return false;
      seen.add(key);
      return true;
    });
  }

  return Array.from(selectEl.selectedOptions || [])
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
  const listRaw = Array.isArray(values) ? values.map((v) => safe(v)).filter(Boolean) : [];
  const seen = new Set();
  const list = listRaw.filter((value) => {
    const key = normalizeToken(value);
    if (!key || seen.has(key)) return false;
    seen.add(key);
    return true;
  });

  list.forEach((value) => ensureOption(selectEl, value));
  selectEl.dataset.values = list.join('; ');
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

  const lockedNorm = parseList(selectEl.dataset.lockedValues || '').map(normalizeToken);
  host.innerHTML = values.map((value) => {
    if (lockedNorm.includes(normalizeToken(value))) {
      return '<span class="multi-chip multi-chip-locked" title="Ajouté par un autre client">' + escapeHtml(value) + '</span>';
    }
    return [
      '<span class="multi-chip">',
      escapeHtml(value),
      '<span class="multi-chip-remove" data-chip-remove="1" data-chip-select="' + escapeHtml(selectEl.id) + '" data-chip-value="' + escapeHtml(value) + '" title="Retirer">x</span>',
      '</span>',
    ].join('');
  }).join('');
}

function bindMultiPicker(selectId) {
  const selectEl = q(selectId);
  if (!selectEl) return;
  selectEl.addEventListener('change', () => {
    const value = safe(selectEl.value);
    if (!value) return;

    const existing = selectedValues(selectEl);
    if (!existing.some((item) => normalizeToken(item) === normalizeToken(value))) {
      existing.push(value);
    }
    selectEl.dataset.values = existing.join('; ');
    refreshMultiPickerChips(selectEl);
    selectEl.value = '';
  });

  const chipTargetId = selectEl.getAttribute('data-chip-target');
  const chipHost = chipTargetId ? q(chipTargetId) : null;
  chipHost?.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-chip-remove="1"][data-chip-select][data-chip-value]');
    if (!btn) return;
    const value = safe(btn.getAttribute('data-chip-value'));
    const lockedNorm = parseList(selectEl.dataset.lockedValues || '').map(normalizeToken);
    if (lockedNorm.includes(normalizeToken(value))) return; // protected — another client added this
    const filtered = selectedValues(selectEl).filter((item) => normalizeToken(item) !== normalizeToken(value));
    selectEl.dataset.values = filtered.join('; ');
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
    facebook_url: 'Facebook',
    instagram_url: 'Instagram',
    linkedin_url: 'LinkedIn',
    logo_url: 'Logo (URL)',
    photo_cover_url: 'Image de couverture (URL)',
    slug: 'Slug public',
    description_short: 'Description courte',
    description_long: 'Description longue',
    supplier_type: 'Type fournisseur',
    activity_text: 'Activités globales',
    labels: 'Labels globaux',
  };
  return labels[safe(field)] || safe(field);
}

function resolveContextClientId() {
  const fromState = Number(state.client?.id || 0);
  if (fromState > 0) return fromState;

  const fromMe = Number(state.me?.client_id || 0);
  if (fromMe > 0) return fromMe;

  const fromQuery = Number(new URLSearchParams(window.location.search).get('client_id') || 0);
  if (fromQuery > 0) return fromQuery;

  return 0;
}

function buildClientScopeQuery() {
  const query = {};
  const clientId = resolveContextClientId();
  if (clientId > 0) {
    query.client_id = String(clientId);
  }
  return query;
}

function formatNowTime() {
  return new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function currentSupplier() {
  return state.suppliers.find((row) => Number(row.id) === Number(state.selectedSupplierId)) || null;
}

function selectSupplier(supplierId, tabName = null) {
  state.selectedSupplierId = Number(supplierId || 0) || null;
  const supplier = currentSupplier();
  if (tabName) {
    setClientTab(tabName);
  }
  renderSupplierList();
  renderEditor();
  renderSupplierConsentOverview();
  renderSupplierCompletenessOverview();
  if (supplier) {
    trackUiEvent('ui_view_supplier', {
      event_type: 'visit',
      app: 'client',
      page: 'client',
      tab: tabName || 'my-suppliers',
      meta: {
        supplier_id: Number(supplier.id),
        supplier_name: safe(supplier.name),
      },
    });
  }
}

function supplierConsentBadgeKey(status) {
  const normalized = safe(status).toLowerCase();
  if (normalized === 'approved') return 'approved';
  if (normalized === 'rejected') return 'rejected';
  if (normalized === 'expired') return 'expired';
  if (normalized === 'sent') return 'sent';
  if (normalized === 'opened') return 'opened';
  if (normalized === 'pending') return 'pending';
  return 'none';
}

function statusLabel(status) {
  const normalized = safe(status).toLowerCase();
  if (normalized === 'approved') return 'Validé';
  if (normalized === 'rejected') return 'Refusé';
  if (normalized === 'expired') return 'Expiré';
  if (normalized === 'sent') return 'Demande envoyée';
  if (normalized === 'opened') return 'Demande ouverte';
  if (normalized === 'pending') return 'En attente';
  return 'Aucune demande';
}

function getSupplierConsentState(supplier) {
  const status = safe(supplier?.supplier_consent_status).toLowerCase() || 'none';
  const requestedAt = safe(supplier?.supplier_consent_requested_at);
  const answeredAt = safe(supplier?.supplier_consent_answered_at);
  const sourceClientName = safe(supplier?.supplier_consent_source_client_name);
  return {
    status,
    badgeKey: supplierConsentBadgeKey(status),
    label: statusLabel(status),
    requestedAt,
    answeredAt,
    sourceClientName,
    canSend: status === 'none',
    canResend: ['rejected', 'expired', 'sent', 'opened', 'pending'].includes(status),
    isApproved: status === 'approved',
  };
}

function getSupplierMissingFields(supplier) {
  const missing = [];
  if (!safe(supplier?.name)) missing.push('nom');
  if (!safe(supplier?.address) && !safe(supplier?.city) && !safe(supplier?.postal_code)) missing.push('adresse');
  if (!safe(supplier?.latitude) || !safe(supplier?.longitude)) missing.push('coordonnées GPS');
  if (!safe(supplier?.supplier_type)) missing.push('type');
  if (!parseList(safe(supplier?.global_activity_text) || safe(supplier?.profile_activity_text)).length) missing.push('catégorie / activité');
  if (!parseList(safe(supplier?.global_labels) || safe(supplier?.profile_labels_text)).length) missing.push('label');
  return missing;
}

function renderReferenceSelects() {
  populateSelect(q('profileActivitySelect'), state.activities, { includeEmpty: true, emptyLabel: 'Ajouter une activité...' });
  populateSelect(q('profileLabelsSelect'), state.labels, { includeEmpty: true, emptyLabel: 'Ajouter un label...' });
  populateSelect(q('changeRequestSupplierType'), state.supplierTypes, { includeEmpty: true, emptyLabel: 'Sélectionner un type' });
  populateSelect(q('changeRequestActivities'), state.activities, { includeEmpty: true, emptyLabel: 'Ajouter une activité...' });
  populateSelect(q('changeRequestLabels'), state.labels, { includeEmpty: true, emptyLabel: 'Ajouter un label...' });
  populateSelect(q('newSupplierType'), state.supplierTypes, { includeEmpty: true, emptyLabel: 'Sélectionner un type' });
  populateSelect(q('newSupplierActivities'), state.activities, { includeEmpty: true, emptyLabel: 'Ajouter une activité...' });
  populateSelect(q('newSupplierLabels'), state.labels, { includeEmpty: true, emptyLabel: 'Ajouter un label...' });
  refreshMultiPickerChips(q('profileActivitySelect'));
  refreshMultiPickerChips(q('profileLabelsSelect'));
  refreshMultiPickerChips(q('changeRequestActivities'));
  refreshMultiPickerChips(q('changeRequestLabels'));
  refreshMultiPickerChips(q('newSupplierActivities'));
  refreshMultiPickerChips(q('newSupplierLabels'));
}

function renderClientInfoForm() {
  const c = state.client || {};
  q('clientInfoName').value = safe(c.name);
  q('clientInfoType').value = safe(c.client_type);
  q('clientInfoEmail').value = safe(c.email);
  q('clientInfoPhone').value = safe(c.phone);
  q('clientInfoWebsite').value = safe(c.website);
  q('clientInfoFacebook').value = safe(c.facebook_url);
  q('clientInfoInstagram').value = safe(c.instagram_url);
  q('clientInfoLogoPath').value = safe(c.logo_url) ? 'Logo actuel' : '';
  q('clientInfoAddress').value = safe(c.address);
  q('clientInfoCity').value = safe(c.city);
  q('clientInfoPostal').value = safe(c.postal_code);
  q('clientInfoCountry').value = safe(c.country) || 'France';
  q('clientInfoLundi').value = safe(c.lundi);
  q('clientInfoMardi').value = safe(c.mardi);
  q('clientInfoMercredi').value = safe(c.mercredi);
  q('clientInfoJeudi').value = safe(c.jeudi);
  q('clientInfoVendredi').value = safe(c.vendredi);
  q('clientInfoSamedi').value = safe(c.samedi);
  q('clientInfoDimanche').value = safe(c.dimanche);
  q('clientInfoLat').value = safe(c.latitude);
  q('clientInfoLng').value = safe(c.longitude);
  q('clientInfoDescriptionShort').value = safe(c.description_short);
  q('clientInfoDescriptionLong').value = safe(c.description_long);
  state.clientGalleryDraft = parseGalleryImages(c.gallery_images);
  q('clientInfoGalleryFiles').value = '';
  renderClientGalleryPreview();
  syncLongTextEditorFromTextarea();
  renderClientConsent();

  initOrUpdateClientMap();
}

function parseGalleryImages(rawValue) {
  if (!rawValue) return [];
  try {
    const parsed = JSON.parse(String(rawValue));
    if (!Array.isArray(parsed)) return [];
    return parsed
      .map((item) => {
        if (typeof item === 'string') return item;
        if (item && typeof item.url === 'string') return item.url;
        return '';
      })
      .map((url) => safe(url))
      .filter(Boolean);
  } catch (_) {
    return [];
  }
}

function renderGalleryPreview(urls, removable) {
  const preview = q('clientInfoGalleryPreview');
  if (!preview) return;
  preview.innerHTML = '';

  urls.forEach((url, idx) => {
    const item = document.createElement('div');
    item.className = 'gallery-preview-item';
    item.innerHTML = `<img src="${url}" alt="image galerie" />`;

    if (removable) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'gallery-preview-item-remove';
      btn.textContent = 'x';
      btn.dataset.idx = String(idx);
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const fileInput = q('clientInfoGalleryFiles');
        const dt = new DataTransfer();
        Array.from(fileInput.files || []).forEach((f, i) => {
          if (i !== Number(btn.dataset.idx)) {
            dt.items.add(f);
          }
        });
        fileInput.files = dt.files;
        renderSelectedGalleryPreview();
      });
      item.appendChild(btn);
    }

    preview.appendChild(item);
  });
}

function renderClientGalleryPreview() {
  const preview = q('clientInfoGalleryPreview');
  const fileInput = q('clientInfoGalleryFiles');
  if (!preview || !fileInput) return;

  preview.innerHTML = '';

  const existingUrls = Array.isArray(state.clientGalleryDraft) ? state.clientGalleryDraft : [];
  existingUrls.forEach((url, idx) => {
    const item = document.createElement('div');
    item.className = 'gallery-preview-item';
    item.innerHTML = `<img src="${url}" alt="image galerie" />`;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'gallery-preview-item-remove';
    btn.textContent = 'x';
    btn.title = 'Supprimer cette image';
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      state.clientGalleryDraft = existingUrls.filter((_, i) => i !== idx);
      renderClientGalleryPreview();
    });

    item.appendChild(btn);
    preview.appendChild(item);
  });

  const selectedFiles = Array.from(fileInput.files || []);
  selectedFiles.forEach((file, idx) => {
    const blobUrl = URL.createObjectURL(file);
    const item = document.createElement('div');
    item.className = 'gallery-preview-item';

    const img = document.createElement('img');
    img.src = blobUrl;
    img.alt = 'image galerie';
    img.addEventListener('load', () => URL.revokeObjectURL(blobUrl), { once: true });
    img.addEventListener('error', () => URL.revokeObjectURL(blobUrl), { once: true });
    item.appendChild(img);

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'gallery-preview-item-remove';
    btn.textContent = 'x';
    btn.title = 'Retirer ce fichier';
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const dt = new DataTransfer();
      selectedFiles.forEach((f, i) => {
        if (i !== idx) dt.items.add(f);
      });
      fileInput.files = dt.files;
      renderClientGalleryPreview();
    });

    item.appendChild(btn);
    preview.appendChild(item);
  });
}

function renderSelectedGalleryPreview() {
  renderClientGalleryPreview();
}

function renderClientConsent() {
  const consent = state.clientConsent || {};
  const status = safe(consent.status) || 'none';
  const acceptedAt = safe(consent.accepted_at);
  const version = safe(consent.version);

  const checkbox = q('clientConsentCheckbox');
  const btnConfirm = q('btnClientConsentConfirm');
  const btnRevoke = q('btnClientConsentRevoke');
  const statusHost = q('clientConsentStatus');

  if (!checkbox || !btnConfirm || !btnRevoke || !statusHost) {
    return;
  }

  checkbox.checked = false;

  if (status === 'approved') {
    statusHost.textContent = acceptedAt
      ? `Consentement validé le ${acceptedAt}${version ? ` (version ${version})` : ''}`
      : `Consentement validé${version ? ` (version ${version})` : ''}`;
    btnConfirm.disabled = true;
    checkbox.disabled = true;
    btnRevoke.disabled = false;
    return;
  }

  statusHost.textContent = 'Consentement non validé';
  btnConfirm.disabled = false;
  checkbox.disabled = false;
  btnRevoke.disabled = true;
}

function sanitizePastedHtml(html) {
  const tmp = document.createElement('div');
  tmp.innerHTML = html;

  const allowedTags = new Set(['p', 'h2', 'h3', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'a', 'br']);

  function cleanNode(node) {
    if (node.nodeType === Node.TEXT_NODE) return node.cloneNode();
    if (node.nodeType !== Node.ELEMENT_NODE) return null;

    const tag = node.tagName.toLowerCase();

    if (allowedTags.has(tag)) {
      const el = document.createElement(tag);
      if (tag === 'a') {
        const href = node.getAttribute('href') || '';
        if (href && /^https?:\/\//i.test(href)) {
          el.setAttribute('href', href);
          el.setAttribute('target', '_blank');
          el.setAttribute('rel', 'noopener');
        }
      }
      Array.from(node.childNodes).forEach((child) => {
        const cleaned = cleanNode(child);
        if (cleaned) el.appendChild(cleaned);
      });
      return el;
    }

    // Disallowed tag: unwrap, keep children
    const frag = document.createDocumentFragment();
    Array.from(node.childNodes).forEach((child) => {
      const cleaned = cleanNode(child);
      if (cleaned) frag.appendChild(cleaned);
    });
    return frag;
  }

  const result = document.createElement('div');
  Array.from(tmp.childNodes).forEach((child) => {
    const cleaned = cleanNode(child);
    if (cleaned) result.appendChild(cleaned);
  });

  return result.innerHTML.trim() || '<p></p>';
}

function syncLongTextEditorFromTextarea() {
  const source = q('clientInfoDescriptionLong');
  const editor = q('clientInfoDescriptionLongEditor');
  if (!source || !editor) return;

  const html = String(source.value || '').trim();
  editor.innerHTML = html || '<p></p>';
}

function updateEditorToolbarState() {
  const toolbar = q('clientInfoLongEditorToolbar');
  if (!toolbar) return;

  let currentBlock = '';
  try {
    currentBlock = (document.queryCommandValue('formatBlock') || '').toLowerCase();
  } catch (_) { /* ignore */ }

  toolbar.querySelectorAll('button[data-editor-cmd]').forEach((btn) => {
    const cmd = safe(btn.getAttribute('data-editor-cmd'));
    const arg = (btn.getAttribute('data-editor-arg') || '').toLowerCase();
    try {
      let on = false;
      if (cmd === 'formatBlock') {
        on = currentBlock === arg;
      } else if (cmd === 'bold' || cmd === 'italic') {
        on = document.queryCommandState(cmd);
      }
      btn.classList.toggle('active', on);
    } catch (_) {
      btn.classList.remove('active');
    }
  });
}

function syncLongTextTextareaFromEditor() {
  const source = q('clientInfoDescriptionLong');
  const editor = q('clientInfoDescriptionLongEditor');
  if (!source || !editor) return;
  source.value = String(editor.innerHTML || '').trim();
}

function initOrUpdateClientMap() {
  const latVal = Number(String(q('clientInfoLat').value || '').replace(',', '.'));
  const lngVal = Number(String(q('clientInfoLng').value || '').replace(',', '.'));
  const hasCoords = Number.isFinite(latVal) && Number.isFinite(lngVal);
  const center = hasCoords ? [latVal, lngVal] : [43.7102, -1.0553];
  const zoom = hasCoords ? 13 : 8;

  if (!mapState.map) {
    mapState.map = L.map('clientInfoMap');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapState.map);
  }

  mapState.map.setView(center, zoom);
  mapState.map.invalidateSize();

  if (mapState.marker) {
    mapState.marker.remove();
  }

  mapState.marker = L.marker(center, { draggable: true }).addTo(mapState.map);
  mapState.marker.on('dragend', () => {
    const pos = mapState.marker.getLatLng();
    q('clientInfoLat').value = Number(pos.lat).toFixed(6);
    q('clientInfoLng').value = Number(pos.lng).toFixed(6);
  });
}

function renderSupplierCreateRequests() {
  const host = q('supplierCreateRequestsList');
  const rows = state.supplierCreateRequests || [];
  if (!rows.length) {
    host.innerHTML = '<div class="list-item"><div class="list-sub">Aucune demande de création.</div></div>';
    return;
  }

  host.innerHTML = rows.map((r) => {
    const status = safe(r.status) || 'pending';
    const approvedName = safe(r.approved_supplier_name);
    const review = safe(r.review_note);
    const details = [safe(r.supplier_type), safe(r.city), safe(r.activity_text)].filter(Boolean).join(' | ');
    return [
      '<div class="list-item">',
      '<div class="list-main">' + escapeHtml(safe(r.name) || '(sans nom)') + ' - ' + escapeHtml(statusLabel(status)) + '</div>',
      '<div class="list-sub">' + (details ? escapeHtml(details) : 'Sans détails') + '</div>',
      (approvedName ? '<div class="list-sub">Fournisseur créé: ' + escapeHtml(approvedName) + '</div>' : ''),
      (review ? '<div class="list-sub">Note admin: ' + escapeHtml(review) + '</div>' : ''),
      '<div class="list-sub">Le ' + escapeHtml(safe(r.created_at)) + '</div>',
      '</div>',
    ].join('');
  }).join('');
}

function renderSupplierLinkSearchResults() {
  const host = q('linkSupplierSearchResults');
  const rows = state.supplierLinkSearchResults || [];
  if (!rows.length) {
    host.innerHTML = '<div class="list-item"><div class="list-sub">Aucun resultat. Essayez avec un autre mot-cle.</div></div>';
    return;
  }

  host.innerHTML = rows.map((r) => {
    const details = [safe(r.city), safe(r.postal_code), safe(r.supplier_type)].filter(Boolean).join(' | ');
    return [
      '<div class="list-item">',
      '<div class="list-main">' + escapeHtml(safe(r.name) || '(sans nom)') + '</div>',
      '<div class="list-sub">' + escapeHtml(details || 'Sans details') + '</div>',
      '<div class="row" style="margin-top:6px;">',
      '<button type="button" data-link-supplier-id="' + Number(r.id) + '">Demander le rattachement</button>',
      '</div>',
      '</div>',
    ].join('');
  }).join('');

  host.querySelectorAll('button[data-link-supplier-id]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const supplierId = Number(btn.getAttribute('data-link-supplier-id') || 0);
      if (supplierId > 0) {
        await onCreateSupplierLinkRequest(supplierId);
      }
    });
  });
}

function renderSupplierLinkRequests() {
  const host = q('supplierLinkRequestsList');
  const rows = state.supplierLinkRequests || [];
  if (!rows.length) {
    host.innerHTML = '<div class="list-item"><div class="list-sub">Aucune demande de rattachement.</div></div>';
    return;
  }

  host.innerHTML = rows.map((r) => {
    const status = safe(r.status) || 'pending';
    const details = [safe(r.supplier_name), safe(r.supplier_city)].filter(Boolean).join(' | ');
    const note = safe(r.note);
    const review = safe(r.review_note);
    return [
      '<div class="list-item">',
      '<div class="list-main">' + escapeHtml(details || '(fournisseur)') + ' - ' + escapeHtml(statusLabel(status)) + '</div>',
      (note ? '<div class="list-sub">Note: ' + escapeHtml(note) + '</div>' : ''),
      (review ? '<div class="list-sub">Note admin: ' + escapeHtml(review) + '</div>' : ''),
      '<div class="list-sub">Le ' + escapeHtml(safe(r.created_at)) + '</div>',
      '</div>',
    ].join('');
  }).join('');
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
    facebook_url: supplier.facebook_url,
    instagram_url: supplier.instagram_url,
    linkedin_url: supplier.linkedin_url,
    logo_url: supplier.logo_url,
    photo_cover_url: supplier.photo_cover_url,
    slug: supplier.slug,
    description_short: supplier.description_short,
    description_long: supplier.description_long,
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

  const logoHost = q('clientHeaderLogo');
  const logoUrl = safe(client.logo_url);
  logoHost.innerHTML = logoUrl
    ? `<img src="${escapeHtml(logoUrl)}" alt="logo client" />`
    : '<span class="muted">Logo</span>';

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
  const logo = safe(s.logo_url);
  const shortDescription = safe(s.description_short) || '-';

  return [
    '<strong>Fiche globale</strong>',
    (logo ? '<div style="margin:6px 0;"><img src="' + logo + '" alt="logo producteur" style="max-height:46px;max-width:160px;object-fit:contain;border:1px solid #e5e7eb;border-radius:6px;padding:2px;background:#fff;"></div>' : ''),
    '<div><b>Type:</b> ' + type + '</div>',
    '<div><b>Activites:</b> ' + acts + '</div>',
    '<div><b>Labels:</b> ' + labels + '</div>',
    '<div><b>Description courte:</b> ' + shortDescription + '</div>',
    '<div><b>Contact:</b> ' + contact + '</div>',
  ].join('');
}

function renderSupplierConsentOverview() {
  const host = q('supplierConsentOverviewList');
  const summary = q('supplierConsentOverviewSummary');
  if (!host || !summary) return;

  const rows = state.suppliers.slice().sort((a, b) => safe(a.name).localeCompare(safe(b.name), 'fr'));
  const counts = {
    approved: 0,
    pending: 0,
    none: 0,
  };

  rows.forEach((supplier) => {
    const consent = getSupplierConsentState(supplier);
    if (consent.isApproved) counts.approved += 1;
    else if (consent.status === 'none') counts.none += 1;
    else counts.pending += 1;
  });

  summary.textContent = `${rows.length} fournisseur(s) liés | ${counts.approved} validé(s) | ${counts.pending} en cours | ${counts.none} à lancer`;

  if (!rows.length) {
    host.innerHTML = '<div class="list-item"><div class="list-sub">Aucun fournisseur lié.</div></div>';
    return;
  }

  host.innerHTML = rows.map((supplier) => {
    const consent = getSupplierConsentState(supplier);
    const relationship = relationshipStatusLabel(safe(supplier.relationship_status) || 'active');
    const location = [safe(supplier.city), safe(supplier.address)].filter(Boolean).join(' - ') || 'Localisation non renseignée';
    const consentInfo = consent.isApproved
      ? (consent.answeredAt ? `Validé le ${escapeHtml(consent.answeredAt)}` : 'Consentement validé')
      : consent.requestedAt
        ? `Dernière demande le ${escapeHtml(consent.requestedAt)}`
        : 'Aucune demande envoyée';
    const sourceInfo = consent.sourceClientName ? `Demande portée par ${escapeHtml(consent.sourceClientName)}` : '';
    return [
      '<article class="overview-card">',
      '<div class="overview-head">',
      '<div>',
      '<div class="overview-title">' + escapeHtml(safe(supplier.name) || '(sans nom)') + '</div>',
      '<div class="overview-meta">' + escapeHtml(location) + ' | relation: ' + escapeHtml(relationship) + '</div>',
      '<div class="overview-meta">' + consentInfo + (sourceInfo ? ' | ' + sourceInfo : '') + '</div>',
      '</div>',
      '<span class="status-badge ' + consent.badgeKey + '">' + escapeHtml(consent.label) + '</span>',
      '</div>',
      '<div class="overview-actions">',
      '<button type="button" data-open-supplier="' + Number(supplier.id) + '">Ouvrir la fiche</button>',
      (!consent.isApproved ? '<button type="button" class="primary" data-send-consent="' + Number(supplier.id) + '">' + (consent.canSend ? 'Envoyer la demande' : 'Relancer la demande') + '</button>' : ''),
      '</div>',
      '</article>',
    ].join('');
  }).join('');

  host.querySelectorAll('[data-open-supplier]').forEach((el) => {
    el.addEventListener('click', () => {
      selectSupplier(Number(el.getAttribute('data-open-supplier')), 'my-suppliers');
    });
  });

  host.querySelectorAll('[data-send-consent]').forEach((el) => {
    el.addEventListener('click', async () => {
      showMsg('supplierConsentOverviewMsg', '');
      try {
        const supplierId = Number(el.getAttribute('data-send-consent'));
        selectSupplier(supplierId, 'supplier-consents');
        await onSendSupplierConsentSingle();
        renderSupplierConsentOverview();
        showMsg('supplierConsentOverviewMsg', 'Demande envoyée.');
      } catch (e) {
        showMsg('supplierConsentOverviewMsg', e.message, true);
      }
    });
  });
}

function renderSupplierCompletenessOverview() {
  const host = q('supplierCompletenessList');
  const summary = q('supplierCompletenessSummary');
  if (!host || !summary) return;

  const rows = state.suppliers
    .map((supplier) => ({ supplier, missing: getSupplierMissingFields(supplier) }))
    .filter((entry) => entry.missing.length > 0)
    .sort((a, b) => b.missing.length - a.missing.length || safe(a.supplier.name).localeCompare(safe(b.supplier.name), 'fr'));

  summary.textContent = rows.length
    ? `${rows.length} fournisseur(s) ont encore des informations à compléter.`
    : 'Toutes les fiches liées ont déjà le socle de données attendu.';

  if (!rows.length) {
    host.innerHTML = '<div class="list-item"><div class="list-sub">Aucune fiche incomplète.</div></div>';
    return;
  }

  host.innerHTML = rows.map(({ supplier, missing }) => {
    const relationship = relationshipStatusLabel(safe(supplier.relationship_status) || 'active');
    const consent = getSupplierConsentState(supplier);
    return [
      '<article class="overview-card">',
      '<div class="overview-head">',
      '<div>',
      '<div class="overview-title">' + escapeHtml(safe(supplier.name) || '(sans nom)') + '</div>',
      '<div class="overview-meta">Relation: ' + escapeHtml(relationship) + ' | consentement: ' + escapeHtml(consent.label) + '</div>',
      '</div>',
      '<span class="status-badge none">' + missing.length + ' champ(s) à compléter</span>',
      '</div>',
      '<div class="overview-tags">' + missing.map((item) => '<span class="overview-tag">' + escapeHtml(item) + '</span>').join('') + '</div>',
      '<div class="overview-actions">',
      '<button type="button" data-open-complete-supplier="' + Number(supplier.id) + '">Compléter la fiche</button>',
      '</div>',
      '</article>',
    ].join('');
  }).join('');

  host.querySelectorAll('[data-open-complete-supplier]').forEach((el) => {
    el.addEventListener('click', () => {
      selectSupplier(Number(el.getAttribute('data-open-complete-supplier')), 'my-suppliers');
    });
  });
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
      const relLabel = relationshipStatusLabel(rel);
      const city = safe(s.city) || '-';
      return [
        '<div class="list-item ' + (isActive ? 'active' : '') + '" data-id="' + Number(s.id) + '">',
        '<div class="list-main">' + (safe(s.name) || '(sans nom)') + '</div>',
        '<div class="list-sub">' + city + ' - statut: ' + relLabel + '</div>',
        '</div>',
      ].join('');
    })
    .join('');

  host.querySelectorAll('.list-item[data-id]').forEach((el) => {
    el.addEventListener('click', () => {
      selectSupplier(Number(el.getAttribute('data-id')));
    });
  });
}

function renderEditor() {
  const s = currentSupplier();
  const form = q('profileForm');
  const global = q('supplierGlobal');
  const reqSection = q('changeRequestSection');
  const consentBox = q('supplierConsentBox');

  if (!s) {
    q('editorTitle').textContent = 'Profil client fournisseur';
    q('editorHint').textContent = 'Selectionnez un fournisseur a gauche.';
    form.classList.add('hidden');
    global.classList.add('hidden');
    consentBox?.classList.add('hidden');
    reqSection.classList.add('hidden');
    q('changeRequestsList').innerHTML = '';
    return;
  }

  q('editorTitle').textContent = safe(s.name) || 'Fournisseur';
  q('editorHint').textContent = safe(s.address) || safe(s.city) || '';

  global.innerHTML = formatGlobalSupplier(s);
  global.classList.remove('hidden');
  renderSupplierConsentBox(s);

  q('profileSupplierId').value = String(Number(s.id));

  // Locked = everything NOT added by this client in their own profile.
  // Admin-imported data and other clients' items are locked; only own items have x.
  const globalActivities = parseList(s.global_activity_text || '');
  const ownActivities    = parseList(s.profile_activity_text || '');
  const lockedActivities = globalActivities.filter(
    (a) => !ownActivities.some((o) => normalizeToken(o) === normalizeToken(a))
  );
  const actSel = q('profileActivitySelect');
  actSel.dataset.lockedValues = lockedActivities.join('; ');
  setMultiSelectValues(actSel, globalActivities);

  const globalLabels = parseList(s.global_labels || '');
  const ownLabels    = parseList(s.profile_labels_text || '');
  const lockedLabels = globalLabels.filter(
    (l) => !ownLabels.some((o) => normalizeToken(o) === normalizeToken(l))
  );
  const labSel = q('profileLabelsSelect');
  labSel.dataset.lockedValues = lockedLabels.join('; ');
  setMultiSelectValues(labSel, globalLabels);
  q('profileNotes').value = safe(s.profile_notes);
  q('profileRelationshipStatus').value = safe(s.relationship_status) || 'active';
  form.classList.remove('hidden');
  reqSection.classList.remove('hidden');
  showMsg('profileMsg', '');
  showMsg('changeRequestMsg', '');
  renderChangeRequestInput();
  renderChangeRequestsForSelectedSupplier();
}

function renderSupplierConsentBox(supplier) {
  const box = q('supplierConsentBox');
  const statusHost = q('supplierConsentStatus');
  const btnSend = q('btnSendSupplierConsentSingle');
  if (!box || !statusHost || !btnSend) return;
  if (!supplier || !supplier.id) {
    box.classList.add('hidden');
    return;
  }

  const supplierId = Number(supplier.id);
  const history = (state.supplierConsentHistory || []).filter((row) => Number(row.supplier_id) === supplierId);
  const hasApproved = history.some((row) => Number(row.consent_id || 0) > 0);
  const latest = history.length ? history[0] : null;

  if (hasApproved) {
    statusHost.textContent = 'Consentement validé pour ce fournisseur.';
    btnSend.disabled = true;
  } else if (latest) {
    statusHost.textContent = 'Dernière demande: ' + statusLabel(safe(latest.status)) + ' le ' + safe(latest.requested_at);
    btnSend.disabled = false;
  } else {
    statusHost.textContent = 'Aucune demande envoyée pour ce fournisseur.';
    btnSend.disabled = false;
  }

  box.classList.remove('hidden');
}

function relationshipStatusLabel(status) {
  if (status === 'active') return 'Actif';
  if (status === 'prospect') return 'Prospect';
  if (status === 'inactive') return 'Inactif';
  if (status === 'blocked') return 'Bloqué';
  return safe(status) || 'Actif';
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
  const query = buildClientScopeQuery();
  const statusFilter = safe(q('changeRequestStatusFilter')?.value || 'all');
  if (statusFilter !== 'all') {
    query.status = statusFilter;
  }

  const res = await api('client/change-request/list', 'GET', null, query);
  state.changeRequests = Array.isArray(res.requests) ? res.requests : [];
}

async function loadSupplierCreateRequests() {
  const query = buildClientScopeQuery();
  const res = await api('client/supplier-create-request/list', 'GET', null, query);
  state.supplierCreateRequests = Array.isArray(res.requests) ? res.requests : [];
}

async function loadSupplierLinkRequests() {
  const query = buildClientScopeQuery();
  const res = await api('client/supplier-link-request/list', 'GET', null, query);
  state.supplierLinkRequests = Array.isArray(res.requests) ? res.requests : [];
}

async function loadSupplierConsentHistory() {
  const query = buildClientScopeQuery();
  const res = await api('client/supplier-consent/history', 'GET', null, query);
  state.supplierConsentHistory = Array.isArray(res.history) ? res.history : [];
}

async function loadBootstrap() {
  const query = buildClientScopeQuery();

  const res = await api('client/bootstrap', 'GET', null, query);
  state.client = res.client || null;
  state.clientConsent = res.client_consent || (res.consents && res.consents.client_consent) || null;
  state.suppliers = Array.isArray(res.suppliers) ? res.suppliers : [];
  state.activities = Array.isArray(res.activities) ? res.activities.map((item) => safe(item.name || item)).filter(Boolean) : [];
  state.labels = Array.isArray(res.labels) ? res.labels.map((item) => safe(item.name || item)).filter(Boolean) : [];
  state.supplierTypes = Array.isArray(res.supplier_types) ? res.supplier_types.map((item) => safe(item.name || item)).filter(Boolean) : [];
  renderReferenceSelects();

  if (!state.suppliers.some((x) => Number(x.id) === Number(state.selectedSupplierId))) {
    state.selectedSupplierId = state.suppliers.length ? Number(state.suppliers[0].id) : null;
  }

  renderClientHeader();
  renderClientInfoForm();
  renderSupplierList();
  renderSupplierConsentOverview();
  renderSupplierCompletenessOverview();
  renderEditor();

  try {
    await loadChangeRequests();
  } catch (e) {
    console.error('loadChangeRequests error:', e);
  }
  renderClientHeader();
  renderChangeRequestsForSelectedSupplier();

  try {
    await loadSupplierCreateRequests();
  } catch (e) {
    console.error('loadSupplierCreateRequests error:', e);
  }
  renderSupplierCreateRequests();

  try {
    await loadSupplierLinkRequests();
  } catch (e) {
    console.error('loadSupplierLinkRequests error:', e);
  }
  renderSupplierLinkRequests();

  try {
    await loadSupplierConsentHistory();
  } catch (e) {
    console.error('loadSupplierConsentHistory error:', e);
  }
  renderSupplierConsentOverview();
  renderEditor();
}

async function onSendSupplierConsentSingle() {
  showMsg('supplierConsentMsg', '');
  try {
    const supplier = currentSupplier();
    const supplierId = Number(supplier?.id || 0);
    if (!supplierId) {
      throw new Error('Sélectionnez un fournisseur');
    }

    await api('client/supplier-consent/send', 'POST', {
      supplier_id: supplierId,
    }, buildClientScopeQuery());

    await loadSupplierConsentHistory();
    const supplierRow = state.suppliers.find((row) => Number(row.id) === supplierId);
    if (supplierRow) {
      supplierRow.supplier_consent_status = 'sent';
      supplierRow.supplier_consent_requested_at = new Date().toISOString().slice(0, 19).replace('T', ' ');
      supplierRow.supplier_consent_source_client_name = safe(state.client?.name);
    }
    renderSupplierConsentBox(supplier);
    renderSupplierConsentOverview();
    showMsg('supplierConsentMsg', `Demande envoyée à ${formatNowTime()}`);
  } catch (e) {
    showMsg('supplierConsentMsg', e.message, true);
    throw e;
  }
}

async function onSendAllSupplierConsents() {
  showMsg('supplierConsentOverviewMsg', '');
  try {
    const toSend = state.suppliers.filter((supplier) => {
      const consent = getSupplierConsentState(supplier);
      return !consent.isApproved;
    });

    if (!toSend.length) {
      showMsg('supplierConsentOverviewMsg', 'Tous les fournisseurs ont déjà validé leur consentement.');
      return;
    }

    let sent = 0;
    let failed = 0;

    for (const supplier of toSend) {
      try {
        const supplierId = Number(supplier.id);
        await api('client/supplier-consent/send', 'POST', {
          supplier_id: supplierId,
        }, buildClientScopeQuery());

        const supplierRow = state.suppliers.find((row) => Number(row.id) === supplierId);
        if (supplierRow) {
          supplierRow.supplier_consent_status = 'sent';
          supplierRow.supplier_consent_requested_at = new Date().toISOString().slice(0, 19).replace('T', ' ');
          supplierRow.supplier_consent_source_client_name = safe(state.client?.name);
        }
        sent += 1;
      } catch (e) {
        console.error(`Erreur envoi consentement fournisseur ${supplier.id}:`, e);
        failed += 1;
      }
    }

    renderSupplierConsentOverview();
    let msg = `${sent} demande(s) envoyée(s)`;
    if (failed > 0) {
      msg += ` | ${failed} erreur(s)`;
    }
    showMsg('supplierConsentOverviewMsg', msg);
  } catch (e) {
    showMsg('supplierConsentOverviewMsg', e.message, true);
  }
}

async function onConfirmClientConsent() {
  showMsg('clientConsentMsg', '');
  try {
    if (!q('clientConsentCheckbox')?.checked) {
      throw new Error('Cochez la case avant de valider');
    }

    const currentVersion = safe(state.clientConsent?.version) || '2026-04-v1';
    await api('client/consent/confirm', 'POST', {
      accept: true,
      text_version: currentVersion,
    }, buildClientScopeQuery());

    await loadBootstrap();
    showMsg('clientConsentMsg', `Consentement validé à ${formatNowTime()}`);
  } catch (e) {
    showMsg('clientConsentMsg', e.message, true);
  }
}

async function onRevokeClientConsent() {
  showMsg('clientConsentMsg', '');
  try {
    await api('client/consent/revoke', 'POST', {
      reason: 'Révocation depuis espace client',
    }, buildClientScopeQuery());

    await loadBootstrap();
    showMsg('clientConsentMsg', `Consentement révoqué à ${formatNowTime()}`);
  } catch (e) {
    showMsg('clientConsentMsg', e.message, true);
  }
}

async function onSearchSupplierLinkCandidates() {
  showMsg('linkSupplierMsg', '');
  try {
    const qText = safe(q('linkSupplierSearch').value);
    if (qText.length < 2) {
      throw new Error('Saisissez au moins 2 caracteres');
    }
    const query = buildClientScopeQuery();
    query.q = qText;
    const res = await api('client/supplier-link-search', 'GET', null, query);
    state.supplierLinkSearchResults = Array.isArray(res.suppliers) ? res.suppliers : [];
    trackUiEvent('ui_search_supplier_link_candidates', {
      event_type: 'action',
      app: 'client',
      page: 'client',
      tab: 'new-supplier',
      meta: {
        result_count: state.supplierLinkSearchResults.length,
      },
    });
    renderSupplierLinkSearchResults();
    showMsg('linkSupplierMsg', `${state.supplierLinkSearchResults.length} resultat(s)`);
  } catch (e) {
    showMsg('linkSupplierMsg', e.message, true);
  }
}

async function onCreateSupplierLinkRequest(supplierId) {
  showMsg('linkSupplierMsg', '');
  try {
    const payload = {
      client_id: resolveContextClientId(),
      supplier_id: Number(supplierId || 0),
      note: safe(q('linkSupplierNote').value),
    };
    if (!payload.supplier_id) {
      throw new Error('Fournisseur invalide');
    }

    await api('client/supplier-link-request/save', 'POST', payload);
    showMsg('linkSupplierMsg', `Demande de rattachement envoyee a ${formatNowTime()}`);
    q('linkSupplierNote').value = '';
    await loadSupplierLinkRequests();
    renderSupplierLinkRequests();
  } catch (e) {
    showMsg('linkSupplierMsg', e.message, true);
  }
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
    setClientTab('my-data');
    await loadBootstrap();
  } catch (e) {
    showMsg('loginMsg', e.message, true);
  }
}

async function onForgotPassword() {
  showMsg('forgotMsg', '');
  try {
    const email = safe(q('forgotEmail').value);
    if (!email) {
      throw new Error('Email requis');
    }
    await api('auth/password-reset/request', 'POST', { email });
    showMsg('forgotMsg', 'Si le compte existe, un email de réinitialisation vient d\'être envoyé.');
  } catch (e) {
    showMsg('forgotMsg', e.message, true);
  }
}

async function onResetPassword() {
  showMsg('resetMsg', '');
  try {
    const token = safe(new URLSearchParams(window.location.search).get('reset_token'));
    if (!token) {
      throw new Error('Token manquant');
    }
    const p1 = q('resetPassword').value || '';
    const p2 = q('resetPasswordConfirm').value || '';
    if (!p1) {
      throw new Error('Nouveau mot de passe requis');
    }
    if (p1 !== p2) {
      throw new Error('Les mots de passe ne correspondent pas');
    }
    await api('auth/password-reset/confirm', 'POST', { token, new_password: p1 });
    showMsg('resetMsg', 'Mot de passe mis à jour. Vous pouvez maintenant vous connecter.');

    const url = new URL(window.location.href);
    url.searchParams.delete('reset_token');
    window.history.replaceState({}, '', url.toString());
    showResetView(false);
    showForgotView(false);
  } catch (e) {
    showMsg('resetMsg', e.message, true);
  }
}

async function onLogout() {
  trackUiEvent('ui_logout_click', {
    event_type: 'action',
    app: 'client',
    page: 'client',
  });
  await api('auth/logout', 'POST');
  state.me = null;
  state.client = null;
  state.suppliers = [];
  state.supplierCreateRequests = [];
  state.selectedSupplierId = null;
  showAuth(false);
}

async function onSaveClientInfo() {
  showMsg('clientInfoMsg', '');
  try {
    syncLongTextTextareaFromEditor();

    let logoUrl = safe(state.client?.logo_url);
    const logoFile = q('clientInfoLogoFile').files?.[0];
    if (logoFile) {
      const form = new FormData();
      form.append('logo', logoFile);
      const up = await apiForm('client/upload/client-logo', form, buildClientScopeQuery());
      logoUrl = safe(up.url);
    }

    let galleryImages = Array.isArray(state.clientGalleryDraft) ? state.clientGalleryDraft.slice() : [];
    const galleryFiles = Array.from(q('clientInfoGalleryFiles').files || []);
    if (galleryFiles.length > 0) {
      const form = new FormData();
      galleryFiles.forEach((file) => {
        form.append('images[]', file);
      });
      const up = await apiForm('client/upload/gallery-images', form, buildClientScopeQuery());
      const uploadedUrls = Array.isArray(up.urls) ? up.urls.map((u) => safe(u)).filter(Boolean) : [];
      galleryImages = galleryImages.concat(uploadedUrls);
    }

    const payload = {
      client_id: resolveContextClientId(),
      name: safe(q('clientInfoName').value),
      client_type: safe(q('clientInfoType').value),
      email: safe(q('clientInfoEmail').value),
      phone: safe(q('clientInfoPhone').value),
      website: safe(q('clientInfoWebsite').value),
      facebook_url: safe(q('clientInfoFacebook').value),
      instagram_url: safe(q('clientInfoInstagram').value),
      logo_url: logoUrl,
      address: safe(q('clientInfoAddress').value),
      city: safe(q('clientInfoCity').value),
      postal_code: safe(q('clientInfoPostal').value),
      country: safe(q('clientInfoCountry').value),
      lundi: safe(q('clientInfoLundi').value),
      mardi: safe(q('clientInfoMardi').value),
      mercredi: safe(q('clientInfoMercredi').value),
      jeudi: safe(q('clientInfoJeudi').value),
      vendredi: safe(q('clientInfoVendredi').value),
      samedi: safe(q('clientInfoSamedi').value),
      dimanche: safe(q('clientInfoDimanche').value),
      latitude: safe(q('clientInfoLat').value),
      longitude: safe(q('clientInfoLng').value),
      description_short: safe(q('clientInfoDescriptionShort').value),
      description_long: safe(q('clientInfoDescriptionLong').value),
      gallery_images: JSON.stringify(galleryImages.map((url) => ({ url }))),
    };

    await api('client/profile/save', 'POST', payload);
    q('clientInfoLogoFile').value = '';
    q('clientInfoGalleryFiles').value = '';
    state.clientGalleryDraft = [];
    q('clientInfoGalleryPreview').innerHTML = '';
    await loadBootstrap();
    showMsg('clientInfoMsg', `Fiche client mise à jour à ${formatNowTime()}`);
  } catch (e) {
    showMsg('clientInfoMsg', e.message, true);
  }
}

async function onClientGeocode() {
  showMsg('clientInfoMsg', '');
  try {
    const address = [
      safe(q('clientInfoAddress').value),
      safe(q('clientInfoPostal').value),
      safe(q('clientInfoCity').value),
      safe(q('clientInfoCountry').value),
    ].filter(Boolean).join(', ');

    if (!address) {
      throw new Error('Adresse client incomplète');
    }

    const geo = await api('client/geocode', 'POST', { address }, buildClientScopeQuery());
    if (!geo.found) {
      throw new Error('Adresse introuvable');
    }

    q('clientInfoLat').value = Number(geo.lat).toFixed(6);
    q('clientInfoLng').value = Number(geo.lng).toFixed(6);
    initOrUpdateClientMap();
    showMsg('clientInfoMsg', 'Position trouvée. Vous pouvez déplacer le repère manuellement sur la carte.');
  } catch (e) {
    showMsg('clientInfoMsg', e.message, true);
  }
}

async function onCreateSupplierRequest() {
  showMsg('supplierCreateMsg', '');
  try {
    const payload = {
      client_id: resolveContextClientId(),
      name: safe(q('newSupplierName').value),
      supplier_type: safe(q('newSupplierType').value),
      activity_text: joinSelectedValues(q('newSupplierActivities')),
      labels_text: joinSelectedValues(q('newSupplierLabels')),
      address: safe(q('newSupplierAddress').value),
      city: safe(q('newSupplierCity').value),
      postal_code: safe(q('newSupplierPostal').value),
      country: safe(q('newSupplierCountry').value),
      phone: safe(q('newSupplierPhone').value),
      email: safe(q('newSupplierEmail').value),
      website: safe(q('newSupplierWebsite').value),
      notes: safe(q('newSupplierNotes').value),
    };

    await api('client/supplier-create-request/save', 'POST', payload);

    q('supplierCreateRequestForm').reset();
    q('newSupplierCountry').value = 'France';
    setMultiSelectValues(q('newSupplierActivities'), []);
    setMultiSelectValues(q('newSupplierLabels'), []);

    await loadBootstrap();
    showMsg('supplierCreateMsg', `Demande de création envoyée à ${formatNowTime()}`);
  } catch (e) {
    showMsg('supplierCreateMsg', e.message, true);
  }
}

async function onSaveProfile() {
  showMsg('profileMsg', '');
  try {
    const supplierId = Number(q('profileSupplierId').value || 0);
    if (!supplierId) throw new Error('Aucun fournisseur selectionne');
    const clientId = resolveContextClientId();

    // Send only the items this client owns (exclude locked chips from other clients)
    const actSel = q('profileActivitySelect');
    const lockedActNorm = parseList(actSel.dataset.lockedValues || '').map(normalizeToken);
    const ownActivities = selectedValues(actSel).filter((v) => !lockedActNorm.includes(normalizeToken(v)));

    const labSel = q('profileLabelsSelect');
    const lockedLabNorm = parseList(labSel.dataset.lockedValues || '').map(normalizeToken);
    const ownLabels = selectedValues(labSel).filter((v) => !lockedLabNorm.includes(normalizeToken(v)));

    const payload = {
      supplier_id: supplierId,
      activity_text: ownActivities.join('; '),
      labels_text: ownLabels.join('; '),
      notes: safe(q('profileNotes').value),
      relationship_status: safe(q('profileRelationshipStatus').value) || 'active',
    };

    if (clientId > 0) {
      payload.client_id = clientId;
    }

    if (state.me?.is_admin) {
      const adminClientId = Number(new URLSearchParams(window.location.search).get('client_id') || 0);
      if (adminClientId > 0) payload.client_id = adminClientId;
    }

    await api('client/supplier/profile/save', 'POST', payload);
    await loadBootstrap();
    showMsg('profileMsg', `Profil enregistre a ${formatNowTime()}`);
  } catch (e) {
    showMsg('profileMsg', e.message, true);
  }
}

async function onSaveChangeRequest() {
  showMsg('changeRequestMsg', '');
  try {
    const supplierId = Number(q('profileSupplierId').value || 0);
    if (!supplierId) throw new Error('Aucun fournisseur selectionne');
    const clientId = resolveContextClientId();

    const payload = {
      supplier_id: supplierId,
      field_name: safe(q('changeRequestField').value),
      new_value: '',
    };

    if (clientId > 0) {
      payload.client_id = clientId;
    }

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
    await loadBootstrap();
    showMsg('changeRequestMsg', `Demande envoyee a ${formatNowTime()}`);
  } catch (e) {
    showMsg('changeRequestMsg', e.message, true);
  }
}

function bindEvents() {
  bindClientTabs();
  q('btnLogin').addEventListener('click', onLogin);
  q('btnShowForgot').addEventListener('click', () => {
    const isHidden = q('forgotView').classList.contains('hidden');
    showForgotView(isHidden);
  });
  q('btnForgotSubmit').addEventListener('click', onForgotPassword);
  q('btnResetSubmit').addEventListener('click', onResetPassword);
  q('btnLogout').addEventListener('click', onLogout);
  q('btnClientGeocode').addEventListener('click', onClientGeocode);
  q('btnSaveClientInfo').addEventListener('click', onSaveClientInfo);
  q('btnClientConsentConfirm')?.addEventListener('click', onConfirmClientConsent);
  q('btnClientConsentRevoke')?.addEventListener('click', onRevokeClientConsent);
  q('btnCreateSupplierRequest').addEventListener('click', onCreateSupplierRequest);
  q('btnSearchLinkSupplier').addEventListener('click', onSearchSupplierLinkCandidates);
  q('btnGoNewSupplier').addEventListener('click', () => {
    setClientTab('new-supplier');
  });
  q('btnSaveProfile').addEventListener('click', onSaveProfile);
  q('btnSendSupplierConsentSingle').addEventListener('click', async () => {
    try {
      await onSendSupplierConsentSingle();
    } catch (e) {
      console.error('onSendSupplierConsentSingle error:', e);
    }
  });
  q('btnSendAllSupplierConsents').addEventListener('click', onSendAllSupplierConsents);
  q('btnSaveChangeRequest').addEventListener('click', onSaveChangeRequest);
  q('searchSupplier').addEventListener('input', renderSupplierList);
  q('changeRequestField').addEventListener('change', renderChangeRequestInput);
  bindMultiPicker('profileActivitySelect');
  bindMultiPicker('profileLabelsSelect');
  bindMultiPicker('changeRequestActivities');
  bindMultiPicker('changeRequestLabels');
  bindMultiPicker('newSupplierActivities');
  bindMultiPicker('newSupplierLabels');

  q('clientInfoLogoFile').addEventListener('change', () => {
    const f = q('clientInfoLogoFile').files?.[0];
    if (!f) return;
    q('clientInfoLogoPath').value = f.name;
    const blobUrl = URL.createObjectURL(f);
    q('clientHeaderLogo').innerHTML = `<img src="${blobUrl}" alt="aperçu logo client" />`;
  });

  q('clientInfoGalleryFiles').addEventListener('change', () => {
    renderSelectedGalleryPreview();
  });

  ['clientInfoLat', 'clientInfoLng'].forEach((id) => {
    q(id).addEventListener('change', () => {
      initOrUpdateClientMap();
    });
  });

  q('clientInfoDescriptionLongEditor').addEventListener('input', () => {
    syncLongTextTextareaFromEditor();
  });

  q('clientInfoDescriptionLongEditor').addEventListener('paste', (event) => {
    event.preventDefault();
    const clipData = event.clipboardData || window.clipboardData;
    const html = clipData && clipData.getData('text/html');
    const text = clipData && clipData.getData('text/plain');
    let cleanHtml;
    if (html && html.trim()) {
      cleanHtml = sanitizePastedHtml(html);
    } else if (text) {
      cleanHtml = text
        .split(/\r?\n\r?\n+/)
        .map((para) => para.trim())
        .filter(Boolean)
        .map((para) => '<p>' + escapeHtml(para).replace(/\r?\n/g, '<br>') + '</p>')
        .join('');
    } else {
      return;
    }
    document.execCommand('insertHTML', false, cleanHtml);
    syncLongTextTextareaFromEditor();
    updateEditorToolbarState();
  });

  q('clientInfoLongEditorToolbar').addEventListener('mousedown', (event) => {
    const btn = event.target.closest('button[data-editor-cmd]');
    if (!btn) return;
    // Prevent focus loss from editor before execCommand runs
    event.preventDefault();
    const cmd = safe(btn.getAttribute('data-editor-cmd'));
    const arg = btn.getAttribute('data-editor-arg') || null;
    const editor = q('clientInfoDescriptionLongEditor');
    editor.focus();
    if (cmd === 'createLink') {
      const url = window.prompt('URL du lien (ex: https://exemple.fr)', 'https://');
      if (url && url.trim() !== '' && url.trim() !== 'https://') {
        document.execCommand('createLink', false, url.trim());
      }
    } else {
      document.execCommand(cmd, false, arg);
    }
    syncLongTextTextareaFromEditor();
    updateEditorToolbarState();
  });

  q('clientInfoDescriptionLongEditor').addEventListener('keyup', updateEditorToolbarState);
  q('clientInfoDescriptionLongEditor').addEventListener('mouseup', updateEditorToolbarState);
  q('clientInfoDescriptionLongEditor').addEventListener('selectionchange', updateEditorToolbarState);

  q('changeRequestStatusFilter').addEventListener('change', async () => {
    await loadChangeRequests();
    renderChangeRequestsForSelectedSupplier();
  });
  q('loginPass').addEventListener('keydown', (ev) => {
    if (ev.key === 'Enter') onLogin();
  });
  q('forgotEmail').addEventListener('keydown', (ev) => {
    if (ev.key === 'Enter') onForgotPassword();
  });
  q('resetPasswordConfirm').addEventListener('keydown', (ev) => {
    if (ev.key === 'Enter') onResetPassword();
  });
  q('linkSupplierSearch').addEventListener('keydown', (ev) => {
    if (ev.key === 'Enter') onSearchSupplierLinkCandidates();
  });
}

async function init() {
  bindEvents();

  const resetToken = safe(new URLSearchParams(window.location.search).get('reset_token'));
  if (resetToken) {
    showAuth(false);
    showResetView(true);
    showForgotView(false);
  }

  try {
    const me = await api('auth/me');
    if (me.is_client_user || me.is_admin) {
      if (me.is_client_user && !Number(me.client_id || 0) && !Number(me.client_user_id || 0)) {
        showAuth(false);
        return;
      }
      state.me = me;
      showAuth(true);
      setClientTab('my-data');
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
  if (!resetToken) {
    showResetView(false);
  }
}

init();
