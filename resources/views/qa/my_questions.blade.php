{{-- resources/views/qa/my_questions.blade.php --}}
@extends('layouts.site')

@section('title', 'My questions – Aquatic Plant Advisor')
@section('nav_qa_active', 'active')

@section('content')
  <section class="section">
    <h1 class="section-title">My questions</h1>
    <p class="section-subtitle">
      Track all questions you have posted.
    </p>

    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <div class="qa-toolbar">
      <div class="qa-toolbar-left">
        <input id="myqa-search" class="form-control qa-search" placeholder="Search in my questions…">
        <select id="myqa-status-filter" class="form-control form-control-sm">
          <option value="all">All statuses</option>
          <option value="open">Open</option>
          <option value="resolved">Resolved</option>
        </select>
      </div>

      <div class="qa-toolbar-right" style="display:flex; gap:10px; align-items:center;">
        <button class="btn btn-secondary" id="btn-back-list">Back to list</button>
        <button class="btn btn-primary" id="btn-ask">+ Ask new question</button>
      </div>
    </div>

    <section class="card">
      <div class="table-wrapper">
        <table class="table" id="myqa-table">
          <thead>
          <tr>
            <th style="width: 40%;">Question</th>
            <th style="width: 12%;">Tank</th>
            <th style="width: 12%;">Status</th>
            <th style="width: 10%;">Answers</th>
            <th style="width: 12%;">Image</th>
            <th>Created</th>
          </tr>
          </thead>

          <tbody id="myqa-tbody">
            <tr><td colspan="6" style="color:#6b7280;">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
      <div id="myqa-meta" class="page-subtitle"></div>
      <div style="display:flex; gap:8px;">
        <button class="btn btn-secondary btn-xs" id="btn-prev">Prev</button>
        <button class="btn btn-secondary btn-xs" id="btn-next">Next</button>
      </div>
    </div>
  </section>

  <script>
    const alertBox = document.getElementById('page-alert');

    const qSearch = document.getElementById('myqa-search');
    const qStatus = document.getElementById('myqa-status-filter');
    const tbody   = document.getElementById('myqa-tbody');
    const meta    = document.getElementById('myqa-meta');

    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');

    let state = { q: '', status: 'all', page: 1, per_page: 10, last_page: 1, total: 0 };

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

    function fmtDate(iso) {
      if (!iso) return '-';
      // 2026-01-07T12:34:56 -> 2026-01-07
      return String(iso).slice(0, 10);
    }

    function badgeStatus(status) {
      if (status === 'resolved') return `<span class="badge badge-resolved">Resolved</span>`;
      return `<span class="badge badge-open">Open</span>`;
    }

    function safeUrl(path) {
      if (!path) return '';
      const p = String(path);
      if (p.startsWith('http://') || p.startsWith('https://')) return p;
      if (p.startsWith('/')) return p;
      return '/' + p.replace(/^\/+/, '');
    }

    async function fetchMyQuestions() {
      const params = new URLSearchParams();
      if (state.q) params.set('q', state.q);
      if (state.status) params.set('status', state.status);
      params.set('page', String(state.page));
      params.set('per_page', String(state.per_page));

      const res = await fetch(`/api/my-questions?${params.toString()}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load my questions.');
        return null;
      }
      return json.data;
    }

    function renderRows(items) {
      if (!items || !items.length) {
        tbody.innerHTML = `<tr><td colspan="6" style="color:#6b7280;">No questions found.</td></tr>`;
        return;
      }

      tbody.innerHTML = items.map(it => {
        const tankName     = it?.tank?.name ?? '-';
        const title        = it?.title ?? '(no title)';
        const status       = it?.status ?? 'open';
        const answersCount = it?.answers_count ?? 0;
        const created      = fmtDate(it?.created_at);
        const hasBest      = !!it?.has_best_answer;

        const href = `{{ route('qa.question_detail') }}?question_id=${encodeURIComponent(it.id)}`;

        const bestMark = hasBest
          ? `<span class="badge badge-best" style="margin-left:8px;">Best</span>`
          : ``;

        const img = it?.image_path ? safeUrl(it.image_path) : '';
        const imgCell = img
          ? `<a href="${href}" title="View question">
               <img src="${img}" class="table-img" alt="Question image">
             </a>`
          : `<span style="color:#9ca3af; font-size:12px;">—</span>`;

        return `
          <tr>
            <td class="title-cell">
              <a href="${href}" class="question-title-link">${escapeHtml(title)}</a>
              <div class="question-meta">${bestMark}</div>
            </td>
            <td>${escapeHtml(tankName)}</td>
            <td class="status-cell">${badgeStatus(status)}</td>
            <td>${escapeHtml(answersCount)}</td>
            <td>${imgCell}</td>
            <td>${escapeHtml(created)}</td>
          </tr>
        `;
      }).join('');
    }

    function renderMeta(m) {
      state.last_page = m?.last_page ?? 1;
      state.total     = m?.total ?? 0;

      const current = m?.current_page ?? state.page;
      meta.textContent = `Page ${current} / ${state.last_page} • Total: ${state.total}`;

      btnPrev.disabled = state.page <= 1;
      btnNext.disabled = state.page >= state.last_page;
    }

    async function load() {
      hideError();
      tbody.innerHTML = `<tr><td colspan="6" style="color:#6b7280;">Loading...</td></tr>`;

      const data = await fetchMyQuestions();
      if (!data) return;

      renderRows(data.items || []);
      renderMeta(data.meta || {});
    }

    let tmr = null;
    function scheduleLoad(resetPage = false) {
      if (resetPage) state.page = 1;
      clearTimeout(tmr);
      tmr = setTimeout(load, 250);
    }

    document.getElementById('btn-back-list').onclick = () => {
      window.location.href = `{{ route('qa.questions_list') }}`;
    };

    document.getElementById('btn-ask').onclick = () => {
      window.location.href = `{{ route('qa.ask_question') }}`;
    };

    qSearch.addEventListener('input', () => {
      state.q = qSearch.value.trim();
      scheduleLoad(true);
    });

    qStatus.addEventListener('change', () => {
      state.status = qStatus.value;
      scheduleLoad(true);
    });

    btnPrev.addEventListener('click', () => {
      if (state.page > 1) { state.page--; load(); }
    });

    btnNext.addEventListener('click', () => {
      if (state.page < state.last_page) { state.page++; load(); }
    });

    document.addEventListener('DOMContentLoaded', load);
  </script>
@endsection
