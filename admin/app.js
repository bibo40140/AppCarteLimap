const apiBase = '../api/index.php?action=';
let state = { clients: [], activities: [], labels: [], suppliers: [], preview: null, previewOriginalRows: [], import: { rowsRaw: [], headers: [], mapping: {}, headerRowIndex: 0 } };

const q = (id) => document.getElementById(id);

async function api(action, method = 'GET', body = null) {
  const opts = { method, credentials: 'include', headers: {} };
  if (body) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }
  const resp = await fetch(apiBase + encodeURIComponent(action), opts);
  const data = await resp.json().catch(() => ({}));
  if (!resp.ok || data.ok === false) throw new Error(data.error || `HTTP ${resp.status}`);
  return data;
}

async function apiForm(action, formData) {
  const resp = await fetch(apiBase + encodeURIComponent(action), {
    method: 'POST',
    credentials: 'include',
    body: formData,
  });
  const data = await resp.json().catch(() => ({}));
  if (!resp.ok || data.ok === false) throw new Error(data.error || `HTTP ${resp.status}`);
  return data;
}

function showApp(logged, user = '') {
  q('loginView').classList.toggle('hidden', logged);
  q('appView').classList.toggle('hidden', !logged);
  q('helloUser').textContent = logged ? `Connecté: ${user}` : '';
}

function selectedMultiValues(selectEl) {
  return Array.from(selectEl.selectedOptions).map(o => Number(o.value));
}

function isEmptyCoord(value) {
  return value === undefined || value === null || String(value).trim() === '';
}

function buildAddress(parts) {
  return parts.map(p => String(p || '').trim()).filter(Boolean).join(', ');
}

async function geocodeAddress(addressText) {
  if (!addressText.trim()) return null;
  const res = await api('admin/geocode', 'POST', { address: addressText });
  if (!res.found) return null;
  return { lat: res.lat, lng: res.lng, display: res.display_name || '' };
}

function renderTable(hostId, columns, rows, editCbName) {
  const host = q(hostId);
  if (!rows.length) {
    host.innerHTML = '<div class="muted">Aucune donnée.</div>';
    return;
  }
  const th = columns.map(c => `<th>${c.label}</th>`).join('') + '<th></th>';
  const tr = rows.map(r => {
    const tds = columns.map(c => `<td>${r[c.key] ?? ''}</td>`).join('');
    return `<tr>${tds}<td><button onclick="${editCbName}(${r.id})">Modifier</button></td></tr>`;
  }).join('');
  host.innerHTML = `<table><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table>`;
}

function renderSuppliers() {
  const host = q('suppliersTable');
  if (!state.suppliers.length) {
    host.innerHTML = '<div class="muted">Aucun fournisseur.</div>';
    return;
  }
  const rows = state.suppliers.map(s => `
    <tr>
      <td>${s.name || ''}</td>
      <td>${s.city || ''}</td>
      <td>${s.phone || ''}</td>
      <td>${s.email || ''}</td>
      <td>${(s.activity_text || '').split(';').map(v => v.trim()).filter(Boolean).map(v => `<span class="pill">${v}</span>`).join(' ')}</td>
      <td>${s.clients || ''}</td>
    </tr>
  `).join('');
  host.innerHTML = `<table><thead><tr><th>Nom</th><th>Ville</th><th>Tél</th><th>Email</th><th>Activités</th><th>Clients</th></tr></thead><tbody>${rows}</tbody></table>`;
}

function hydrateSelects() {
  const options = state.clients.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
  q('supplierClients').innerHTML = options;
  q('importClient').innerHTML = `<option value="">-- choisir --</option>${options}`;
}

function renderAll() {
  renderTable('clientsTable', [
    { key: 'name', label: 'Nom' },
    { key: 'client_type', label: 'Type' },
    { key: 'city', label: 'Ville' },
    { key: 'opening_short', label: 'Horaires' },
    { key: 'email', label: 'Email' },
  ], state.clients.map(c => ({
    ...c,
    opening_short: summarizeOpeningHours(c),
  })), 'editClient');

  renderTable('activitiesTable', [
    { key: 'name', label: 'Activité' },
    { key: 'family', label: 'Famille' },
    { key: 'icon_url', label: 'Icône' },
  ], state.activities, 'editActivity');

  renderTable('labelsTable', [
    { key: 'name', label: 'Label' },
    { key: 'color', label: 'Couleur' },
  ], state.labels, 'editLabel');

  renderSuppliers();
  hydrateSelects();
  renderVisualSettings();
}

