{{-- resources/views/admin/posts_moderation.blade.php --}}
@extends('layouts.admin')

@section('title', 'Community posts – Admin moderation')
@section('sidebar_posts_active', 'active')

@section('content')
  <div class="page-header">
    <div>
      <h1 class="page-title">Community posts moderation</h1>
      <p class="page-subtitle">Approve/reject posts before they appear publicly.</p>
    </div>
  </div>

  <div class="page-content">
    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <div class="admin-toolbar">
      <div class="admin-toolbar-left">
        <input type="text" id="postSearchInput" class="form-control admin-search"
               placeholder="Search by title/content/author name/email...">
      </div>

      <div class="admin-toolbar-right">
        <select id="filterStatus" class="form-control" style="width:170px;">
          <option value="all">All statuses</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>

        <select id="sortBy" class="form-control" style="width:170px;">
          <option value="created_at">Sort: Created</option>
          <option value="comments_count">Sort: Comments</option>
          <option value="status">Sort: Status</option>
        </select>

        <select id="sortDir" class="form-control" style="width:120px;">
          <option value="desc">DESC</option>
          <option value="asc">ASC</option>
        </select>
      </div>
    </div>

    <section class="card">
      <div class="table-wrapper">
        <table class="table" id="postsTable">
          <thead>
          <tr>
            <th>Title</th>
            <th style="width:240px;">Author</th>
            <th style="width:130px;">Status</th>
            <th style="width:90px;">Comments</th>
            <th style="width:180px;">Created at</th>
            <th style="width:320px;">Actions</th>
          </tr>
          </thead>
          <tbody id="posts-tbody">
            <tr><td colspan="6" style="color:#6b7280;">Loading...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="plants-pagination" style="margin-top:14px;">
        <div id="posts-pagination" class="plants-pager"></div>
      </div>
    </section>
  </div>

  <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const API = '/api/admin/posts';

    const alertBox = document.getElementById('page-alert');
    const tbody = document.getElementById('posts-tbody');
    const pager = document.getElementById('posts-pagination');

    const searchInput = document.getElementById('postSearchInput');
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

    function statusBadge(status) {
      const s = String(status ?? '').toLowerCase();
      if (s === 'approved') return `<span class="badge badge-open">Approved</span>`;
      if (s === 'rejected') return `<span class="badge badge-resolved">Rejected</span>`;
      return `<span class="badge badge-best">Pending</span>`;
    }

    async function fetchPosts() {
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
        showError(json?.message || 'Failed to load posts.');
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
        tbody.innerHTML = `<tr><td colspan="6" style="color:#6b7280;">No posts found.</td></tr>`;
        return;
      }

      tbody.innerHTML = list.map(p => {
        const id = p.id;
        const title = escapeHtml(p.title ?? '');
        const excerpt = escapeHtml(p.content_excerpt ?? '');
        const authorName = escapeHtml(p.user?.name ?? '—');
        const authorEmail = escapeHtml(p.user?.email ?? '');
        const status = String(p.status ?? 'pending');
        const cc = Number(p.comments_count ?? 0);
        const created = fmtDate(p.created_at);

        const authorLine = authorEmail
          ? `<div>${authorName}</div><div class="question-meta">${authorEmail}</div>`
          : `<div>${authorName}</div>`;

        const meta = excerpt ? `<div class="question-meta">${excerpt}</div>` : '';

        // Actions theo status
        let actions = `
          <button class="btn btn-secondary btn-xs" onclick="viewPost(${id})">View</button>
        `;

        if (status === 'pending') {
          actions += `
            <button class="btn btn-secondary btn-xs" onclick="setStatus(${id}, 'approved')">Approve</button>
            <button class="btn btn-secondary btn-xs" onclick="setStatus(${id}, 'rejected')">Reject</button>
          `;
        } else if (status === 'approved') {
          actions += `
            <button class="btn btn-secondary btn-xs" onclick="setStatus(${id}, 'pending')">Unapprove</button>
            <button class="btn btn-secondary btn-xs" onclick="setStatus(${id}, 'rejected')">Reject</button>
          `;
        } else { // rejected
          actions += `
            <button class="btn btn-secondary btn-xs" onclick="setStatus(${id}, 'approved')">Approve</button>
            <button class="btn btn-secondary btn-xs" onclick="setStatus(${id}, 'pending')">Move to Pending</button>
          `;
        }

        actions += `
          <button class="btn btn-secondary btn-xs btn-danger-soft" onclick="deletePost(${id}, '${title}')">Delete</button>
        `;

        return `
          <tr>
            <td>
              <div class="question-title-cell">
                <div class="question-title-row">
                  <a class="question-title-link" href="javascript:void(0)" onclick="viewPost(${id})">${title}</a>
                </div>
                ${meta}
              </div>
            </td>
            <td>${authorLine}</td>
            <td>${statusBadge(status)}</td>
            <td>${cc}</td>
            <td>${created}</td>
            <td class="table-actions">${actions}</td>
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
      fetchPosts();
    };

    window.viewPost = function(id) {
      window.location.href = `/admin/community-posts/${id}`;
    };

    window.setStatus = async function(id, status) {
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
        await fetchPosts();
        return;
      }

      await fetchPosts();
    };

    window.deletePost = async function(id, title) {
      hideError();
      const ok = confirm(`HARD DELETE this post?\n\n#${id}: ${title}\n\nThis will permanently delete the post and ALL comments.`);
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

      await fetchPosts();

      const rows = tbody.querySelectorAll('tr');
      const isEmpty = rows.length === 1 && rows[0].innerText.includes('No posts found');
      if (isEmpty && state.page > 1) {
        state.page -= 1;
        await fetchPosts();
      }
    };

    let searchTimer = null;
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        state.q = String(searchInput.value ?? '').trim();
        state.page = 1;
        fetchPosts();
      }, 250);
    });

    filterStatus.addEventListener('change', () => {
      state.status = String(filterStatus.value ?? 'all').trim();
      state.page = 1;
      fetchPosts();
    });

    sortBy.addEventListener('change', () => {
      state.sort_by = String(sortBy.value ?? 'created_at');
      state.page = 1;
      fetchPosts();
    });

    sortDir.addEventListener('change', () => {
      state.sort_dir = String(sortDir.value ?? 'desc');
      state.page = 1;
      fetchPosts();
    });

    document.addEventListener('DOMContentLoaded', () => {
      state.status = filterStatus.value;
      state.sort_by = sortBy.value;
      state.sort_dir = sortDir.value;
      fetchPosts();
    });
  </script>
@endsection
