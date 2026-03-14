const apiBase = '../api/index.php?action=';
let state = {
  clients: [],
  client_users: [],
  admin_users: [],
  password_reset_audit: [],
  audit_logs: [],
  change_requests: [],
  supplier_create_requests: [],
  supplier_link_requests: [],
  activities: [],
  labels: [],
  supplier_types: [],
  suppliers: [],
  preview: null,
  previewOriginalRows: [],
  import: {
    rowsRaw: [],
    headers: [],
    mapping: {},
    headerRowIndex: 0,
    preflight: { decisions: {}, pendingRows: [], active: false },
  },
};
let selectedChangeRequestIds = new Set();

const q = (id) => document.getElementById(id);
const mapEditorState = { map: null, marker: null, rowIndex: null };
let importLoaderTimer = null;
let geocodeProgressAbort = false;
let geocodeLastCallAt = 0;

function waitMs(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

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

function setImportLoading(active, steps = []) {
  const loader = q('importLoader');
  if (!loader) return;

  if (importLoaderTimer) {
    clearInterval(importLoaderTimer);
    importLoaderTimer = null;
  }

  loader.classList.toggle('hidden', !active);
  if (!active) return;

  const sequence = steps.length ? steps : ['Lecture du fichier Excel'];
  let index = 0;
  q('importLoaderTitle').innerHTML = '<strong>Prévisualisation en cours...</strong>';
  q('importLoaderDetail').textContent = sequence[0];
  importLoaderTimer = setInterval(() => {
    index = Math.min(index + 1, sequence.length - 1);
    q('importLoaderDetail').textContent = sequence[index];
  }, 900);
}

async function geocodeAddress(addressText) {
  if (!addressText.trim()) return null;

  // Nominatim public endpoint enforces strict rate limits.
  // Keep at most ~1 request/second from this client.
  const minIntervalMs = 1100;
  const elapsed = Date.now() - geocodeLastCallAt;
  if (elapsed < minIntervalMs) {
    await waitMs(minIntervalMs - elapsed);
  }

  geocodeLastCallAt = Date.now();
  const res = await api('admin/geocode', 'POST', { address: addressText });
  if (!res.found) return null;
  return { lat: res.lat, lng: res.lng, display: res.display_name || '' };
}

async function geocodePreviewRowsProgressively() {
  geocodeProgressAbort = false;
  const progressEl = q('importGeocodeProgress');
  const geocodeCache = new Map();

  const trs = Array.from(document.querySelectorAll('#importConflicts tr[data-row-index]'));
  const toGeocode = trs.filter(tr => {
    const rowIndex = tr.getAttribute('data-row-index');
    const latVal = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="latitude"]`)?.value?.trim();
    const lngVal = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="longitude"]`)?.value?.trim();
    return !latVal || !lngVal;
  });

  if (!toGeocode.length) {
    if (progressEl) progressEl.textContent = '';
    return;
  }

  if (progressEl) {
    progressEl.innerHTML = `<span class="import-geocode-bar"><span class="import-loader-spinner" style="display:inline-block;"></span> Géocodage en cours : 0 / ${toGeocode.length}</span>`;
  }

  let done = 0;
  let foundCount = 0;
  let missingCount = 0;
  for (const tr of toGeocode) {
    if (geocodeProgressAbort) break;

    const rowIndex = tr.getAttribute('data-row-index');
    const sel = key => document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="${key}"]`);
    const latInput = sel('latitude');
    const lngInput = sel('longitude');
    if (!latInput || !lngInput) { done++; continue; }

    const address = sel('address')?.value?.trim() || '';
    const postal  = sel('postal_code')?.value?.trim() || '';
    const city    = sel('city')?.value?.trim() || '';
    const country = sel('country')?.value?.trim() || 'France';

    // cache key on village-level (postal + city) to avoid duplicate Nominatim calls
    const cacheKey = `${postal}|${city}|${country}`;
    let geo = null;
    let isVillage = false;

    if (geocodeCache.has(cacheKey)) {
      const cached = geocodeCache.get(cacheKey);
      geo = cached?.geo || null;
      isVillage = cached?.isVillage || false;
    } else {
      try {
        const fullAddr = buildAddress([address, postal, city, country]);
        if (fullAddr) {
          geo = await geocodeAddress(fullAddr);
        }
        if (!geo && (postal || city)) {
          geo = await geocodeAddress(buildAddress([postal, city, country]));
          if (geo) isVillage = true;
        }
      } catch (_) { /* ignore individual geocode errors */ }
      geocodeCache.set(cacheKey, { geo, isVillage });
    }

    if (geo) {
      latInput.value = String(geo.lat);
      lngInput.value = String(geo.lng);
      foundCount++;
      // Update geocode note in the address cell
      const addrCell = sel('address')?.closest('td');
      const noteEl = addrCell?.querySelector('.muted');
      if (noteEl) {
        noteEl.textContent = isVillage
          ? 'Position approximative sur le village — ajuste le pin si besoin.'
          : '';
      } else if (isVillage && addrCell) {
        const note = document.createElement('div');
        note.className = 'muted';
        note.style.marginTop = '4px';
        note.textContent = 'Position approximative sur le village — ajuste le pin si besoin.';
        addrCell.appendChild(note);
      }
    } else {
      missingCount++;
    }

    done++;
    if (progressEl && !geocodeProgressAbort) {
      progressEl.innerHTML = `<span class="import-geocode-bar"><span class="import-loader-spinner" style="display:inline-block;"></span> Géocodage en cours : ${done} / ${toGeocode.length}</span>`;
    }
    updateImportGridStatuses();
    refreshImportCommitState();

    // Yield to the event loop so the UI stays responsive between geocode requests
    await waitMs(0);
  }

  const geocoded = toGeocode.filter((_, i) => i < done).length;
  if (progressEl) {
    if (geocodeProgressAbort) {
      progressEl.textContent = '';
    } else {
      progressEl.innerHTML = `<span class="muted">Géocodage terminé : ${geocoded} lignes traitées, ${foundCount} coordonnées trouvées, ${missingCount} non trouvées.</span>`;
    }
  }
  updateImportGridStatuses();
  applyImportGridFilters();
  refreshImportCommitState();
}

function closeMapEditor() {
  q('mapEditorModal')?.classList.add('hidden');
  mapEditorState.rowIndex = null;
}

async function openMapEditorForRow(rowIndex) {
  const latInput = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="latitude"]`);
  const lngInput = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="longitude"]`);
  const nameInput = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="name"]`);
  const addressInput = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="address"]`);
  const postalInput = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="postal_code"]`);
  const cityInput = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="city"]`);
  const countryInput = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="country"]`);

  if (!latInput || !lngInput) return;

  const lat = Number(String(latInput.value || '').replace(',', '.'));
  const lng = Number(String(lngInput.value || '').replace(',', '.'));
  let center = Number.isFinite(lat) && Number.isFinite(lng)
    ? { lat, lng }
    : null;

  if (!center) {
    const query = buildAddress([
      addressInput?.value || '',
      postalInput?.value || '',
      cityInput?.value || '',
      countryInput?.value || 'France',
    ]);
    if (query) {
      const geo = await geocodeAddress(query);
      if (geo) center = { lat: Number(geo.lat), lng: Number(geo.lng) };
    }
  }

  if (!center) {
    center = { lat: 46.603354, lng: 1.888334 };
  }

  mapEditorState.rowIndex = rowIndex;
  q('mapEditorTitle').textContent = `Ajuster la position - ${String(nameInput?.value || `Ligne ${rowIndex + 1}`)}`;
  q('mapEditorModal').classList.remove('hidden');

  if (!mapEditorState.map) {
    mapEditorState.map = L.map('mapEditorCanvas');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapEditorState.map);
  }

  mapEditorState.map.invalidateSize();
  mapEditorState.map.setView([center.lat, center.lng], Number.isFinite(lat) && Number.isFinite(lng) ? 15 : 11);

  if (mapEditorState.marker) {
    mapEditorState.marker.remove();
  }
  mapEditorState.marker = L.marker([center.lat, center.lng], { draggable: true }).addTo(mapEditorState.map);
}

function renderTable(hostId, columns, rows, editCbName) {
  const host = q(hostId);
  if (!rows.length) {
    host.innerHTML = '<div class="muted">Aucune donnée.</div>';
    return;
  }
  const deleteCbName = arguments[4] || '';
  const th = columns.map(c => `<th>${c.label}</th>`).join('') + '<th>Actions</th>';
  const tr = rows.map(r => {
    const tds = columns.map(c => `<td>${r[c.key] ?? ''}</td>`).join('');
    const deleteButton = deleteCbName ? `<button type="button" class="danger" onclick="${deleteCbName}(${r.id})">Supprimer</button>` : '';
    return `<tr>${tds}<td><div class="row"><button type="button" onclick="${editCbName}(${r.id})">Modifier</button>${deleteButton}</div></td></tr>`;
  }).join('');
  host.innerHTML = `<table><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table>`;
}

function renderSuppliers() {
  const host = q('suppliersTable');
  const textFilter = String(q('supplierFilterText')?.value || '').trim().toLocaleLowerCase('fr');
  const clientIdFilter = Number(q('supplierFilterClient')?.value || 0);
  const activityFilter = String(q('supplierFilterActivity')?.value || '').trim().toLocaleLowerCase('fr');
  const typeFilter = String(q('supplierFilterType')?.value || '').trim().toLocaleLowerCase('fr');

  const filteredSuppliers = (state.suppliers || []).filter((supplier) => {
    const supplierType = String(supplier.supplier_type || '').trim().toLocaleLowerCase('fr');
    const supplierActivities = splitDelimitedUnique(String(supplier.activity_text || ''))
      .map((v) => v.toLocaleLowerCase('fr'));
    const linkedClientIds = String(supplier.client_ids || '')
      .split(',')
      .map((value) => Number(value.trim()))
      .filter(Number.isFinite);

    if (clientIdFilter > 0 && !linkedClientIds.includes(clientIdFilter)) {
      return false;
    }

    if (activityFilter && !supplierActivities.includes(activityFilter)) {
      return false;
    }

    if (typeFilter && supplierType !== typeFilter) {
      return false;
    }

    if (textFilter) {
      const haystack = [
        supplier.name,
        supplier.city,
        supplier.email,
        supplier.phone,
        supplier.supplier_type,
        supplier.activity_text,
        supplier.clients,
      ]
        .map((value) => String(value || '').toLocaleLowerCase('fr'))
        .join(' ');
      if (!haystack.includes(textFilter)) {
        return false;
      }
    }

    return true;
  });

  const summary = q('supplierFilterSummary');
  if (summary) {
    summary.textContent = `${filteredSuppliers.length} fournisseur(s) affiché(s) / ${state.suppliers.length}`;
  }

  if (!filteredSuppliers.length) {
    host.innerHTML = '<div class="muted">Aucun fournisseur.</div>';
    return;
  }
  const rows = filteredSuppliers.map(s => `
    <tr>
      <td>${escapeHtml(s.name || '')}</td>
      <td>${escapeHtml(s.supplier_type || '')}</td>
      <td>${escapeHtml(s.city || '')}</td>
      <td>${escapeHtml(s.phone || '')}</td>
      <td>${escapeHtml(s.email || '')}</td>
      <td>${Number(s.is_public) === 1 ? 'Oui' : 'Non'}</td>
      <td>${(s.activity_text || '').split(';').map(v => v.trim()).filter(Boolean).map(v => `<span class="pill">${escapeHtml(v)}</span>`).join(' ')}</td>
      <td>${escapeHtml(s.clients || '')}</td>
      <td><div class="row"><button type="button" onclick="editSupplier(${s.id})">Modifier</button><button type="button" class="danger" onclick="deleteSupplier(${s.id})">Supprimer</button></div></td>
    </tr>
  `).join('');
  host.innerHTML = `<table><thead><tr><th>Nom</th><th>Type</th><th>Ville</th><th>Tél</th><th>Email</th><th>Public</th><th>Activités</th><th>Clients</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table>`;
}

