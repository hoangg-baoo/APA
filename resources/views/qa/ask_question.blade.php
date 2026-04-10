{{-- resources/views/qa/ask_question.blade.php --}}
@extends('layouts.site')

@section('title', 'Ask a question – Aquatic Plant Advisor')
@section('nav_qa_active', 'active')

@section('content')
  <section class="section">
    <button class="btn btn-secondary btn-xs"
            onclick="window.location.href='{{ route('qa.questions_list') }}'">
      ← Back to Q&amp;A
    </button>

    <div id="page-alert" class="alert alert-danger" style="display:none; margin:12px 0;"></div>

    <section class="card" style="margin-top: 12px;">
      <h1 class="section-title" style="margin-bottom: 4px;">Ask a new question</h1>
      <p class="section-subtitle">
        Describe your issue clearly. Optionally attach one of your tanks.
      </p>

      <form id="q-form" class="form-grid-2" enctype="multipart/form-data">
        @csrf

        <div class="form-group form-group-full">
          <label for="q-title">Question title</label>
          <input id="q-title" class="form-control" placeholder="e.g. Why is my Monte Carlo carpet turning yellow?">
        </div>

        <div class="form-group form-group-full">
          <label for="q-content">Details</label>
          <textarea id="q-content" rows="6" class="form-control"
                    placeholder="Tank size, light, CO₂, fertilizing schedule, what changed recently..."></textarea>
        </div>

        <div class="form-group">
          <label for="q-tank">Related tank (optional)</label>
          <select id="q-tank" class="form-control">
            <option value="">-- Choose one of your tanks --</option>
          </select>
        </div>

        <div class="form-group">
          <label for="q-image">Attach image (optional)</label>
          <input id="q-image" name="image" type="file" accept="image/*" class="form-control">
          <div class="img-preview" id="q-preview-wrap" style="display:none;">
            <img id="q-preview-img" alt="Preview">
          </div>

          <div style="margin-top:6px; display:flex; gap:8px; align-items:center;">
            <button type="button" id="q-image-clear" class="btn btn-secondary btn-xs" style="display:none;">
              Remove image
            </button>
            <span id="q-image-info" style="font-size:12px; color:#6b7280;"></span>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Post question</button>
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

    function showError(msg) {
      alertBox.style.display = 'block';
      alertBox.textContent = msg;
    }
    function hideError() {
      alertBox.style.display = 'none';
      alertBox.textContent = '';
    }

    async function fetchMyTanks() {
      const res = await fetch('/api/tanks', {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load tanks.');
        return null;
      }
      return Array.isArray(json.data) ? json.data : [];
    }

    async function createQuestion(formData) {
      const res = await fetch('/api/questions', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: formData,
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Create question failed.');
        return null;
      }
      return json.data?.question || null;
    }

    function bindImagePreview() {
      const input = document.getElementById('q-image');
      const wrap  = document.getElementById('q-preview-wrap');
      const img   = document.getElementById('q-preview-img');
      const btnClear = document.getElementById('q-image-clear');
      const info = document.getElementById('q-image-info');

      function clear() {
        input.value = '';
        wrap.style.display = 'none';
        img.src = '';
        btnClear.style.display = 'none';
        info.textContent = '';
      }

      input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) { clear(); return; }

        // preview
        const reader = new FileReader();
        reader.onload = (e) => {
          img.src = e.target.result;
          wrap.style.display = '';
          btnClear.style.display = '';
          info.textContent = `${file.name} (${Math.round(file.size / 1024)} KB)`;
        };
        reader.readAsDataURL(file);
      });

      btnClear.addEventListener('click', clear);
    }

    async function init() {
      hideError();

      bindImagePreview();

      const tanks = await fetchMyTanks();
      if (tanks) {
        const sel = document.getElementById('q-tank');
        tanks.forEach(t => {
          const opt = document.createElement('option');
          opt.value = t.id;
          opt.textContent = t.name || `Tank #${t.id}`;
          sel.appendChild(opt);
        });
      }

      document.getElementById('q-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();

        const title = document.getElementById('q-title').value.trim();
        const content = document.getElementById('q-content').value.trim();
        const tankId = document.getElementById('q-tank').value || null;

        if (!title) { showError('Title is required.'); return; }
        if (!content) { showError('Content is required.'); return; }

        const fd = new FormData();
        fd.append('title', title);
        fd.append('content', content);
        if (tankId) fd.append('tank_id', String(tankId));

        const file = document.getElementById('q-image').files?.[0];
        if (file) fd.append('image', file);

        const q = await createQuestion(fd);
        if (!q) return;

        window.location.href = `{{ route('qa.question_detail') }}?question_id=${encodeURIComponent(q.id)}`;
      });
    }

    document.addEventListener('DOMContentLoaded', init);
  </script>
@endsection
