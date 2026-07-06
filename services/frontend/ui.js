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

// ── Client-side pagination ──
export const PAGE_SIZE = 10;

// Renders a paginated list into `el`. Slices `list` into pages of PAGE_SIZE and
// draws Prev/Next controls under the table. Changing page re-renders over the
// already-loaded list — no refetch. `getPage`/`setPage` hold the per-list page.
export function renderList(el, list, { renderTable, emptyMessage, getPage, setPage }) {
  function draw() {
    if (!list.length) {
      el.innerHTML = `<div class="empty">${emptyMessage}</div>`;
      return;
    }
    const pages = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
    const page  = Math.min(Math.max(1, getPage()), pages);
    setPage(page);

    const start = (page - 1) * PAGE_SIZE;
    el.innerHTML = renderTable(list.slice(start, start + PAGE_SIZE)) + pager(page, pages);

    const prev  = el.querySelector('[data-page="prev"]');
    const next  = el.querySelector('[data-page="next"]');
    const input = el.querySelector('[data-page="input"]');
    if (prev) prev.addEventListener('click', () => { setPage(page - 1); draw(); });
    if (next) next.addEventListener('click', () => { setPage(page + 1); draw(); });
    if (input) input.addEventListener('change', () => {
      const parsed = parseInt(input.value, 10);
      const target = Number.isNaN(parsed) ? page : Math.min(Math.max(1, parsed), pages);
      setPage(target);
      draw();
    });
  }
  draw();
}

function pager(page, pages) {
  if (pages <= 1) return '';
  return `
    <div class="pager">
      <button class="btn btn-sm btn-ghost" data-page="prev" ${page <= 1 ? 'disabled' : ''}>Prev</button>
      <span class="dim mono">Page
        <input class="page-input" type="number" min="1" max="${pages}" value="${page}" data-page="input" aria-label="Go to page">
        of ${pages}</span>
      <button class="btn btn-sm btn-ghost" data-page="next" ${page >= pages ? 'disabled' : ''}>Next</button>
    </div>
  `;
}

// Exposed for inline onclick handlers in dynamically generated HTML
window.closeModal = closeModal;