function renderClientUsers() {
  const host = q('clientUsersTable');
  if (!state.client_users.length) {
    host.innerHTML = '<div class="muted">Aucun utilisateur client.</div>';
    return;
  }

  const rows = state.client_users.map((u) => {
    const activeText = Number(u.is_active) === 1 ? 'Actif' : 'Inactif';
    const lastLogin = u.last_login_at ? escapeHtml(String(u.last_login_at)) : '—';
    const hasEmail = String(u.email || '').trim() !== '';
    return `
      <tr>
        <td>${escapeHtml(u.client_name || '')}</td>
        <td>${escapeHtml(u.username || '')}</td>
        <td>${escapeHtml(u.email || '')}</td>
        <td>${escapeHtml(u.role || '')}</td>
        <td>${activeText}</td>
        <td>${lastLogin}</td>
        <td>
          <div class="row">
            <button type="button" onclick="editClientUser(${u.id})">Modifier</button>
            <button type="button" onclick="toggleClientUserActive(${u.id}, ${Number(u.is_active) === 1 ? 0 : 1})">${Number(u.is_active) === 1 ? 'Désactiver' : 'Activer'}</button>
            <button type="button" onclick="sendClientUserResetLink(${u.id})" ${hasEmail ? '' : 'disabled title="Email utilisateur manquant"'}>${hasEmail ? 'Envoyer lien reset' : 'Email manquant'}</button>
            <button type="button" onclick="resetClientUserPassword(${u.id})">Reset mot de passe</button>
            <button type="button" class="danger" onclick="deleteClientUser(${u.id})">Supprimer</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  host.innerHTML = `<table><thead><tr><th>Client</th><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Dernière connexion</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table>`;
}

function renderAdminUsers() {
  const host = q('adminUsersTable');
  if (!host) return;

  if (!state.admin_users.length) {
    host.innerHTML = '<div class="muted">Aucun utilisateur admin.</div>';
    return;
  }

  const rows = state.admin_users.map((u) => {
    const activeText = Number(u.is_active) === 1 ? 'Actif' : 'Inactif';
    const lastLogin = u.last_login_at ? escapeHtml(String(u.last_login_at)) : '—';
    const hasEmail = String(u.email || '').trim() !== '';
    return `
      <tr>
        <td>${escapeHtml(u.username || '')}</td>
        <td>${escapeHtml(u.email || '')}</td>
        <td>${activeText}</td>
        <td>${lastLogin}</td>
        <td>
          <div class="row">
            <button type="button" onclick="editAdminUser(${u.id})">Modifier</button>
            <button type="button" onclick="toggleAdminUserActive(${u.id}, ${Number(u.is_active) === 1 ? 0 : 1})">${Number(u.is_active) === 1 ? 'Désactiver' : 'Activer'}</button>
            <button type="button" onclick="sendAdminUserResetLink(${u.id})" ${hasEmail ? '' : 'disabled title="Email admin manquant"'}>${hasEmail ? 'Envoyer lien reset' : 'Email manquant'}</button>
            <button type="button" onclick="resetAdminUserPassword(${u.id})">Reset mot de passe</button>
            <button type="button" class="danger" onclick="deleteAdminUser(${u.id})">Supprimer</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  host.innerHTML = `<table><thead><tr><th>Utilisateur admin</th><th>Email</th><th>Statut</th><th>Dernière connexion</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table>`;
}

function renderAuditLogs() {
  const host = q('auditLogsTable');
  if (!host) return;

  const rowsData = state.audit_logs || [];
  const textFilter = String(q('auditFilterText')?.value || '').trim().toLocaleLowerCase('fr');
  const actorTypeFilter = String(q('auditFilterActorType')?.value || '').trim();
  const kindFilter = String(q('auditFilterKind')?.value || '').trim();

  const classifyLogKind = (row) => {
    const action = String(row.action_name || '');
    const targetType = String(row.target_type || '');
    if (action.startsWith('auth_')) return 'auth';
    if (targetType === 'visit' || action.startsWith('ui_visit')) return 'visit';
    return 'action';
  };

  const filtered = rowsData.filter((row) => {
    if (actorTypeFilter && String(row.actor_type || '') !== actorTypeFilter) {
      return false;
    }

    const kind = classifyLogKind(row);
    if (kindFilter && kind !== kindFilter) {
      return false;
    }

    if (textFilter) {
      const haystack = [
        row.created_at,
        row.actor_type,
        row.actor_name,
        row.action_name,
        kind,
        row.target_type,
        row.target_label,
        row.ip_address,
        row.user_agent,
      ].map((value) => String(value || '').toLocaleLowerCase('fr')).join(' ');
      if (!haystack.includes(textFilter)) {
        return false;
      }
    }

    return true;
  });

  const summary = q('auditSummary');
  if (summary) {
    summary.textContent = `${filtered.length} trace(s) affichée(s) / ${rowsData.length}`;
  }

  if (!filtered.length) {
    host.innerHTML = '<div class="muted">Aucune trace pour ce filtre.</div>';
    return;
  }

  const rows = filtered.map((r) => {
    const details = r.details_json ? escapeHtml(String(r.details_json)) : '—';
    const kind = classifyLogKind(r);
    return `
      <tr>
        <td>${escapeHtml(String(r.created_at || ''))}</td>
        <td>${escapeHtml(String(kind || ''))}</td>
        <td>${escapeHtml(String(r.actor_type || ''))}</td>
        <td>${escapeHtml(String(r.actor_name || ''))}</td>
        <td>${escapeHtml(String(r.action_name || ''))}</td>
        <td>${escapeHtml(String(r.target_type || ''))}</td>
        <td>${escapeHtml(String(r.target_label || (r.target_id ?? '')))}</td>
        <td>${details}</td>
        <td>${escapeHtml(String(r.ip_address || ''))}</td>
      </tr>
    `;
  }).join('');

  host.innerHTML = `<table><thead><tr><th>Date</th><th>Nature</th><th>Type acteur</th><th>Acteur</th><th>Action</th><th>Type cible</th><th>Cible</th><th>Détails</th><th>IP</th></tr></thead><tbody>${rows}</tbody></table>`;
}

function renderResetAudit() {
  const host = q('resetAuditTable');
  if (!host) return;

  const rowsData = state.password_reset_audit || [];
  if (!rowsData.length) {
    host.innerHTML = '<div class="muted">Aucun envoi enregistré.</div>';
    return;
  }

  const rows = rowsData.map((r) => {
    const status = String(r.status || '').toLowerCase() === 'sent' ? 'Envoye' : 'Echec';
    return `
      <tr>
        <td>${escapeHtml(r.created_at || '')}</td>
        <td>${escapeHtml(r.user_type || '')}</td>
        <td>${escapeHtml(r.username || '')}</td>
        <td>${escapeHtml(r.email || '')}</td>
        <td>${escapeHtml(status)}</td>
        <td>${escapeHtml(r.error_message || '')}</td>
      </tr>
    `;
  }).join('');

  host.innerHTML = `<table><thead><tr><th>Date</th><th>Type</th><th>Utilisateur</th><th>Email</th><th>Statut</th><th>Détail</th></tr></thead><tbody>${rows}</tbody></table>`;
}

function renderChangeRequests() {
  const host = q('requestsTable');
  const rowsData = state.change_requests || [];
  if (!rowsData.length) {
    host.innerHTML = '<div class="muted">Aucune demande pour ce filtre.</div>';
    if (q('requestSummary')) q('requestSummary').textContent = '0 demande';
    selectedChangeRequestIds = new Set();
    updateRequestSelectionSummary();
    return;
  }

  const pendingCount = rowsData.filter(r => String(r.status || '') === 'pending').length;
  if (q('requestSummary')) {
    q('requestSummary').textContent = `${rowsData.length} demande(s), dont ${pendingCount} en attente`;
  }

  const rows = rowsData.map((r) => {
    const status = String(r.status || 'pending');
    const rowBadgeClass = status === 'approved' ? 'status-ok' : (status === 'rejected' ? 'status-conflict' : 'status-warn');
    const reviewNote = escapeHtml(r.review_note || '');
    const isSelected = selectedChangeRequestIds.has(Number(r.id));
    return `
      <tr>
        <td><input type="checkbox" ${isSelected ? 'checked' : ''} onchange="toggleChangeRequestSelection(${Number(r.id)}, this.checked)" /></td>
        <td>${escapeHtml(r.client_name || '')}</td>
        <td>${escapeHtml(r.supplier_name || '')}</td>
        <td>${escapeHtml(r.field_name || '')}</td>
        <td>${escapeHtml(r.old_value || '')}</td>
        <td>${escapeHtml(r.new_value || '')}</td>
        <td><span class="status-badge ${rowBadgeClass}">${escapeHtml(status)}</span></td>
        <td>${escapeHtml(r.requested_by_username || '')}</td>
        <td>${escapeHtml(r.created_at || '')}</td>
        <td>${reviewNote || '—'}</td>
        <td>
          ${status === 'pending'
            ? `<div class="row"><button type="button" onclick="reviewChangeRequest(${r.id}, 'approved')">Approuver</button><button type="button" class="danger" onclick="reviewChangeRequest(${r.id}, 'rejected')">Refuser</button></div>`
            : '<span class="muted">Traitée</span>'}
        </td>
      </tr>
    `;
  }).join('');

  host.innerHTML = `<table><thead><tr><th><input id="requestSelectAll" type="checkbox" /></th><th>Client</th><th>Fournisseur</th><th>Champ</th><th>Ancienne valeur</th><th>Nouvelle valeur</th><th>Statut</th><th>Demandé par</th><th>Date</th><th>Note admin</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table>`;

  const selectAll = q('requestSelectAll');
  if (selectAll) {
    const allIds = rowsData.map(r => Number(r.id));
    const allSelected = allIds.length > 0 && allIds.every(id => selectedChangeRequestIds.has(id));
    selectAll.checked = allSelected;
    selectAll.addEventListener('change', () => {
      if (selectAll.checked) {
        allIds.forEach(id => selectedChangeRequestIds.add(id));
      } else {
        allIds.forEach(id => selectedChangeRequestIds.delete(id));
      }
      renderChangeRequests();
    });
  }

  updateRequestSelectionSummary();
}

function renderSupplierCreateRequests() {
  const host = q('supplierCreateRequestsTable');
  const rowsData = state.supplier_create_requests || [];
  const summaryEl = q('supplierCreateRequestSummary');

  if (summaryEl) {
    const pendingCount = rowsData.filter(r => String(r.status || '') === 'pending').length;
    summaryEl.textContent = `${rowsData.length} demande(s), dont ${pendingCount} en attente`;
  }

  if (!rowsData.length) {
    host.innerHTML = '<div class="muted">Aucune demande de création pour ce filtre.</div>';
    return;
  }

  const rows = rowsData.map((r) => {
    const status = String(r.status || 'pending');
    const rowBadgeClass = status === 'approved' ? 'status-ok' : (status === 'rejected' ? 'status-conflict' : 'status-warn');
    const approvedName = escapeHtml(r.approved_supplier_name || '');
    const reviewNote = escapeHtml(r.review_note || '');
    const details = [
      r.supplier_type ? `Type: ${escapeHtml(r.supplier_type)}` : '',
      r.city ? `Ville: ${escapeHtml(r.city)}` : '',
      r.email ? `Email: ${escapeHtml(r.email)}` : '',
      r.phone ? `Tél: ${escapeHtml(r.phone)}` : '',
      r.activity_text ? `Activités: ${escapeHtml(r.activity_text)}` : '',
      r.labels_text ? `Labels: ${escapeHtml(r.labels_text)}` : '',
    ].filter(Boolean).join(' | ');

    return `
      <tr>
        <td>${escapeHtml(r.client_name || '')}</td>
        <td>${escapeHtml(r.name || '')}</td>
        <td>${details || '—'}</td>
        <td><span class="status-badge ${rowBadgeClass}">${escapeHtml(status)}</span></td>
        <td>${escapeHtml(r.requested_by_username || '')}</td>
        <td>${escapeHtml(r.created_at || '')}</td>
        <td>${approvedName || '—'}</td>
        <td>${reviewNote || '—'}</td>
        <td>
          ${status === 'pending'
            ? `<div class="row"><button type="button" onclick="reviewSupplierCreateRequest(${Number(r.id)}, 'approved')">Approuver</button><button type="button" class="danger" onclick="reviewSupplierCreateRequest(${Number(r.id)}, 'rejected')">Refuser</button></div>`
            : '<span class="muted">Traitée</span>'}
        </td>
      </tr>
    `;
  }).join('');

  host.innerHTML = `<table><thead><tr><th>Client</th><th>Nom fournisseur</th><th>Détails</th><th>Statut</th><th>Demandé par</th><th>Date</th><th>Fournisseur créé</th><th>Note admin</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table>`;
}

function renderSupplierLinkRequests() {
  const host = q('supplierLinkRequestsTable');
  if (!host) return;

  const rowsData = state.supplier_link_requests || [];
  q('supplierLinkRequestSummary').textContent = `${rowsData.length} demande(s) de rattachement`;

  if (!rowsData.length) {
    host.innerHTML = '<div class="muted">Aucune demande de rattachement.</div>';
    return;
  }

  const rows = rowsData.map((r) => {
    const status = String(r.status || 'pending');
    const rowBadgeClass = status === 'approved' ? 'status-approved' : status === 'rejected' ? 'status-rejected' : 'status-pending';
    const supplierName = String(r.supplier_name || '').trim();
    const supplierCity = String(r.supplier_city || '').trim();
    const details = [supplierName, supplierCity].filter(Boolean).join(' | ');
    const note = String(r.note || '').trim();
    const reviewNote = String(r.review_note || '').trim();

    return `
      <tr>
        <td>${escapeHtml(r.client_name || '')}</td>
        <td>${escapeHtml(details || '—')}</td>
        <td>${note ? escapeHtml(note) : '—'}</td>
        <td><span class="status-badge ${rowBadgeClass}">${escapeHtml(status)}</span></td>
        <td>${escapeHtml(r.requested_by_username || '')}</td>
        <td>${escapeHtml(r.created_at || '')}</td>
        <td>${reviewNote || '—'}</td>
        <td>
          ${status === 'pending'
            ? `<div class="row"><button type="button" onclick="reviewSupplierLinkRequest(${Number(r.id)}, 'approved')">Approuver</button><button type="button" class="danger" onclick="reviewSupplierLinkRequest(${Number(r.id)}, 'rejected')">Refuser</button></div>`
            : '<span class="muted">Traitée</span>'}
        </td>
      </tr>
    `;
  }).join('');

  host.innerHTML = `<table><thead><tr><th>Client</th><th>Fournisseur</th><th>Note client</th><th>Statut</th><th>Demandé par</th><th>Date</th><th>Note admin</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table>`;
}

function updateRequestSelectionSummary() {
  const selectedCount = selectedChangeRequestIds.size;
  const selectedRows = (state.change_requests || []).filter(r => selectedChangeRequestIds.has(Number(r.id)));
  const selectedPending = selectedRows.filter(r => String(r.status || '') === 'pending').length;
  if (q('requestSelectionSummary')) {
    q('requestSelectionSummary').textContent = `${selectedCount} sélectionnée(s), ${selectedPending} en attente`;
  }
}

async function loadChangeRequests() {
  const status = q('requestStatusFilter')?.value || 'all';
  const clientId = Number(q('requestClientFilter')?.value || 0);
  const sortBy = q('requestSortBy')?.value || 'created_at_desc';

  const statusParam = status === 'all' ? '' : ('&status=' + encodeURIComponent(status));
  const clientParam = clientId > 0 ? ('&client_id=' + encodeURIComponent(String(clientId))) : '';
  const data = await fetch(apiBase + encodeURIComponent('admin/change-request/list') + statusParam + clientParam, {
    method: 'GET',
    credentials: 'include',
  }).then(async (resp) => {
    const payload = await resp.json().catch(() => ({}));
    if (!resp.ok || payload.ok === false) throw new Error(payload.error || `HTTP ${resp.status}`);
    return payload;
  });

  const rows = data.requests || [];

  const validIds = new Set(rows.map(r => Number(r.id)));
  selectedChangeRequestIds = new Set(Array.from(selectedChangeRequestIds).filter(id => validIds.has(id)));

  rows.sort((a, b) => {
    if (sortBy === 'created_at_asc') {
      return String(a.created_at || '').localeCompare(String(b.created_at || ''));
    }
    if (sortBy === 'client_asc') {
      return String(a.client_name || '').localeCompare(String(b.client_name || ''), 'fr', { sensitivity: 'base' });
    }
    if (sortBy === 'supplier_asc') {
      return String(a.supplier_name || '').localeCompare(String(b.supplier_name || ''), 'fr', { sensitivity: 'base' });
    }
    if (sortBy === 'status_asc') {
      return String(a.status || '').localeCompare(String(b.status || ''));
    }
    return String(b.created_at || '').localeCompare(String(a.created_at || ''));
  });

  state.change_requests = rows;
  renderChangeRequests();

  const createStatusParam = status === 'all' ? '' : ('&status=' + encodeURIComponent(status));
  const createClientParam = clientId > 0 ? ('&client_id=' + encodeURIComponent(String(clientId))) : '';
  const createData = await fetch(apiBase + encodeURIComponent('admin/supplier-create-request/list') + createStatusParam + createClientParam, {
    method: 'GET',
    credentials: 'include',
  }).then(async (resp) => {
    const payload = await resp.json().catch(() => ({}));
    if (!resp.ok || payload.ok === false) throw new Error(payload.error || `HTTP ${resp.status}`);
    return payload;
  });

  state.supplier_create_requests = createData.requests || [];
  renderSupplierCreateRequests();

  try {
    const linkData = await fetch(apiBase + encodeURIComponent('admin/supplier-link-request/list') + createStatusParam + createClientParam, {
      method: 'GET',
      credentials: 'include',
    }).then(async (resp) => {
      const payload = await resp.json().catch(() => ({}));
      if (!resp.ok || payload.ok === false) throw new Error(payload.error || `HTTP ${resp.status}`);
      return payload;
    });
    state.supplier_link_requests = linkData.requests || [];
  } catch (linkErr) {
    console.error('Erreur chargement demandes de rattachement:', linkErr);
    state.supplier_link_requests = [];
  }
  renderSupplierLinkRequests();
}

function csvEscape(value) {
  const v = String(value ?? '');
  if (/[";,\n\r]/.test(v)) {
    return '"' + v.replace(/"/g, '""') + '"';
  }
  return v;
}

function downloadRequestsCsv() {
  const rows = state.change_requests || [];
  if (!rows.length) {
    q('requestMsg').textContent = 'Aucune demande à exporter pour ce filtre';
    return;
  }

  const headers = ['id', 'status', 'client_name', 'supplier_name', 'field_name', 'old_value', 'new_value', 'requested_by_username', 'created_at', 'reviewed_by_admin', 'reviewed_at', 'review_note'];
  const lines = [headers.join(';')];
  rows.forEach((r) => {
    const line = headers.map((h) => csvEscape(r[h] ?? '')).join(';');
    lines.push(line);
  });

  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  const stamp = new Date().toISOString().replace(/[:T]/g, '-').slice(0, 16);
  a.href = url;
  a.download = `demandes-modification-${stamp}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

function renderActivitiesByFamily() {
  const host = q('activitiesTable');
  if (!state.activities.length) {
    host.innerHTML = '<div class="muted">Aucune catégorie.</div>';
    return;
  }

  const families = new Map();
  state.activities.forEach((activity) => {
    const family = String(activity.family || '').trim() || 'Sans famille';
    if (!families.has(family)) families.set(family, []);
    families.get(family).push(activity);
  });

  const cards = Array.from(families.entries())
    .sort((a, b) => a[0].localeCompare(b[0], 'fr', { sensitivity: 'base' }))
    .map(([family, items]) => {
      const children = items
        .sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'fr', { sensitivity: 'base' }))
        .map((activity) => {
          const icon = activity.icon_url
            ? `<img src="${escapeHtml(activity.icon_url)}" alt="icone" class="activity-child-icon" />`
            : '<span class="muted">-</span>';
          return `
            <tr>
              <td><strong>${escapeHtml(activity.name || '')}</strong></td>
              <td>${icon}</td>
              <td>
                <div class="row">
                  <button type="button" onclick="editActivity(${activity.id})">Modifier</button>
                  <button type="button" class="danger" onclick="deleteActivity(${activity.id})">Supprimer</button>
                </div>
              </td>
            </tr>
          `;
        })
        .join('');

      return `
        <section class="activity-family-card">
          <div class="activity-family-head">
            <h4>${escapeHtml(family)}</h4>
            <span class="status-badge">${items.length} categorie(s)</span>
          </div>
          <table class="activity-family-table">
            <thead><tr><th>Categorie enfant</th><th>Icone</th><th>Actions</th></tr></thead>
            <tbody>${children}</tbody>
          </table>
        </section>
      `;
    })
    .join('');

  host.innerHTML = `<div class="activity-family-grid">${cards}</div>`;
}

function hydrateSelects() {
  const options = state.clients.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
  q('supplierClients').innerHTML = options;
  q('importClient').innerHTML = `<option value="">-- choisir --</option>${options}`;
  q('clientUserClientId').innerHTML = `<option value="">-- choisir --</option>${options}`;
  q('requestClientFilter').innerHTML = `<option value="">Tous les clients</option>${options}`;

  const supplierFilterClient = q('supplierFilterClient');
  if (supplierFilterClient) {
    const previousValue = supplierFilterClient.value;
    supplierFilterClient.innerHTML = `<option value="">Tous les clients</option>${options}`;
    supplierFilterClient.value = previousValue;
  }

  const typeOptions = getSupplierTypeOptions()
    .map(v => `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`)
    .join('');
  q('supplierType').innerHTML = `<option value="">Sélectionner un type</option>${typeOptions}`;

  const supplierFilterType = q('supplierFilterType');
  if (supplierFilterType) {
    const previousValue = supplierFilterType.value;
    supplierFilterType.innerHTML = `<option value="">Tous les types</option>${typeOptions}`;
    supplierFilterType.value = previousValue;
  }

  const supplierFilterActivity = q('supplierFilterActivity');
  if (supplierFilterActivity) {
    const previousValue = supplierFilterActivity.value;
    const activityOptions = getActivityOptions()
      .map(v => `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`)
      .join('');
    supplierFilterActivity.innerHTML = `<option value="">Toutes les catégories</option>${activityOptions}`;
    supplierFilterActivity.value = previousValue;
  }

  q('supplierActivitiesSelect').innerHTML = getActivityOptionsGroupedHtml('Ajouter une catégorie / activité...');
  q('supplierLabelsSelect').innerHTML = renderSimpleOptionsHtmlUnselected(getLabelOptions(), 'Ajouter un label...');
}

function renderAll() {
  renderTable('clientsTable', [
    { key: 'name', label: 'Nom' },
    { key: 'client_type', label: 'Type' },
    { key: 'city', label: 'Ville' },
    { key: 'public_label', label: 'Publication' },
    { key: 'opening_short', label: 'Horaires' },
    { key: 'email', label: 'Email' },
  ], state.clients.map(c => ({
    ...c,
    public_label: Number(c.is_public) === 1 ? 'Oui' : 'Non',
    opening_short: summarizeOpeningHours(c),
  })), 'editClient', 'deleteClient');

  renderActivitiesByFamily();

  renderTable('typesTable', [
    { key: 'name', label: 'Type' },
  ], state.supplier_types, 'editType', 'deleteType');

  renderTable('labelsTable', [
    { key: 'name', label: 'Label' },
    { key: 'color', label: 'Couleur' },
  ], state.labels, 'editLabel', 'deleteLabel');

  renderSuppliers();
  renderClientUsers();
  renderAdminUsers();
  renderResetAudit();
  renderAuditLogs();
  hydrateSelects();
  renderVisualSettings();
  renderChangeRequests();
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
      api('audit/ui-event', 'POST', {
        event_name: 'ui_visit_tab',
        event_type: 'visit',
        app: 'admin',
        page: 'admin',
        tab: String(tab || ''),
      }).catch(() => {});
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
  if (q('visAdminNotificationEmails')) q('visAdminNotificationEmails').value = s.admin_notification_emails || '';
  if (q('visPublicAssetsBaseUrl')) q('visPublicAssetsBaseUrl').value = s.public_assets_base_url || '';
  if (q('visSmtpHost')) q('visSmtpHost').value = s.smtp_host || '';
  if (q('visSmtpPort')) q('visSmtpPort').value = s.smtp_port || '';
  if (q('visSmtpEncryption')) q('visSmtpEncryption').value = s.smtp_encryption || 'tls';
  if (q('visSmtpUsername')) q('visSmtpUsername').value = s.smtp_username || '';
  if (q('visSmtpPassword')) q('visSmtpPassword').value = s.smtp_password || '';
  if (q('visSmtpFromEmail')) q('visSmtpFromEmail').value = s.smtp_from_email || '';
  if (q('visSmtpFromName')) q('visSmtpFromName').value = s.smtp_from_name || '';
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

  // Fallback for the historical "Fournisseurs" column order when a header is noisy.
  const conventionalIndexByKey = {
    'Nom': 0,
    'Adresse': 1,
    'Code postal': 2,
    'Ville': 3,
    'Activité': 4,
    'Type': 5,
    'Label': 6,
    'Email': 8,
    'Téléphone': 9,
    'Client': 14,
    'Latitude': 15,
    'Longitude': 16,
  };
  Object.entries(conventionalIndexByKey).forEach(([key, idx]) => {
    if (mapping[key]) return;
    if (idx < headers.length) {
      mapping[key] = headers[idx] || '';
    }
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
    const sourceValues = { activity: '', label: '', type: '', phone: '' };

    const rowHeaders = Object.keys(row || {});
    const findHeaderByAliases = (aliases) => {
      const found = rowHeaders.find(h => aliases.includes(normalizeHeaderName(h)));
      return found || '';
    };

    importFieldDefs.forEach(f => {
      const source = mapping[f.key] || findHeaderByAliases(f.aliases);
      if (!source) return;
      out[f.key] = row[source] ?? '';
      if (f.key === 'Activité') sourceValues.activity = String(row[source] ?? '').trim();
      if (f.key === 'Label') sourceValues.label = String(row[source] ?? '').trim();
      if (f.key === 'Type') sourceValues.type = String(row[source] ?? '').trim();
      if (f.key === 'Téléphone') sourceValues.phone = String(row[source] ?? '').trim();
    });

    if (Object.prototype.hasOwnProperty.call(out, 'Activité')) {
      out['Activité'] = transformDelimitedValues(out['Activité'], activityRules);
    }
    if (Object.prototype.hasOwnProperty.call(out, 'Label')) {
      out['Label'] = transformDelimitedValues(out['Label'], labelRules);
    }

    out._source_values = sourceValues;

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

function normalizePhoneDigits(v) {
  return String(v || '').replace(/\D+/g, '');
}

function detectInternalDuplicateGroups(mappedRows) {
  const groupsByKey = new Map();

  mappedRows.forEach((row, idx) => {
    const name = normalizeHeaderName(row.Nom || row.name || '');
    const city = normalizeHeaderName(row.Ville || row.city || '');
    const email = normalizeHeaderName(row.Email || row.email || '');
    const phone = normalizePhoneDigits(row['Téléphone'] || row.phone || '');

    let key = '';
    let kind = '';
    if (email) {
      key = `email:${email}`;
      kind = 'email';
    } else if (phone) {
      key = `phone:${phone}`;
      kind = 'phone';
    } else if (name && city) {
      key = `name_city:${name}|${city}`;
      kind = 'nom+ville';
    }
    if (!key) return;

    if (!groupsByKey.has(key)) {
      groupsByKey.set(key, { key, kind, items: [] });
    }
    groupsByKey.get(key).items.push({
      index: idx,
      name: String(row.Nom || row.name || '').trim(),
      city: String(row.Ville || row.city || '').trim(),
      address: String(row.Adresse || row.address || '').trim(),
      phone: String(row['Téléphone'] || row.phone || '').trim(),
      email: String(row.Email || row.email || '').trim(),
      activity: String(row['Activité'] || row.activities || row.activity || '').trim(),
      type: String(row.Type || row.supplier_type || '').trim(),
    });
  });

  return Array.from(groupsByKey.values()).filter(g => g.items.length > 1);
}

function renderPreflightDuplicatePanel(groups, mappedRows) {
  const panel = q('importPreflight');
  const summary = q('importPreflightSummary');
  const host = q('importPreflightGrid');
  if (!panel || !summary || !host) return;

  if (!groups.length) {
    panel.classList.add('hidden');
    summary.textContent = '';
    host.innerHTML = '';
    state.import.preflight.active = false;
    return;
  }

  state.import.preflight.pendingRows = mappedRows;
  state.import.preflight.active = true;
  panel.classList.remove('hidden');

  const totalDupRows = groups.reduce((acc, g) => acc + g.items.length, 0);
  summary.textContent = `${groups.length} groupe(s) de doublons détectés (${totalDupRows} lignes). Choisis ligne par ligne: garder ou ignorer avant prévisualisation.`;

  const rowsHtml = groups.map((g, gi) => {
    return g.items.map((item, ii) => {
      const key = `${gi}:${item.index}`;
      if (!Object.prototype.hasOwnProperty.call(state.import.preflight.decisions, key)) {
        state.import.preflight.decisions[key] = (ii === 0) ? 'keep' : 'drop';
      }
      const selected = state.import.preflight.decisions[key];
      return `
        <tr>
          <td>${gi + 1}</td>
          <td><span class="preflight-dup-key">${escapeHtml(g.kind)} - ${escapeHtml(g.key)}</span></td>
          <td>${item.index + 1}</td>
          <td>${escapeHtml(item.name)}</td>
          <td>${escapeHtml(item.city)}</td>
          <td>${escapeHtml(item.address)}</td>
          <td>${escapeHtml(item.phone)}</td>
          <td>${escapeHtml(item.email)}</td>
          <td>${escapeHtml(item.activity)}</td>
          <td>${escapeHtml(item.type)}</td>
          <td>
            <select data-preflight-key="${key}">
              <option value="keep"${selected === 'keep' ? ' selected' : ''}>Garder</option>
              <option value="drop"${selected === 'drop' ? ' selected' : ''}>Ignorer</option>
            </select>
          </td>
        </tr>
      `;
    }).join('');
  }).join('');

  host.innerHTML = `
    <div class="preflight-grid-wrap">
      <table class="preflight-grid">
        <thead>
          <tr>
            <th>Groupe</th>
            <th>Clé doublon</th>
            <th>Ligne</th>
            <th>Nom</th>
            <th>Ville</th>
            <th>Adresse</th>
            <th>Téléphone</th>
            <th>Email</th>
            <th>Activité</th>
            <th>Type</th>
            <th>Décision</th>
          </tr>
        </thead>
        <tbody>${rowsHtml}</tbody>
      </table>
    </div>
  `;

  host.querySelectorAll('select[data-preflight-key]').forEach(sel => {
    sel.addEventListener('change', () => {
      const k = sel.getAttribute('data-preflight-key');
      if (!k) return;
      state.import.preflight.decisions[k] = sel.value;
    });
  });
}

function applyPreflightDecisions() {
  const mappedRows = state.import.preflight.pendingRows || [];
  const groups = detectInternalDuplicateGroups(mappedRows);
  if (!groups.length) return mappedRows;

  const dropIndexes = new Set();
  groups.forEach((g, gi) => {
    g.items.forEach(item => {
      const key = `${gi}:${item.index}`;
      const decision = state.import.preflight.decisions[key] || 'keep';
      if (decision === 'drop') {
        dropIndexes.add(item.index);
      }
    });
  });

  return mappedRows.filter((_, idx) => !dropIndexes.has(idx));
}

function resetImportAnalysisState() {
  state.import.rowsRaw = [];
  state.import.headers = [];
  state.import.mapping = {};
  state.import.headerRowIndex = 0;
  state.import.preflight = { decisions: {}, pendingRows: [], active: false };
  state.preview = null;
  state.previewOriginalRows = [];

  renderPreflightDuplicatePanel([], []);
  if (q('importMapper')) q('importMapper').style.display = 'none';
  if (q('importSummary')) q('importSummary').textContent = '';
  if (q('importConflicts')) q('importConflicts').innerHTML = '';
  if (q('importMsg')) q('importMsg').textContent = '';
  if (q('importAnalyzeMsg')) q('importAnalyzeMsg').textContent = '';
  if (q('importGeocodeProgress')) q('importGeocodeProgress').textContent = '';
  if (q('dbDupAlert')) q('dbDupAlert').classList.add('hidden');
  if (q('dbDupSummary')) q('dbDupSummary').textContent = '';
  if (q('dbDupGrid')) q('dbDupGrid').innerHTML = '';
  if (q('previewGeocodeActions')) q('previewGeocodeActions').classList.add('hidden');
}

function focusImportRow(rowIndex) {
  const tr = document.querySelector(`#importConflicts tr[data-row-index="${rowIndex}"]`);
  if (!tr) return;
  tr.classList.remove('row-hidden');
  tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
  tr.style.outline = '2px solid #2563eb';
  setTimeout(() => { tr.style.outline = ''; }, 1800);
}

function syncConflictSelects(rowIndex, field, value) {
  document.querySelectorAll(`select[data-row="${rowIndex}"][data-field="${field}"]`).forEach(sel => {
    if (sel.value !== value) sel.value = value;
  });
}

function renderDbDuplicateAlert(rows) {
  const alertEl = q('dbDupAlert');
  const summaryEl = q('dbDupSummary');
  const gridEl = q('dbDupGrid');
  if (!alertEl || !summaryEl || !gridEl) return;

  const existingRows = (rows || []).filter(r => Number(r.existing_supplier_id || 0) > 0);
  if (!existingRows.length) {
    alertEl.classList.add('hidden');
    summaryEl.textContent = '';
    gridEl.innerHTML = '';
    return;
  }

  const conflictsTotal = existingRows.reduce((acc, row) => acc + ((row.conflicts || []).length), 0);
  summaryEl.textContent = `${existingRows.length} fournisseur(s) déjà existant(s), ${conflictsTotal} conflit(s) détecté(s). Choisis les résolutions ci-dessous ou dans la grille de prévisualisation.`;

  const rowsHtml = existingRows.map(row => {
    const payload = row.payload || {};
    const conflicts = row.conflicts || [];
    const conflictHtml = conflicts.length
      ? conflicts.map(c => {
        const isActivity = c.field === 'activity_text';
        const options = isActivity
          ? `
              <option value="merge_existing" selected>Fusionner</option>
              <option value="keep_existing">Garder base</option>
              <option value="replace_existing">Prendre import</option>
            `
          : `
              <option value="keep_existing" selected>Garder base</option>
              <option value="replace_existing">Prendre import</option>
            `;
        return `
          <div style="margin-bottom:8px;">
            <div><strong>${escapeHtml(c.field)}</strong></div>
            <div class="muted">Base: ${escapeHtml(c.existing || '')}</div>
            <div class="muted">Import: ${escapeHtml(c.incoming || '')}</div>
            <select data-row="${row.row_index}" data-field="${escapeHtml(c.field)}" class="dbdup-resolution">${options}</select>
          </div>
        `;
      }).join('')
      : '<span class="muted">Aucun conflit de champs détecté.</span>';

    return `
      <tr>
        <td>${row.row_index + 1}</td>
        <td>${escapeHtml(payload.name || row.name || '')}</td>
        <td>${escapeHtml(payload.city || row.city || '')}</td>
        <td>${escapeHtml(payload.phone || '')}</td>
        <td>${escapeHtml(payload.email || '')}</td>
        <td>${conflictHtml}</td>
        <td><button type="button" data-focus-row="${row.row_index}">Voir ligne</button></td>
      </tr>
    `;
  }).join('');

  gridEl.innerHTML = `
    <div class="dbdup-grid-wrap">
      <table class="dbdup-grid">
        <thead>
          <tr>
            <th>Ligne</th>
            <th>Nom</th>
            <th>Ville</th>
            <th>Téléphone</th>
            <th>Email</th>
            <th>Résolution des conflits</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>${rowsHtml}</tbody>
      </table>
    </div>
  `;

  gridEl.querySelectorAll('button[data-focus-row]').forEach(btn => {
    btn.addEventListener('click', () => {
      const rowIndex = Number(btn.getAttribute('data-focus-row'));
      focusImportRow(rowIndex);
    });
  });

  gridEl.querySelectorAll('select.dbdup-resolution[data-row][data-field]').forEach(sel => {
    sel.addEventListener('change', () => {
      const row = sel.getAttribute('data-row');
      const field = sel.getAttribute('data-field');
      if (!row || !field) return;
      syncConflictSelects(row, field, sel.value);
    });
  });

  alertEl.classList.remove('hidden');
}

function collectConflictResolutionsFromDom() {
  const resolutions = {};
  document.querySelectorAll('select[data-row][data-field]').forEach(sel => {
    const row = sel.getAttribute('data-row');
    const field = sel.getAttribute('data-field');
    if (!row || !field) return;
    if (!resolutions[row]) resolutions[row] = {};
    resolutions[row][field] = sel.value;
  });
  return resolutions;
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

function splitDelimitedUnique(value) {
  return Array.from(new Set(
    String(value || '')
      .split(/[;,]/)
      .map(v => v.trim())
      .filter(Boolean)
  ));
}

function setSupplierPickerValues(selectId, hiddenId, chipsId, values) {
  const selectEl = q(selectId);
  const hiddenEl = q(hiddenId);
  const chipsEl = q(chipsId);
  if (!selectEl || !hiddenEl || !chipsEl) return;

  const list = splitDelimitedUnique((values || []).join('; '));
  hiddenEl.value = list.join('; ');

  chipsEl.innerHTML = list.map(v => [
    '<span class="multi-chip">',
    escapeHtml(v),
    '<span class="multi-chip-remove" data-supplier-chip-remove="1" data-supplier-select="' + escapeHtml(selectId) + '" data-supplier-value="' + escapeHtml(v) + '" title="Retirer">x</span>',
    '</span>'
  ].join('')).join('');

  selectEl.value = '';
}

function getSupplierPickerValues(hiddenId) {
  return splitDelimitedUnique(q(hiddenId)?.value || '');
}

function bindSupplierPicker(selectId, hiddenId, chipsId) {
  const selectEl = q(selectId);
  const hiddenEl = q(hiddenId);
  const chipsEl = q(chipsId);
  if (!selectEl || !hiddenEl || !chipsEl) return;

  selectEl.addEventListener('change', () => {
    const value = String(selectEl.value || '').trim();
    if (!value) return;

    const current = getSupplierPickerValues(hiddenId);
    if (!current.some(v => normalizeHeaderName(v) === normalizeHeaderName(value))) {
      current.push(value);
    }
    setSupplierPickerValues(selectId, hiddenId, chipsId, current);
  });

  chipsEl.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-supplier-chip-remove="1"][data-supplier-select][data-supplier-value]');
    if (!btn) return;
    const value = String(btn.getAttribute('data-supplier-value') || '').trim();
    const filtered = getSupplierPickerValues(hiddenId).filter(v => normalizeHeaderName(v) !== normalizeHeaderName(value));
    setSupplierPickerValues(selectId, hiddenId, chipsId, filtered);
  });
}

function getSupplierTypeOptions() {
  return (state.supplier_types || [])
    .map(t => String(t.name || '').trim())
    .filter(Boolean)
    .sort((a, b) => a.localeCompare(b, 'fr'));
}

function getActivityOptions() {
  return Array.from(new Set((state.activities || [])
    .map(a => String(a.name || '').trim())
    .filter(Boolean))).sort((a, b) => a.localeCompare(b, 'fr'));
}

function getActivityOptionsGroupedHtml(placeholder = 'Activité...') {
  const grouped = new Map();
  (state.activities || []).forEach(activity => {
    const name = String(activity.name || '').trim();
    if (!name) return;
    const family = String(activity.family || '').trim() || 'Sans famille';
    if (!grouped.has(family)) grouped.set(family, []);
    grouped.get(family).push(name);
  });

  const groups = Array.from(grouped.entries())
    .sort((a, b) => a[0].localeCompare(b[0], 'fr'))
    .map(([family, names]) => {
      const options = Array.from(new Set(names))
        .sort((a, b) => a.localeCompare(b, 'fr'))
        .map(name => `<option value="${escapeHtml(name)}">${escapeHtml(name)}</option>`)
        .join('');
      return `<optgroup label="${escapeHtml(family)}">${options}</optgroup>`;
    })
    .join('');

  return `<option value="">${escapeHtml(placeholder)}</option>${groups}`;
}

function getActivityOptionsGroupedHtmlWithSelected(selectedValue = '', placeholder = 'Activité...') {
  const selectedNorm = normalizeHeaderName(selectedValue);
  const grouped = new Map();
  (state.activities || []).forEach(activity => {
    const name = String(activity.name || '').trim();
    if (!name) return;
    const family = String(activity.family || '').trim() || 'Sans famille';
    if (!grouped.has(family)) grouped.set(family, []);
    grouped.get(family).push(name);
  });

  const groups = Array.from(grouped.entries())
    .sort((a, b) => a[0].localeCompare(b[0], 'fr'))
    .map(([family, names]) => {
      const options = Array.from(new Set(names))
        .sort((a, b) => a.localeCompare(b, 'fr'))
        .map(name => `<option value="${escapeHtml(name)}"${normalizeHeaderName(name) === selectedNorm ? ' selected' : ''}>${escapeHtml(name)}</option>`)
        .join('');
      return `<optgroup label="${escapeHtml(family)}">${options}</optgroup>`;
    })
    .join('');

  return `<option value="">${escapeHtml(placeholder)}</option>${groups}`;
}

function getActivityOptionsGroupedHtmlWithSelectedMany(selectedValues = [], placeholder = 'Catégorie...') {
  const selectedSet = new Set((selectedValues || []).map(v => normalizeHeaderName(v)).filter(Boolean));
  const grouped = new Map();
  (state.activities || []).forEach(activity => {
    const name = String(activity.name || '').trim();
    if (!name) return;
    const family = String(activity.family || '').trim() || 'Sans famille';
    if (!grouped.has(family)) grouped.set(family, []);
    grouped.get(family).push(name);
  });

  const groups = Array.from(grouped.entries())
    .sort((a, b) => a[0].localeCompare(b[0], 'fr'))
    .map(([family, names]) => {
      const options = Array.from(new Set(names))
        .sort((a, b) => a.localeCompare(b, 'fr'))
        .map(name => `<option value="${escapeHtml(name)}"${selectedSet.has(normalizeHeaderName(name)) ? ' selected' : ''}>${escapeHtml(name)}</option>`)
        .join('');
      return `<optgroup label="${escapeHtml(family)}">${options}</optgroup>`;
    })
    .join('');

  return `<option value="" disabled>${escapeHtml(placeholder)}</option>${groups}`;
}

function getLabelOptions() {
  return Array.from(new Set((state.labels || [])
    .map(l => String(l.name || '').trim())
    .filter(Boolean))).sort((a, b) => a.localeCompare(b, 'fr'));
}

function resolveExistingReferenceValue(rawValue, fallbackValue, options) {
  const available = (options || []).map(v => ({ raw: v, norm: normalizeHeaderName(v) }));
  const candidates = [rawValue, fallbackValue]
    .flatMap(value => splitDelimitedUnique(value))
    .map(value => ({ raw: value, norm: normalizeHeaderName(value) }))
    .filter(v => v.norm);

  for (const candidate of candidates) {
    const found = available.find(option => option.norm === candidate.norm);
    if (found) return found.raw;
  }
  return '';
}

function resolveExistingReferenceValues(rawValue, fallbackValue, options) {
  const available = (options || []).map(v => ({ raw: v, norm: normalizeHeaderName(v) }));
  const candidates = [rawValue, fallbackValue]
    .flatMap(value => splitDelimitedUnique(value))
    .map(value => ({ raw: value, norm: normalizeHeaderName(value) }))
    .filter(v => v.norm);

  const found = [];
  for (const candidate of candidates) {
    const match = available.find(option => option.norm === candidate.norm);
    if (match && !found.some(x => x.norm === match.norm)) {
      found.push(match);
    }
  }
  return found.map(x => x.raw);
}

function renderSimpleOptionsHtml(options, placeholder, selectedValue = '') {
  const selectedNorm = normalizeHeaderName(selectedValue);
  const rendered = (options || [])
    .map(v => `<option value="${escapeHtml(v)}"${normalizeHeaderName(v) === selectedNorm ? ' selected' : ''}>${escapeHtml(v)}</option>`)
    .join('');
  return `<option value="">${escapeHtml(placeholder)}</option>${rendered}`;
}

function renderSimpleOptionsHtmlUnselected(options, placeholder = 'Ajouter...') {
  const rendered = (options || [])
    .map(v => `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`)
    .join('');
  return `<option value="">${escapeHtml(placeholder)}</option>${rendered}`;
}

function renderMultiPickerHtml(rowIndex, key, selectedValues, optionsHtml) {
  const normalizedValues = splitDelimitedUnique((selectedValues || []).join('; '));
  const chips = normalizedValues
    .map(v => `
      <span class="multi-chip">
        ${escapeHtml(v)}
        <button type="button" class="multi-chip-remove" data-multi-remove-row="${rowIndex}" data-multi-remove-key="${key}" data-multi-remove-value="${escapeHtml(v)}">x</button>
      </span>
    `)
    .join('');

  return `
    <input type="hidden" data-edit-row="${rowIndex}" data-edit-key="${key}" value="${escapeHtml(normalizedValues.join('; '))}" />
    <div class="multi-picker" data-multi-picker-row="${rowIndex}" data-multi-picker-key="${key}">
      <div class="multi-chip-list" data-multi-chip-list-row="${rowIndex}" data-multi-chip-list-key="${key}">${chips}</div>
      <select data-multi-select-row="${rowIndex}" data-multi-select-key="${key}">${optionsHtml}</select>
    </div>
  `;
}

function refreshMultiPickerChips(rowIndex, key) {
  const hidden = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="${key}"]`);
  const host = document.querySelector(`#importConflicts [data-multi-chip-list-row="${rowIndex}"][data-multi-chip-list-key="${key}"]`);
  if (!hidden || !host) return;

  const values = splitDelimitedUnique(hidden.value || '');
  host.innerHTML = values
    .map(v => `
      <span class="multi-chip">
        ${escapeHtml(v)}
        <button type="button" class="multi-chip-remove" data-multi-remove-row="${rowIndex}" data-multi-remove-key="${key}" data-multi-remove-value="${escapeHtml(v)}">x</button>
      </span>
    `)
    .join('');
}

function renderSimpleOptionsHtmlMany(options, placeholder, selectedValues = []) {
  const selectedSet = new Set((selectedValues || []).map(v => normalizeHeaderName(v)).filter(Boolean));
  const rendered = (options || [])
    .map(v => `<option value="${escapeHtml(v)}"${selectedSet.has(normalizeHeaderName(v)) ? ' selected' : ''}>${escapeHtml(v)}</option>`)
    .join('');
  return `<option value="" disabled>${escapeHtml(placeholder)}</option>${rendered}`;
}

function getPreviewRowMeta(rowIndex) {
  const row = (state.preview?.rows || []).find(r => Number(r.row_index) === Number(rowIndex));
  if (!row) return { isExisting: false, hasConflict: false, conflicts: [] };
  const conflicts = Array.isArray(row.conflicts) ? row.conflicts : [];
  return {
    isExisting: Number(row.existing_supplier_id || 0) > 0,
    hasConflict: conflicts.length > 0,
    conflicts,
  };
}

function collectRowPayloadFromDom(rowIndex) {
  const payload = {};
  document.querySelectorAll(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key]`).forEach(el => {
    const key = el.getAttribute('data-edit-key');
    if (!key) return;
    if (el instanceof HTMLSelectElement && el.multiple) {
      payload[key] = Array.from(el.selectedOptions)
        .map(opt => String(opt.value || '').trim())
        .filter(Boolean)
        .join('; ');
      return;
    }
    payload[key] = String(el.value || '').trim();
  });
  return payload;
}

function buildImportRowStatus(meta, payload) {
  const statuses = [];
  const name = String(payload.name || '').trim();
  const city = String(payload.city || '').trim();
  const address = String(payload.address || '').trim();
  const lat = String(payload.latitude || '').trim();
  const lng = String(payload.longitude || '').trim();

  if (meta.hasConflict) statuses.push({ key: 'conflict', label: 'Conflit', cls: 'status-conflict' });
  if (!name || (!city && !address)) statuses.push({ key: 'incomplete', label: 'A completer', cls: 'status-warn' });
  if (!lat || !lng) statuses.push({ key: 'geocode', label: 'Sans coord.', cls: 'status-warn' });
  if (!String(payload.activity_text || payload.activities || '').trim() || !String(payload.labels || '').trim() || !String(payload.supplier_type || '').trim()) {
    statuses.push({ key: 'taxonomy', label: 'Cat/label/type requis', cls: 'status-conflict' });
  }
  if (String(payload.geocode_mode || '') === 'village') statuses.push({ key: 'village', label: 'Approx. village', cls: 'status-warn' });
  statuses.push({ key: meta.isExisting ? 'existing' : 'new', label: meta.isExisting ? 'Existant' : 'Nouveau', cls: 'status-ok' });

  const onlyTag = statuses.every(s => s.key === 'existing' || s.key === 'new');
  if (onlyTag) statuses.unshift({ key: 'ok', label: 'OK', cls: 'status-ok' });

  return statuses;
}

function getPreviewStatusCounts(rows) {
  const counts = { incomplete: 0, geocode: 0, conflict: 0, taxonomy: 0 };
  (rows || []).forEach(row => {
    const meta = {
      isExisting: Number(row.existing_supplier_id || 0) > 0,
      hasConflict: Array.isArray(row.conflicts) && row.conflicts.length > 0,
    };
    const statuses = buildImportRowStatus(meta, row.payload || {});
    if (statuses.some(s => s.key === 'incomplete')) counts.incomplete++;
    if (statuses.some(s => s.key === 'geocode')) counts.geocode++;
    if (statuses.some(s => s.key === 'conflict')) counts.conflict++;
    if (statuses.some(s => s.key === 'taxonomy')) counts.taxonomy++;
  });
  return counts;
}

function getImportRequiredFieldIssues(rows) {
  return (rows || []).flatMap(row => {
    const payload = row.payload || {};
    const missing = [];
    if (!String(payload.activity_text || payload.activities || '').trim()) missing.push('catégorie');
    if (!String(payload.labels || '').trim()) missing.push('label');
    if (!String(payload.supplier_type || '').trim()) missing.push('type');
    if (!missing.length) return [];
    return [{ rowIndex: Number(row.row_index), name: String(payload.name || '').trim(), missing }];
  });
}

function refreshImportCommitState() {
  const commitArea = q('importCommitArea');
  const commitBtn = q('btnCommitImport');
  const validationMsg = q('importValidationMsg');
  if (!commitArea || !commitBtn || !validationMsg) return;

  if (!state.preview || !Array.isArray(state.preview.rows) || !state.preview.rows.length) {
    commitArea.classList.add('hidden');
    commitBtn.disabled = true;
    validationMsg.textContent = '';
    return;
  }

  commitArea.classList.remove('hidden');
  const issues = getImportRequiredFieldIssues(collectEditedPreviewRows());
  if (issues.length) {
    const sample = issues.slice(0, 3).map(i => `ligne ${i.rowIndex + 1}${i.name ? ` (${i.name})` : ''}: ${i.missing.join(', ')}`).join(' | ');
    commitBtn.disabled = true;
    validationMsg.textContent = `${issues.length} ligne(s) bloquent la validation: ${sample}`;
    return;
  }

  commitBtn.disabled = false;
  validationMsg.textContent = 'Validation possible: catégorie, label et type sont renseignés sur toutes les lignes.';
}

function updateImportGridStatuses() {
  document.querySelectorAll('#importConflicts tr[data-row-index]').forEach(tr => {
    const rowIndex = Number(tr.getAttribute('data-row-index'));
    const meta = getPreviewRowMeta(rowIndex);
    const payload = collectRowPayloadFromDom(rowIndex);
    const statuses = buildImportRowStatus(meta, payload);

    tr.dataset.hasConflict = statuses.some(s => s.key === 'conflict') ? '1' : '0';
    tr.dataset.incomplete = statuses.some(s => s.key === 'incomplete') ? '1' : '0';
    tr.dataset.geocode = statuses.some(s => s.key === 'geocode') ? '1' : '0';
    tr.dataset.scope = meta.isExisting ? 'existing' : 'new';

    const cell = tr.querySelector('[data-status-row]');
    if (!cell) return;
    cell.innerHTML = statuses
      .map(s => `<span class="status-badge ${s.cls}">${escapeHtml(s.label)}</span>`)
      .join('');
  });
}

function applyImportGridFilters() {
  const text = normalizeHeaderName(q('importFilterText')?.value || '');
  const status = q('importFilterStatus')?.value || 'all';

  let visible = 0;
  document.querySelectorAll('#importConflicts tr[data-row-index]').forEach(tr => {
    const rowIndex = Number(tr.getAttribute('data-row-index'));
    const payload = collectRowPayloadFromDom(rowIndex);
    const searchable = normalizeHeaderName([
      payload.name,
      payload.city,
      payload.email,
      payload.phone,
      payload.supplier_type,
      payload.activities,
      payload.labels,
    ].join(' '));

    const textOk = !text || searchable.includes(text);
    let statusOk = true;
    if (status === 'incomplete') statusOk = tr.dataset.incomplete === '1';
    else if (status === 'conflict') statusOk = tr.dataset.hasConflict === '1';
    else if (status === 'geocode') statusOk = tr.dataset.geocode === '1';
    else if (status === 'existing') statusOk = tr.dataset.scope === 'existing';
    else if (status === 'new') statusOk = tr.dataset.scope === 'new';

    const isVisible = textOk && statusOk;
    tr.classList.toggle('row-hidden', !isVisible);
    if (isVisible) visible++;
  });

  const total = document.querySelectorAll('#importConflicts tr[data-row-index]').length;
  if (q('importGridInfo')) q('importGridInfo').textContent = `${visible} lignes visibles sur ${total}`;
}

function appendDelimitedToInput(input, value) {
  const values = splitDelimitedUnique(input.value || '');
  if (!values.some(v => normalizeHeaderName(v) === normalizeHeaderName(value))) {
    values.push(String(value || '').trim());
  }
  input.value = values.join('; ');
}

function populateBulkSelectors() {
  const typeOptions = ['<option value="">-- type --</option>']
    .concat(getSupplierTypeOptions().map(v => `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`))
    .join('');
  const labelOptions = ['<option value="">-- label --</option>']
    .concat(getLabelOptions().map(v => `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`))
    .join('');
  const activityOptions = getActivityOptionsGroupedHtml('-- activité --');

  if (q('bulkTypeValue')) q('bulkTypeValue').innerHTML = typeOptions;
  if (q('bulkLabelValue')) q('bulkLabelValue').innerHTML = labelOptions;
  if (q('bulkActivityValue')) q('bulkActivityValue').innerHTML = activityOptions;
}

function applyBulkToFilteredRows(mode, key, value) {
  if (!value) return;
  document.querySelectorAll('#importConflicts tr[data-row-index]:not(.row-hidden)').forEach(tr => {
    const rowIndex = Number(tr.getAttribute('data-row-index'));
    const input = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="${key}"]`);
    if (!input) return;
    if (mode === 'append') {
      if (input instanceof HTMLSelectElement && input.multiple) {
        const targetNorm = normalizeHeaderName(value);
        Array.from(input.options).forEach(opt => {
          if (normalizeHeaderName(opt.value) === targetNorm) {
            opt.selected = true;
          }
        });
        return;
      }
      appendDelimitedToInput(input, value);
      if (key === 'activities' || key === 'labels') {
        refreshMultiPickerChips(rowIndex, key);
      }
    } else {
      input.value = value;
      if (key === 'activities' || key === 'labels') {
        refreshMultiPickerChips(rowIndex, key);
      }
    }
  });
  updateImportGridStatuses();
  applyImportGridFilters();
}

