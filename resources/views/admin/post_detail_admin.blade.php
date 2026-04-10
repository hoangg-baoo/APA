{{-- resources/views/admin/post_detail_admin.blade.php --}}
@extends('layouts.admin')

@section('title', 'Post detail – Admin')
@section('sidebar_posts_active', 'active')

@section('content')
  <div class="page-header">
    <div>
      <h1 class="page-title">Post detail</h1>
      <p class="page-subtitle">Review content, approve/reject, and moderate comments.</p>
    </div>
  </div>

  <div class="page-content">
    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <section class="card" style="margin-bottom:14px;">
      <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
        <div>
          <div style="color:#6b7280; font-size:13px;">
            <a href="{{ route('admin.posts_moderation') }}">← Back to posts moderation</a>
          </div>
          <h2 id="p-title" style="margin:8px 0 0;">Loading...</h2>
          <div id="p-meta" style="margin-top:6px; color:#6b7280;"></div>
        </div>

        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <button class="btn btn-secondary btn-xs" id="btn-approve" style="display:none;">Approve</button>
          <button class="btn btn-secondary btn-xs" id="btn-reject" style="display:none;">Reject</button>
          <button class="btn btn-secondary btn-xs" id="btn-pending" style="display:none;">Move to Pending</button>
          <button class="btn btn-secondary btn-xs btn-danger-soft" id="btn-delete" style="display:none;">Delete</button>
        </div>
      </div>

      <div id="p-image-wrap" style="display:none; margin-top:12px;">
        <img id="p-image" alt="Post image" style="max-width:100%; border-radius:12px;">
      </div>

      <article id="p-content" style="margin-top:12px; white-space:pre-wrap;"></article>
    </section>

    <section class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:10px;">
        <div>
          <h3 class="card-title" id="c-title">Comments</h3>
          <p class="card-subtitle">Admin can hard-delete any comment.</p>
        </div>
      </div>

      <div id="comments-wrap">
        <div style="color:#6b7280;">Loading...</div>
      </div>
    </section>
  </div>

  <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const alertBox = document.getElementById('page-alert');

    const postId = @json((int)request()->route('id'));
    const API = '/api/admin/posts';

    let post = null;
    let comments = [];

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
      const d = new Date(iso);
      if (Number.isNaN(d.getTime())) return String(iso);
      return d.toISOString().slice(0, 19).replace('T',' ');
    }

    async function fetchDetail() {
      hideError();

      const res = await fetch(`${API}/${postId}`, {
        method: 'GET',
        headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href='/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load post.');
        return;
      }

      post = json.data?.post || null;
      comments = Array.isArray(json.data?.comments) ? json.data.comments : [];
      render();
    }

    async function setStatus(status) {
      hideError();

      const res = await fetch(`${API}/${postId}/status`, {
        method: 'PATCH',
        headers: {
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
          'Content-Type':'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ status }),
      });

      if (res.status === 401) { window.location.href='/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Update status failed.');
        return;
      }

      await fetchDetail();
    }

    async function deletePost() {
      hideError();
      const ok = confirm('HARD DELETE this post and all comments?');
      if (!ok) return;

      const res = await fetch(`${API}/${postId}`, {
        method: 'DELETE',
        headers: {
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href='/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Delete failed.');
        return;
      }

      window.location.href = '{{ route('admin.posts_moderation') }}';
    }

    async function deleteComment(commentId) {
      hideError();
      const ok = confirm('Delete this comment?');
      if (!ok) return;

      const res = await fetch(`/api/admin/comments/${commentId}`, {
        method: 'DELETE',
        headers: {
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href='/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Delete comment failed.');
        return;
      }

      await fetchDetail();
    }

    function render() {
      if (!post) return;

      document.getElementById('p-title').textContent = post.title ?? '(no title)';

      const author = post.user?.name ? `${post.user.name} (${post.user.email ?? ''})` : '—';
      const created = fmtDate(post.created_at);
      const updated = fmtDate(post.updated_at);
      const reviewedAt = fmtDate(post.reviewed_at);

      document.getElementById('p-meta').innerHTML = `
        <span>Author: <strong>${escapeHtml(author)}</strong></span>
        &nbsp; • &nbsp; Status: <strong>${escapeHtml(post.status)}</strong>
        &nbsp; • &nbsp; Created: ${escapeHtml(created)}
        &nbsp; • &nbsp; Updated: ${escapeHtml(updated)}
        &nbsp; • &nbsp; Reviewed: ${escapeHtml(reviewedAt)}
      `;

      document.getElementById('p-content').textContent = post.content ?? '';

      const wrap = document.getElementById('p-image-wrap');
      const img = document.getElementById('p-image');
      if (post.image_path) {
        wrap.style.display = '';
        img.src = post.image_path;
      } else {
        wrap.style.display = 'none';
        img.src = '';
      }

      // Buttons theo status
      const s = String(post.status ?? 'pending');
      document.getElementById('btn-approve').style.display = (s !== 'approved') ? '' : 'none';
      document.getElementById('btn-reject').style.display = (s !== 'rejected') ? '' : 'none';
      document.getElementById('btn-pending').style.display = (s !== 'pending') ? '' : 'none';
      document.getElementById('btn-delete').style.display = '';

      // comments
      document.getElementById('c-title').textContent = `${comments.length} comment(s)`;

      const cWrap = document.getElementById('comments-wrap');
      if (!comments.length) {
        cWrap.innerHTML = `<div style="color:#6b7280;">No comments.</div>`;
        return;
      }

      cWrap.innerHTML = comments.map(c => {
        const by = c.user?.name ?? '—';
        const when = fmtDate(c.created_at);
        return `
          <article class="comment-item">
            <div class="comment-meta" style="display:flex; align-items:center; gap:10px;">
              <strong>${escapeHtml(by)}</strong>
              <span style="color:#6b7280;">${escapeHtml(when)}</span>
              <span style="margin-left:auto;">
                <button class="btn btn-xs btn-secondary btn-danger-soft" data-del-comment="${c.id}">Delete</button>
              </span>
            </div>
            <div class="comment-body">${escapeHtml(c.content ?? '')}</div>
          </article>
        `;
      }).join('');
    }

    document.addEventListener('DOMContentLoaded', async () => {
      await fetchDetail();

      document.getElementById('btn-approve').addEventListener('click', () => setStatus('approved'));
      document.getElementById('btn-reject').addEventListener('click', () => setStatus('rejected'));
      document.getElementById('btn-pending').addEventListener('click', () => setStatus('pending'));
      document.getElementById('btn-delete').addEventListener('click', deletePost);

      document.getElementById('comments-wrap').addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-del-comment]');
        if (!btn) return;
        const cid = btn.getAttribute('data-del-comment');
        await deleteComment(cid);
      });
    });
  </script>
@endsection