function summarizeOpeningHours(c) {
  const days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
  const count = days.filter(d => (c[d] || '').trim() !== '').length;
  return count ? `${count}/7 jours renseignés` : '—';
}

function normalizeOpeningHours(value) {
  return String(value || '')
    .split(/\r?\n/)
    .map(v => v.trim())
    .filter(Boolean)
    .join('\n');
}

function bindTabs() {
  const btns = Array.from(document.querySelectorAll('#adminTabs .tab-btn'));
  const panels = Array.from(document.querySelectorAll('.tab-panel'));
  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.getAttribute('data-tab');
      btns.forEach(b => b.classList.toggle('active', b === btn));
      panels.forEach(p => p.classList.toggle('active', p.id === `panel-${tab}`));
    });
  });
}

function renderVisualSettings() {
  const s = state.settings || {};
  if (q('visOrgName')) q('visOrgName').value = s.org_name || '';
  if (q('visOrgLogo')) q('visOrgLogo').value = s.org_logo_url || '';
  if (q('visDefaultClient')) q('visDefaultClient').value = s.default_client_icon || '/assets/icons/default-client.svg';
  if (q('visDefaultProducer')) q('visDefaultProducer').value = s.default_producer_icon || '/assets/icons/default-producer.svg';
  if (q('visFarmDirect')) q('visFarmDirect').value = s.farm_direct_icon || 'https://i.imgur.com/DTHAKrv.jpeg';
}

const importFieldDefs = [
  { key: 'Nom', label: 'Nom fournisseur', aliases: ['nom', 'name'] },
  { key: 'Activité', label: 'Activité / Catégorie', aliases: ['activite', 'activites', 'activity'] },
  { key: 'Label', label: 'Label', aliases: ['label', 'labels'] },
  { key: 'Type', label: 'Type', aliases: ['type'] },
  { key: 'Téléphone', label: 'Téléphone', aliases: ['telephone', 'tel', 'phone'] },
  { key: 'Email', label: 'Email', aliases: ['email', 'mail'] },
  { key: 'Adresse', label: 'Adresse', aliases: ['adresse', 'address'] },
  { key: 'Ville', label: 'Ville', aliases: ['ville', 'city'] },
  { key: 'Code postal', label: 'Code postal', aliases: ['code postal', 'postal code', 'postal_code'] },
  { key: 'Pays', label: 'Pays', aliases: ['pays', 'country'] },
  { key: 'Latitude', label: 'Latitude', aliases: ['latitude', 'lat'] },
  { key: 'Longitude', label: 'Longitude', aliases: ['longitude', 'lng', 'lon'] },
  { key: 'Client', label: 'Client', aliases: ['client', 'clients'] },
];

