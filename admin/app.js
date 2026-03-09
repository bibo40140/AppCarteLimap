const apiBase = '../api/index.php?action=';
let state = { clients: [], activities: [], labels: [], suppliers: [], preview: null };

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
    { key: 'email', label: 'Email' },
  ], state.clients, 'editClient');

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

function renderVisualSettings() {
  const s = state.settings || {};
  if (q('visOrgName')) q('visOrgName').value = s.org_name || '';
  if (q('visOrgLogo')) q('visOrgLogo').value = s.org_logo_url || '';
  if (q('visDefaultClient')) q('visDefaultClient').value = s.default_client_icon || '/assets/icons/default-client.svg';
  if (q('visDefaultProducer')) q('visDefaultProducer').value = s.default_producer_icon || '/assets/icons/default-producer.svg';
  if (q('visFarmDirect')) q('visFarmDirect').value = s.farm_direct_icon || 'https://i.imgur.com/DTHAKrv.jpeg';
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
        is_active: true,
      });
      q('clientMsg').textContent = 'Client enregistré';
      q('formClient').reset();
      q('clientId').value = '';
      q('clientLogoPath').value = '';
      q('clientLogoPreview').innerHTML = '';
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

      const ab = await file.arrayBuffer();
      const wb = XLSX.read(ab, { type: 'array' });
      const sheet = wb.Sheets['Fournisseurs'] || wb.Sheets[wb.SheetNames[0]];
      const rows = XLSX.utils.sheet_to_json(sheet, { defval: '' });

      const preview = await api('admin/import/preview', 'POST', {
        client_id: clientId,
        file_name: file.name,
        rows,
      });
      state.preview = preview;
      q('importSummary').textContent = `Nouveaux: ${preview.summary.new} | Existants: ${preview.summary.existing} | Lignes en conflit: ${preview.summary.conflicts} | Erreurs: ${preview.summary.errors}`;

      const conflictRows = (preview.rows || []).filter(r => (r.conflicts || []).length > 0);
      if (!conflictRows.length) {
        q('importConflicts').innerHTML = '<div class="muted">Aucun conflit détecté.</div>';
      } else {
        q('importConflicts').innerHTML = conflictRows.map(row => {
          const blocks = row.conflicts.map(c => `
            <div class="conflict">
              <div><strong>${c.field}</strong></div>
              <div class="muted">Existant: ${c.existing}</div>
              <div class="muted">Import: ${c.incoming}</div>
              <label>Résolution
                <select data-row="${row.row_index}" data-field="${c.field}">
                  <option value="keep_existing">Garder existant</option>
                  <option value="replace_existing">Remplacer par import</option>
                </select>
              </label>
            </div>
          `).join('');
          return `<div class="card"><strong>Ligne ${row.row_index + 1} - ${row.name}</strong>${blocks}</div>`;
        }).join('');
      }
      q('importMsg').textContent = 'Prévisualisation terminée';
    } catch (e) {
      q('importMsg').textContent = e.message;
    }
  });

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
      const clientId = Number(q('importClient').value || 0);

      const commit = await api('admin/import/commit', 'POST', {
        batch_id: state.preview.batch_id,
        client_id: clientId,
        rows: state.preview.rows,
        resolutions,
      });
      q('importMsg').textContent = `Import OK - créés: ${commit.created}, mis à jour: ${commit.updated}`;
      state.preview = null;
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