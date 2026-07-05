import { CORE, MONITOR, apiGet } from './api.js';
import { openModal, closeModal, toast, fmtDate } from './ui.js';

export async function openStationReportsModal(station) {
  openModal(`Reports — ${station.stationName}`, '<div class="loading">Loading…</div>');
  try {
    const [daily, weekly] = await Promise.all([
      apiGet(`${MONITOR}/reports/avg/day?station_id=${station.id}`),
      apiGet(`${MONITOR}/reports/avg/week?station_id=${station.id}`),
    ]);
    document.getElementById('modal-body').innerHTML = renderReports(daily, weekly);
    loadCurrentTemp(station.id);
  } catch (err) {
    toast(err.message, 'err');
    closeModal();
  }
}

async function loadCurrentTemp(stationId) {
  const slot = document.getElementById('current-temp-slot');
  if (!slot) return;
  try {
    const current = await apiGet(`${CORE}/reports/current-temp/${stationId}`);
    slot.innerHTML = renderCurrentTemp(current);
  } catch (err) {
    slot.innerHTML = `
      <label>Current temperature</label>
      <div class="dim">Current temperature unavailable: ${err.message}</div>
    `;
  }
}

function renderReports(daily, weekly) {
  return `
    <div class="form">
      <div id="current-temp-slot" class="form-group">
        <label>Current temperature</label>
        <div class="loading">Loading…</div>
      </div>
      ${reportBlock('Daily average (last 24 h)', daily)}
      ${reportBlock('Weekly average (last 7 days)', weekly)}
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Close</button>
      </div>
    </div>
  `;
}

function renderCurrentTemp(current) {
  const cached = current.stale === true || current.source === 'fallback-cache';
  const badge = cached
    ? '<div class="dim">cached reading — provider unavailable</div>'
    : '<div class="dim">live</div>';

  return `
    <label>Current temperature</label>
    <div class="mono">${current.temperature.toFixed(1)} °C</div>
    ${badge}
    <div class="dim mono">${fmtDate(current.reported_at)}</div>
  `;
}

function reportBlock(label, report) {
  const value = report.averageTemperature === null
    ? `<div class="dim">${report.message ?? 'No measurements in this window.'}</div>`
    : `<div class="mono">${report.averageTemperature.toFixed(1)} °C</div>`;

  return `
    <div class="form-group">
      <label>${label}</label>
      ${value}
      <div class="dim mono">${fmtDate(report.from)} → ${fmtDate(report.to)}</div>
    </div>
  `;
}

window.openStationReportsModal = openStationReportsModal;