function renderEditableImportRows(rows) {
  const host = q('importConflicts');
  const toolbar = q('importGridToolbar');
  if (!rows || !rows.length) {
    if (toolbar) toolbar.style.display = 'none';
    host.innerHTML = '<div class="muted">Aucune ligne en prévisualisation.</div>';
    return;
  }

  if (toolbar) toolbar.style.display = 'block';

  const activityList = getActivityOptions();
  const labelList = getLabelOptions();
  const supplierTypeList = getSupplierTypeOptions();

  const bodyRows = rows.map(row => {
    const p = row.payload || {};
    const sourceValues = row.source_values || {};
    const selectedType = resolveExistingReferenceValue(sourceValues.type, p.supplier_type || '', supplierTypeList);
    const selectedActivities = resolveExistingReferenceValues(sourceValues.activity, p.activities || p.activity_text || '', activityList);
    const selectedLabels = resolveExistingReferenceValues(sourceValues.label, p.labels || '', labelList);
    const activityOptions = getActivityOptionsGroupedHtmlWithSelectedMany(selectedActivities, 'Catégorie...');
    const activityPicker = renderMultiPickerHtml(row.row_index, 'activities', selectedActivities, getActivityOptionsGroupedHtml('Ajouter une catégorie...'));
    const labelPicker = renderMultiPickerHtml(row.row_index, 'labels', selectedLabels, renderSimpleOptionsHtmlUnselected(labelList, 'Ajouter un label...'));
    const supplierTypeOptions = renderSimpleOptionsHtml(supplierTypeList, 'Type...', selectedType);
    const conflictSelectors = (row.conflicts || []).map(c => {
      const isActivity = c.field === 'activity_text';
      const options = isActivity
        ? `
          <option value="merge_existing" selected>Fusionner existant + import</option>
          <option value="keep_existing">Garder existant</option>
          <option value="replace_existing">Remplacer par import</option>
        `
        : `
          <option value="keep_existing" selected>Garder existant</option>
          <option value="replace_existing">Remplacer par import</option>
        `;

      return `
      <label style="margin-bottom:4px; display:block;">
        ${escapeHtml(c.field)}
        <div class="muted" style="margin:2px 0;">Base: ${escapeHtml(c.existing || '')}</div>
        <div class="muted" style="margin:2px 0;">Import: ${escapeHtml(c.incoming || '')}</div>
        <select data-row="${row.row_index}" data-field="${escapeHtml(c.field)}">
          ${options}
        </select>
      </label>
    `;
    }).join('');

    const geocodeNote = String(p.geocode_note || '').trim();

    return `
      <tr data-row-index="${row.row_index}">
        <td data-status-row="${row.row_index}"></td>
        <td>${row.row_index + 1}</td>
        <td><input data-edit-row="${row.row_index}" data-edit-key="name" value="${escapeHtml(p.name || '')}" /></td>
        <td>
          <input class="excel-source-input" value="${escapeHtml(sourceValues.type || '')}" readonly tabindex="-1" title="Source" />
          <select data-edit-row="${row.row_index}" data-edit-key="supplier_type">${supplierTypeOptions}</select>
        </td>
        <td>
          <input class="excel-source-input" value="${escapeHtml(sourceValues.activity || '')}" readonly tabindex="-1" title="Source" />
          ${activityPicker}
        </td>
        <td>
          <input class="excel-source-input" value="${escapeHtml(sourceValues.label || '')}" readonly tabindex="-1" title="Source" />
          ${labelPicker}
        </td>
        <td><input data-edit-row="${row.row_index}" data-edit-key="phone" value="${escapeHtml((p.phone || sourceValues.phone || '').trim())}" /></td>
        <td><input data-edit-row="${row.row_index}" data-edit-key="email" value="${escapeHtml(p.email || '')}" /></td>
        <td>
          <input data-edit-row="${row.row_index}" data-edit-key="address" value="${escapeHtml(p.address || '')}" />
          ${geocodeNote ? `<div class="muted" style="margin-top:4px;">${escapeHtml(geocodeNote)}</div>` : ''}
        </td>
        <td><input data-edit-row="${row.row_index}" data-edit-key="city" value="${escapeHtml(p.city || '')}" /></td>
        <td><input data-edit-row="${row.row_index}" data-edit-key="postal_code" value="${escapeHtml(p.postal_code || '')}" /></td>
        <td><input data-edit-row="${row.row_index}" data-edit-key="country" value="${escapeHtml(p.country || '')}" /></td>
        <td><input data-edit-row="${row.row_index}" data-edit-key="latitude" value="${escapeHtml(p.latitude ?? '')}" /></td>
        <td><input data-edit-row="${row.row_index}" data-edit-key="longitude" value="${escapeHtml(p.longitude ?? '')}" /></td>
        <td><button type="button" class="btn-map-edit" data-map-edit-row="${row.row_index}">Ajuster sur carte</button></td>
        <td>${conflictSelectors || '<span class="muted">-</span>'}</td>
      </tr>
    `;
  }).join('');

  host.innerHTML = `
    <div class="import-grid-wrap">
      <table class="import-grid">
        <thead>
          <tr>
            <th>Statut</th>
            <th>Ligne</th>
            <th>Nom</th>
            <th>Type</th>
            <th>Catégorie</th>
            <th>Label</th>
            <th>Téléphone</th>
            <th>Email</th>
            <th>Adresse</th>
            <th>Ville</th>
            <th>Code postal</th>
            <th>Pays</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th>Carte</th>
            <th>Conflits</th>
          </tr>
        </thead>
        <tbody>${bodyRows}</tbody>
      </table>
    </div>
  `;

  populateBulkSelectors();
  updateImportGridStatuses();
  applyImportGridFilters();
  refreshImportCommitState();

  host.querySelectorAll('[data-edit-row][data-edit-key]').forEach(input => {
    input.addEventListener('input', () => {
      updateImportGridStatuses();
      applyImportGridFilters();
      refreshImportCommitState();
    });
    input.addEventListener('change', () => {
      updateImportGridStatuses();
      applyImportGridFilters();
      refreshImportCommitState();
    });
  });

  host.querySelectorAll('select[data-multi-select-row][data-multi-select-key]').forEach(sel => {
    sel.addEventListener('change', () => {
      const rowIndex = Number(sel.getAttribute('data-multi-select-row'));
      const key = String(sel.getAttribute('data-multi-select-key') || '');
      const value = String(sel.value || '').trim();
      if (!Number.isFinite(rowIndex) || !key || !value) return;
      const hidden = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="${key}"]`);
      if (!hidden) return;

      const existing = splitDelimitedUnique(hidden.value || '');
      if (!existing.some(v => normalizeHeaderName(v) === normalizeHeaderName(value))) {
        existing.push(value);
      }
      hidden.value = existing.join('; ');
      refreshMultiPickerChips(rowIndex, key);
      sel.value = '';
      updateImportGridStatuses();
      applyImportGridFilters();
      refreshImportCommitState();
    });
  });

  host.addEventListener('click', (event) => {
    const btn = event.target.closest('button[data-multi-remove-row][data-multi-remove-key][data-multi-remove-value]');
    if (!btn) return;
    const rowIndex = Number(btn.getAttribute('data-multi-remove-row'));
    const key = String(btn.getAttribute('data-multi-remove-key') || '');
    const value = String(btn.getAttribute('data-multi-remove-value') || '');
    const hidden = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="${key}"]`);
    if (!hidden) return;

    const filtered = splitDelimitedUnique(hidden.value || '')
      .filter(v => normalizeHeaderName(v) !== normalizeHeaderName(value));
    hidden.value = filtered.join('; ');
    refreshMultiPickerChips(rowIndex, key);
    updateImportGridStatuses();
    applyImportGridFilters();
    refreshImportCommitState();
  });

  host.querySelectorAll('[data-map-edit-row]').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (!window.L) {
        q('importMsg').textContent = 'Carte indisponible (Leaflet non chargé).';
        return;
      }
      const rowIndex = Number(btn.getAttribute('data-map-edit-row'));
      try {
        await openMapEditorForRow(rowIndex);
      } catch (e) {
        q('importMsg').textContent = e.message;
      }
    });
  });
}