function normalizeHeaderName(v) {
  return String(v || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();
}

function autoDetectImportMapping(headers) {
  const normalized = headers.map(h => ({ raw: h, norm: normalizeHeaderName(h) }));
  const mapping = {};
  importFieldDefs.forEach(f => {
    const found = normalized.find(h => f.aliases.includes(h.norm));
    mapping[f.key] = found ? found.raw : '';
  });
  return mapping;
}

function detectHeaderRow(matrix) {
  const aliasSet = new Set(importFieldDefs.flatMap(f => f.aliases));
  const maxScan = Math.min(matrix.length, 30);
  let best = { idx: 0, score: -1 };

  for (let i = 0; i < maxScan; i++) {
    const row = Array.isArray(matrix[i]) ? matrix[i] : [];
    const nonEmpty = row.map(v => String(v || '').trim()).filter(Boolean);
    if (!nonEmpty.length) continue;

    const matched = nonEmpty
      .map(normalizeHeaderName)
      .filter(h => aliasSet.has(h)).length;
    const score = matched * 10 + Math.min(nonEmpty.length, 20);

    if (matched > 0 && score > best.score) {
      best = { idx: i, score };
    }
  }

  return best.score >= 0 ? best.idx : 0;
}

function buildRowsFromMatrix(matrix, headerRowIndex) {
  const headerRaw = Array.isArray(matrix[headerRowIndex]) ? matrix[headerRowIndex] : [];
  const headers = headerRaw.map((h, i) => {
    const t = String(h || '').trim();
    return t || `Col_${i + 1}`;
  });

  const rows = [];
  for (let r = headerRowIndex + 1; r < matrix.length; r++) {
    const rowArr = Array.isArray(matrix[r]) ? matrix[r] : [];
    const obj = {};
    let hasValue = false;
    headers.forEach((h, i) => {
      const v = rowArr[i] ?? '';
      obj[h] = v;
      if (String(v).trim() !== '') hasValue = true;
    });
    if (hasValue) rows.push(obj);
  }

  return { headers, rows };
}

function parseRulesMap(text) {
  const out = {};
  String(text || '').split(/\r?\n/).forEach(line => {
    const t = line.trim();
    if (!t || !t.includes('=')) return;
    const i = t.indexOf('=');
    const src = t.slice(0, i).trim();
    const dst = t.slice(i + 1).trim();
    if (!src || !dst) return;
    out[normalizeHeaderName(src)] = dst;
  });
  return out;
}

function transformDelimitedValues(value, rulesMap) {
  const raw = String(value || '').trim();
  if (!raw) return '';
  const parts = raw.split(/,|;|\||\s\/\s/).map(v => v.trim()).filter(Boolean);
  if (!parts.length) return raw;
  const mapped = parts.map(v => rulesMap[normalizeHeaderName(v)] || v);
  return Array.from(new Set(mapped)).join('; ');
}

function getMappedImportRows() {
  const rowsRaw = state.import.rowsRaw || [];
  const mapping = state.import.mapping || {};
  const activityRules = parseRulesMap(q('importActivityRules')?.value || '');
  const labelRules = parseRulesMap(q('importLabelRules')?.value || '');

  return rowsRaw.map(row => {
    const out = {};
    importFieldDefs.forEach(f => {
      const source = mapping[f.key];
      if (!source) return;
      out[f.key] = row[source] ?? '';
    });

    if (Object.prototype.hasOwnProperty.call(out, 'Activité')) {
      out['Activité'] = transformDelimitedValues(out['Activité'], activityRules);
    }
    if (Object.prototype.hasOwnProperty.call(out, 'Label')) {
      out['Label'] = transformDelimitedValues(out['Label'], labelRules);
    }

    return out;
  });
}

function renderImportMappingUi() {
  const mapper = q('importMapper');
  const headers = state.import.headers || [];
  const mapping = state.import.mapping || {};
  if (!headers.length) {
    mapper.style.display = 'none';
    return;
  }

  mapper.style.display = 'block';
  q('importHeadersInfo').textContent = `${headers.length} colonnes détectées: ${headers.join(', ')}`;

  const options = ['<option value="">-- non mappé --</option>']
    .concat(headers.map(h => `<option value="${String(h).replace(/"/g, '&quot;')}">${h}</option>`))
    .join('');

  q('importMappingGrid').innerHTML = importFieldDefs.map(f => `
    <label>${f.label}
      <select id="map_${f.key.replace(/[^A-Za-z0-9]/g, '_')}">${options}</select>
    </label>
  `).join('');

  importFieldDefs.forEach(f => {
    const id = `map_${f.key.replace(/[^A-Za-z0-9]/g, '_')}`;
    const el = q(id);
    if (!el) return;
    el.value = mapping[f.key] || '';
    el.addEventListener('change', () => {
      state.import.mapping[f.key] = el.value;
      renderImportTransformedPreview();
    });
  });
}

function renderImportTransformedPreview() {
  const rows = getMappedImportRows().slice(0, 10);
  const host = q('importTransformedPreview');
  if (!rows.length) {
    host.innerHTML = '<div class="muted">Aucune ligne à afficher.</div>';
    return;
  }
  const cols = ['Nom', 'Activité', 'Label', 'Type', 'Ville', 'Client'];
  const th = cols.map(c => `<th>${c}</th>`).join('');
  const tr = rows.map(r => `<tr>${cols.map(c => `<td>${r[c] || ''}</td>`).join('')}</tr>`).join('');
  host.innerHTML = `<table class="preview-table"><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table>`;
}

function escapeHtml(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function toDelimitedString(value) {
  if (Array.isArray(value)) return value.join('; ');
  return String(value || '');
}

function renderEditableImportRows(rows) {
  const host = q('importConflicts');
  if (!rows || !rows.length) {
    host.innerHTML = '<div class="muted">Aucune ligne en prévisualisation.</div>';
    return;
  }

  const content = rows.map(row => {
    const p = row.payload || {};
    const isExisting = Number(row.existing_supplier_id || 0) > 0;
    const activitiesText = toDelimitedString(p.activities || p.activity_text || '');
    const labelsText = toDelimitedString(p.labels || '');
    const conflicts = row.conflicts || [];

    const conflictBlocks = conflicts.length ? conflicts.map(c => `
      <div class="conflict">
        <div><strong>${escapeHtml(c.field)}</strong></div>
        <div class="muted">Existant: ${escapeHtml(c.existing)}</div>
        <div class="muted">Import: ${escapeHtml(c.incoming)}</div>
        <label>Résolution
          <select data-row="${row.row_index}" data-field="${c.field}">
            <option value="keep_existing">Garder existant</option>
            <option value="replace_existing">Remplacer par import</option>
          </select>
        </label>
      </div>
    `).join('') : '<div class="muted">Aucun conflit détecté sur cette ligne.</div>';

    return `
      <div class="card import-row-card" data-row="${row.row_index}">
        <div class="row" style="justify-content:space-between;">
          <strong>Ligne ${row.row_index + 1} - ${escapeHtml(row.name || p.name || '')}</strong>
          <span class="pill">${isExisting ? 'Existant' : 'Nouveau'}</span>
        </div>
        <div class="grid" style="margin-top:8px;">
          <label>Nom <input data-edit-row="${row.row_index}" data-edit-key="name" value="${escapeHtml(p.name || '')}" /></label>
          <label>Type <input data-edit-row="${row.row_index}" data-edit-key="supplier_type" value="${escapeHtml(p.supplier_type || '')}" /></label>
          <label>Ville <input data-edit-row="${row.row_index}" data-edit-key="city" value="${escapeHtml(p.city || '')}" /></label>
          <label>Adresse <input data-edit-row="${row.row_index}" data-edit-key="address" value="${escapeHtml(p.address || '')}" /></label>
          <label>Code postal <input data-edit-row="${row.row_index}" data-edit-key="postal_code" value="${escapeHtml(p.postal_code || '')}" /></label>
          <label>Pays <input data-edit-row="${row.row_index}" data-edit-key="country" value="${escapeHtml(p.country || '')}" /></label>
          <label>Téléphone <input data-edit-row="${row.row_index}" data-edit-key="phone" value="${escapeHtml(p.phone || '')}" /></label>
          <label>Email <input data-edit-row="${row.row_index}" data-edit-key="email" value="${escapeHtml(p.email || '')}" /></label>
          <label>Activités (; séparateur) <input data-edit-row="${row.row_index}" data-edit-key="activities" value="${escapeHtml(activitiesText)}" /></label>
          <label>Labels (; séparateur) <input data-edit-row="${row.row_index}" data-edit-key="labels" value="${escapeHtml(labelsText)}" /></label>
          <label>Latitude <input data-edit-row="${row.row_index}" data-edit-key="latitude" value="${escapeHtml(p.latitude ?? '')}" /></label>
          <label>Longitude <input data-edit-row="${row.row_index}" data-edit-key="longitude" value="${escapeHtml(p.longitude ?? '')}" /></label>
        </div>
        <div style="margin-top:8px;">
          ${conflictBlocks}
        </div>
      </div>
    `;
  }).join('');

  host.innerHTML = content;
}

function collectEditedPreviewRows() {
  if (!state.preview || !Array.isArray(state.preview.rows)) return [];
  const out = state.preview.rows.map(r => ({ ...r, payload: { ...(r.payload || {}) } }));

  document.querySelectorAll('#importConflicts [data-edit-row][data-edit-key]').forEach(el => {
    const rowIndex = Number(el.getAttribute('data-edit-row'));
    const key = el.getAttribute('data-edit-key');
    const row = out.find(x => Number(x.row_index) === rowIndex);
    if (!row || !key) return;

    const v = String(el.value || '').trim();
    if (key === 'activities') {
      row.payload.activities = v;
      row.payload.activity_text = v;
      return;
    }
    if (key === 'labels') {
      row.payload.labels = v;
      return;
    }
    row.payload[key] = v;
  });

  return out;
}

function enrichResolutionsForEditedExistingRows(editedRows, resolutions) {
  const base = state.previewOriginalRows || [];
  editedRows.forEach(r => {
    if (!Number(r.existing_supplier_id || 0)) return;
    const orig = base.find(x => Number(x.row_index) === Number(r.row_index));
    if (!orig) return;
    const prev = orig.payload || {};
    const curr = r.payload || {};

    const rowKey = String(r.row_index);
    if (!resolutions[rowKey]) resolutions[rowKey] = {};

    if (String(curr.phone || '').trim() !== String(prev.phone || '').trim()) {
      resolutions[rowKey].phone = 'replace_existing';
    }
    if (String(curr.email || '').trim() !== String(prev.email || '').trim()) {
      resolutions[rowKey].email = 'replace_existing';
    }
    const prevAct = toDelimitedString(prev.activities || prev.activity_text || '').trim();
    const currAct = toDelimitedString(curr.activities || curr.activity_text || '').trim();
    if (currAct !== prevAct) {
      resolutions[rowKey].activity_text = 'replace_existing';
    }
    if (String(curr.supplier_type || '').trim() !== String(prev.supplier_type || '').trim()) {
      resolutions[rowKey].supplier_type = 'replace_existing';
    }
  });
}

async function analyzeImportFile() {
  q('importAnalyzeMsg').textContent = '';
  const file = q('importFile').files?.[0];
  if (!file) {
    q('importAnalyzeMsg').textContent = 'Choisis un fichier Excel';
    return;
  }

  const ab = await file.arrayBuffer();
  const wb = XLSX.read(ab, { type: 'array' });
  const sheet = wb.Sheets['Fournisseurs'] || wb.Sheets[wb.SheetNames[0]];
  const matrix = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
  const detectedHeader = detectHeaderRow(matrix);
  state.import.headerRowIndex = detectedHeader;
  q('importHeaderRow').value = String(detectedHeader + 1);

  const built = buildRowsFromMatrix(matrix, detectedHeader);
  const headers = built.headers;
  const rows = built.rows;

  state.import.rowsRaw = rows;
  state.import.headers = headers;
  state.import.mapping = autoDetectImportMapping(headers);

  renderImportMappingUi();
  renderImportTransformedPreview();
  q('importAnalyzeMsg').textContent = `${rows.length} lignes chargées, entêtes détectées ligne ${detectedHeader + 1}`;
}

async function applyManualHeaderRow() {
  q('importAnalyzeMsg').textContent = '';
  const file = q('importFile').files?.[0];
  if (!file) throw new Error('Choisis un fichier Excel');

  const headerRow1 = Number(q('importHeaderRow').value || 1);
  if (!Number.isFinite(headerRow1) || headerRow1 < 1) {
    throw new Error('Ligne d\'entêtes invalide');
  }

  const ab = await file.arrayBuffer();
  const wb = XLSX.read(ab, { type: 'array' });
  const sheet = wb.Sheets['Fournisseurs'] || wb.Sheets[wb.SheetNames[0]];
  const matrix = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });

  const headerRowIndex = headerRow1 - 1;
  if (headerRowIndex >= matrix.length) {
    throw new Error('La ligne d\'entêtes est hors du fichier');
  }

  const built = buildRowsFromMatrix(matrix, headerRowIndex);
  state.import.headerRowIndex = headerRowIndex;
  state.import.rowsRaw = built.rows;
  state.import.headers = built.headers;
  state.import.mapping = autoDetectImportMapping(built.headers);

  renderImportMappingUi();
  renderImportTransformedPreview();
  q('importAnalyzeMsg').textContent = `${built.rows.length} lignes chargées, entêtes forcées ligne ${headerRow1}`;
}

