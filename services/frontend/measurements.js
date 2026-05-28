import { MONITOR, apiGet, apiPost, apiPut, apiDel } from './api.js';
import { toast, openModal, closeModal, shortId, fmtDate, alertBadges } from './ui.js';
import { state, stationOptions } from './state.js';

export async function loadMeasurements(filters = {}) {
  const el = document.getElementById('measurements-list');
  el.innerHTML = '<div class="loading">Loading…</div>';

  const params = new URLSearchParams();
  if (filters.stationName)  params.set('station_name', filters.stationName);
  if (filters.dateFrom)     params.set('date_from',    filters.dateFrom);
  if (filters.dateTo)       params.set('date_to',      filters.dateTo);
  if (filters.tempMin  !== undefined && filters.tempMin  !== '') params.set('temp_min',     filters.tempMin);
  if (filters.tempMax  !== undefined && filters.tempMax  !== '') params.set('temp_max',     filters.tempMax);
  if (filters.humMin   !== undefined && filters.humMin   !== '') params.set('humidity_min', filters.humMin);
  if (filters.humMax   !== undefined && filters.humMax   !== '') params.set('humidity_max', filters.humMax);
  if (filters.pressMin !== undefined && filters.pressMin !== '') params.set('pressure_min', filters.pressMin);
  if (filters.pressMax !== undefined && filters.pressMax !== '') params.set('pressure_max', filters.pressMax);
  if (filters.alertOnly) params.set('alert',      'true');
  if (filters.alertType) params.set('alert_type', filters.alertType);

  const qs = params.toString();
  try {
    const data = await apiGet(`${MONITOR}/measurements${qs ? '?' + qs : ''}`);
    const list = Array.isArray(data) ? data : (data?.data ?? []);
    renderMeasurements(el, list);
  } catch (err) {
    el.innerHTML = `<div class="empty">Could not load measurements: ${err.message}</div>`;
  }
}

function renderMeasurements(el, list) {
  if (!list.length) {
    el.innerHTML = '<div class="empty">No measurements found.</div>';
    return;
  }
  el.innerHTML = `
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Station</th>
            <th>Temp (°C)</th>
            <th>Humidity (%)</th>
            <th>Pressure (hPa)</th>
            <th>Reported At</th>
            <th>Alerts</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          ${list.map(m => `
            <tr>
              <td>
                <div>${m.stationName ?? '—'}</div>
                <div class="dim mono">${shortId(m.id)}</div>
              </td>
              <td class="mono">${m.temperature ?? '—'}</td>
              <td class="mono">${m.humidity ?? '—'}</td>
              <td class="mono">${m.atmosphericPressure ?? '—'}</td>
              <td>${fmtDate(m.reportedAt)}</td>
              <td>${alertBadges(m.alertTypes)}</td>
              <td>
                <div class="actions">
                  <button class="btn btn-sm btn-secondary" onclick='openEditMeasurementModal(${JSON.stringify(m)})'>Edit</button>
                  <button class="btn btn-sm btn-danger"    onclick="deleteMeasurement('${m.id}')">Delete</button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

export async function deleteMeasurement(id) {
  if (!confirm('Delete this measurement?')) return;
  try {
    await apiDel(`${MONITOR}/measurements/${id}`);
    toast('Measurement deleted');
    loadMeasurements(currentFilters());
  } catch (err) {
    toast(err.message, 'err');
  }
}

export function currentFilters() {
  const alertSel = document.getElementById('f-alert-type').value;
  return {
    stationName: document.getElementById('f-station-name').value.trim(),
    dateFrom:    isoOrEmpty(document.getElementById('f-date-from').value),
    dateTo:      isoOrEmpty(document.getElementById('f-date-to').value),
    tempMin:     document.getElementById('f-temp-min').value,
    tempMax:     document.getElementById('f-temp-max').value,
    humMin:      document.getElementById('f-humidity-min').value,
    humMax:      document.getElementById('f-humidity-max').value,
    pressMin:    document.getElementById('f-pressure-min').value,
    pressMax:    document.getElementById('f-pressure-max').value,
    alertOnly:   alertSel !== '',
    alertType:   alertSel === 'alert_only' ? '' : alertSel,
  };
}

function isoOrEmpty(localVal) {
  return localVal ? new Date(localVal).toISOString() : '';
}

export function openNewMeasurementModal() {
  if (!state.stations.length) {
    toast('No stations loaded — visit the Stations tab first', 'err');
    return;
  }
  const now = new Date().toISOString().slice(0, 16);
  openModal('New Measurement', `
    <form class="form">
      <div class="form-group">
        <label>Station</label>
        <select name="station_id" required>
          <option value="">Select a station…</option>
          ${stationOptions()}
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Temperature (°C)</label>
          <input type="number" name="temperature" step="0.1" required placeholder="22.5">
        </div>
        <div class="form-group">
          <label>Humidity (%)</label>
          <input type="number" name="humidity" step="0.1" min="0" max="100" required placeholder="65.0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Pressure (hPa)</label>
          <input type="number" name="atmospheric_pressure" step="0.01" required placeholder="1013.0">
        </div>
        <div class="form-group">
          <label>Reported At</label>
          <input type="datetime-local" name="reported_at" required value="${now}">
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Register</button>
      </div>
    </form>
  `, async (fd) => {
    try {
      await apiPost(`${MONITOR}/measurements`, {
        station_id:           fd.get('station_id'),
        temperature:          parseFloat(fd.get('temperature')),
        humidity:             parseFloat(fd.get('humidity')),
        atmospheric_pressure: parseFloat(fd.get('atmospheric_pressure')),
        reported_at:          new Date(fd.get('reported_at')).toISOString(),
      });
      toast('Measurement registered');
      closeModal();
      loadMeasurements();
    } catch (err) {
      toast(err.message, 'err');
    }
  });
}

export function openEditMeasurementModal(measurement) {
  const reportedAt = measurement.reportedAt
    ? new Date(measurement.reportedAt).toISOString().slice(0, 16)
    : '';
  openModal('Edit Measurement', `
    <form class="form">
      <div class="form-group">
        <label>Station</label>
        <input type="text" value="${measurement.stationName ?? '—'}" disabled>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Temperature (°C)</label>
          <input type="number" name="temperature" step="0.1" required value="${measurement.temperature}">
        </div>
        <div class="form-group">
          <label>Humidity (%)</label>
          <input type="number" name="humidity" step="0.1" min="0" max="100" required value="${measurement.humidity}">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Pressure (hPa)</label>
          <input type="number" name="atmospheric_pressure" step="0.01" required value="${measurement.atmosphericPressure}">
        </div>
        <div class="form-group">
          <label>Reported At</label>
          <input type="datetime-local" name="reported_at" required value="${reportedAt}">
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  `, async (fd) => {
    try {
      await apiPut(`${MONITOR}/measurements/${measurement.id}`, {
        temperature:          parseFloat(fd.get('temperature')),
        humidity:             parseFloat(fd.get('humidity')),
        atmospheric_pressure: parseFloat(fd.get('atmospheric_pressure')),
        reported_at:          new Date(fd.get('reported_at')).toISOString(),
      });
      toast('Measurement updated');
      closeModal();
      loadMeasurements(currentFilters());
    } catch (err) {
      toast(err.message, 'err');
    }
  });
}

window.deleteMeasurement = deleteMeasurement;
window.openEditMeasurementModal = openEditMeasurementModal;
