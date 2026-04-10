{{-- resources/views/qa/question_detail.blade.php --}}
@extends('layouts.site')

@section('title', 'Question detail – Aquatic Plant Advisor')
@section('nav_qa_active', 'active')

@section('content')
  <section class="section">
    <button class="btn btn-secondary btn-xs"
            onclick="window.location.href='{{ route('qa.questions_list') }}'">
      ← Back to list
    </button>

    <div id="page-alert" class="alert alert-danger" style="display:none; margin:12px 0;"></div>

    <section class="card" style="margin-top: 12px;">
      <div class="question-header">
        <h2 id="q-title">Loading...</h2>
        <div class="qa-meta-row" id="q-meta"></div>
      </div>

      <div class="question-body" id="q-content"></div>

      <div id="q-image-wrap" style="display:none; margin-top:10px;">
        <img id="q-image" alt="Question image" style="max-width:100%; border-radius:12px;">
      </div>

      <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
        <button id="btn-edit-q" class="btn btn-secondary btn-xs" style="display:none;">Edit</button>
        <button id="btn-del-q" class="btn btn-secondary btn-xs" style="display:none;">Delete</button>
      </div>
    </section>

    <section class="card">
      <div class="answers-header">
        <h3 class="card-title" id="ans-title">Answers</h3>
        <p class="card-subtitle">Best answer is highlighted.</p>
      </div>

      <div id="answers-wrap">
        <div style="color:#6b7280;">Loading...</div>
      </div>
    </section>

    <section class="card">
      <div class="answer-form-header">
        <h3>Post your answer</h3>
        <p class="card-subtitle">Share your experience clearly.</p>
      </div>

      <form id="a-form">
        @csrf
        <div class="form-group">
          <label for="answer-content">Your answer</label>
          <textarea id="answer-content" rows="5" class="form-control"
                    placeholder="Write a clear step-by-step suggestion…"></textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Submit answer</button>
          <button type="button" class="btn btn-secondary"
                  onclick="window.location.href='{{ route('qa.questions_list') }}'">
            Cancel
          </button>
        </div>
      </form>
    </section>
  </section>

  <script>
    const CSRF = @json(csrf_token());
    const alertBox = document.getElementById('page-alert');

    let me = null;
    let question = null;
    let answers = [];
    let editingAnswerId = null;

    function showError(msg) {
      alertBox.style.display = 'block';
      alertBox.textContent = msg;
    }
    function hideError() {
      alertBox.style.display = 'none';
      alertBox.textContent = '';
    }

    function escapeHtml(v) {
      return String(v ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }

    function questionIdFromUrl() {
      const url = new URL(window.location.href);
      return url.searchParams.get('question_id');
    }

    function fmtDate(iso) {
      if (!iso) return '-';
      return String(iso).replace('T',' ').slice(0, 16);
    }

    function badgeStatus(status) {
      if (status === 'resolved') return `<span class="badge badge-resolved">Resolved</span>`;
      return `<span class="badge badge-open">Open</span>`;
    }

    async function apiMe() {
      const res = await fetch('/api/me', {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) return null;
      return json.data;
    }

    async function fetchDetail(id) {
      const res = await fetch(`/api/questions/${id}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load question.');
        return null;
      }
      return json.data;
    }

    async function createAnswer(questionId, content) {
      const res = await fetch(`/api/questions/${questionId}/answers`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ content }),
      });

      if (res.status === 401) { window.location.href = '/login'; return false; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Submit answer failed.');
        return false;
      }
      return true;
    }

    async function updateAnswer(answerId, content) {
      const res = await fetch(`/api/answers/${answerId}`, {
        method: 'PUT',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ content }),
      });

      if (res.status === 401) { window.location.href = '/login'; return false; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Update answer failed.');
        return false;
      }
      return true;
    }

    async function deleteAnswer(answerId) {
      const res = await fetch(`/api/answers/${answerId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return false; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Delete answer failed.');
        return false;
      }
      return true;
    }

    async function acceptAnswer(questionId, answerId) {
      const res = await fetch(`/api/questions/${questionId}/answers/${answerId}/accept`, {
        method: 'PATCH',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return false; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Select best answer failed.');
        return false;
      }
      return true;
    }

    async function deleteQuestion(questionId) {
      const res = await fetch(`/api/questions/${questionId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return false; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Delete question failed.');
        return false;
      }
      return true;
    }

    function renderQuestion() {
      document.getElementById('q-title').textContent = question?.title ?? '(no title)';

      const askedBy = question?.user?.name ?? '-';
      const tankName = question?.tank?.name ?? '-';
      const created = fmtDate(question?.created_at);
      const status = question?.status ?? 'open';

      document.getElementById('q-meta').innerHTML = `
        <span>Asked by <strong>${escapeHtml(askedBy)}</strong></span>
        <span>Tank: <strong>${escapeHtml(tankName)}</strong></span>
        <span>Created: ${escapeHtml(created)}</span>
        <span>Status: ${badgeStatus(status)}</span>
      `;

      document.getElementById('q-content').textContent = question?.content ?? '';

      const img = question?.image_path;
      const wrap = document.getElementById('q-image-wrap');
      const imgEl = document.getElementById('q-image');

      function safeUrl(path) {
        if (!path) return '';
        const p = String(path);
        if (p.startsWith('http://') || p.startsWith('https://')) return p;
        if (p.startsWith('/')) return p;
        return '/' + p.replace(/^\/+/, '');
      }

      if (img) {
        wrap.style.display = '';
        imgEl.src = safeUrl(img);
      } else {
        wrap.style.display = 'none';
        imgEl.src = '';
      }


      const isOwner = me && question && (Number(me.id) === Number(question.user?.id));
      document.getElementById('btn-edit-q').style.display = 'none';
      document.getElementById('btn-del-q').style.display = isOwner ? '' : 'none';
    }

    function renderAnswers() {
      document.getElementById('ans-title').textContent = `${answers.length} answer(s)`;

      const wrap = document.getElementById('answers-wrap');
      if (!answers.length) {
        wrap.innerHTML = `<div style="color:#6b7280;">No answers yet.</div>`;
        return;
      }

      const isOwnerQ = me && question && (Number(me.id) === Number(question.user?.id));
      const isAdmin = (me?.role === 'admin');

      wrap.innerHTML = answers.map(a => {
        const by = a?.user?.name ?? '-';
        const when = fmtDate(a?.created_at);
        const best = a?.is_accepted ? 'answer-best' : '';
        const bestChip = a?.is_accepted ? `<span class="badge badge-best">Best answer</span>` : '';

        const canDelete = me && (Number(me.id) === Number(a.user_id) || isOwnerQ);

        // ✅ only answer owner can edit, and admin cannot edit
        const canEdit = me && !isAdmin && (Number(me.id) === Number(a.user_id));

        const btnAccept = (isOwnerQ && !a?.is_accepted)
          ? `<button class="btn btn-xs btn-primary" data-accept="${a.id}">Mark as best</button>`
          : ``;

        const btnEdit = canEdit
          ? `<button class="btn btn-xs btn-secondary" data-edit-answer="${a.id}">Edit</button>`
          : ``;

        const btnDel = canDelete
          ? `<button class="btn btn-xs btn-secondary" data-del-answer="${a.id}">Delete</button>`
          : ``;

        const isEditing = (editingAnswerId && Number(editingAnswerId) === Number(a.id));

        const bodyHtml = isEditing
          ? `
            <textarea class="form-control" rows="5" data-edit-text="${a.id}">${escapeHtml(a?.content ?? '')}</textarea>
            <div style="margin-top:8px; display:flex; gap:8px; justify-content:flex-end;">
              <button class="btn btn-xs btn-primary" data-save-answer="${a.id}">Save</button>
              <button class="btn btn-xs btn-secondary" data-cancel-edit="${a.id}">Cancel</button>
            </div>
          `
          : `<div class="answer-body">${escapeHtml(a?.content ?? '')}</div>`;

        return `
          <article class="answer-item ${best}">
            ${bodyHtml}
            <div class="answer-meta" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
              <span>Answered by <strong>${escapeHtml(by)}</strong></span>
              <span>${escapeHtml(when)}</span>
              ${bestChip}
              <span style="margin-left:auto; display:flex; gap:8px;">
                ${btnAccept}
                ${btnEdit}
                ${btnDel}
              </span>
            </div>
          </article>
        `;
      }).join('');
    }

    async function reload() {
      const id = questionIdFromUrl();
      if (!id) { showError('Missing question_id on URL.'); return; }

      const data = await fetchDetail(id);
      if (!data) return;

      question = data.question;
      answers = Array.isArray(data.answers) ? data.answers : [];

      hideError();
      renderQuestion();
      renderAnswers();
    }

    async function init() {
      hideError();

      const id = questionIdFromUrl();
      if (!id) { showError('Missing question_id on URL. Example: /qa/question_detail?question_id=1'); return; }

      me = await apiMe();

      await reload();

      document.getElementById('a-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();

        const content = document.getElementById('answer-content').value.trim();
        if (!content) { showError('Answer content is required.'); return; }

        const ok = await createAnswer(id, content);
        if (!ok) return;

        document.getElementById('answer-content').value = '';
        await reload();
      });

      document.getElementById('answers-wrap').addEventListener('click', async (e) => {
        const btnAccept = e.target.closest('[data-accept]');
        if (btnAccept) {
          const aid = btnAccept.getAttribute('data-accept');
          if (!confirm('Mark this as best answer?')) return;

          const ok = await acceptAnswer(id, aid);
          if (!ok) return;

          await reload();
          return;
        }

        const btnEdit = e.target.closest('[data-edit-answer]');
        if (btnEdit) {
          editingAnswerId = btnEdit.getAttribute('data-edit-answer');
          renderAnswers();
          return;
        }

        const btnCancel = e.target.closest('[data-cancel-edit]');
        if (btnCancel) {
          editingAnswerId = null;
          renderAnswers();
          return;
        }

        const btnSave = e.target.closest('[data-save-answer]');
        if (btnSave) {
          const aid = btnSave.getAttribute('data-save-answer');
          const ta = document.querySelector(`[data-edit-text="${aid}"]`);
          const newContent = (ta?.value ?? '').trim();
          if (!newContent) { showError('Answer content is required.'); return; }

          const ok = await updateAnswer(aid, newContent);
          if (!ok) return;

          editingAnswerId = null;
          await reload();
          return;
        }

        const btnDel = e.target.closest('[data-del-answer]');
        if (btnDel) {
          const aid = btnDel.getAttribute('data-del-answer');
          if (!confirm('Delete this answer?')) return;

          const ok = await deleteAnswer(aid);
          if (!ok) return;

          editingAnswerId = null;
          await reload();
          return;
        }
      });

      document.getElementById('btn-del-q').addEventListener('click', async () => {
        if (!confirm('Delete this question (and all its answers)?')) return;
        const ok = await deleteQuestion(id);
        if (!ok) return;
        window.location.href = `{{ route('qa.questions_list') }}`;
      });
    }

    document.addEventListener('DOMContentLoaded', init);
  </script>
@endsection