function collectEditedPreviewRows() {
  if (!state.preview || !Array.isArray(state.preview.rows)) return [];
  const out = state.preview.rows.map(r => ({ ...r, payload: { ...(r.payload || {}) } }));

  document.querySelectorAll('#importConflicts [data-edit-row][data-edit-key]').forEach(el => {
    const rowIndex = Number(el.getAttribute('data-edit-row'));
    const key = el.getAttribute('data-edit-key');
    const row = out.find(x => Number(x.row_index) === rowIndex);
    if (!row || !key) return;

    let v = '';
    if (el instanceof HTMLSelectElement && el.multiple) {
      v = Array.from(el.selectedOptions)
        .map(opt => String(opt.value || '').trim())
        .filter(Boolean)
        .join('; ');
    } else {
      v = String(el.value || '').trim();
    }
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

    const fields = ['name', 'address', 'city', 'postal_code', 'country', 'latitude', 'longitude', 'phone', 'email', 'website', 'supplier_type'];
    fields.forEach(field => {
      if (String(curr[field] ?? '').trim() !== String(prev[field] ?? '').trim()) {
        if (!resolutions[rowKey][field]) {
          resolutions[rowKey][field] = 'replace_existing';
        }
      }
    });

    const prevAct = toDelimitedString(prev.activities || prev.activity_text || '').trim();
    const currAct = toDelimitedString(curr.activities || curr.activity_text || '').trim();
    if (currAct !== prevAct && !resolutions[rowKey].activity_text) {
      resolutions[rowKey].activity_text = 'merge_existing';
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
  const wb = XLSX.read(ab, { type: 'array', cellText: true, cellDates: false });
  const sheet = wb.Sheets['Fournisseurs'] || wb.Sheets[wb.SheetNames[0]];
  const matrix = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '', raw: false });
  const detectedHeader = detectHeaderRow(matrix);
  state.import.headerRowIndex = detectedHeader;
  q('importHeaderRow').value = String(detectedHeader + 1);

  const built = buildRowsFromMatrix(matrix, detectedHeader);
  const headers = built.headers;
  const rows = built.rows;

  state.import.rowsRaw = rows;
  state.import.headers = headers;
  state.import.mapping = autoDetectImportMapping(headers);
  state.import.preflight = { decisions: {}, pendingRows: [], active: false };
  renderPreflightDuplicatePanel([], []);

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
  const wb = XLSX.read(ab, { type: 'array', cellText: true, cellDates: false });
  const sheet = wb.Sheets['Fournisseurs'] || wb.Sheets[wb.SheetNames[0]];
  const matrix = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '', raw: false });

  const headerRowIndex = headerRow1 - 1;
  if (headerRowIndex >= matrix.length) {
    throw new Error('La ligne d\'entêtes est hors du fichier');
  }

  const built = buildRowsFromMatrix(matrix, headerRowIndex);
  state.import.headerRowIndex = headerRowIndex;
  state.import.rowsRaw = built.rows;
  state.import.headers = built.headers;
  state.import.mapping = autoDetectImportMapping(built.headers);
  state.import.preflight = { decisions: {}, pendingRows: [], active: false };
  renderPreflightDuplicatePanel([], []);

  renderImportMappingUi();
  renderImportTransformedPreview();
  q('importAnalyzeMsg').textContent = `${built.rows.length} lignes chargées, entêtes forcées ligne ${headerRow1}`;
}

