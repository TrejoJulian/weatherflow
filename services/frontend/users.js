import { CORE, apiGet, apiPost, apiPut, apiDel } from './api.js';
import { toast, openModal, closeModal, shortId } from './ui.js';
import { state, stationOptions } from './state.js';

export async function loadUsers() {
  const el = document.getElementById('users-list');
  el.innerHTML = '<div class="loading">Loading…</div>';
  try {
    const data = await apiGet(`${CORE}/users`);
    state.users = Array.isArray(data) ? data : (data?.data ?? []);
    renderUsers(el, state.users);
  } catch (err) {
    el.innerHTML = `<div class="empty">Could not load users: ${err.message}</div>`;
  }
}

function renderUsers(el, list) {
  if (!list.length) {
    el.innerHTML = '<div class="empty">No users found.</div>';
    return;
  }
  el.innerHTML = `
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Subscriptions</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          ${list.map(u => `
            <tr>
              <td>
                <div>${u.firstName} ${u.lastName}</div>
                <div class="dim mono">${shortId(u.id)}</div>
              </td>
              <td>${u.email}</td>
              <td>
                <div class="sub-tags">
                  ${renderSubscriptions(u.subscriptions)}
                </div>
              </td>
              <td>
                <div class="actions">
                  <button class="btn btn-sm btn-secondary" onclick='openEditUserModal(${JSON.stringify(u)})'>Edit</button>
                  <button class="btn btn-sm btn-secondary" onclick="openSubscribeModal('${u.id}')">Subscribe</button>
                  <button class="btn btn-sm btn-danger"    onclick="deleteUser('${u.id}')">Delete</button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

function renderSubscriptions(subs) {
  if (!subs || subs.length === 0) return '<span style="color:var(--muted)">—</span>';
  return subs.map(sub => `<span class="sub-tag" title="${sub.stationId}">${sub.name ?? shortId(sub.stationId)}</span>`).join('');
}

export function openNewUserModal() {
  openModal('New User', `
    <form class="form">
      <div class="form-row">
        <div class="form-group">
          <label>First Name</label>
          <input type="text" name="first_name" required placeholder="Ana">
        </div>
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" name="last_name" required placeholder="García">
        </div>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required placeholder="ana@example.com">
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Create</button>
      </div>
    </form>
  `, async (fd) => {
    try {
      await apiPost(`${CORE}/users`, {
        first_name: fd.get('first_name'),
        last_name:  fd.get('last_name'),
        email:      fd.get('email'),
      });
      toast('User created');
      closeModal();
      loadUsers();
    } catch (err) {
      toast(err.message, 'err');
    }
  });
}

export function openSubscribeModal(userId) {
  if (!state.stations.length) {
    toast('No stations loaded — visit the Stations tab first', 'err');
    return;
  }
  openModal('Subscribe to Station', `
    <form class="form">
      <div class="form-group">
        <label>Station</label>
        <select name="station_id" required>
          <option value="">Select a station…</option>
          ${stationOptions()}
        </select>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Subscribe</button>
      </div>
    </form>
  `, async (fd) => {
    try {
      await apiPost(`${CORE}/users/${userId}/subscriptions`, {
        station_id: fd.get('station_id'),
      });
      toast('Subscribed');
      closeModal();
      loadUsers();
    } catch (err) {
      toast(err.message, 'err');
    }
  });
}

export async function deleteUser(id) {
  if (!confirm('Delete this user?')) return;
  try {
    await apiDel(`${CORE}/users/${id}`);
    toast('User deleted');
    state.users = state.users.filter(u => u.id !== id);
    loadUsers();
  } catch (err) {
    toast(err.message, 'err');
  }
}

export function openEditUserModal(user) {
  openModal('Edit User', `
    <form class="form">
      <div class="form-row">
        <div class="form-group">
          <label>First Name</label>
          <input type="text" name="first_name" required value="${user.firstName}">
        </div>
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" name="last_name" required value="${user.lastName}">
        </div>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required value="${user.email}">
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  `, async (fd) => {
    try {
      await apiPut(`${CORE}/users/${user.id}`, {
        first_name: fd.get('first_name'),
        last_name:  fd.get('last_name'),
        email:      fd.get('email'),
      });
      toast('User updated');
      closeModal();
      loadUsers();
    } catch (err) {
      toast(err.message, 'err');
    }
  });
}

window.openEditUserModal = openEditUserModal;
window.openSubscribeModal = openSubscribeModal;
window.deleteUser = deleteUser;
