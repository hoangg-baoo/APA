{{-- resources/views/admin/users_list.blade.php --}}
@extends('layouts.admin')

@section('title', 'Admin – Users & Roles')
@section('sidebar_users_active', 'active')

@section('content')
  <div class="page-header">
    <div>
      <h1 class="page-title">User accounts</h1>
      <p class="page-subtitle">Manage roles, status, and delete / restore users.</p>
    </div>
    <div class="page-actions">
      <button class="btn btn-primary" onclick="window.location.href='{{ url('/admin/users/create') }}'">
        + Add new user
      </button>
    </div>
  </div>

  <div class="page-content">
    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <div class="admin-toolbar">
      <div class="admin-toolbar-left">
        <input type="text" id="userSearchInput"
               class="form-control admin-search"
               placeholder="Search by name or email...">
      </div>

      <div class="admin-toolbar-right">
        <select id="filterRole" class="form-control" style="width:140px;">
          <option value="">All roles</option>
          <option value="user">User</option>
          <option value="expert">Expert</option>
          <option value="admin">Admin</option>
        </select>

        <select id="filterStatus" class="form-control" style="width:140px;">
          <option value="">All status</option>
          <option value="active">Active</option>
          <option value="blocked">Blocked</option>
        </select>

        <select id="filterDeleted" class="form-control" style="width:170px;">
          <option value="exclude">Deleted: Exclude</option>
          <option value="include">Deleted: Include</option>
          <option value="only">Deleted: Only</option>
        </select>

        <select id="sortBy" class="form-control" style="width:140px;">
          <option value="id">Sort: ID</option>
          <option value="created_at">Sort: Created</option>
          <option value="name">Sort: Name</option>
          <option value="email">Sort: Email</option>
          <option value="role">Sort: Role</option>
          <option value="status">Sort: Status</option>
        </select>

        <select id="sortDir" class="form-control" style="width:120px;">
          <option value="asc">ASC</option>
          <option value="desc">DESC</option>
        </select>
      </div>
    </div>

    <section class="card">
      <div class="table-wrapper">
        <table class="table" id="usersTable">
          <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th>Name</th>
            <th>Email</th>
            <th style="width:140px;">Role</th>
            <th style="width:160px;">Status</th>
            <th style="width:160px;">Registered</th>
            <th style="width:320px;">Actions</th>
          </tr>
          </thead>
          <tbody id="users-tbody">
            <tr><td colspan="7" style="color:#6b7280;">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="plants-pagination" style="margin-top:14px;">
        <div id="users-pagination" class="plants-pager"></div>
      </div>
    </section>
  </div>

  <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const API = '/api/admin/users';

    const alertBox = document.getElementById('page-alert');
    const tbody = document.getElementById('users-tbody');
    const pager = document.getElementById('users-pagination');

    const searchInput = document.getElementById('userSearchInput');
    const filterRole = document.getElementById('filterRole');
    const filterStatus = document.getElementById('filterStatus');
    const filterDeleted = document.getElementById('filterDeleted');
    const sortBy = document.getElementById('sortBy');
    const sortDir = document.getElementById('sortDir');

    const state = {
      page: 1,
      q: '',
      role: '',
      status: '',
      deleted: 'exclude', // exclude|include|only
      per_page: 15,
      sort_by: 'id',
      sort_dir: 'asc',
      last_page: 1,
    };

    function showError(msg) {
      alertBox.style.display = 'block';
      alertBox.textContent = msg;
    }
    function hideError() {
      alertBox.style.display = 'none';
      alertBox.textContent = '';
    }

    function escapeHtml(v) {
      return String(v ?? '').replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }

    function fmtDate(iso) {
      if (!iso) return '-';
      const d = new Date(iso);
      if (Number.isNaN(d.getTime())) return String(iso);
      return d.toISOString().slice(0, 19).replace('T', ' ');
    }

    function rolePill(role) {
      const r = String(role ?? '').toLowerCase();
      if (r === 'admin') return `<span class="role-pill role-pill-admin">Admin</span>`;
      if (r === 'expert') return `<span class="role-pill role-pill-expert">Expert</span>`;
      return `<span class="role-pill">User</span>`;
    }

    function statusBadge(status, deletedAt) {
      if (deletedAt) return `<span class="badge badge-soft">Deleted</span>`;
      const s = String(status ?? '').toLowerCase();
      if (s === 'blocked') return `<span class="badge badge-hard">Blocked</span>`;
      return `<span class="badge badge-soft-green">Active</span>`;
    }

    async function fetchUsers() {
      hideError();
      tbody.innerHTML = `<tr><td colspan="7" style="color:#6b7280;">Loading...</td></tr>`;
      pager.innerHTML = '';

      const url = new URL(API, window.location.origin);
      url.searchParams.set('page', String(state.page));
      url.searchParams.set('per_page', String(state.per_page));
      url.searchParams.set('sort_by', state.sort_by);
      url.searchParams.set('sort_dir', state.sort_dir);
      url.searchParams.set('deleted', state.deleted);

      if (state.q) url.searchParams.set('q', state.q);
      if (state.role) url.searchParams.set('role', state.role);
      if (state.status) url.searchParams.set('status', state.status);

      const res = await fetch(url.toString(), {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load users.');
        tbody.innerHTML = `<tr><td colspan="7" style="color:#6b7280;">No data.</td></tr>`;
        return;
      }

      const payload = json.data;
      const list = Array.isArray(payload?.data) ? payload.data : [];
      state.last_page = payload?.last_page || 1;

      renderRows(list);
      renderPager(payload);
    }

    function renderRows(list) {
      if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="color:#6b7280;">No users found.</td></tr>`;
        return;
      }

      tbody.innerHTML = list.map(u => {
        const id = u.id;
        const name = escapeHtml(u.name ?? '');
        const email = escapeHtml(u.email ?? '');
        const role = String(u.role ?? 'user');
        const status = String(u.status ?? 'active');
        const created = fmtDate(u.created_at);
        const deletedAt = u.deleted_at ? String(u.deleted_at) : '';
        const isDeleted = !!deletedAt;

        const roleSelect = `
          <select class="form-control form-control-sm" ${isDeleted ? 'disabled' : ''}
                  onchange="changeRole(${id}, this.value)">
            <option value="user" ${role==='user'?'selected':''}>User</option>
            <option value="expert" ${role==='expert'?'selected':''}>Expert</option>
            <option value="admin" ${role==='admin'?'selected':''}>Admin</option>
          </select>
        `;

        const statusSelect = `
          <select class="form-control form-control-sm" ${isDeleted ? 'disabled' : ''}
                  onchange="changeStatus(${id}, this.value)">
            <option value="active" ${status==='active'?'selected':''}>Active</option>
            <option value="blocked" ${status==='blocked'?'selected':''}>Blocked</option>
          </select>
        `;

        const restoreBtn = isDeleted
          ? `<button class="btn btn-secondary btn-xs" onclick="restoreUser(${id}, '${name}', '${email}')">Restore</button>`
          : '';

        const deleteBtn = isDeleted
          ? `<button class="btn btn-secondary btn-xs btn-danger-soft" disabled>Deleted</button>`
          : `<button class="btn btn-secondary btn-xs btn-danger-soft" onclick="deleteUser(${id}, '${name}', '${email}')">Delete</button>`;

        return `
          <tr style="${isDeleted ? 'opacity:0.65;' : ''}">
            <td>${id}</td>
            <td>${name}</td>
            <td>${email}</td>
            <td>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                ${rolePill(role)}
                <div style="min-width:120px;">${roleSelect}</div>
              </div>
            </td>
            <td>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                ${statusBadge(status, deletedAt)}
                <div style="min-width:120px;">${statusSelect}</div>
              </div>
            </td>
            <td>${created}</td>
            <td class="table-actions">
              <button class="btn btn-secondary btn-xs" onclick="editUser(${id})" ${isDeleted ? 'disabled' : ''}>Edit</button>
              <button class="btn btn-secondary btn-xs btn-danger-soft"
                      onclick="quickToggle(${id}, '${status}')"
                      ${isDeleted ? 'disabled' : ''}>
                ${status === 'blocked' ? 'Unblock' : 'Block'}
              </button>
              ${restoreBtn}
              ${deleteBtn}
            </td>
          </tr>
        `;
      }).join('');
    }

    function renderPager(paginate) {
      const current = paginate?.current_page || 1;
      const last = paginate?.last_page || 1;

      const btn = (label, page, disabled=false, active=false) => {
        const cls = ['plants-page', disabled ? 'disabled' : '', active ? 'active' : ''].join(' ').trim();
        return `<a href="javascript:void(0)" class="${cls}" onclick="${disabled ? 'void(0)' : `gotoPage(${page})`}">${label}</a>`;
      };

      let html = '';
      html += btn('«', 1, current === 1);
      html += btn('‹', Math.max(1, current - 1), current === 1);

      const start = Math.max(1, current - 2);
      const end = Math.min(last, current + 2);

      if (start > 1) html += `<span class="plants-page dots">…</span>`;
      for (let i = start; i <= end; i++) html += btn(String(i), i, false, i === current);
      if (end < last) html += `<span class="plants-page dots">…</span>`;

      html += btn('›', Math.min(last, current + 1), current === last);
      html += btn('»', last, current === last);

      pager.innerHTML = html;
    }

    window.gotoPage = function(page) {
      state.page = page;
      fetchUsers();
    };

    window.editUser = function(id) {
      window.location.href = `{{ url('/admin/users/create') }}?user_id=${id}`;
    };

    window.changeRole = async function(id, role) {
      hideError();
      const res = await fetch(`${API}/${id}/role`, {
        method: 'PATCH',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ role }),
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Update role failed.');
        await fetchUsers();
        return;
      }

      await fetchUsers();
    };

    window.changeStatus = async function(id, status) {
      hideError();
      const res = await fetch(`${API}/${id}/status`, {
        method: 'PATCH',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ status }),
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Update status failed.');
        await fetchUsers();
        return;
      }

      await fetchUsers();
    };

    window.quickToggle = async function(id, currentStatus) {
      const next = currentStatus === 'blocked' ? 'active' : 'blocked';
      await window.changeStatus(id, next);
    };

    window.deleteUser = async function(id, name, email) {
      hideError();
      const ok = confirm(`Delete user #${id}?\n\n${name} (${email})\n\nThis is a soft-delete. You can restore later.`);
      if (!ok) return;

      const res = await fetch(`${API}/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Delete failed.');
        return;
      }

      await fetchUsers();
      const rows = tbody.querySelectorAll('tr');
      const isEmpty = rows.length === 1 && rows[0].innerText.includes('No users found');
      if (isEmpty && state.page > 1) {
        state.page -= 1;
        await fetchUsers();
      }
    };

    window.restoreUser = async function(id, name, email) {
      hideError();
      const ok = confirm(`Restore user #${id}?\n\n${name} (${email})`);
      if (!ok) return;

      const res = await fetch(`${API}/${id}/restore`, {
        method: 'PATCH',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Restore failed.');
        return;
      }

      await fetchUsers();
    };

    let searchTimer = null;
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        state.q = String(searchInput.value ?? '').trim();
        state.page = 1;
        fetchUsers();
      }, 250);
    });

    filterRole.addEventListener('change', () => {
      state.role = String(filterRole.value ?? '').trim();
      state.page = 1;
      fetchUsers();
    });

    filterStatus.addEventListener('change', () => {
      state.status = String(filterStatus.value ?? '').trim();
      state.page = 1;
      fetchUsers();
    });

    filterDeleted.addEventListener('change', () => {
      state.deleted = String(filterDeleted.value ?? 'exclude').trim();
      state.page = 1;
      fetchUsers();
    });

    sortBy.addEventListener('change', () => {
      state.sort_by = String(sortBy.value ?? 'id');
      state.page = 1;
      fetchUsers();
    });

    sortDir.addEventListener('change', () => {
      state.sort_dir = String(sortDir.value ?? 'asc');
      state.page = 1;
      fetchUsers();
    });

    document.addEventListener('DOMContentLoaded', () => {
      state.sort_by = sortBy.value;
      state.sort_dir = sortDir.value;
      state.deleted = filterDeleted.value;
      fetchUsers();
    });
  </script>
@endsection