async function loadBootstrap() {
  const data = await api('admin/bootstrap');
  state.clients = data.clients || [];
  state.client_users = data.client_users || [];
  state.admin_users = data.admin_users || [];
  state.password_reset_audit = data.password_reset_audit || [];
  state.audit_logs = data.audit_logs || [];
  state.activities = data.activities || [];
  state.labels = data.labels || [];
  state.supplier_types = data.supplier_types || [];
  state.suppliers = data.suppliers || [];
  state.settings = data.settings || {};
  renderAll();
  await loadChangeRequests();
}

function resetSupplierForm() {
  q('formSupplier').reset();
  q('supplierId').value = '';
  q('supplierCountry').value = 'France';
  q('supplierSlug').value = '';
  q('supplierDescriptionShort').value = '';
  q('supplierDescriptionLong').value = '';
  q('supplierIsPublic').checked = true;
  setSupplierPickerValues('supplierActivitiesSelect', 'supplierActivities', 'supplierActivitiesChips', []);
  setSupplierPickerValues('supplierLabelsSelect', 'supplierLabels', 'supplierLabelsChips', []);
}

window.deleteClient = async function deleteClient(id) {
  const c = state.clients.find(x => Number(x.id) === Number(id));
  if (!c) return;
  if (!window.confirm(`Supprimer le client "${c.name}" ? Cette action est irréversible.`)) return;
  q('clientMsg').textContent = '';
  try {
    await api('admin/client/delete', 'POST', { id });
    q('clientMsg').textContent = 'Client supprimé';
    if (Number(q('clientId').value) === Number(id)) {
      q('formClient').reset();
      q('clientId').value = '';
      q('clientLogoPath').value = '';
      q('clientLogoPreview').innerHTML = '';
    }
    await loadBootstrap();
  } catch (e) {
    q('clientMsg').textContent = e.message;
  }
};

