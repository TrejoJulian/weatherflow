import { CORE, apiGet } from './api.js';
import { closeModal } from './ui.js';
import { state } from './state.js';
import { loadMeasurements, currentFilters, openNewMeasurementModal } from './measurements.js';
import { loadStations, openNewStationModal, currentStationFilters } from './stations.js';
import { loadUsers, openNewUserModal } from './users.js';

async function preload() {
  try {
    const [users, stations] = await Promise.all([
      apiGet(`${CORE}/users`),
      apiGet(`${CORE}/stations`),
    ]);
    state.users    = Array.isArray(users)    ? users    : [];
    state.stations = Array.isArray(stations) ? stations : [];
  } catch (_) { /* silent — tabs will reload on demand */ }
}

function switchTab(name) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
  document.getElementById(`tab-${name}`).classList.add('active');
  document.querySelector(`[data-tab="${name}"]`).classList.add('active');

  if (name === 'measurements') loadMeasurements();
  if (name === 'stations')     loadStations();
  if (name === 'users')        loadUsers();
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', () => switchTab(btn.dataset.tab));
  });

  document.getElementById('btn-new-measurement').addEventListener('click', openNewMeasurementModal);
  document.getElementById('btn-new-station').addEventListener('click', openNewStationModal);
  document.getElementById('btn-new-user').addEventListener('click', openNewUserModal);

  document.getElementById('btn-apply-filters').addEventListener('click', () => loadMeasurements(currentFilters()));
  document.getElementById('btn-clear-filters').addEventListener('click', () => {
    ['f-station-name', 'f-date-from', 'f-date-to', 'f-temp-min', 'f-temp-max',
     'f-humidity-min', 'f-humidity-max', 'f-pressure-min', 'f-pressure-max'].forEach(id => {
      document.getElementById(id).value = '';
    });
    document.getElementById('f-alert-type').value = '';
    loadMeasurements();
  });

  document.getElementById('btn-apply-station-filters').addEventListener('click', () => loadStations(currentStationFilters()));
  document.getElementById('btn-clear-station-filters').addEventListener('click', () => {
    ['sf-name', 'sf-created-from', 'sf-created-to'].forEach(id => {
      document.getElementById(id).value = '';
    });
    loadStations();
  });

  document.getElementById('modal-close').addEventListener('click', closeModal);
  document.getElementById('modal-backdrop').addEventListener('click', closeModal);

  preload().then(() => switchTab('measurements'));
});