async function loadBootstrap() {
  const data = await api('admin/bootstrap');
  state.clients = data.clients || [];
  state.activities = data.activities || [];
  state.labels = data.labels || [];
  state.suppliers = data.suppliers || [];
  state.settings = data.settings || {};
  renderAll();
}

window.editClient = function editClient(id) {
  const c = state.clients.find(x => Number(x.id) === Number(id));
  if (!c) return;
  q('clientId').value = c.id;
  q('clientName').value = c.name || '';
  q('clientType').value = c.client_type || '';
  q('clientEmail').value = c.email || '';
  q('clientPhone').value = c.phone || '';
  q('clientWebsite').value = c.website || '';
  q('clientLogo').value = c.logo_url || '';
  q('clientLogoPath').value = c.logo_url || '';
  q('clientAddress').value = c.address || '';
  q('clientCity').value = c.city || '';
  q('clientPostal').value = c.postal_code || '';
  q('clientCountry').value = c.country || '';
  q('clientLat').value = c.latitude || '';
  q('clientLng').value = c.longitude || '';
  q('clientMonday').value = c.lundi || '';
  q('clientTuesday').value = c.mardi || '';
  q('clientWednesday').value = c.mercredi || '';
  q('clientThursday').value = c.jeudi || '';
  q('clientFriday').value = c.vendredi || '';
  q('clientSaturday').value = c.samedi || '';
  q('clientSunday').value = c.dimanche || '';
  q('clientLogoPreview').innerHTML = c.logo_url ? `<img src="${c.logo_url}" alt="logo client" style="max-height:52px;border:1px solid #e5e7eb;border-radius:6px;padding:2px;"/>` : '<span class="muted">Aucun logo</span>';
};

