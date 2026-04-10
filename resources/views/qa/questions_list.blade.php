{{-- resources/views/qa/questions_list.blade.php --}}
@extends('layouts.site')

@section('title', 'Q&A – Aquatic Plant Advisor')
@section('nav_qa_active', 'active')

@section('content')
  <section class="section">
    <h1 class="section-title">Q&amp;A – Ask aquascaping experts</h1>
    <p class="section-subtitle">
      Browse questions, filter by status and ask your own when you need help with plants or water issues.
    </p>

    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <div class="qa-toolbar">
      <div class="qa-toolbar-left">
        <input id="qa-search" class="form-control qa-search" placeholder="Search questions…">
        <select id="qa-status-filter" class="form-control form-control-sm">
          <option value="all">All statuses</option>
          <option value="open">Open</option>
          <option value="resolved">Resolved</option>
        </select>
      </div>

      <div class="qa-toolbar-right" style="display:flex; gap:10px; align-items:center;">
        <button class="btn btn-secondary" id="btn-my-questions">My questions</button>
        <button class="btn btn-primary" id="btn-ask">+ Ask a question</button>
      </div>
    </div>

    <section class="card">
      <div class="table-wrapper">
        <table class="table table-qa" id="qa-table">
          <thead>
          <tr>
            <th style="width: 44%;">Question</th>
            <th style="width: 14%;">Tank</th>
            <th style="width: 12%;">Status</th>
            <th style="width: 10%;">Answers</th>
            <th style="width: 14%;">Asked by</th>
            <th>Created</th>
          </tr>
          </thead>
          <tbody id="qa-tbody">
            <tr><td colspan="6" style="color:#6b7280;">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
      <div id="qa-meta" class="page-subtitle"></div>
      <div style="display:flex; gap:8px;">
        <button class="btn btn-secondary btn-xs" id="btn-prev">Prev</button>
        <button class="btn btn-secondary btn-xs" id="btn-next">Next</button>
      </div>
    </div>
  </section>

  <script>
    const CSRF = @json(csrf_token());
    const alertBox = document.getElementById('page-alert');

    const qSearch = document.getElementById('qa-search');
    const qStatus = document.getElementById('qa-status-filter');
    const tbody = document.getElementById('qa-tbody');
    const meta = document.getElementById('qa-meta');

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
      return String(iso).slice(0, 10);
    }

    function badgeStatus(status) {
      if (status === 'resolved') return `<span class="badge badge-resolved">Resolved</span>`;
      return `<span class="badge badge-open">Open</span>`;
    }

    async function fetchQuestions() {
      const params = new URLSearchParams();
      if (state.q) params.set('q', state.q);
      if (state.status) params.set('status', state.status);
      params.set('page', String(state.page));
      params.set('per_page', String(state.per_page));

      const res = await fetch(`/api/questions?${params.toString()}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load questions.');
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
        const tankName = it?.tank?.name ?? '-';
        const askedBy = it?.user?.name ?? '-';
        const title = it?.title ?? '(no title)';
        const status = it?.status ?? 'open';
        const answersCount = it?.answers_count ?? 0;
        const created = fmtDate(it?.created_at);

        const bestMark = it?.has_best_answer ? `<span class="badge badge-best" style="margin-left:8px;">Best</span>` : '';

        const href = `{{ route('qa.question_detail') }}?question_id=${encodeURIComponent(it.id)}`;

        return `
          <tr>
            <td class="title-cell">
              <a href="${href}" class="question-title-link">${escapeHtml(title)}</a>
              <div class="question-meta">
                ${escapeHtml((it?.content ?? '').slice(0, 110))}${(it?.content ?? '').length > 110 ? '…' : ''} ${bestMark}
              </div>
            </td>
            <td>${escapeHtml(tankName)}</td>
            <td class="status-cell">${badgeStatus(status)}</td>
            <td>${escapeHtml(answersCount)}</td>
            <td>${escapeHtml(askedBy)}</td>
            <td>${escapeHtml(created)}</td>
          </tr>
        `;
      }).join('');
    }

    function renderMeta(m) {
      state.last_page = m?.last_page ?? 1;
      state.total = m?.total ?? 0;
      meta.textContent = `Page ${m?.current_page ?? state.page} / ${state.last_page} • Total: ${state.total}`;
      btnPrev.disabled = state.page <= 1;
      btnNext.disabled = state.page >= state.last_page;
    }

    async function load() {
      hideError();
      tbody.innerHTML = `<tr><td colspan="6" style="color:#6b7280;">Loading...</td></tr>`;

      const data = await fetchQuestions();
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

    document.getElementById('btn-ask').onclick = () => {
      window.location.href = `{{ route('qa.ask_question') }}`;
    };

    document.getElementById('btn-my-questions').onclick = () => {
      window.location.href = `{{ route('qa.my_questions') }}`;
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
      if (state.page > 1) {
        state.page--;
        load();
      }
    });

    btnNext.addEventListener('click', () => {
      if (state.page < state.last_page) {
        state.page++;
        load();
      }
    });

    document.addEventListener('DOMContentLoaded', load);
  </script>
@endsection
