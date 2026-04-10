{{-- resources/views/admin/qa_detail.blade.php --}}
@extends('layouts.admin')

@section('title', 'Q&A detail – Admin')
@section('sidebar_qa_active', 'active')

@section('content')
  <div class="page-header">
    <div>
      <h1 class="page-title">Q&amp;A detail</h1>
      <p class="page-subtitle">View the question and manage answers. Hard delete is permanent.</p>
    </div>
    <div class="page-actions">
      <button class="btn btn-secondary" onclick="window.location.href='{{ url('/admin/qa') }}'">
        ← Back
      </button>
    </div>
  </div>

  <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

  <section class="card" id="q-card">
    <div style="color:#6b7280;">Loading...</div>
  </section>

  <section class="card">
    <div class="card-header-inline">
      <div>
        <div class="card-title">Answers</div>
        <div class="card-subtitle" id="answers-subtitle">—</div>
      </div>
    </div>

    <div id="answers-list" style="margin-top:12px;">
      <div style="color:#6b7280;">Loading...</div>
    </div>
  </section>

  <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const alertBox = document.getElementById('page-alert');
    const qCard = document.getElementById('q-card');
    const answersList = document.getElementById('answers-list');
    const answersSubtitle = document.getElementById('answers-subtitle');

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

    function statusBadge(status, hasBest) {
      const s = String(status ?? '').toLowerCase();
      if (s === 'resolved') return `<span class="badge badge-resolved">Resolved</span>`;
      if (hasBest) return `<span class="badge badge-best">Has best</span>`;
      return `<span class="badge badge-open">Open</span>`;
    }

    function acceptedBadge(isAccepted) {
      return isAccepted ? `<span class="badge badge-best" style="margin-left:8px;">Accepted</span>` : '';
    }

    function getQuestionId() {
      // URL: /admin/qa/{id}
      const parts = window.location.pathname.split('/').filter(Boolean);
      const last = parts[parts.length - 1];
      const id = Number(last);
      return Number.isFinite(id) ? id : null;
    }

    const QID = getQuestionId();
    const API_Q = '/api/admin/questions';
    const API_A = '/api/admin/answers';

    async function loadDetail() {
      hideError();
      if (!QID) {
        showError('Invalid question id in URL.');
        qCard.innerHTML = `<div style="color:#6b7280;">No data.</div>`;
        answersList.innerHTML = `<div style="color:#6b7280;">No data.</div>`;
        return;
      }

      qCard.innerHTML = `<div style="color:#6b7280;">Loading...</div>`;
      answersList.innerHTML = `<div style="color:#6b7280;">Loading...</div>`;

      const res = await fetch(`${API_Q}/${QID}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load question.');
        qCard.innerHTML = `<div style="color:#6b7280;">No data.</div>`;
        answersList.innerHTML = `<div style="color:#6b7280;">No data.</div>`;
        return;
      }

      const data = json.data;
      renderQuestion(data.question);
      renderAnswers(data.answers || []);
    }

    function renderQuestion(q) {
      const title = escapeHtml(q?.title ?? '');
      const content = escapeHtml(q?.content ?? '');
      const status = String(q?.status ?? 'open');
      const created = fmtDate(q?.created_at);
      const updated = fmtDate(q?.updated_at);

      const ownerName = escapeHtml(q?.user?.name ?? '—');
      const ownerEmail = escapeHtml(q?.user?.email ?? '');
      const tankName = escapeHtml(q?.tank?.name ?? '');
      const answersCount = Number(q?.answers_count ?? 0);
      const hasBest = !!q?.has_best_answer;

      const toggleLabel = (status === 'resolved') ? 'Re-open' : 'Resolve';
      const nextStatus = (status === 'resolved') ? 'open' : 'resolved';

      const ownerLine = ownerEmail ? `${ownerName} <span class="question-meta">(${ownerEmail})</span>` : ownerName;

      qCard.innerHTML = `
        <div class="card-header-inline">
          <div>
            <div class="card-title" style="font-size:18px;">${title}</div>
            <div class="question-meta" style="margin-top:6px;">
              ${statusBadge(status, hasBest)}
              <span style="margin-left:10px;">Owner: ${ownerLine}</span>
              ${tankName ? `<span style="margin-left:10px;">Tank: ${tankName}</span>` : ''}
              <span style="margin-left:10px;">Answers: ${answersCount}</span>
            </div>
          </div>
          <div class="table-actions">
            <button class="btn btn-secondary btn-xs" onclick="updateStatus('${nextStatus}')">${toggleLabel}</button>
            <button class="btn btn-secondary btn-xs btn-danger-soft" onclick="hardDeleteQuestion('${title}')">Delete</button>
          </div>
        </div>

        <div style="margin-top:12px;" class="question-body">${content}</div>

        <div class="question-meta" style="margin-top:10px;">
          Created: ${created} • Updated: ${updated}
        </div>
      `;
    }

    function renderAnswers(list) {
      answersSubtitle.textContent = `${list.length} answer(s)`;

      if (!list.length) {
        answersList.innerHTML = `<div style="color:#6b7280;">No answers.</div>`;
        return;
      }

      answersList.innerHTML = list.map(a => {
        const id = a.id;
        const content = escapeHtml(a.content ?? '');
        const isAccepted = !!a.is_accepted;
        const created = fmtDate(a.created_at);

        const authorName = escapeHtml(a?.user?.name ?? '—');
        const authorEmail = escapeHtml(a?.user?.email ?? '');

        const authorLine = authorEmail ? `${authorName} <span class="question-meta">(${authorEmail})</span>` : authorName;

        return `
          <div class="answer-item ${isAccepted ? 'answer-best' : ''}">
            <div class="answer-meta">
              <div>
                <strong>${authorLine}</strong>
                ${acceptedBadge(isAccepted)}
              </div>
              <div style="margin-left:auto;" class="question-meta">${created}</div>
            </div>

            <div class="answer-body" style="margin-top:8px;">${content}</div>

            <div class="answer-actions table-actions">
              <button class="btn btn-secondary btn-xs btn-danger-soft" onclick="hardDeleteAnswer(${id}, ${isAccepted ? 'true' : 'false'})">
                Delete answer
              </button>
            </div>
          </div>
        `;
      }).join('');
    }

    async function updateStatus(status) {
      hideError();
      const res = await fetch(`${API_Q}/${QID}/status`, {
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
        return;
      }

      await loadDetail();
    }

    async function hardDeleteQuestion(title) {
      hideError();
      const ok = confirm(`HARD DELETE this question?\n\n#${QID}: ${title}\n\nThis will permanently delete the question and ALL answers (DB cascade).`);
      if (!ok) return;

      const res = await fetch(`${API_Q}/${QID}`, {
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

      window.location.href = '{{ url('/admin/qa') }}';
    }

    async function hardDeleteAnswer(answerId, isAccepted) {
      hideError();
      const ok = confirm(`HARD DELETE this answer?\n\nAnswer #${answerId}\n\nThis is permanent.`);
      if (!ok) return;

      const res = await fetch(`${API_A}/${answerId}`, {
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
        showError(json?.message || 'Delete answer failed.');
        return;
      }

      await loadDetail();
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadDetail();
    });
  </script>
@endsection