window.editClient = function editClient(id) {
  const c = state.clients.find(x => Number(x.id) === Number(id));
  if (!c) return;
  q('clientId').value = c.id;
  q('clientName').value = c.name || '';
  q('clientType').value = c.client_type || '';
  q('clientEmail').value = c.email || '';
  q('clientPhone').value = c.phone || '';
  q('clientWebsite').value = c.website || '';
  q('clientSlug').value = c.slug || '';
  q('clientFacebook').value = c.facebook_url || '';
  q('clientInstagram').value = c.instagram_url || '';
  q('clientLinkedin').value = c.linkedin_url || '';
  q('clientLogo').value = c.logo_url || '';
  q('clientLogoPath').value = c.logo_url || '';
  q('clientCoverUrl').value = c.photo_cover_url || '';
  q('clientAddress').value = c.address || '';
  q('clientCity').value = c.city || '';
  q('clientPostal').value = c.postal_code || '';
  q('clientCountry').value = c.country || '';
  q('clientLat').value = c.latitude || '';
  q('clientLng').value = c.longitude || '';
  q('clientDescriptionShort').value = c.description_short || '';
  q('clientDescriptionLong').value = c.description_long || '';
  q('clientIsPublic').checked = Number(c.is_public) === 1;
  q('clientMonday').value = c.lundi || '';
  q('clientTuesday').value = c.mardi || '';
  q('clientWednesday').value = c.mercredi || '';
  q('clientThursday').value = c.jeudi || '';
  q('clientFriday').value = c.vendredi || '';
  q('clientSaturday').value = c.samedi || '';
  q('clientSunday').value = c.dimanche || '';
  q('clientLogoPreview').innerHTML = c.logo_url ? `<img src="${c.logo_url}" alt="logo client" style="max-height:52px;border:1px solid #e5e7eb;border-radius:6px;padding:2px;"/>` : '<span class="muted">Aucun logo</span>';
};

window.editClientUser = function editClientUser(id) {
  const u = state.client_users.find(x => Number(x.id) === Number(id));
  if (!u) return;
  q('clientUserId').value = u.id;
  q('clientUserClientId').value = u.client_id || '';
  q('clientUserUsername').value = u.username || '';
  q('clientUserEmail').value = u.email || '';
  q('clientUserRole').value = u.role || 'client_manager';
  q('clientUserPassword').value = '';
  q('clientUserIsActive').checked = Number(u.is_active) === 1;
  q('clientUserMsg').textContent = `Edition utilisateur: ${u.username || ''}`;
};

window.toggleClientUserActive = async function toggleClientUserActive(id, isActive) {
  const u = state.client_users.find(x => Number(x.id) === Number(id));
  if (!u) return;
  const label = isActive ? 'activer' : 'désactiver';
  if (!window.confirm(`${label.charAt(0).toUpperCase() + label.slice(1)} le compte "${u.username}" ?`)) return;

  q('clientUserMsg').textContent = '';
  try {
    await api('admin/client-user/toggle-active', 'POST', { id, is_active: !!isActive });
    q('clientUserMsg').textContent = 'Statut utilisateur mis à jour';
    await loadBootstrap();
  } catch (e) {
    q('clientUserMsg').textContent = e.message;
  }
};

window.resetClientUserPassword = async function resetClientUserPassword(id) {
  const u = state.client_users.find(x => Number(x.id) === Number(id));
  if (!u) return;
  const value = window.prompt(`Nouveau mot de passe pour ${u.username} (8 caractères min):`, '');
  if (value === null) return;
  const newPassword = String(value || '').trim();

  q('clientUserMsg').textContent = '';
  try {
    await api('admin/client-user/reset-password', 'POST', { id, new_password: newPassword });
    q('clientUserMsg').textContent = 'Mot de passe réinitialisé';
  } catch (e) {
    q('clientUserMsg').textContent = e.message;
  }
};

window.deleteClientUser = async function deleteClientUser(id) {
  const u = state.client_users.find(x => Number(x.id) === Number(id));
  if (!u) return;
  if (!window.confirm(`Supprimer définitivement l'utilisateur client "${u.username}" ?`)) return;

  q('clientUserMsg').textContent = '';
  try {
    await api('admin/client-user/delete', 'POST', { id });
    q('clientUserMsg').textContent = 'Utilisateur client supprimé';
    if (Number(q('clientUserId').value) === Number(id)) {
      q('formClientUser').reset();
      q('clientUserId').value = '';
      q('clientUserRole').value = 'client_manager';
      q('clientUserIsActive').checked = true;
    }
    await loadBootstrap();
  } catch (e) {
    q('clientUserMsg').textContent = e.message;
  }
};

window.sendClientUserResetLink = async function sendClientUserResetLink(id) {
  const u = state.client_users.find(x => Number(x.id) === Number(id));
  if (!u) return;
  if (!String(u.email || '').trim()) {
    q('clientUserMsg').textContent = 'Ajoute un email à cet utilisateur avant envoi du lien';
    return;
  }
  if (!window.confirm(`Envoyer un lien de réinitialisation à ${u.email || 'cet utilisateur'} ?`)) return;

  q('clientUserMsg').textContent = '';
  try {
    await api('admin/client-user/send-reset-link', 'POST', { id });
    q('clientUserMsg').textContent = 'Lien de réinitialisation envoyé';
  } catch (e) {
    q('clientUserMsg').textContent = e.message;
  }
};

