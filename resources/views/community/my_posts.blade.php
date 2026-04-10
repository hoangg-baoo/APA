{{-- resources/views/community/my_posts.blade.php --}}
@extends('layouts.site')

@section('title', 'My posts')
@section('nav_com_active', 'active')

@section('content')
  <section class="section">
    <h1 class="section-title">My posts</h1>
    <p class="section-subtitle">
      Quickly review and edit the stories and guides you have shared with the community.
    </p>

    <div id="page-alert" class="alert alert-danger" style="display:none; margin:12px 0;"></div>

    <div class="com-toolbar">
      <div class="com-toolbar-left">
        <input id="mypost-search" class="form-control com-search" placeholder="Search in my posts…">
        <select id="mypost-category" class="form-control form-control-sm">
          <option value="all">All categories</option>
          <option value="diary">Tank diary</option>
          <option value="guides">How-to guides</option>
          <option value="algae">Algae control</option>
        </select>
      </div>
      <div class="com-toolbar-right">
        <button class="btn btn-primary" onclick="location.href='{{ route('community.create_post') }}'">+ New post</button>
      </div>
    </div>

    <section class="card">
      <div class="table-wrapper">
        <table class="table table-posts" id="mypost-table">
          <thead>
          <tr>
            <th style="width: 55%;">Title</th>
            <th style="width: 12%;">Comments</th>
            <th style="width: 18%;">Updated</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody id="mypost-tbody">
            <tr><td colspan="4" style="color:#6b7280;">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>

  <script>
    const CSRF = @json(csrf_token());
    const alertBox = document.getElementById('page-alert');

    function showError(msg) { alertBox.style.display='block'; alertBox.textContent=msg; }
    function hideError() { alertBox.style.display='none'; alertBox.textContent=''; }

    function escapeHtml(v) {
      return String(v ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }

    function fmtDate(iso) {
      if (!iso) return '-';
      return String(iso).replace('T',' ').slice(0, 16);
    }

    async function apiMe() {
      const res = await fetch('/api/me', {
        method: 'GET',
        headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) return null;
      return json.data;
    }

    async function fetchMyPosts(q) {
      const url = new URL('/api/my-posts', window.location.origin);
      if (q) url.searchParams.set('q', q);
      url.searchParams.set('limit', '50');

      const res = await fetch(url.toString(), {
        method: 'GET',
        headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) return { needLogin:true };

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) return { error: json?.message || 'Failed to load my posts.' };

      return { items: json.data?.items || [] };
    }

    async function deletePost(postId) {
      const res = await fetch(`/api/posts/${postId}`, {
        method: 'DELETE',
        headers: {
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      if (res.status === 401) return { needLogin:true };

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) return { error: json?.message || 'Delete post failed.' };

      return { ok:true };
    }

    function render(items) {
      const tb = document.getElementById('mypost-tbody');

      if (!items.length) {
        tb.innerHTML = `<tr><td colspan="4" style="color:#6b7280;">No posts yet.</td></tr>`;
        return;
      }

      tb.innerHTML = items.map(p => {
        const link = `{{ route('community.post_detail') }}?post_id=${encodeURIComponent(p.id)}`;
        return `
          <tr>
            <td class="title-cell">
              <a href="${link}" class="question-title-link">${escapeHtml(p.title)}</a>
            </td>
            <td>${Number(p.comments_count ?? 0)}</td>
            <td>${escapeHtml(fmtDate(p.updated_at))}</td>
            <td class="table-actions">
              <button class="btn btn-secondary btn-xs" data-view="${p.id}">View</button>
              <button class="btn btn-secondary btn-xs" data-del="${p.id}">Delete</button>
            </td>
          </tr>
        `;
      }).join('');
    }

    let debounceTimer = null;

    async function load() {
      hideError();
      const q = document.getElementById('mypost-search').value.trim();

      const r = await fetchMyPosts(q);
      if (r.needLogin) { window.location.href='/login'; return; }
      if (r.error) { showError(r.error); return; }

      render(r.items);
    }

    document.addEventListener('DOMContentLoaded', async () => {
      const me = await apiMe();
      if (!me) { window.location.href='/login'; return; }

      document.getElementById('mypost-search').addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(load, 250);
      });

      document.getElementById('mypost-category').addEventListener('change', () => {
        // category is UI-only for now (no DB field).
        load();
      });

      document.getElementById('mypost-tbody').addEventListener('click', async (e) => {
        const btnView = e.target.closest('[data-view]');
        if (btnView) {
          const id = btnView.getAttribute('data-view');
          window.location.href = `{{ route('community.post_detail') }}?post_id=${encodeURIComponent(id)}`;
          return;
        }

        const btnDel = e.target.closest('[data-del]');
        if (btnDel) {
          const id = btnDel.getAttribute('data-del');
          if (!confirm('Delete this post?')) return;

          const r = await deletePost(id);
          if (r.needLogin) { window.location.href='/login'; return; }
          if (r.error) { showError(r.error); return; }

          await load();
          return;
        }
      });

      await load();
    });
  </script>
@endsection
