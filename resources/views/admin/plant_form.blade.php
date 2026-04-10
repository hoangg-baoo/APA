{{-- resources/views/admin/plant_form.blade.php --}}
@extends('layouts.admin')

@section('title', 'Admin – Plant form')
@section('sidebar_plants_active', 'active')

@section('content')
  <div class="page-header">
    <div>
      <h1 id="pageTitle" class="page-title">Plant details</h1>
      <p class="page-subtitle">
        Create or update a plant in the master library used by all tanks and the image retrieval module.
      </p>
    </div>
    <div class="page-actions">
      <button class="btn btn-secondary" onclick="window.location.href='{{ url('/admin/plants') }}'">
        ← Back to plant list
      </button>
    </div>
  </div>

  <div class="page-content">
    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <section class="card">
      <form id="plantForm" class="form-grid-2" novalidate>
        <div class="form-group">
          <label for="pl_name">Name *</label>
          <input type="text" id="pl_name" class="form-control" placeholder="Example: Rotala rotundifolia" autocomplete="off">
        </div>

        <div class="form-group">
          <label for="pl_difficulty">Difficulty *</label>
          <select id="pl_difficulty" class="form-control">
            <option value="">-- Select difficulty --</option>
            <option value="easy">Easy</option>
            <option value="medium">Medium</option>
            <option value="hard">Hard</option>
          </select>
        </div>

        <div class="form-group form-group-full">
          <label for="pl_description">Short description</label>
          <textarea id="pl_description" rows="3" class="form-control"
                    placeholder="General description of the plant (growth form, color, etc.)."></textarea>
        </div>

        <div class="form-group form-group-full">
          <label for="pl_care">Care guide</label>
          <textarea id="pl_care" rows="6" class="form-control"
                    placeholder="Detailed notes about light, CO₂, trimming, fertilization..."></textarea>
        </div>

        <div class="form-group">
          <label for="pl_light">Light level *</label>
          <select id="pl_light" class="form-control">
            <option value="">-- Select light level --</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>

        <div class="form-group">
          <label>pH range</label>
          <div style="display:flex;gap:8px;">
            <input
              type="number"
              id="pl_ph_min"
              class="form-control"
              placeholder="Min (e.g. 6.0)"
              step="0.1"
              min="0" max="14"
              inputmode="decimal"
            >
            <input
              type="number"
              id="pl_ph_max"
              class="form-control"
              placeholder="Max (e.g. 7.5)"
              step="0.1"
              min="0" max="14"
              inputmode="decimal"
            >
          </div>
          <p style="font-size:12px;color:#6b7280;margin-top:4px;">
            Allowed: <strong>0 → 14</strong> (min must be ≤ max).
          </p>
        </div>

        <div class="form-group">
          <label>Temperature range (°C)</label>
          <div style="display:flex;gap:8px;">
            <input
              type="number"
              id="pl_temp_min"
              class="form-control"
              placeholder="Min (e.g. 20)"
              step="0.1"
              min="0" max="40"
              inputmode="decimal"
            >
            <input
              type="number"
              id="pl_temp_max"
              class="form-control"
              placeholder="Max (e.g. 28)"
              step="0.1"
              min="0" max="40"
              inputmode="decimal"
            >
          </div>
          <p style="font-size:12px;color:#6b7280;margin-top:4px;">
            Allowed: <strong>0 → 40</strong> °C (min must be ≤ max).
          </p>
        </div>

        <div class="form-group">
          <label for="pl_image_file">Cover image (upload file)</label>
          <input type="file" id="pl_image_file" class="form-control" accept="image/*">
          <div style="margin-top:8px; display:flex; gap:10px; align-items:center;">
            <img id="imgPreview" src="" alt="preview" style="display:none; width:90px; height:60px; object-fit:cover; border-radius:8px; border:1px solid #e5e7eb;">
            <span id="imgHint" style="font-size:12px;color:#6b7280;">Optional. If uploaded, it will be saved to public/plants/&lt;slug&gt;/N.jpg</span>
          </div>
        </div>

        <div class="form-group">
          <label for="pl_image_sample">Image sample path (optional)</label>
          <input type="text" id="pl_image_sample" class="form-control"
                 placeholder="Example: /plants/rotala_rotundifolia/1.jpg">
          <p style="font-size:12px;color:#6b7280;margin-top:4px;">
            Nếu bạn đã có sẵn ảnh trong <code>public/plants/...</code> thì dán path vào đây cũng được.
          </p>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" id="btnSave">Save plant</button>
          <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ url('/admin/plants') }}'">
            Cancel
          </button>
        </div>
      </form>
    </section>
  </div>

  <script>
    const CSRF = @json(csrf_token());
    const API = '/api/admin/plants';

    const alertBox = document.getElementById('page-alert');
    const pageTitle = document.getElementById('pageTitle');

    const el = {
      name: document.getElementById('pl_name'),
      difficulty: document.getElementById('pl_difficulty'),
      description: document.getElementById('pl_description'),
      care: document.getElementById('pl_care'),
      light: document.getElementById('pl_light'),
      phMin: document.getElementById('pl_ph_min'),
      phMax: document.getElementById('pl_ph_max'),
      tempMin: document.getElementById('pl_temp_min'),
      tempMax: document.getElementById('pl_temp_max'),
      imageSample: document.getElementById('pl_image_sample'),
      imageFile: document.getElementById('pl_image_file'),
      imgPreview: document.getElementById('imgPreview'),
      form: document.getElementById('plantForm'),
      btnSave: document.getElementById('btnSave'),
    };

    function showError(msg) {
      alertBox.style.display = 'block';
      alertBox.textContent = msg;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function hideError() {
      alertBox.style.display = 'none';
      alertBox.textContent = '';
    }

    function plantIdFromUrl() {
      const url = new URL(window.location.href);
      return url.searchParams.get('plant_id');
    }

    function numOrNull(v) {
      const s = String(v ?? '').trim();
      if (!s) return null;
      const n = Number(s);
      return Number.isFinite(n) ? n : null;
    }

    function validateRanges() {
      // pH: 0..14
      const phMin = numOrNull(el.phMin.value);
      const phMax = numOrNull(el.phMax.value);

      if (phMin !== null && (phMin < 0 || phMin > 14)) return 'pH min must be between 0 and 14.';
      if (phMax !== null && (phMax < 0 || phMax > 14)) return 'pH max must be between 0 and 14.';
      if (phMin !== null && phMax !== null && phMin > phMax) return 'pH range invalid: min must be ≤ max.';

      // Temp: 0..40
      const tMin = numOrNull(el.tempMin.value);
      const tMax = numOrNull(el.tempMax.value);

      if (tMin !== null && (tMin < 0 || tMin > 40)) return 'Temperature min must be between 0 and 40.';
      if (tMax !== null && (tMax < 0 || tMax > 40)) return 'Temperature max must be between 0 and 40.';
      if (tMin !== null && tMax !== null && tMin > tMax) return 'Temperature range invalid: min must be ≤ max.';

      return null;
    }

    // block negative / exponent keys in number inputs
    function blockInvalidNumberKeys(e) {
      const badKeys = ['-', '+', 'e', 'E'];
      if (badKeys.includes(e.key)) e.preventDefault();
    }

    async function fetchPlant(id) {
      hideError();

      const res = await fetch(`${API}/${id}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load plant.');
        return null;
      }
      return json.data;
    }

    function fillForm(p) {
      el.name.value = p?.name ?? '';
      el.difficulty.value = p?.difficulty ?? '';
      el.description.value = p?.description ?? '';
      el.care.value = p?.care_guide ?? '';
      el.light.value = p?.light_level ?? '';

      el.phMin.value = p?.ph_min ?? '';
      el.phMax.value = p?.ph_max ?? '';
      el.tempMin.value = p?.temp_min ?? '';
      el.tempMax.value = p?.temp_max ?? '';

      el.imageSample.value = p?.image_sample ?? '';

      if (p?.thumb) {
        el.imgPreview.src = p.thumb;
        el.imgPreview.style.display = 'block';
      }
    }

    function appendField(fd, key, val) {
      const v = (val === null || val === undefined) ? '' : String(val);
      fd.append(key, v);
    }

    async function savePlant(e) {
      e.preventDefault();
      hideError();

      const id = plantIdFromUrl();

      const name = String(el.name.value ?? '').trim();
      const difficulty = String(el.difficulty.value ?? '').trim();
      const light = String(el.light.value ?? '').trim();

      if (!name) { showError('Name is required.'); return; }
      if (!difficulty) { showError('Difficulty is required.'); return; }
      if (!light) { showError('Light level is required.'); return; }

      const rangeErr = validateRanges();
      if (rangeErr) { showError(rangeErr); return; }

      el.btnSave.disabled = true;

      const fd = new FormData();
      appendField(fd, 'name', name);
      appendField(fd, 'difficulty', difficulty);
      appendField(fd, 'light_level', light);

      appendField(fd, 'description', String(el.description.value ?? '').trim());
      appendField(fd, 'care_guide', String(el.care.value ?? '').trim());
      appendField(fd, 'image_sample', String(el.imageSample.value ?? '').trim());

      appendField(fd, 'ph_min', el.phMin.value);
      appendField(fd, 'ph_max', el.phMax.value);
      appendField(fd, 'temp_min', el.tempMin.value);
      appendField(fd, 'temp_max', el.tempMax.value);

      const file = el.imageFile.files && el.imageFile.files[0] ? el.imageFile.files[0] : null;
      if (file) fd.append('image_file', file);

      let url = API;
      let method = 'POST';

      if (id) {
        url = `${API}/${id}`;
        fd.append('_method', 'PUT'); // method spoof
      }

      const res = await fetch(url, {
        method,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: fd,
      });

      if (res.status === 401) { window.location.href = '/admin/login'; return; }

      const json = await res.json().catch(() => null);
      el.btnSave.disabled = false;

      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Save failed.');
        return;
      }

      window.location.href = `{{ url('/admin/plants') }}`;
    }

    function setupPreview() {
      el.imageFile.addEventListener('change', () => {
        const file = el.imageFile.files && el.imageFile.files[0] ? el.imageFile.files[0] : null;
        if (!file) return;

        const url = URL.createObjectURL(file);
        el.imgPreview.src = url;
        el.imgPreview.style.display = 'block';
      });
    }

    function setupNumberGuards() {
      [el.phMin, el.phMax, el.tempMin, el.tempMax].forEach(inp => {
        if (!inp) return;
        inp.addEventListener('keydown', blockInvalidNumberKeys);
        inp.addEventListener('input', () => {
          // if user paste "-1", clean it
          const v = String(inp.value ?? '');
          if (v.includes('-')) inp.value = v.replaceAll('-', '');
        });
      });
    }

    async function init() {
      const id = plantIdFromUrl();
      el.form.addEventListener('submit', savePlant);
      setupPreview();
      setupNumberGuards();

      if (id) {
        pageTitle.textContent = 'Edit plant';
        const plant = await fetchPlant(id);
        if (plant) fillForm(plant);
      } else {
        pageTitle.textContent = 'Create plant';
      }
    }

    document.addEventListener('DOMContentLoaded', init);
  </script>
@endsection