window.editAdminUser = function editAdminUser(id) {
  const u = state.admin_users.find(x => Number(x.id) === Number(id));
  if (!u) return;
  q('adminUserId').value = u.id;
  q('adminUserUsername').value = u.username || '';
  q('adminUserEmail').value = u.email || '';
  q('adminUserPassword').value = '';
  q('adminUserIsActive').checked = Number(u.is_active) === 1;
  q('adminUserMsg').textContent = `Edition admin: ${u.username || ''}`;
};

window.toggleAdminUserActive = async function toggleAdminUserActive(id, isActive) {
  const u = state.admin_users.find(x => Number(x.id) === Number(id));
  if (!u) return;
  const label = isActive ? 'activer' : 'désactiver';
  if (!window.confirm(`${label.charAt(0).toUpperCase() + label.slice(1)} le compte admin "${u.username}" ?`)) return;

  q('adminUserMsg').textContent = '';
  try {
    await api('admin/admin-user/toggle-active', 'POST', { id, is_active: !!isActive });
    q('adminUserMsg').textContent = 'Statut admin mis à jour';
    await loadBootstrap();
  } catch (e) {
    q('adminUserMsg').textContent = e.message;
  }
};

window.resetAdminUserPassword = async function resetAdminUserPassword(id) {
  const u = state.admin_users.find(x => Number(x.id) === Number(id));
  if (!u) return;
  const value = window.prompt(`Nouveau mot de passe pour ${u.username} (12 caractères min):`, '');
  if (value === null) return;
  const newPassword = String(value || '').trim();

  q('adminUserMsg').textContent = '';
  try {
    await api('admin/admin-user/reset-password', 'POST', { id, new_password: newPassword });
    q('adminUserMsg').textContent = 'Mot de passe admin réinitialisé';
  } catch (e) {
    q('adminUserMsg').textContent = e.message;
  }
};

window.deleteAdminUser = async function deleteAdminUser(id) {
  const u = state.admin_users.find(x => Number(x.id) === Number(id));
  if (!u) return;
  if (!window.confirm(`Supprimer définitivement l'admin "${u.username}" ?`)) return;

  q('adminUserMsg').textContent = '';
  try {
    await api('admin/admin-user/delete', 'POST', { id });
    q('adminUserMsg').textContent = 'Utilisateur admin supprimé';
    if (Number(q('adminUserId').value) === Number(id)) {
      q('formAdminUser').reset();
      q('adminUserId').value = '';
      q('adminUserIsActive').checked = true;
    }
    await loadBootstrap();
  } catch (e) {
    q('adminUserMsg').textContent = e.message;
  }
};

window.sendAdminUserResetLink = async function sendAdminUserResetLink(id) {
  const u = state.admin_users.find(x => Number(x.id) === Number(id));
  if (!u) return;
  if (!String(u.email || '').trim()) {
    q('adminUserMsg').textContent = 'Ajoute un email à cet admin avant envoi du lien';
    return;
  }
  if (!window.confirm(`Envoyer un lien de réinitialisation à ${u.email || 'cet admin'} ?`)) return;

  q('adminUserMsg').textContent = '';
  try {
    await api('admin/admin-user/send-reset-link', 'POST', { id });
    q('adminUserMsg').textContent = 'Lien de réinitialisation envoyé';
  } catch (e) {
    q('adminUserMsg').textContent = e.message;
  }
};

window.reviewChangeRequest = async function reviewChangeRequest(id, decision) {
  const request = state.change_requests.find(x => Number(x.id) === Number(id));
  if (!request) return;

  const actionLabel = decision === 'approved' ? 'approuver' : 'refuser';
  if (!window.confirm(`Confirmer: ${actionLabel} la demande #${id} ?`)) return;

  const reviewNote = window.prompt('Note admin (optionnelle):', '') ?? '';

  q('requestMsg').textContent = '';
  try {
    await api('admin/change-request/review', 'POST', {
      id,
      decision,
      review_note: reviewNote,
    });
    q('requestMsg').textContent = `Demande #${id} traitée (${decision})`;
    await loadBootstrap();
  } catch (e) {
    q('requestMsg').textContent = e.message;
  }
};

window.reviewSupplierCreateRequest = async function reviewSupplierCreateRequest(id, decision) {
  const request = state.supplier_create_requests.find(x => Number(x.id) === Number(id));
  if (!request) return;

  const actionLabel = decision === 'approved' ? 'approuver' : 'refuser';
  if (!window.confirm(`Confirmer: ${actionLabel} la demande de création #${id} ?`)) return;

  const reviewNote = window.prompt('Note admin (optionnelle):', '') ?? '';

  q('requestMsg').textContent = '';
  try {
    await api('admin/supplier-create-request/review', 'POST', {
      id,
      decision,
      review_note: reviewNote,
    });
    q('requestMsg').textContent = `Demande création #${id} traitée (${decision})`;
    await loadBootstrap();
  } catch (e) {
    q('requestMsg').textContent = e.message;
  }
};

window.reviewSupplierLinkRequest = async function reviewSupplierLinkRequest(id, decision) {
  const request = state.supplier_link_requests.find(x => Number(x.id) === Number(id));
  if (!request) return;

  const actionLabel = decision === 'approved' ? 'approuver' : 'refuser';
  if (!window.confirm(`Confirmer: ${actionLabel} la demande de rattachement #${id} ?`)) return;

  const reviewNote = window.prompt('Note admin (optionnelle):', '') ?? '';

  q('requestMsg').textContent = '';
  try {
    await api('admin/supplier-link-request/review', 'POST', {
      id,
      decision,
      review_note: reviewNote,
    });
    q('requestMsg').textContent = `Demande rattachement #${id} traitée (${decision})`;
    await loadBootstrap();
  } catch (e) {
    q('requestMsg').textContent = e.message;
  }
};

window.reviewChangeRequestsBulk = async function reviewChangeRequestsBulk(decision) {
  const pendingIds = (state.change_requests || [])
    .filter(r => String(r.status || '') === 'pending')
    .map(r => Number(r.id))
    .filter(v => Number.isFinite(v) && v > 0);

  if (!pendingIds.length) {
    q('requestMsg').textContent = 'Aucune demande en attente dans le filtre courant';
    return;
  }

  const actionLabel = decision === 'approved' ? 'approuver' : 'refuser';
  if (!window.confirm(`Confirmer: ${actionLabel} ${pendingIds.length} demande(s) filtrée(s) ?`)) return;
  const reviewNote = window.prompt('Note admin (optionnelle):', '') ?? '';

  q('requestMsg').textContent = '';
  try {
    const res = await api('admin/change-request/review-bulk', 'POST', {
      ids: pendingIds,
      decision,
      review_note: reviewNote,
    });
    q('requestMsg').textContent = `Traitement lot: ${res.processed || 0} traitée(s), ${res.skipped || 0} ignorée(s)`;
    await loadBootstrap();
  } catch (e) {
    q('requestMsg').textContent = e.message;
  }
};

window.toggleChangeRequestSelection = function toggleChangeRequestSelection(id, checked) {
  const n = Number(id);
  if (!Number.isFinite(n) || n <= 0) return;
  if (checked) {
    selectedChangeRequestIds.add(n);
  } else {
    selectedChangeRequestIds.delete(n);
  }
  updateRequestSelectionSummary();
};

