{{-- resources/views/tanks/tank_form.blade.php --}}
@extends('layouts.site')

@section('title', 'Create / Edit Tank – Aquatic Plant Advisor')
@section('nav_tanks_active', 'active')

@section('content')
  <section class="section">
    <h1 id="page-title" class="section-title">Create a new tank</h1>
    <p class="section-subtitle">
      Define the basic setup for your aquarium: size, substrate, light and CO₂. You can attach plants and logs later.
    </p>

    <section class="card">
      <div id="form-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

      <form id="tank-form" class="form-grid-2">
        @csrf

        <div class="form-group">
          <label for="tank-name">Tank name</label>
          <input id="tank-name" class="form-control" placeholder="e.g. 60P Iwagumi" required>
        </div>

        <div class="form-group">
          <label>Size (L × W × H in cm)</label>
          <div style="display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:10px;">
            <input id="tank-length" type="number" inputmode="numeric" min="1" step="1"
                   class="form-control" placeholder="Length">
            <input id="tank-width" type="number" inputmode="numeric" min="1" step="1"
                   class="form-control" placeholder="Width">
            <input id="tank-height" type="number" inputmode="numeric" min="1" step="1"
                   class="form-control" placeholder="Height">
          </div>

          <div style="display:flex; align-items:center; gap:10px; margin-top:8px; flex-wrap:wrap;">
            <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#047857;">
              <input id="auto-volume" type="checkbox" checked>
              Auto-calculate Volume (L) from size
            </label>
            <span style="font-size:12px;color:#047857;">Formula: (L×W×H)/1000</span>
          </div>

          <div style="font-size:12px;color:#047857;margin-top:6px;">
            Leave empty if you don’t want to specify size.
          </div>
        </div>

        <div class="form-group">
          <label for="tank-volume">Volume (L)</label>
          <input id="tank-volume"
                 type="number"
                 inputmode="decimal"
                 min="0.1"
                 step="0.1"
                 class="form-control"
                 placeholder="e.g. 64">
          <div id="volume-hint" style="font-size:12px;color:#047857;margin-top:6px;display:none;">
            Auto-calculated from size. Uncheck “Auto-calculate” to edit manually.
          </div>
        </div>

        <div class="form-group">
          <label for="tank-substrate">Substrate</label>
          <select id="tank-substrate" class="form-control">
            <option value="">-- Select substrate --</option>
            <option value="aqua_soil">Aqua soil</option>
            <option value="sand">Sand</option>
            <option value="gravel">Gravel</option>
            <option value="nutrient_substrate">Nutrient substrate</option>
            <option value="lava_rock">Lava rock</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="form-group">
          <label for="tank-light">Light</label>
          <select id="tank-light" class="form-control">
            <option value="">-- Select light --</option>
            <option value="low">Low (6–7 hours/day)</option>
            <option value="medium">Medium (7–8 hours/day)</option>
            <option value="high">High (8+ hours/day)</option>
          </select>
        </div>

        <div class="form-group">
          <label for="tank-co2">CO₂</label>
          <select id="tank-co2" class="form-control">
            <option value="none">None</option>
            <option value="liquid">Liquid carbon</option>
            <option value="diy">DIY CO₂</option>
            <option value="pressurized">Pressurized CO₂ system</option>
          </select>
        </div>

        <div class="form-group form-group-full">
          <label for="tank-desc">Description / notes (optional)</label>
          <textarea id="tank-desc" rows="3" class="form-control"
                    placeholder="Short description of hardscape, livestock or goals for this tank."></textarea>
        </div>

        <div class="form-actions">
          <button id="btn-save" type="submit" class="btn btn-primary">Save tank</button>
          <button type="button" class="btn btn-secondary"
                  onclick="window.location.href='{{ route('tanks.my_tanks') }}'">
            Cancel
          </button>
        </div>
      </form>
    </section>
  </section>

  <script>
    const CSRF = @json(csrf_token());
    const API_TANKS = '/api/tanks';

    const el = (id) => document.getElementById(id);
    const alertBox = el('form-alert');
    const btnSave = el('btn-save');

    const lenEl = el('tank-length');
    const widEl = el('tank-width');
    const heiEl = el('tank-height');
    const volEl = el('tank-volume');
    const autoEl = el('auto-volume');
    const volHint = el('volume-hint');

    function showError(html) {
      alertBox.style.display = 'block';
      alertBox.innerHTML = html;
    }
    function hideError() {
      alertBox.style.display = 'none';
      alertBox.innerHTML = '';
    }

    function tankIdFromUrl() {
      const url = new URL(window.location.href);
      return url.searchParams.get('tank_id');
    }

    function numOrNull(v) {
      const s = String(v ?? '').trim();
      if (s === '') return null;
      const n = Number(s);
      return Number.isFinite(n) ? n : null;
    }

    function round1(n) {
      return Math.round(n * 10) / 10;
    }

    function setVolumeReadonly(isReadonly) {
      volEl.readOnly = isReadonly;
      volEl.style.background = isReadonly ? '#f9fafb' : '#ffffff';
      volHint.style.display = isReadonly ? 'block' : 'none';
    }

    function tryAutoCalcVolume() {
      if (!autoEl.checked) return;

      const L = numOrNull(lenEl.value);
      const W = numOrNull(widEl.value);
      const H = numOrNull(heiEl.value);

      // only calculate when all 3 exist and > 0
      if (L && W && H && L > 0 && W > 0 && H > 0) {
        const liters = (L * W * H) / 1000;
        volEl.value = round1(liters);
      } else {
        // if size incomplete, clear auto volume (optional behavior)
        volEl.value = '';
      }
    }

    function formPayload() {
      const length = numOrNull(lenEl.value);
      const width  = numOrNull(widEl.value);
      const height = numOrNull(heiEl.value);

      return {
        name: el('tank-name').value.trim(),
        length_cm: length,
        width_cm: width,
        height_cm: height,
        volume_liters: numOrNull(volEl.value),
        substrate: el('tank-substrate').value || null,
        light: el('tank-light').value || null,
        co2: el('tank-co2').value,
        description: el('tank-desc').value.trim() || null,
      };
    }

    async function fetchTank(id) {
      const res = await fetch(`${API_TANKS}/${id}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load tank.');
        return null;
      }
      return json.data;
    }

    function fillForm(t) {
      el('tank-name').value = t?.name ?? '';

      lenEl.value = (t?.length_cm ?? '') === null ? '' : (t?.length_cm ?? '');
      widEl.value = (t?.width_cm ?? '') === null ? '' : (t?.width_cm ?? '');
      heiEl.value = (t?.height_cm ?? '') === null ? '' : (t?.height_cm ?? '');

      volEl.value = (t?.volume_liters ?? '') === null ? '' : (t?.volume_liters ?? '');

      el('tank-substrate').value = t?.substrate ?? '';
      el('tank-light').value = t?.light ?? '';
      el('tank-co2').value = t?.co2 ?? 'none';

      el('tank-desc').value = t?.description ?? '';

      // after loading, apply readonly state & auto calc if enabled and size exists
      setVolumeReadonly(autoEl.checked);
      if (autoEl.checked) tryAutoCalcVolume();
    }

    function validateFrontend(data) {
      if (!data.name) return 'Tank name is required.';

      if (data.volume_liters !== null && !(data.volume_liters > 0)) {
        return 'Volume must be greater than 0.';
      }

      const hasAnySize = (data.length_cm !== null) || (data.width_cm !== null) || (data.height_cm !== null);
      const hasAllSize = (data.length_cm !== null) && (data.width_cm !== null) && (data.height_cm !== null);

      if (hasAnySize && !hasAllSize) {
        return 'Please fill Length, Width, and Height (or leave all empty).';
      }

      if (hasAllSize) {
        if (!(data.length_cm > 0) || !(data.width_cm > 0) || !(data.height_cm > 0)) {
          return 'Size values must be greater than 0.';
        }
      }

      return null;
    }

    async function init() {
      // initial readonly state
      setVolumeReadonly(autoEl.checked);

      const id = tankIdFromUrl();
      if (!id) return;

      el('page-title').textContent = 'Edit tank';
      const tank = await fetchTank(id);
      if (tank) fillForm(tank);
    }

    // prevent negative quick fix
    [volEl, lenEl, widEl, heiEl].forEach((input) => {
      input.addEventListener('input', () => {
        if (input.value && Number(input.value) < 0) input.value = '';
      });
    });

    // auto calc events
    [lenEl, widEl, heiEl].forEach((input) => {
      input.addEventListener('input', () => {
        tryAutoCalcVolume();
      });
    });

    autoEl.addEventListener('change', () => {
      setVolumeReadonly(autoEl.checked);
      if (autoEl.checked) tryAutoCalcVolume();
    });

    el('tank-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      hideError();

      // ensure latest auto calc before submit
      if (autoEl.checked) tryAutoCalcVolume();

      const id = tankIdFromUrl();
      const isEdit = !!id;

      const data = formPayload();

      const frontErr = validateFrontend(data);
      if (frontErr) {
        showError(frontErr);
        return;
      }

      btnSave.disabled = true;

      try {
        const res = await fetch(isEdit ? `${API_TANKS}/${id}` : API_TANKS, {
          method: isEdit ? 'PUT' : 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF,
          },
          credentials: 'same-origin',
          body: JSON.stringify(data),
        });

        if (res.status === 401) { window.location.href = '/login'; return; }

        const json = await res.json().catch(() => null);

        if (!res.ok || !json || json.success !== true) {
          if (json?.errors && typeof json.errors === 'object') {
            const msgs = Object.values(json.errors).flat().map(m => `<li>${m}</li>`).join('');
            showError(`<ul style="margin:0;padding-left:18px;">${msgs}</ul>`);
          } else {
            showError(json?.message || 'Save failed.');
          }
          return;
        }

        window.location.href = '{{ route('tanks.my_tanks') }}';
      } catch (err) {
        showError('Network error.');
      } finally {
        btnSave.disabled = false;
      }
    });

    document.addEventListener('DOMContentLoaded', init);
  </script>
@endsection
