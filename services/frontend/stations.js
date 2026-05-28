import { CORE, apiGet, apiPost, apiPut, apiDel } from './api.js';
import { toast, openModal, closeModal, shortId, fmtDate } from './ui.js';
import { state, userOptions } from './state.js';

export async function loadStations(filters = {}) {
  const el = document.getElementById('stations-list');
  el.innerHTML = '<div class="loading">Loading…</div>';

  const params = new URLSearchParams();
  if (filters.name)        params.set('name',         filters.name);
  if (filters.createdFrom) params.set('created_from', filters.createdFrom);
  if (filters.createdTo)   params.set('created_to',   filters.createdTo);

  const qs = params.toString();
  try {
    const data = await apiGet(`${CORE}/stations${qs ? '?' + qs : ''}`);
    state.stations = Array.isArray(data) ? data : (data?.data ?? []);
    renderStations(el, state.stations);
  } catch (err) {
    el.innerHTML = `<div class="empty">Could not load stations: ${err.message}</div>`;
  }
}

export function currentStationFilters() {
  return {
    name:        document.getElementById('sf-name').value.trim(),
    createdFrom: document.getElementById('sf-created-from').value,
    createdTo:   document.getElementById('sf-created-to').value,
  };
}

function renderStations(el, list) {
  if (!list.length) {
    el.innerHTML = '<div class="empty">No stations found.</div>';
    return;
  }
  el.innerHTML = `
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Location</th>
            <th>Sensor</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Owner</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          ${list.map(s => `
            <tr>
              <td>
                <div>${s.stationName}</div>
                <div class="dim mono">${shortId(s.id)}</div>
              </td>
              <td class="mono dim">${s.latitude}, ${s.longitude}</td>
              <td>${s.sensorModel ?? '—'}</td>
              <td><span class="badge ${s.status === 'active' ? 'b-active' : 'b-inactive'}">${s.status ?? '—'}</span></td>
              <td>${fmtDate(s.createdAt)}</td>
              <td class="dim mono">${shortId(s.ownerId)}</td>
              <td>
                <div class="actions">
                  <button class="btn btn-sm btn-secondary" onclick='openEditStationModal(${JSON.stringify(s)})'>Edit</button>
                  <button class="btn btn-sm btn-danger"    onclick="deleteStation('${s.id}')">Delete</button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

export function openNewStationModal() {
  if (!state.users.length) {
    toast('No users loaded — visit the Users tab first', 'err');
    return;
  }
  openModal('New Station', `
    <form class="form">
      <div class="form-group">
        <label>Station Name</label>
        <input type="text" name="station_name" required placeholder="Estación Central BA">
      </div>
      <div class="form-group">
        <label>Owner</label>
        <select name="owner_id" required>
          <option value="">Select owner…</option>
          ${userOptions()}
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Latitude</label>
          <input type="number" name="latitude" step="0.0001" required placeholder="-34.6037">
        </div>
        <div class="form-group">
          <label>Longitude</label>
          <input type="number" name="longitude" step="0.0001" required placeholder="-58.3816">
        </div>
      </div>
      <div class="form-group">
        <label>Sensor Model</label>
        <input type="text" name="sensor_model" required placeholder="Davis Vantage Pro2">
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Create</button>
      </div>
    </form>
  `, async (fd) => {
    try {
      await apiPost(`${CORE}/stations`, {
        owner_id:     fd.get('owner_id'),
        station_name: fd.get('station_name'),
        latitude:     parseFloat(fd.get('latitude')),
        longitude:    parseFloat(fd.get('longitude')),
        sensor_model: fd.get('sensor_model'),
      });
      toast('Station created');
      closeModal();
      loadStations();
    } catch (err) {
      toast(err.message, 'err');
    }
  });
}

export function openEditStationModal(station) {
  if (!state.users.length) {
    toast('No users loaded — visit the Users tab first', 'err');
    return;
  }
  openModal('Edit Station', `
    <form class="form">
      <div class="form-group">
        <label>Station Name</label>
        <input type="text" name="station_name" required value="${station.stationName}">
      </div>
      <div class="form-group">
        <label>Owner</label>
        <select name="owner_id" required>
          ${userOptions(station.ownerId)}
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Latitude</label>
          <input type="number" name="latitude" step="0.0001" required value="${station.latitude}">
        </div>
        <div class="form-group">
          <label>Longitude</label>
          <input type="number" name="longitude" step="0.0001" required value="${station.longitude}">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Sensor Model</label>
          <input type="text" name="sensor_model" required value="${station.sensorModel}">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="active"   ${station.status === 'active'   ? 'selected' : ''}>Active</option>
            <option value="inactive" ${station.status === 'inactive' ? 'selected' : ''}>Inactive</option>
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  `, async (fd) => {
    try {
      await apiPut(`${CORE}/stations/${station.id}`, {
        owner_id:     fd.get('owner_id'),
        station_name: fd.get('station_name'),
        latitude:     parseFloat(fd.get('latitude')),
        longitude:    parseFloat(fd.get('longitude')),
        sensor_model: fd.get('sensor_model'),
        status:       fd.get('status'),
      });
      toast('Station updated');
      closeModal();
      loadStations();
    } catch (err) {
      toast(err.message, 'err');
    }
  });
}

export async function deleteStation(id) {
  if (!confirm('Delete this station?')) return;
  try {
    await apiDel(`${CORE}/stations/${id}`);
    toast('Station deleted');
    state.stations = state.stations.filter(s => s.id !== id);
    loadStations();
  } catch (err) {
    toast(err.message, 'err');
  }
}

window.openEditStationModal = openEditStationModal;
window.deleteStation = deleteStation;
