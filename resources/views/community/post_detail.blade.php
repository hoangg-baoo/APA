{{-- resources/views/community/post_detail.blade.php --}}
@extends('layouts.site')

@section('title', 'Post detail')
@section('nav_com_active', 'active')

@section('content')
  <section class="section">
    <button class="btn btn-secondary btn-xs" onclick="window.location.href='{{ route('community.posts_list') }}'">← Back to posts</button>

    <div id="page-alert" class="alert alert-danger" style="display:none; margin:12px 0;"></div>

    <section class="card" style="margin-top: 12px;">
      <header class="post-header">
        <h2 id="p-title">Loading...</h2>
        <div class="post-meta-row" id="p-meta"></div>
      </header>

      <div id="p-image-wrap" style="display:none; margin-top:10px;">
        <img id="p-image" alt="Post image" style="max-width:100%; border-radius:12px;">
      </div>

      <article class="post-content" id="p-content" style="margin-top:12px;"></article>

      <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
        <button id="btn-edit-post" class="btn btn-secondary btn-xs" style="display:none;">Edit</button>
        <button id="btn-del-post" class="btn btn-secondary btn-xs" style="display:none;">Delete</button>
      </div>

      <div id="post-edit-wrap" style="display:none; margin-top:12px;">
        <div class="form-group">
          <label>Title</label>
          <input id="edit-title" class="form-control">
        </div>
        <div class="form-group">
          <label>Content</label>
          <textarea id="edit-content" class="form-control" rows="8"></textarea>
        </div>
        <div class="form-group">
          <label>Replace image (optional)</label>
          <input id="edit-image" type="file" class="form-control">
        </div>
        <div class="form-group" style="display:flex; align-items:center; gap:8px;">
          <input id="edit-remove-image" type="checkbox">
          <label for="edit-remove-image" style="margin:0;">Remove current image</label>
        </div>
        <div class="form-actions" style="display:flex; gap:8px; justify-content:flex-end;">
          <button id="btn-save-post" class="btn btn-primary btn-xs">Save</button>
          <button id="btn-cancel-post" class="btn btn-secondary btn-xs">Cancel</button>
        </div>
      </div>
    </section>

    <section class="card">
      <div class="comments-header">
        <h3 class="card-title" id="c-title">Comments</h3>
        <p class="card-subtitle">Join the discussion or share your own experience.</p>
      </div>

      <div id="comments-wrap">
        <div style="color:#6b7280;">Loading...</div>
      </div>

      <form id="c-form" style="margin-top: 12px;">
        @csrf
        <div class="form-group">
          <label for="comment-text">Add a comment</label>
          <textarea id="comment-text" rows="3" class="form-control"
                    placeholder="Be kind and constructive. Share what has worked for you."></textarea>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Post comment</button>
          <button type="button" class="btn btn-secondary" id="btn-clear-comment">Cancel</button>
        </div>
      </form>
    </section>
  </section>

  <script>
    const CSRF = @json(csrf_token());
    const alertBox = document.getElementById('page-alert');

    let me = null;
    let post = null;
    let comments = [];
    let editingCommentId = null;
    let editingPost = false;

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

    function postIdFromUrl() {
      const url = new URL(window.location.href);
      return url.searchParams.get('post_id');
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

    async function fetchDetail(id) {
      const res = await fetch(`/api/posts/${id}`, {
        method: 'GET',
        headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) return { needLogin:true };

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        return { error: json?.message || 'Failed to load post.' };
      }
      return { data: json.data };
    }

    async function createComment(postId, content) {
      const res = await fetch(`/api/posts/${postId}/comments`, {
        method: 'POST',
        headers: {
          'Accept':'application/json',
          'Content-Type':'application/json',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ content }),
      });

      if (res.status === 401) return { needLogin:true };

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) return { error: json?.message || 'Create comment failed.' };
      return { ok:true };
    }

    async function updateComment(commentId, content) {
      const res = await fetch(`/api/comments/${commentId}`, {
        method: 'PUT',
        headers: {
          'Accept':'application/json',
          'Content-Type':'application/json',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ content }),
      });

      if (res.status === 401) return { needLogin:true };

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) return { error: json?.message || 'Update comment failed.' };
      return { ok:true };
    }

    async function deleteComment(commentId) {
      const res = await fetch(`/api/comments/${commentId}`, {
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
      if (!res.ok || !json || json.success !== true) return { error: json?.message || 'Delete comment failed.' };
      return { ok:true };
    }

    async function updatePost(postId, title, content, file, removeImage) {
      const fd = new FormData();
      fd.append('title', title);
      fd.append('content', content);
      fd.append('remove_image', removeImage ? '1' : '0');
      if (file) fd.append('image', file);

      const res = await fetch(`/api/posts/${postId}`, {
        method: 'PUT',
        headers: {
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: fd,
      });

      if (res.status === 401) return { needLogin:true };

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) return { error: json?.message || 'Update post failed.' };
      return { ok:true };
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

    function renderPost() {
      document.getElementById('p-title').textContent = post?.title ?? '(no title)';

      const by = post?.user?.name ?? '-';
      const created = fmtDate(post?.created_at);
      const updated = fmtDate(post?.updated_at);

      document.getElementById('p-meta').innerHTML = `
        <span>by <strong>${escapeHtml(by)}</strong></span>
        <span>Created: ${escapeHtml(created)}</span>
        <span>Updated: ${escapeHtml(updated)}</span>
      `;

      document.getElementById('p-content').textContent = post?.content ?? '';

      const wrap = document.getElementById('p-image-wrap');
      const imgEl = document.getElementById('p-image');
      if (post?.image_path) {
        wrap.style.display = '';
        imgEl.src = post.image_path;
      } else {
        wrap.style.display = 'none';
        imgEl.src = '';
      }

      const isOwnerPost = me && post && (Number(me.id) === Number(post.user_id));
      document.getElementById('btn-edit-post').style.display = isOwnerPost ? '' : 'none';
      document.getElementById('btn-del-post').style.display = isOwnerPost ? '' : 'none';
    }

    function renderPostEditor() {
      const wrap = document.getElementById('post-edit-wrap');
      wrap.style.display = editingPost ? '' : 'none';

      if (editingPost) {
        document.getElementById('edit-title').value = post?.title ?? '';
        document.getElementById('edit-content').value = post?.content ?? '';
        document.getElementById('edit-remove-image').checked = false;
        document.getElementById('edit-image').value = '';
      }
    }

    function renderComments() {
      document.getElementById('c-title').textContent = `${comments.length} comment(s)`;

      const wrap = document.getElementById('comments-wrap');
      if (!comments.length) {
        wrap.innerHTML = `<div style="color:#6b7280;">No comments yet.</div>`;
        return;
      }

      const isOwnerPost = me && post && (Number(me.id) === Number(post.user_id));
      const isAdmin = (me?.role === 'admin');

      wrap.innerHTML = comments.map(c => {
        const by = c?.user?.name ?? '-';
        const when = fmtDate(c?.created_at);

        const canDelete = me && (Number(me.id) === Number(c.user_id) || isOwnerPost);
        const canEdit = me && !isAdmin && (Number(me.id) === Number(c.user_id));

        const isEditing = editingCommentId && Number(editingCommentId) === Number(c.id);

        const body = isEditing
          ? `
            <textarea class="form-control" rows="3" data-edit-text="${c.id}">${escapeHtml(c.content ?? '')}</textarea>
            <div style="margin-top:8px; display:flex; gap:8px; justify-content:flex-end;">
              <button class="btn btn-xs btn-primary" data-save-comment="${c.id}">Save</button>
              <button class="btn btn-xs btn-secondary" data-cancel-comment="${c.id}">Cancel</button>
            </div>
          `
          : `<div class="comment-body">${escapeHtml(c.content ?? '')}</div>`;

        const btnEdit = canEdit ? `<button class="btn btn-xs btn-secondary" data-edit-comment="${c.id}">Edit</button>` : '';
        const btnDel  = canDelete ? `<button class="btn btn-xs btn-secondary" data-del-comment="${c.id}">Delete</button>` : '';

        return `
          <article class="comment-item">
            <div class="comment-meta" style="display:flex; align-items:center; gap:10px;">
              <strong>${escapeHtml(by)}</strong>
              <span style="color:#6b7280;">${escapeHtml(when)}</span>
              <span style="margin-left:auto; display:flex; gap:8px;">
                ${btnEdit}
                ${btnDel}
              </span>
            </div>
            ${body}
          </article>
        `;
      }).join('');
    }

    async function reload() {
      const id = postIdFromUrl();
      if (!id) { showError('Missing post_id on URL. Example: /community/post_detail?post_id=1'); return; }

      const result = await fetchDetail(id);

      if (result.needLogin) { window.location.href = '/login'; return; }
      if (result.error) { showError(result.error); return; }

      post = result.data.post;
      comments = Array.isArray(result.data.comments) ? result.data.comments : [];

      hideError();
      renderPost();
      renderPostEditor();
      renderComments();
    }

    document.addEventListener('DOMContentLoaded', async () => {
      const id = postIdFromUrl();
      if (!id) { showError('Missing post_id on URL.'); return; }

      me = await apiMe();
      if (!me) { window.location.href = '/login'; return; }

      await reload();

      document.getElementById('btn-edit-post').addEventListener('click', () => {
        editingPost = true;
        renderPostEditor();
      });

      document.getElementById('btn-cancel-post').addEventListener('click', () => {
        editingPost = false;
        renderPostEditor();
      });

      document.getElementById('btn-save-post').addEventListener('click', async () => {
        hideError();

        const title = document.getElementById('edit-title').value.trim();
        const content = document.getElementById('edit-content').value.trim();
        const file = document.getElementById('edit-image').files?.[0] || null;
        const removeImage = document.getElementById('edit-remove-image').checked;

        if (!title) { showError('Title is required.'); return; }
        if (!content) { showError('Content is required.'); return; }

        const r = await updatePost(id, title, content, file, removeImage);
        if (r.needLogin) { window.location.href = '/login'; return; }
        if (r.error) { showError(r.error); return; }

        editingPost = false;
        await reload();
      });

      document.getElementById('btn-del-post').addEventListener('click', async () => {
        if (!confirm('Delete this post (and all its comments)?')) return;

        const r = await deletePost(id);
        if (r.needLogin) { window.location.href = '/login'; return; }
        if (r.error) { showError(r.error); return; }

        window.location.href = `{{ route('community.posts_list') }}`;
      });

      document.getElementById('c-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();

        const content = document.getElementById('comment-text').value.trim();
        if (!content) { showError('Comment content is required.'); return; }

        const r = await createComment(id, content);
        if (r.needLogin) { window.location.href = '/login'; return; }
        if (r.error) { showError(r.error); return; }

        document.getElementById('comment-text').value = '';
        await reload();
      });

      document.getElementById('btn-clear-comment').addEventListener('click', () => {
        document.getElementById('comment-text').value = '';
      });

      document.getElementById('comments-wrap').addEventListener('click', async (e) => {
        const btnEdit = e.target.closest('[data-edit-comment]');
        if (btnEdit) {
          editingCommentId = btnEdit.getAttribute('data-edit-comment');
          renderComments();
          return;
        }

        const btnCancel = e.target.closest('[data-cancel-comment]');
        if (btnCancel) {
          editingCommentId = null;
          renderComments();
          return;
        }

        const btnSave = e.target.closest('[data-save-comment]');
        if (btnSave) {
          hideError();
          const cid = btnSave.getAttribute('data-save-comment');
          const ta = document.querySelector(`[data-edit-text="${cid}"]`);
          const newContent = (ta?.value ?? '').trim();
          if (!newContent) { showError('Comment content is required.'); return; }

          const r = await updateComment(cid, newContent);
          if (r.needLogin) { window.location.href = '/login'; return; }
          if (r.error) { showError(r.error); return; }

          editingCommentId = null;
          await reload();
          return;
        }

        const btnDel = e.target.closest('[data-del-comment]');
        if (btnDel) {
          const cid = btnDel.getAttribute('data-del-comment');
          if (!confirm('Delete this comment?')) return;

          const r = await deleteComment(cid);
          if (r.needLogin) { window.location.href = '/login'; return; }
          if (r.error) { showError(r.error); return; }

          editingCommentId = null;
          await reload();
          return;
        }
      });
    });
  </script>
@endsection
