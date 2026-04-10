{{-- resources/views/community/posts_list.blade.php --}}
@extends('layouts.site')

@section('title', 'Community posts')
@section('nav_com_active', 'active')

@section('content')
  <section class="section">
    <h1 class="section-title">Community stories &amp; guides</h1>
    <p class="section-subtitle">
      Read aquascaping journals, maintenance tips and success stories from other hobbyists.
      Share your own experience to inspire the community.
    </p>

    <div id="page-alert" class="alert alert-danger" style="display:none; margin:12px 0;"></div>

    <div class="com-toolbar">
      <div class="com-toolbar-left">
        <input id="com-search" class="form-control com-search" placeholder="Search posts…">
        <select id="com-category" class="form-control form-control-sm">
          <option value="all">All categories</option>
          <option value="diary">Tank diary</option>
          <option value="guides">How-to guides</option>
          <option value="algae">Algae control</option>
        </select>
      </div>
      <div class="com-toolbar-right">
        <button class="btn btn-primary" id="btn-write">+ Write a post</button>
        <button class="btn btn-secondary" id="btn-my-posts" style="display:none;">My posts</button>
      </div>
    </div>

    <section class="posts-grid" id="posts-grid">
      <div style="color:#6b7280;">Loading...</div>
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
      return String(iso).replace('T',' ').slice(0, 10);
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

    async function fetchPosts(q) {
      const url = new URL('/api/posts', window.location.origin);
      if (q) url.searchParams.set('q', q);
      url.searchParams.set('limit', '24');

      const res = await fetch(url.toString(), {
        method: 'GET',
        headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) return { needLogin: true };

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        return { error: json?.message || 'Failed to load posts.' };
      }
      return { items: json.data?.items || [] };
    }

    function excerpt(text, n=140) {
      const t = String(text ?? '').trim();
      if (t.length <= n) return t;
      return t.slice(0, n) + '…';
    }

    function renderPosts(items) {
      const grid = document.getElementById('posts-grid');
      if (!items.length) {
        grid.innerHTML = `<div style="color:#6b7280;">No posts found.</div>`;
        return;
      }

      grid.innerHTML = items.map(p => {
        const title = escapeHtml(p.title);
        const by = escapeHtml(p.user?.name || 'User');
        const date = escapeHtml(fmtDate(p.created_at));
        const cc = Number(p.comments_count ?? 0);

        const link = `{{ route('community.post_detail') }}?post_id=${encodeURIComponent(p.id)}`;
        const img = p.image_path ? `<div class="post-cover" style="margin-top:10px;"><img src="${escapeHtml(p.image_path)}" alt="Post image"></div>` : '';

        return `
          <article class="post-card">
            <div class="post-card-title">
              <a href="${link}">${title}</a>
            </div>
            <div class="post-card-meta">
              by <strong>${by}</strong> • ${date} • ${cc} comment(s)
            </div>
            <p class="post-card-excerpt">${escapeHtml(excerpt(p.content))}</p>
            ${img}
          </article>
        `;
      }).join('');
    }

    let me = null;
    let debounceTimer = null;

    async function load() {
      hideError();

      me = await apiMe();
      const btnMy = document.getElementById('btn-my-posts');
      if (btnMy) btnMy.style.display = me ? '' : 'none';

      const q = document.getElementById('com-search').value.trim();
      const result = await fetchPosts(q);

      if (result.needLogin) { window.location.href = '/login'; return; }
      if (result.error) { showError(result.error); return; }

      renderPosts(result.items);
    }

    document.addEventListener('DOMContentLoaded', async () => {
      document.getElementById('btn-write').addEventListener('click', async () => {
        const m = await apiMe();
        if (!m) { window.location.href = '/login'; return; }
        window.location.href = `{{ route('community.create_post') }}`;
      });

      document.getElementById('btn-my-posts').addEventListener('click', () => {
        window.location.href = `{{ route('community.my_posts') }}`;
      });

      document.getElementById('com-search').addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(load, 250);
      });

      document.getElementById('com-category').addEventListener('change', () => {
        // category is UI-only for now (no DB field). Kept for future.
        load();
      });

      await load();
    });
  </script>
@endsection