window.editActivity = function editActivity(id) {
  const a = state.activities.find(x => Number(x.id) === Number(id));
  if (!a) return;
  q('activityId').value = a.id;
  q('activityName').value = a.name || '';
  q('activityFamily').value = a.family || '';
  q('activityIcon').value = a.icon_url || '';
  q('activityIconPath').value = a.icon_url || '';
  q('activityIconPreview').innerHTML = a.icon_url ? `<img src="${a.icon_url}" alt="icône activité" style="max-height:44px;border:1px solid #e5e7eb;border-radius:6px;padding:2px;"/>` : '<span class="muted">Aucune icône</span>';
};

window.editLabel = function editLabel(id) {
  const l = state.labels.find(x => Number(x.id) === Number(id));
  if (!l) return;
  q('labelId').value = l.id;
  q('labelName').value = l.name || '';
  q('labelColor').value = l.color || '';
};

function bindEvents() {
  q('btnLogin').addEventListener('click', async () => {
    q('loginMsg').textContent = '';
    try {
      const res = await api('auth/login', 'POST', { username: q('loginUser').value, password: q('loginPass').value });
      showApp(true, res.username || 'admin');
      await loadBootstrap();
    } catch (e) {
      q('loginMsg').textContent = e.message;
    }
  });

  q('btnLogout').addEventListener('click', async () => {
    await api('auth/logout', 'POST');
    showApp(false);
  });

  q('btnSaveClient').addEventListener('click', async () => {
    q('clientMsg').textContent = '';
    try {
      let logoUrl = q('clientLogo').value;
      const logoFile = q('clientLogoFile').files?.[0];
      if (logoFile) {
        const form = new FormData();
        form.append('logo', logoFile);
        const up = await apiForm('admin/upload/client-logo', form);
        logoUrl = up.url || logoUrl;
        q('clientLogo').value = logoUrl;
        q('clientLogoPath').value = logoUrl;
      }

      if (isEmptyCoord(q('clientLat').value) || isEmptyCoord(q('clientLng').value)) {
        const addr = buildAddress([q('clientAddress').value, q('clientPostal').value, q('clientCity').value, q('clientCountry').value]);
        const geo = await geocodeAddress(addr);
        if (geo) {
          q('clientLat').value = geo.lat;
          q('clientLng').value = geo.lng;
        }
      }

      await api('admin/client/save', 'POST', {
        id: q('clientId').value || null,
        name: q('clientName').value,
        client_type: q('clientType').value,
        email: q('clientEmail').value,
        phone: q('clientPhone').value,
        website: q('clientWebsite').value,
        logo_url: logoUrl,
        address: q('clientAddress').value,
        city: q('clientCity').value,
        postal_code: q('clientPostal').value,
        country: q('clientCountry').value,
        latitude: q('clientLat').value,
        longitude: q('clientLng').value,
        lundi: normalizeOpeningHours(q('clientMonday').value),
        mardi: normalizeOpeningHours(q('clientTuesday').value),
        mercredi: normalizeOpeningHours(q('clientWednesday').value),
        jeudi: normalizeOpeningHours(q('clientThursday').value),
        vendredi: normalizeOpeningHours(q('clientFriday').value),
        samedi: normalizeOpeningHours(q('clientSaturday').value),
        dimanche: normalizeOpeningHours(q('clientSunday').value),
        is_active: true,
      });
      q('clientMsg').textContent = 'Client enregistré';
      q('formClient').reset();
      q('clientId').value = '';
      q('clientLogoPath').value = '';
      q('clientLogoPreview').innerHTML = '';
      q('clientMonday').value = '';
      q('clientTuesday').value = '';
      q('clientWednesday').value = '';
      q('clientThursday').value = '';
      q('clientFriday').value = '';
      q('clientSaturday').value = '';
      q('clientSunday').value = '';
      await loadBootstrap();
    } catch (e) {
      q('clientMsg').textContent = e.message;
    }
  });

  q('clientLogoFile').addEventListener('change', () => {
    const f = q('clientLogoFile').files?.[0];
    if (!f) return;
    const url = URL.createObjectURL(f);
    q('clientLogoPath').value = f.name;
    q('clientLogoPreview').innerHTML = `<img src="${url}" alt="aperçu logo client" style="max-height:52px;border:1px solid #e5e7eb;border-radius:6px;padding:2px;"/>`;
  });

  q('btnGeocodeClient').addEventListener('click', async () => {
    q('clientMsg').textContent = '';
    try {
      const addr = buildAddress([q('clientAddress').value, q('clientPostal').value, q('clientCity').value, q('clientCountry').value]);
      if (!addr) throw new Error('Adresse client incomplète');
      const geo = await geocodeAddress(addr);
      if (!geo) throw new Error('Adresse client introuvable');
      q('clientLat').value = geo.lat;
      q('clientLng').value = geo.lng;
      q('clientMsg').textContent = 'Coordonnées client mises à jour';
    } catch (e) {
      q('clientMsg').textContent = e.message;
    }
  });

  q('btnSaveActivity').addEventListener('click', async () => {
    q('activityMsg').textContent = '';
    try {
      let iconUrl = q('activityIcon').value;
      const iconFile = q('activityIconFile').files?.[0];
      if (iconFile) {
        const form = new FormData();
        form.append('icon', iconFile);
        const up = await apiForm('admin/upload/activity-icon', form);
        iconUrl = up.url || iconUrl;
        q('activityIcon').value = iconUrl;
        q('activityIconPath').value = iconUrl;
      }

      await api('admin/activity/save', 'POST', {
        id: q('activityId').value || null,
        name: q('activityName').value,
        family: q('activityFamily').value,
        icon_url: iconUrl,
        is_active: true,
      });
      q('activityMsg').textContent = 'Activité enregistrée';
      q('formActivity').reset();
      q('activityId').value = '';
      q('activityIconPath').value = '';
      q('activityIconPreview').innerHTML = '';
      await loadBootstrap();
    } catch (e) {
      q('activityMsg').textContent = e.message;
    }
  });

  q('activityIconFile').addEventListener('change', () => {
    const f = q('activityIconFile').files?.[0];
    if (!f) return;
    const url = URL.createObjectURL(f);
    q('activityIconPath').value = f.name;
    q('activityIconPreview').innerHTML = `<img src="${url}" alt="aperçu icône activité" style="max-height:44px;border:1px solid #e5e7eb;border-radius:6px;padding:2px;"/>`;
  });

  q('btnSaveLabel').addEventListener('click', async () => {
    q('labelMsg').textContent = '';
    try {
      await api('admin/label/save', 'POST', {
        id: q('labelId').value || null,
        name: q('labelName').value,
        color: q('labelColor').value,
        is_active: true,
      });
      q('labelMsg').textContent = 'Label enregistré';
      q('formLabel').reset();
      q('labelId').value = '';
      await loadBootstrap();
    } catch (e) {
      q('labelMsg').textContent = e.message;
    }
  });

  q('btnSaveSupplier').addEventListener('click', async () => {
    q('supplierMsg').textContent = '';
    try {
      if (isEmptyCoord(q('supplierLat').value) || isEmptyCoord(q('supplierLng').value)) {
        const addr = buildAddress([q('supplierAddress').value, q('supplierPostal').value, q('supplierCity').value, q('supplierCountry').value]);
        const geo = await geocodeAddress(addr);
        if (geo) {
          q('supplierLat').value = geo.lat;
          q('supplierLng').value = geo.lng;
        }
      }

      await api('admin/supplier/save', 'POST', {
        name: q('supplierName').value,
        supplier_type: q('supplierType').value,
        activities: q('supplierActivities').value,
        labels: q('supplierLabels').value,
        phone: q('supplierPhone').value,
        email: q('supplierEmail').value,
        website: q('supplierWebsite').value,
        address: q('supplierAddress').value,
        city: q('supplierCity').value,
        postal_code: q('supplierPostal').value,
        country: q('supplierCountry').value,
        latitude: q('supplierLat').value,
        longitude: q('supplierLng').value,
        client_ids: selectedMultiValues(q('supplierClients')),
      });
      q('supplierMsg').textContent = 'Fournisseur enregistré';
      q('formSupplier').reset();
      await loadBootstrap();
    } catch (e) {
      q('supplierMsg').textContent = e.message;
    }
  });

  q('btnGeocodeSupplier').addEventListener('click', async () => {
    q('supplierMsg').textContent = '';
    try {
      const addr = buildAddress([q('supplierAddress').value, q('supplierPostal').value, q('supplierCity').value, q('supplierCountry').value]);
      if (!addr) throw new Error('Adresse fournisseur incomplète');
      const geo = await geocodeAddress(addr);
      if (!geo) throw new Error('Adresse fournisseur introuvable');
      q('supplierLat').value = geo.lat;
      q('supplierLng').value = geo.lng;
      q('supplierMsg').textContent = 'Coordonnées fournisseur mises à jour';
    } catch (e) {
      q('supplierMsg').textContent = e.message;
    }
  });

  q('btnPreviewImport').addEventListener('click', async () => {
    q('importMsg').textContent = '';
    q('importSummary').textContent = '';
    q('importConflicts').innerHTML = '';
    try {
      const file = q('importFile').files[0];
      const clientId = Number(q('importClient').value || 0);
      if (!file) throw new Error('Choisis un fichier Excel');
      if (!clientId) throw new Error('Choisis le client cible');

      if (!state.import.rowsRaw.length) {
        await analyzeImportFile();
      }

      const rows = getMappedImportRows();
      if (!rows.length) throw new Error('Aucune ligne à importer après mapping');

      const preview = await api('admin/import/preview', 'POST', {
        client_id: clientId,
        file_name: file.name,
        rows,
      });
      state.preview = preview;
      state.previewOriginalRows = JSON.parse(JSON.stringify(preview.rows || []));
      q('importSummary').textContent = `Nouveaux: ${preview.summary.new} | Existants: ${preview.summary.existing} | Lignes en conflit: ${preview.summary.conflicts} | Erreurs: ${preview.summary.errors}`;
      renderEditableImportRows(preview.rows || []);
      q('importMsg').textContent = 'Prévisualisation terminée';
    } catch (e) {
      q('importMsg').textContent = e.message;
    }
  });

  q('btnAnalyzeImport').addEventListener('click', async () => {
    try {
      await analyzeImportFile();
    } catch (e) {
      q('importAnalyzeMsg').textContent = e.message;
    }
  });

  q('btnApplyHeaderRow').addEventListener('click', async () => {
    try {
      await applyManualHeaderRow();
    } catch (e) {
      q('importAnalyzeMsg').textContent = e.message;
    }
  });

  q('importActivityRules').addEventListener('input', renderImportTransformedPreview);
  q('importLabelRules').addEventListener('input', renderImportTransformedPreview);

  q('btnCommitImport').addEventListener('click', async () => {
    q('importMsg').textContent = '';
    try {
      if (!state.preview) throw new Error('Lance d\'abord une prévisualisation');
      const resolutions = {};
      document.querySelectorAll('#importConflicts select[data-row][data-field]').forEach(sel => {
        const row = sel.getAttribute('data-row');
        const field = sel.getAttribute('data-field');
        if (!resolutions[row]) resolutions[row] = {};
        resolutions[row][field] = sel.value;
      });
      const editedRows = collectEditedPreviewRows();
      enrichResolutionsForEditedExistingRows(editedRows, resolutions);
      const clientId = Number(q('importClient').value || 0);

      const commit = await api('admin/import/commit', 'POST', {
        batch_id: state.preview.batch_id,
        client_id: clientId,
        rows: editedRows,
        resolutions,
      });
      q('importMsg').textContent = `Import OK - créés: ${commit.created}, mis à jour: ${commit.updated}`;
      state.preview = null;
      state.previewOriginalRows = [];
      await loadBootstrap();
    } catch (e) {
      q('importMsg').textContent = e.message;
    }
  });

  q('btnSaveVisual').addEventListener('click', async () => {
    q('visualMsg').textContent = '';
    try {
      await api('admin/settings/save', 'POST', {
        org_name: q('visOrgName').value,
        org_logo_url: q('visOrgLogo').value,
        default_client_icon: q('visDefaultClient').value,
        default_producer_icon: q('visDefaultProducer').value,
        farm_direct_icon: q('visFarmDirect').value,
      });
      q('visualMsg').textContent = 'Paramètres visuels enregistrés';
      await loadBootstrap();
    } catch (e) {
      q('visualMsg').textContent = e.message;
    }
  });
}

async function init() {
  bindTabs();
  bindEvents();
  try {
    const me = await api('auth/me');
    if (me.is_admin) {
      showApp(true, me.username || 'admin');
      await loadBootstrap();
    } else {
      showApp(false);
    }
  } catch {
    showApp(false);
  }
}

init();