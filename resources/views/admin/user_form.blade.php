{{-- resources/views/admin/user_form.blade.php --}}
@extends('layouts.admin')

@section('title', 'Admin – User form')
@section('sidebar_users_active', 'active')

@section('content')
  <div class="page-header">
    <div>
      <h1 class="page-title" id="formTitle">User form</h1>
      <p class="page-subtitle">Create a new user or edit an existing one.</p>
    </div>
  </div>

  <div class="page-content">
    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <section class="card">
      <form class="form-grid-2" onsubmit="return false;">
        <div class="form-group">
          <label for="user_name">Full name</label>
          <input id="user_name" type="text" class="form-control" placeholder="e.g. Alex Nguyen">
        </div>

        <div class="form-group">
          <label for="user_email">Email</label>
          <input id="user_email" type="email" class="form-control" placeholder="name@example.com">
        </div>

        <div class="form-group">
          <label for="user_password">Password</label>
          <input id="user_password" type="password" class="form-control" placeholder="Set/reset password">
          <div style="font-size:12px;color:#6b7280;margin-top:6px;">
            (When editing: leave empty to keep current password)
          </div>
        </div>

        <div class="form-group">
          <label for="user_password_confirm">Confirm password</label>
          <input id="user_password_confirm" type="password" class="form-control" placeholder="Re-type password">
        </div>

        <div class="form-group form-group-full">
          <label for="user_bio">Short bio (optional)</label>
          <textarea id="user_bio" rows="2" class="form-control" placeholder="Short introduction..."></textarea>
        </div>

        <div class="form-group">
          <label for="user_role">Role</label>
          <select id="user_role" class="form-control">
            <option value="user">User</option>
            <option value="expert">Expert</option>
            <option value="admin">Admin</option>
          </select>
          <p style="font-size:12px;color:#6b7280;margin-top:6px;">
            Note: At least one Admin must remain in the system.
          </p>
        </div>

        <div class="form-group">
          <label for="user_status">Status</label>
          <select id="user_status" class="form-control">
            <option value="active">Active</option>
            <option value="blocked">Blocked</option>
          </select>
        </div>

        <div class="form-actions form-group-full">
          <button type="button" class="btn btn-primary" id="btnSave">Save user</button>
          <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ url('/admin/users') }}'">
            Cancel
          </button>
        </div>
      </form>
    </section>
  </div>

  <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const API = '/api/admin/users';

    const alertBox = document.getElementById('page-alert');
    const formTitle = document.getElementById('formTitle');

    const inpName = document.getElementById('user_name');
    const inpEmail = document.getElementById('user_email');
    const inpPass = document.getElementById('user_password');
    const inpPass2 = document.getElementById('user_password_confirm');
    const inpBio = document.getElementById('user_bio');
    const selRole = document.getElementById('user_role');
    const selStatus = document.getElementById('user_status');
    const btnSave = document.getElementById('btnSave');

    function showError(msg) {
      alertBox.style.display = 'block';
      alertBox.textContent = msg;
    }
    function hideError() {
      alertBox.style.display = 'none';
      alertBox.textContent = '';
    }

    function getUserIdFromQuery() {
      const url = new URL(window.location.href);
      const id = url.searchParams.get('user_id');
      const n = Number(id);
      return Number.isFinite(n) && n > 0 ? n : null;
    }

    async function loadUser(id) {
      hideError();
      const res = await fetch(`${API}/${id}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load user.');
        return null;
      }

      return json.data;
    }

    function buildPayload(isEdit) {
      const name = String(inpName.value ?? '').trim();
      const email = String(inpEmail.value ?? '').trim();
      const bio = String(inpBio.value ?? '').trim();
      const role = String(selRole.value ?? 'user');
      const status = String(selStatus.value ?? 'active');

      const password = String(inpPass.value ?? '');
      const password_confirmation = String(inpPass2.value ?? '');

      const payload = { name, email, role, status, bio: bio === '' ? null : bio };

      // create: password required (backend enforce)
      // edit: password optional
      if (password !== '' || password_confirmation !== '') {
        payload.password = password;
        payload.password_confirmation = password_confirmation;
      }

      return payload;
    }

    async function saveUser() {
      hideError();
      const userId = getUserIdFromQuery();
      const isEdit = !!userId;

      const payload = buildPayload(isEdit);

      const res = await fetch(isEdit ? `${API}/${userId}` : API, {
        method: isEdit ? 'PUT' : 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        // Laravel validation errors -> show message
        showError(json?.message || 'Save failed.');
        return;
      }

      window.location.href = '{{ url('/admin/users') }}';
    }

    document.addEventListener('DOMContentLoaded', async () => {
      const userId = getUserIdFromQuery();
      if (userId) {
        formTitle.textContent = `Edit user #${userId}`;
        btnSave.textContent = 'Update user';

        const u = await loadUser(userId);
        if (!u) return;

        inpName.value = u.name ?? '';
        inpEmail.value = u.email ?? '';
        inpBio.value = u.bio ?? '';
        selRole.value = u.role ?? 'user';
        selStatus.value = u.status ?? 'active';
      } else {
        formTitle.textContent = 'Create user';
        btnSave.textContent = 'Create user';
      }

      btnSave.addEventListener('click', saveUser);
    });
  </script>
@endsection