window.reviewSelectedChangeRequestsBulk = async function reviewSelectedChangeRequestsBulk(decision) {
  const ids = Array.from(selectedChangeRequestIds);
  if (!ids.length) {
    q('requestMsg').textContent = 'Aucune demande sélectionnée';
    return;
  }

  const pendingIds = (state.change_requests || [])
    .filter(r => ids.includes(Number(r.id)) && String(r.status || '') === 'pending')
    .map(r => Number(r.id));

  if (!pendingIds.length) {
    q('requestMsg').textContent = 'Aucune demande sélectionnée en attente';
    return;
  }

  const actionLabel = decision === 'approved' ? 'approuver' : 'refuser';
  if (!window.confirm(`Confirmer: ${actionLabel} ${pendingIds.length} demande(s) sélectionnée(s) ?`)) return;
  const reviewNote = window.prompt('Note admin (optionnelle):', '') ?? '';

  q('requestMsg').textContent = '';
  try {
    const res = await api('admin/change-request/review-bulk', 'POST', {
      ids: pendingIds,
      decision,
      review_note: reviewNote,
    });
    selectedChangeRequestIds = new Set();
    q('requestMsg').textContent = `Traitement sélection: ${res.processed || 0} traitée(s), ${res.skipped || 0} ignorée(s)`;
    await loadBootstrap();
  } catch (e) {
    q('requestMsg').textContent = e.message;
  }
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

window.editType = function editType(id) {
  const t = state.supplier_types.find(x => Number(x.id) === Number(id));
  if (!t) return;
  q('typeId').value = t.id;
  q('typeName').value = t.name || '';
};

window.deleteType = async function deleteType(id) {
  const t = state.supplier_types.find(x => Number(x.id) === Number(id));
  if (!t) return;
  if (!window.confirm(`Supprimer le type "${t.name}" ?`)) return;
  q('typeMsg').textContent = '';
  try {
    await api('admin/type/delete', 'POST', { id });
    q('typeMsg').textContent = 'Type supprimé';
    if (Number(q('typeId').value) === Number(id)) {
      q('formType').reset();
      q('typeId').value = '';
    }
    await loadBootstrap();
  } catch (e) {
    q('typeMsg').textContent = e.message;
  }
};

window.editSupplier = function editSupplier(id) {
  const supplier = state.suppliers.find(x => Number(x.id) === Number(id));
  if (!supplier) return;

  q('supplierId').value = supplier.id;
  q('supplierName').value = supplier.name || '';
  q('supplierType').value = supplier.supplier_type || '';
  setSupplierPickerValues('supplierActivitiesSelect', 'supplierActivities', 'supplierActivitiesChips', splitDelimitedUnique(supplier.activity_text || ''));
  setSupplierPickerValues('supplierLabelsSelect', 'supplierLabels', 'supplierLabelsChips', splitDelimitedUnique(supplier.labels || ''));
  q('supplierPhone').value = supplier.phone || '';
  q('supplierEmail').value = supplier.email || '';
  q('supplierWebsite').value = supplier.website || '';
  q('supplierSlug').value = supplier.slug || '';
  q('supplierFacebook').value = supplier.facebook_url || '';
  q('supplierInstagram').value = supplier.instagram_url || '';
  q('supplierLinkedin').value = supplier.linkedin_url || '';
  q('supplierLogoUrl').value = supplier.logo_url || '';
  q('supplierCoverUrl').value = supplier.photo_cover_url || '';
  q('supplierAddress').value = supplier.address || '';
  q('supplierCity').value = supplier.city || '';
  q('supplierPostal').value = supplier.postal_code || '';
  q('supplierCountry').value = supplier.country || 'France';
  q('supplierLat').value = supplier.latitude || '';
  q('supplierLng').value = supplier.longitude || '';
  q('supplierDescriptionShort').value = supplier.description_short || '';
  q('supplierDescriptionLong').value = supplier.description_long || '';
  q('supplierIsPublic').checked = Number(supplier.is_public) === 1;

  const selectedClientIds = String(supplier.client_ids || '')
    .split(',')
    .map(value => Number(value.trim()))
    .filter(Number.isFinite);
  Array.from(q('supplierClients').options).forEach(option => {
    option.selected = selectedClientIds.includes(Number(option.value));
  });

  q('supplierMsg').textContent = `Edition du fournisseur: ${supplier.name || ''}`;
};

window.deleteActivity = async function deleteActivity(id) {
  const activity = state.activities.find(x => Number(x.id) === Number(id));
  if (!activity) return;
  if (!window.confirm(`Supprimer l'activité "${activity.name}" ?`)) return;

  q('activityMsg').textContent = '';
  try {
    await api('admin/activity/delete', 'POST', { id });
    q('activityMsg').textContent = 'Activité supprimée';
    if (Number(q('activityId').value) === Number(id)) {
      q('formActivity').reset();
      q('activityId').value = '';
      q('activityIcon').value = '';
      q('activityIconPath').value = '';
      q('activityIconPreview').innerHTML = '';
    }
    await loadBootstrap();
  } catch (e) {
    q('activityMsg').textContent = e.message;
  }
};

window.deleteLabel = async function deleteLabel(id) {
  const label = state.labels.find(x => Number(x.id) === Number(id));
  if (!label) return;
  if (!window.confirm(`Supprimer le label "${label.name}" ?`)) return;

  q('labelMsg').textContent = '';
  try {
    await api('admin/label/delete', 'POST', { id });
    q('labelMsg').textContent = 'Label supprimé';
    if (Number(q('labelId').value) === Number(id)) {
      q('formLabel').reset();
      q('labelId').value = '';
    }
    await loadBootstrap();
  } catch (e) {
    q('labelMsg').textContent = e.message;
  }
};

window.deleteSupplier = async function deleteSupplier(id) {
  const supplier = state.suppliers.find(x => Number(x.id) === Number(id));
  if (!supplier) return;
  if (!window.confirm(`Supprimer le fournisseur "${supplier.name}" ?`)) return;

  q('supplierMsg').textContent = '';
  try {
    await api('admin/supplier/delete', 'POST', { id });
    q('supplierMsg').textContent = 'Fournisseur supprimé';
    if (Number(q('supplierId').value) === Number(id)) {
      resetSupplierForm();
    }
    await loadBootstrap();
  } catch (e) {
    q('supplierMsg').textContent = e.message;
  }
};

function bindEvents() {
  bindSupplierPicker('supplierActivitiesSelect', 'supplierActivities', 'supplierActivitiesChips');
  bindSupplierPicker('supplierLabelsSelect', 'supplierLabels', 'supplierLabelsChips');

  q('btnLogin').addEventListener('click', async () => {
    q('loginMsg').textContent = '';
    try {
      const res = await api('auth/login', 'POST', { username: q('loginUser').value, password: q('loginPass').value });
      if (res.role !== 'admin') {
        await api('auth/logout', 'POST').catch(() => {});
        throw new Error("Accès réservé aux administrateurs. Utilisez l'espace client.");
      }
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
        slug: q('clientSlug').value,
        facebook_url: q('clientFacebook').value,
        instagram_url: q('clientInstagram').value,
        linkedin_url: q('clientLinkedin').value,
        logo_url: logoUrl,
        photo_cover_url: q('clientCoverUrl').value,
        description_short: q('clientDescriptionShort').value,
        description_long: q('clientDescriptionLong').value,
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
        is_public: q('clientIsPublic').checked,
        is_active: true,
      });
      q('clientMsg').textContent = 'Client enregistré';
      q('formClient').reset();
      q('clientId').value = '';
      q('clientLogoPath').value = '';
      q('clientLogoPreview').innerHTML = '';
      q('clientSlug').value = '';
      q('clientCoverUrl').value = '';
      q('clientDescriptionShort').value = '';
      q('clientDescriptionLong').value = '';
      q('clientIsPublic').checked = true;
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

  q('btnExportClients').addEventListener('click', () => {
    window.open('../api/index.php?action=' + encodeURIComponent('admin/client/export'), '_blank');
  });

  q('btnSaveClientUser').addEventListener('click', async () => {
    q('clientUserMsg').textContent = '';
    try {
      await api('admin/client-user/save', 'POST', {
        id: q('clientUserId').value || null,
        client_id: Number(q('clientUserClientId').value || 0),
        username: q('clientUserUsername').value,
        email: q('clientUserEmail').value,
        role: q('clientUserRole').value,
        password: q('clientUserPassword').value,
        is_active: q('clientUserIsActive').checked,
      });

      q('clientUserMsg').textContent = q('clientUserId').value ? 'Utilisateur client modifié' : 'Utilisateur client créé';
      q('formClientUser').reset();
      q('clientUserId').value = '';
      q('clientUserEmail').value = '';
      q('clientUserRole').value = 'client_manager';
      q('clientUserIsActive').checked = true;
      await loadBootstrap();
    } catch (e) {
      q('clientUserMsg').textContent = e.message;
    }
  });

  q('btnSaveAdminUser').addEventListener('click', async () => {
    q('adminUserMsg').textContent = '';
    try {
      await api('admin/admin-user/save', 'POST', {
        id: q('adminUserId').value || null,
        username: q('adminUserUsername').value,
        email: q('adminUserEmail').value,
        password: q('adminUserPassword').value,
        is_active: q('adminUserIsActive').checked,
      });

      q('adminUserMsg').textContent = q('adminUserId').value ? 'Utilisateur admin modifié' : 'Utilisateur admin créé';
      q('formAdminUser').reset();
      q('adminUserId').value = '';
      q('adminUserEmail').value = '';
      q('adminUserIsActive').checked = true;
      await loadBootstrap();
    } catch (e) {
      q('adminUserMsg').textContent = e.message;
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

  q('btnSaveType').addEventListener('click', async () => {
    q('typeMsg').textContent = '';
    try {
      await api('admin/type/save', 'POST', {
        id: q('typeId').value || null,
        name: q('typeName').value,
      });
      q('typeMsg').textContent = 'Type enregistré';
      q('formType').reset();
      q('typeId').value = '';
      await loadBootstrap();
    } catch (e) {
      q('typeMsg').textContent = e.message;
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
        id: q('supplierId').value || null,
        name: q('supplierName').value,
        supplier_type: q('supplierType').value,
        activities: q('supplierActivities').value,
        labels: q('supplierLabels').value,
        phone: q('supplierPhone').value,
        email: q('supplierEmail').value,
        website: q('supplierWebsite').value,
        slug: q('supplierSlug').value,
        facebook_url: q('supplierFacebook').value,
        instagram_url: q('supplierInstagram').value,
        linkedin_url: q('supplierLinkedin').value,
        logo_url: q('supplierLogoUrl').value,
        photo_cover_url: q('supplierCoverUrl').value,
        address: q('supplierAddress').value,
        city: q('supplierCity').value,
        postal_code: q('supplierPostal').value,
        country: q('supplierCountry').value,
        latitude: q('supplierLat').value,
        longitude: q('supplierLng').value,
        description_short: q('supplierDescriptionShort').value,
        description_long: q('supplierDescriptionLong').value,
        is_public: q('supplierIsPublic').checked,
        client_ids: selectedMultiValues(q('supplierClients')),
      });
      q('supplierMsg').textContent = q('supplierId').value ? 'Fournisseur modifié' : 'Fournisseur enregistré';
      resetSupplierForm();
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

  q('btnExportProducersAll').addEventListener('click', () => {
    window.open('../api/index.php?action=' + encodeURIComponent('admin/producer/export'), '_blank');
  });

  q('btnExportProducersChanged').addEventListener('click', () => {
    const params = new URLSearchParams({ action: 'admin/producer/export', scope: 'changed' });
    window.open('../api/index.php?' + params.toString(), '_blank');
  });

  q('supplierFilterText').addEventListener('input', () => {
    renderSuppliers();
  });

  q('supplierFilterClient').addEventListener('change', () => {
    renderSuppliers();
  });

  q('supplierFilterActivity').addEventListener('change', () => {
    renderSuppliers();
  });

  q('supplierFilterType').addEventListener('change', () => {
    renderSuppliers();
  });

  q('btnResetSupplierFilters').addEventListener('click', () => {
    q('supplierFilterText').value = '';
    q('supplierFilterClient').value = '';
    q('supplierFilterActivity').value = '';
    q('supplierFilterType').value = '';
    renderSuppliers();
  });

  q('btnPreviewImport').addEventListener('click', async () => {
    q('importMsg').textContent = '';
    q('importSummary').textContent = '';
    q('importConflicts').innerHTML = '';
    if (q('dbDupAlert')) q('dbDupAlert').classList.add('hidden');
    if (q('previewGeocodeActions')) q('previewGeocodeActions').classList.add('hidden');
    refreshImportCommitState();
    try {
      const file = q('importFile').files[0];
      const clientId = Number(q('importClient').value || 0);
      if (!file) throw new Error('Choisis un fichier Excel');
      if (!clientId) throw new Error('Choisis le client cible');

      setImportLoading(true, [
        'Lecture du fichier Excel',
        'Application du mapping et des règles',
        'Détection des conflits',
        'Construction de la grille de prévisualisation',
      ]);

      if (!state.import.rowsRaw.length) {
        await analyzeImportFile();
      }

      const rowsRawMapped = getMappedImportRows();
      if (!state.import.preflight.active) {
        const duplicateGroups = detectInternalDuplicateGroups(rowsRawMapped);
        if (duplicateGroups.length) {
          renderPreflightDuplicatePanel(duplicateGroups, rowsRawMapped);
          throw new Error('Doublons détectés dans le fichier: choisis garder/ignorer puis clique "Appliquer ces choix".');
        }
      }

      const rows = state.import.preflight.active ? applyPreflightDecisions() : rowsRawMapped;
      if (!rows.length) throw new Error('Aucune ligne à importer après mapping');

      const preview = await api('admin/import/preview', 'POST', {
        client_id: clientId,
        file_name: file.name,
        rows,
        auto_geocode: q('importAutoGeocode').checked,
      });
      state.preview = preview;
      state.previewOriginalRows = JSON.parse(JSON.stringify(preview.rows || []));
      const statusCounts = getPreviewStatusCounts(preview.rows || []);
      q('importSummary').textContent = `Nouveaux: ${preview.summary.new} | Existants: ${preview.summary.existing} | Lignes en conflit: ${preview.summary.conflicts} | Cat/label/type manquants: ${statusCounts.taxonomy} | A compléter: ${statusCounts.incomplete} | Sans coordonnées: ${statusCounts.geocode} | Erreurs: ${preview.summary.errors}`;
      renderEditableImportRows(preview.rows || []);
      renderDbDuplicateAlert(preview.rows || []);
      if (preview.summary.existing > 0 || preview.summary.conflicts > 0) {
        q('importMsg').textContent = `Doublons base détectés: ${preview.summary.existing} fournisseur(s) déjà existant(s), ${preview.summary.conflicts} ligne(s) en conflit. Vérifie les décisions avant validation.`;
      } else {
        q('importMsg').textContent = 'Prévisualisation terminée. Modifie les champs si besoin puis valide.';
      }
      renderPreflightDuplicatePanel([], []);
      if (q('importAutoGeocode').checked) {
        // Geocoding is intentionally NOT done during preview (too slow).
        // Run it progressively in the background now that the table is visible.
        geocodePreviewRowsProgressively();
      }
    } catch (e) {
      geocodeProgressAbort = true;
      const msgEl = q('importMsg');
      msgEl.textContent = e.message;
      msgEl.style.color = '#dc2626';
      msgEl.style.fontWeight = 'bold';
    } finally {
      setImportLoading(false);
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

  q('importFile').addEventListener('change', () => {
    geocodeProgressAbort = true;
    resetImportAnalysisState();
  });

  q('btnApplyPreflight').addEventListener('click', async () => {
    try {
      const rows = applyPreflightDecisions();
      if (!rows.length) throw new Error('Aucune ligne conservée après décisions sur doublons');
      q('importMsg').textContent = `Pré-contrôle validé: ${rows.length} ligne(s) conservée(s). Relance la prévisualisation.`;
    } catch (e) {
      q('importMsg').textContent = e.message;
    }
  });

  q('btnRunPreviewGeocode').addEventListener('click', () => {
    geocodePreviewRowsProgressively();
    if (q('previewGeocodeActions')) q('previewGeocodeActions').classList.add('hidden');
  });

  q('importFilterText').addEventListener('input', () => {
    applyImportGridFilters();
  });
  q('importFilterStatus').addEventListener('change', () => {
    applyImportGridFilters();
  });

  q('btnBulkApplyType').addEventListener('click', () => {
    const value = String(q('bulkTypeValue').value || '').trim();
    applyBulkToFilteredRows('replace', 'supplier_type', value);
  });

  q('btnBulkAppendLabel').addEventListener('click', () => {
    const value = String(q('bulkLabelValue').value || '').trim();
    applyBulkToFilteredRows('append', 'labels', value);
  });

  q('btnBulkAppendActivity').addEventListener('click', () => {
    const value = String(q('bulkActivityValue').value || '').trim();
    applyBulkToFilteredRows('append', 'activities', value);
  });

  q('btnBulkApplyCountry').addEventListener('click', () => {
    const value = String(q('bulkCountryValue').value || '').trim();
    applyBulkToFilteredRows('replace', 'country', value);
  });

  document.querySelectorAll('[data-map-close]').forEach(el => {
    el.addEventListener('click', () => closeMapEditor());
  });

  q('btnMapEditorSave').addEventListener('click', () => {
    const rowIndex = mapEditorState.rowIndex;
    if (rowIndex === null || !mapEditorState.marker) {
      closeMapEditor();
      return;
    }

    const point = mapEditorState.marker.getLatLng();
    const latInput = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="latitude"]`);
    const lngInput = document.querySelector(`#importConflicts [data-edit-row="${rowIndex}"][data-edit-key="longitude"]`);
    if (latInput && lngInput) {
      latInput.value = Number(point.lat).toFixed(6);
      lngInput.value = Number(point.lng).toFixed(6);
    }

    const row = (state.preview?.rows || []).find(r => Number(r.row_index) === Number(rowIndex));
    if (row && row.payload) {
      row.payload.latitude = Number(point.lat).toFixed(6);
      row.payload.longitude = Number(point.lng).toFixed(6);
      row.payload.geocode_mode = 'manual';
      row.payload.geocode_note = '';
    }

    updateImportGridStatuses();
    applyImportGridFilters();
    closeMapEditor();
  });

  q('btnCommitImport').addEventListener('click', async () => {
    q('importMsg').textContent = '';
    try {
      if (!state.preview) throw new Error('Lance d\'abord une prévisualisation');
      const resolutions = collectConflictResolutionsFromDom();
      const editedRows = collectEditedPreviewRows();
      enrichResolutionsForEditedExistingRows(editedRows, resolutions);
      const clientId = Number(q('importClient').value || 0);

      const commit = await api('admin/import/commit', 'POST', {
        batch_id: state.preview.batch_id,
        client_id: clientId,
        rows: editedRows,
        resolutions,
        auto_geocode: q('importAutoGeocode').checked,
      });
      q('importMsg').textContent = `Import OK - créés: ${commit.created}, mis à jour: ${commit.updated}`;
      state.preview = null;
      state.previewOriginalRows = [];
      q('importConflicts').innerHTML = '';
      q('importSummary').textContent = '';
      if (q('importGeocodeProgress')) q('importGeocodeProgress').textContent = '';
      geocodeProgressAbort = true;
      if (q('importGridToolbar')) q('importGridToolbar').style.display = 'none';
      refreshImportCommitState();
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
        admin_notification_emails: q('visAdminNotificationEmails').value,
        public_assets_base_url: q('visPublicAssetsBaseUrl').value,
        smtp_host: q('visSmtpHost').value,
        smtp_port: q('visSmtpPort').value,
        smtp_encryption: q('visSmtpEncryption').value,
        smtp_username: q('visSmtpUsername').value,
        smtp_password: q('visSmtpPassword').value,
        smtp_from_email: q('visSmtpFromEmail').value,
        smtp_from_name: q('visSmtpFromName').value,
      });
      q('visualMsg').textContent = 'Paramètres visuels enregistrés';
      await loadBootstrap();
    } catch (e) {
      q('visualMsg').textContent = e.message;
    }
  });

  q('btnTestNotification').addEventListener('click', async () => {
    q('visualMsg').textContent = '';
    try {
      await api('admin/notification/test', 'POST', {
        to: q('visSmtpTestTo').value,
      });
      q('visualMsg').textContent = 'Email de test envoyé';
    } catch (e) {
      q('visualMsg').textContent = e.message;
    }
  });

  q('requestStatusFilter').addEventListener('change', async () => {
    await loadChangeRequests();
  });

  q('requestClientFilter').addEventListener('change', async () => {
    await loadChangeRequests();
  });

  q('requestSortBy').addEventListener('change', async () => {
    await loadChangeRequests();
  });

  q('btnReloadRequests').addEventListener('click', async () => {
    await loadChangeRequests();
  });

  q('btnBulkApproveRequests').addEventListener('click', async () => {
    await window.reviewChangeRequestsBulk('approved');
  });

  q('btnBulkApproveSelected').addEventListener('click', async () => {
    await window.reviewSelectedChangeRequestsBulk('approved');
  });

  q('btnBulkRejectSelected').addEventListener('click', async () => {
    await window.reviewSelectedChangeRequestsBulk('rejected');
  });

  q('btnExportRequestsCsv').addEventListener('click', () => {
    downloadRequestsCsv();
  });

  q('btnReloadAuditLogs').addEventListener('click', async () => {
    q('auditMsg').textContent = '';
    try {
      await loadBootstrap();
      q('auditMsg').textContent = 'Traces rafraîchies';
    } catch (e) {
      q('auditMsg').textContent = e.message;
    }
  });

  q('auditFilterText').addEventListener('input', () => {
    renderAuditLogs();
  });

  q('auditFilterActorType').addEventListener('change', () => {
    renderAuditLogs();
  });

  q('auditFilterKind').addEventListener('change', () => {
    renderAuditLogs();
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