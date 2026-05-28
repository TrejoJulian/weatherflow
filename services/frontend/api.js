export const CORE    = '/api/core';
export const MONITOR = '/api/monitor';

async function apiFetch(url, options = {}) {
  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    ...options,
  });
  const text = await res.text();
  const body = text ? JSON.parse(text) : null;
  if (!res.ok) throw new Error(body?.message || `HTTP ${res.status}`);
  return body;
}

export const apiGet  = (url)       => apiFetch(url);
export const apiPost = (url, data) => apiFetch(url, { method: 'POST',   body: JSON.stringify(data) });
export const apiPut  = (url, data) => apiFetch(url, { method: 'PUT',    body: JSON.stringify(data) });
export const apiDel  = (url)       => apiFetch(url, { method: 'DELETE' });
