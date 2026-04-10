{{-- resources/views/admin/qa_moderation.blade.php --}}
@extends('layouts.admin')

@section('title', 'Q&A moderation – Admin')
@section('sidebar_qa_active', 'active')

@section('content')
  <div class="page-header">
    <div>
      <h1 class="page-title">Q&amp;A moderation</h1>
      <p class="page-subtitle">Review questions, view answers, set status, and hard-delete content.</p>
    </div>
  </div>

  <div class="page-content">
    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <!-- Toolbar -->
    <div class="admin-toolbar">
      <div class="admin-toolbar-left">
        <input type="text" id="qaSearchInput" class="form-control admin-search"
               placeholder="Search by title/content/owner name/email...">
      </div>

      <div class="admin-toolbar-right">
        <select id="filterStatus" class="form-control" style="width:160px;">
          <option value="all">All statuses</option>
          <option value="open">Open</option>
          <option value="resolved">Resolved</option>
        </select>

        <select id="sortBy" class="form-control" style="width:160px;">
          <option value="created_at">Sort: Created</option>
          <option value="answers_count">Sort: Answers</option>
          <option value="status">Sort: Status</option>
        </select>

        <select id="sortDir" class="form-control" style="width:120px;">
          <option value="desc">DESC</option>
          <option value="asc">ASC</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <section class="card">
      <div class="table-wrapper">
        <table class="table" id="qaTable">
          <thead>
          <tr>
            <th>Title</th>
            <th style="width:220px;">Owner</th>
            <th style="width:120px;">Status</th>
            <th style="width:90px;">Answers</th>
            <th style="width:180px;">Created at</th>
            <th style="width:240px;">Actions</th>
          </tr>
          </thead>
          <tbody id="qa-tbody">
            <tr><td colspan="6" style="color:#6b7280;">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="plants-pagination" style="margin-top:14px;">
        <div id="qa-pagination" class="plants-pager"></div>
      </div>
    </section>
  </div>

  <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const API = '/api/admin/questions';

    const alertBox = document.getElementById('page-alert');
    const tbody = document.getElementById('qa-tbody');
    const pager = document.getElementById('qa-pagination');

    const searchInput = document.getElementById('qaSearchInput');
    const filterStatus = document.getElementById('filterStatus');
    const sortBy = document.getElementById('sortBy');
    const sortDir = document.getElementById('sortDir');

    const state = {
      page: 1,
      q: '',
      status: 'all',
      per_page: 15,
      sort_by: 'created_at',
      sort_dir: 'desc',
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

    // ✅ show được cả 2 badge: Has best + Open/Resolved
    function statusBadge(status, hasBest) {
      const s = String(status ?? '').toLowerCase();
      const badges = [];

      if (hasBest) badges.push(`<span class="badge badge-best">Has best</span>`);
      if (s === 'resolved') badges.push(`<span class="badge badge-resolved">Resolved</span>`);
      else badges.push(`<span class="badge badge-open">Open</span>`);

      return `<div style="display:flex;gap:6px;flex-wrap:wrap;">${badges.join('')}</div>`;
    }

    async function fetchQuestions() {
      hideError();
      tbody.innerHTML = `<tr><td colspan="6" style="color:#6b7280;">Loading...</td></tr>`;
      pager.innerHTML = '';

      const url = new URL(API, window.location.origin);
      url.searchParams.set('page', String(state.page));
      url.searchParams.set('per_page', String(state.per_page));
      url.searchParams.set('sort_by', state.sort_by);
      url.searchParams.set('sort_dir', state.sort_dir);
      if (state.q) url.searchParams.set('q', state.q);
      if (state.status) url.searchParams.set('status', state.status);

      const res = await fetch(url.toString(), {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load questions.');
        tbody.innerHTML = `<tr><td colspan="6" style="color:#6b7280;">No data.</td></tr>`;
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
        tbody.innerHTML = `<tr><td colspan="6" style="color:#6b7280;">No questions found.</td></tr>`;
        return;
      }

      tbody.innerHTML = list.map(q => {
        const id = q.id;
        const title = escapeHtml(q.title ?? '');
        const content = escapeHtml(q.content_excerpt ?? '');
        const ownerName = escapeHtml(q.user?.name ?? '—');
        const ownerEmail = escapeHtml(q.user?.email ?? '');
        const tankName = escapeHtml(q.tank?.name ?? '');
        const status = String(q.status ?? 'open');

        // ✅ cái này giờ sẽ đúng vì backend đã count trực tiếp từ bảng answers
        const answersCount = Number(q.answers_count ?? 0);

        const created = fmtDate(q.created_at);
        const hasBest = !!q.has_best_answer;

        const metaParts = [];
        if (tankName) metaParts.push(`Tank: ${tankName}`);
        if (content) metaParts.push(content);

        const meta = metaParts.length ? `<div class="question-meta">${metaParts.join(' • ')}</div>` : '';

        const ownerLine = ownerEmail
          ? `<div>${ownerName}</div><div class="question-meta">${ownerEmail}</div>`
          : `<div>${ownerName}</div>`;

        const toggleLabel = (status === 'resolved') ? 'Re-open' : 'Resolve';

        return `
          <tr>
            <td>
              <div class="question-title-cell">
                <div class="question-title-row">
                  <a class="question-title-link" href="javascript:void(0)" onclick="viewQuestion(${id})">${title}</a>
                </div>
                ${meta}
              </div>
            </td>
            <td>${ownerLine}</td>
            <td>${statusBadge(status, hasBest)}</td>
            <td>${answersCount}</td>
            <td>${created}</td>
            <td class="table-actions">
              <button class="btn btn-secondary btn-xs" onclick="viewQuestion(${id})">View</button>
              <button class="btn btn-secondary btn-xs" onclick="toggleStatus(${id}, '${status}')">${toggleLabel}</button>
              <button class="btn btn-secondary btn-xs btn-danger-soft" onclick="deleteQuestion(${id}, '${title}')">Delete</button>
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
      fetchQuestions();
    };

    window.viewQuestion = function(id) {
      window.location.href = `/admin/qa/${id}`;
    };

    window.toggleStatus = async function(id, currentStatus) {
      hideError();
      const next = (String(currentStatus).toLowerCase() === 'resolved') ? 'open' : 'resolved';

      const res = await fetch(`${API}/${id}/status`, {
        method: 'PATCH',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ status: next }),
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Update status failed.');
        await fetchQuestions();
        return;
      }

      await fetchQuestions();
    };

    window.deleteQuestion = async function(id, title) {
      hideError();
      const ok = confirm(`HARD DELETE this question?\n\n#${id}: ${title}\n\nThis will permanently delete the question and ALL answers.`);
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

      await fetchQuestions();

      const rows = tbody.querySelectorAll('tr');
      const isEmpty = rows.length === 1 && rows[0].innerText.includes('No questions found');
      if (isEmpty && state.page > 1) {
        state.page -= 1;
        await fetchQuestions();
      }
    };

    let searchTimer = null;
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        state.q = String(searchInput.value ?? '').trim();
        state.page = 1;
        fetchQuestions();
      }, 250);
    });

    filterStatus.addEventListener('change', () => {
      state.status = String(filterStatus.value ?? 'all').trim();
      state.page = 1;
      fetchQuestions();
    });

    sortBy.addEventListener('change', () => {
      state.sort_by = String(sortBy.value ?? 'created_at');
      state.page = 1;
      fetchQuestions();
    });

    sortDir.addEventListener('change', () => {
      state.sort_dir = String(sortDir.value ?? 'desc');
      state.page = 1;
      fetchQuestions();
    });

    document.addEventListener('DOMContentLoaded', () => {
      state.status = filterStatus.value;
      state.sort_by = sortBy.value;
      state.sort_dir = sortDir.value;
      fetchQuestions();
    });
  </script>
@endsection
