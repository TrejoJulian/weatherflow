export function toast(msg, kind = 'ok') {
  const el = document.createElement('div');
  el.className = `toast toast-${kind}`;
  el.textContent = msg;
  document.getElementById('toasts').appendChild(el);
  setTimeout(() => el.remove(), 3200);
}

export function openModal(title, html, onSubmit) {
  document.getElementById('modal-title').textContent = title;
  document.getElementById('modal-body').innerHTML = html;
  document.getElementById('modal').classList.remove('hidden');

  const form = document.querySelector('#modal-body form');
  if (form && onSubmit) {
    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      await onSubmit(new FormData(form));
    });
  }
}

export function closeModal() {
  document.getElementById('modal').classList.add('hidden');
}

export function shortId(id) {
  return id ? id.slice(0, 8) + '…' : '—';
}

export function fmtDate(str) {
  if (!str) return '—';
  return new Date(str).toLocaleString();
}

export function alertBadges(alertTypes) {
  if (!alertTypes || alertTypes.length === 0) return '<span class="badge b-none">—</span>';

  const map = {
    'None':              'b-none',
    'Extreme Heat':      'b-heat',
    'Frost':             'b-frost',
    'Storm':             'b-storm',
    'Critical Humidity': 'b-humidity',
  };

  const filtered = alertTypes.filter(t => t !== 'None');
  if (filtered.length === 0) return '<span class="badge b-none">—</span>';

  return filtered.map(label => {
    const cls = map[label] || 'b-none';
    return `<span class="badge ${cls}">${label}</span>`;
  }).join('');
}

// Exposed for inline onclick handlers in dynamically generated HTML
window.closeModal = closeModal;
