{{-- resources/views/community/create_post.blade.php --}}
@extends('layouts.site')

@section('title', 'Create post')
@section('nav_com_active', 'active')

@section('content')
  <section class="section">
    <button class="btn btn-secondary btn-xs" onclick="history.back()">← Back to Community</button>

    <div id="page-alert" class="alert alert-danger" style="display:none; margin:12px 0;"></div>
    <div id="page-ok" class="alert alert-success" style="display:none; margin:12px 0;"></div>

    <section class="card" style="margin-top: 12px;">
      <h1 class="section-title" style="margin-bottom: 4px;">Share a new story or guide</h1>
      <p class="section-subtitle">
        Write about your tank journey, a step-by-step guide, or tips that helped you solve a problem.
      </p>

      <form class="form-grid-2" id="post-form">
        @csrf

        <div class="form-group form-group-full">
          <label for="post-title">Title</label>
          <input id="post-title" class="form-control" placeholder="e.g. How I kept my carpet healthy during summer heat">
        </div>

        <div class="form-group form-group-full">
          <label for="post-content">Content</label>
          <textarea id="post-content" rows="8" class="form-control"
                    placeholder="Tell your story or explain your method step by step…"></textarea>
        </div>

        <div class="form-group">
          <label for="post-media">Cover image (optional)</label>
          <input id="post-media" type="file" class="form-control">
        </div>

        <div class="form-group">
          <label>Note</label>
          <div style="color:#6b7280; font-size:14px;">
            Category / tags / related tank are UI-only for now (no DB fields yet).
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Publish post</button>
          <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
        </div>
      </form>
    </section>
  </section>

  <script>
    const CSRF = @json(csrf_token());
    const alertBox = document.getElementById('page-alert');
    const okBox = document.getElementById('page-ok');

    function showError(msg) { okBox.style.display='none'; alertBox.style.display='block'; alertBox.textContent=msg; }
    function showOk(msg) { alertBox.style.display='none'; okBox.style.display='block'; okBox.textContent=msg; }
    function hideAll() { alertBox.style.display='none'; okBox.style.display='none'; alertBox.textContent=''; okBox.textContent=''; }

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

    async function createPost(title, content, file) {
      const fd = new FormData();
      fd.append('title', title);
      fd.append('content', content);
      if (file) fd.append('image', file);

      const res = await fetch('/api/posts', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: fd,
      });

      if (res.status === 401) return { needLogin:true };

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        return { error: json?.message || 'Create post failed.' };
      }
      return { post: json.data?.post };
    }

    document.addEventListener('DOMContentLoaded', async () => {
      const me = await apiMe();
      if (!me) { window.location.href = '/login'; return; }

      document.getElementById('post-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        hideAll();

        const title = document.getElementById('post-title').value.trim();
        const content = document.getElementById('post-content').value.trim();
        const file = document.getElementById('post-media').files?.[0] || null;

        if (!title) { showError('Title is required.'); return; }
        if (!content) { showError('Content is required.'); return; }

        const result = await createPost(title, content, file);

        if (result.needLogin) { window.location.href = '/login'; return; }
        if (result.error) { showError(result.error); return; }

        showOk('Post created.');
        const id = result.post?.id;
        if (id) {
          window.location.href = `{{ route('community.post_detail') }}?post_id=${encodeURIComponent(id)}`;
        } else {
          window.location.href = `{{ route('community.posts_list') }}`;
        }
      });
    });
  </script>
@endsection
