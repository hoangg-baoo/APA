{{-- resources/views/admin/plants_list.blade.php --}}
@extends('layouts.admin')

@section('title', 'Admin – Plant Library')
@section('sidebar_plants_active', 'active')

@section('content')
  <div class="page-header">
    <div>
      <h1 class="page-title">Master Plant Library</h1>
      <p class="page-subtitle">
        Central list of plants used by all tanks and the image retrieval module.
      </p>
    </div>
    <div class="page-actions">
      <button class="btn btn-primary" onclick="window.location.href='{{ url('/admin/plants/create') }}'">
        + Add new plant
      </button>
    </div>
  </div>

  <div class="page-content">
    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <div class="admin-toolbar">
      <div class="admin-toolbar-left">
        <input type="text" class="form-control admin-search" id="plantSearchInput" placeholder="Search by name...">
      </div>
      <div class="admin-toolbar-right">
        <select id="sortDir" class="form-control" style="width:170px;">
          <option value="asc">ID: 1 → ↑ (ASC)</option>
          <option value="desc">ID: newest ↓ (DESC)</option>
        </select>

        <select id="filterDifficulty" class="form-control" style="width:150px;">
          <option value="">All difficulty</option>
          <option value="easy">Easy</option>
          <option value="medium">Medium</option>
          <option value="hard">Hard</option>
        </select>

        <select id="filterLight" class="form-control" style="width:150px;">
          <option value="">All light levels</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
        </select>
      </div>
    </div>

    <section class="card">
      <div class="table-wrapper">
        <table class="table" id="plantTable">
          <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th style="width:90px;">Image</th>
            <th>Name</th>
            <th>Difficulty</th>
            <th>Light</th>
            <th>pH range</th>
            <th>Temp (°C)</th>
            <th style="width:160px;">Actions</th>
          </tr>
          </thead>
          <tbody id="plants-tbody">
            <tr><td colspan="8" style="color:#6b7280;">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="plants-pagination" style="margin-top:14px;">
        <div id="plants-pagination" class="plants-pager"></div>
      </div>
    </section>
  </div>

  <script>
    const CSRF = @json(csrf_token());
    const API = '/api/admin/plants';

    const alertBox = document.getElementById('page-alert');
    const tbody = document.getElementById('plants-tbody');
    const pager = document.getElementById('plants-pagination');

    const searchInput = document.getElementById('plantSearchInput');
    const sortDir = document.getElementById('sortDir');
    const filterDifficulty = document.getElementById('filterDifficulty');
    const filterLight = document.getElementById('filterLight');

    const state = {
      page: 1,
      q: '',
      difficulty: '',
      light_level: '',
      dir: 'asc',     // ✅ default ASC để ID 1 lên trước
      per_page: 10,
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

    function badgeDifficulty(val) {
      const v = String(val ?? '').toLowerCase();
      if (v === 'easy') return '<span class="badge badge-easy">Easy</span>';
      if (v === 'medium') return '<span class="badge badge-medium">Medium</span>';
      if (v === 'hard') return '<span class="badge badge-hard">Hard</span>';
      return '<span class="badge">-</span>';
    }

    function badgeLight(val) {
      const v = String(val ?? '').toLowerCase();
      if (v === 'low') return '<span class="badge badge-light-low">Low</span>';
      if (v === 'medium') return '<span class="badge badge-light-medium">Medium</span>';
      if (v === 'high') return '<span class="badge badge-light-high">High</span>';
      return '<span class="badge">-</span>';
    }

    function fmtRange(a, b) {
      const A = (a === null || a === undefined || a === '') ? '' : a;
      const B = (b === null || b === undefined || b === '') ? '' : b;
      if (A === '' && B === '') return '-';
      if (A !== '' && B !== '') return `${A} – ${B}`;
      return `${A || B}`;
    }

    async function fetchPlants() {
      hideError();
      tbody.innerHTML = `<tr><td colspan="8" style="color:#6b7280;">Loading...</td></tr>`;
      pager.innerHTML = '';

      const url = new URL(API, window.location.origin);
      url.searchParams.set('page', String(state.page));
      url.searchParams.set('per_page', String(state.per_page));
      url.searchParams.set('dir', String(state.dir || 'asc'));
      if (state.q) url.searchParams.set('q', state.q);
      if (state.difficulty) url.searchParams.set('difficulty', state.difficulty);
      if (state.light_level) url.searchParams.set('light_level', state.light_level);

      const res = await fetch(url.toString(), {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load plants.');
        tbody.innerHTML = `<tr><td colspan="8" style="color:#6b7280;">No data.</td></tr>`;
        return;
      }

      const payload = json.data;
      const list = Array.isArray(payload?.data) ? payload.data : [];
      state.last_page = payload?.last_page || 1;

      // ✅ nếu trang hiện tại rỗng mà page > 1 -> lùi 1 trang và fetch lại
      if (list.length === 0 && state.page > 1) {
        state.page -= 1;
        return fetchPlants();
      }

      renderRows(list);
      renderPager(payload);
    }

    function renderRows(list) {
      if (!list.length) {
        tbody.innerHTML = `<tr><td colspan="8" style="color:#6b7280;">No plants found.</td></tr>`;
        return;
      }

      tbody.innerHTML = list.map(p => {
        const id = p.id;
        const name = escapeHtml(p.name ?? '');
        const difficulty = badgeDifficulty(p.difficulty);
        const lightLevel = badgeLight(p.light_level);
        const ph = escapeHtml(fmtRange(p.ph_min, p.ph_max));
        const temp = escapeHtml(fmtRange(p.temp_min, p.temp_max));

        const thumb = p.thumb ? `<img class="table-img" src="${escapeHtml(p.thumb)}" alt="${name}">` : '<span style="color:#9ca3af;">-</span>';

        return `
          <tr>
            <td>${escapeHtml(id)}</td>
            <td>${thumb}</td>
            <td>${name}</td>
            <td>${difficulty}</td>
            <td>${lightLevel}</td>
            <td>${ph}</td>
            <td>${temp}</td>
            <td class="table-actions">
              <button class="btn btn-secondary btn-xs" onclick="editPlant(${id})">Edit</button>
              <button class="btn btn-secondary btn-xs btn-danger-soft" onclick="deletePlant(${id})">Delete</button>
            </td>
          </tr>
        `;
      }).join('');
    }

    function renderPager(paginate) {
      const current = paginate?.current_page || 1;
      const last = paginate?.last_page || 1;

      const btn = (label, page, disabled=false, active=false) => {
        const cls = [
          'plants-page',
          disabled ? 'disabled' : '',
          active ? 'active' : ''
        ].join(' ').trim();

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
      fetchPlants();
    };

    window.editPlant = function(id) {
      window.location.href = `{{ url('/admin/plants/create') }}?plant_id=${id}`;
    };

    window.deletePlant = async function(id) {
      if (!confirm('Delete this plant?')) return;

      hideError();
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

      await fetchPlants();
    };

    let searchTimer = null;
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        state.q = String(searchInput.value ?? '').trim();
        state.page = 1;
        fetchPlants();
      }, 250);
    });

    sortDir.addEventListener('change', () => {
      state.dir = String(sortDir.value ?? 'asc').trim().toLowerCase();
      state.page = 1;
      fetchPlants();
    });

    filterDifficulty.addEventListener('change', () => {
      state.difficulty = String(filterDifficulty.value ?? '').trim();
      state.page = 1;
      fetchPlants();
    });

    filterLight.addEventListener('change', () => {
      state.light_level = String(filterLight.value ?? '').trim();
      state.page = 1;
      fetchPlants();
    });

    document.addEventListener('DOMContentLoaded', () => {
      // set UI default
      sortDir.value = state.dir;
      fetchPlants();
    });
  </script>
@endsection
