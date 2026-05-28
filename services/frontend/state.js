export const state = {
  users:    [],
  stations: [],
};

export function stationOptions(selected = '') {
  return state.stations
    .map(s => `<option value="${s.id}" ${s.id === selected ? 'selected' : ''}>${s.stationName}</option>`)
    .join('');
}

export function userOptions(selected = '') {
  return state.users
    .map(u => `<option value="${u.id}" ${u.id === selected ? 'selected' : ''}>${u.firstName} ${u.lastName} — ${u.email}</option>`)
    .join('');
}
