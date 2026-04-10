{{-- resources/views/tanks/add_plant_to_tank.blade.php --}}
@extends('layouts.site')

@section('title', 'Add plant to tank – Aquatic Plant Advisor')
@section('nav_tanks_active', 'active')

@section('content')
  <section class="section">
    <h1 class="section-title">Add plant to tank</h1>
    <p class="section-subtitle">
      Pick a plant from the library, then set the planting date and note.
    </p>

    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <div class="grid-2" style="grid-template-columns: 2fr 1.3fr;">
      <section class="card">
        <div class="card-header card-header-inline">
          <div>
            <h2 class="card-title">Plant library</h2>
            <p class="card-subtitle">Search curated plants and attach them to this tank.</p>
          </div>
          <input id="plant-search" class="form-control form-control-sm" placeholder="Search plant…">
        </div>

        <div class="table-wrapper">
          <table class="table" id="plant-table">
            <thead>
            <tr>
              <th style="width: 26%;">Plant</th>
              <th style="width: 14%;">Difficulty</th>
              <th style="width: 14%;">Light</th>
              <th style="width: 22%;">pH range</th>
              <th>Actions</th>
            </tr>
            </thead>
            <tbody id="plant-tbody">
              <tr><td colspan="5" style="color:#6b7280;">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="card">
        <h2 class="card-title">Plant placement</h2>
        <p class="card-subtitle">
          Confirm which plant you’re adding and when it was planted.
        </p>

        <div class="metric-item" style="margin-bottom:10px;">
          <div class="metric-label">Selected plant</div>
          <div id="selected-plant" class="metric-value" style="font-size:16px;">None selected</div>
          <div id="restore-hint" style="display:none;margin-top:6px;color:#f59e0b;font-size:13px;">
            This plant was previously removed from this tank. It will be restored.
          </div>
        </div>

        <form id="attach-form">
          @csrf
          <input type="hidden" id="selected-plant-id" value="">

          <div class="form-group">
            <label for="planted-at">Planted at</label>
            <input type="date" id="planted-at" class="form-control">
          </div>

          <div class="form-group">
            <label for="plant-position">Position (optional)</label>
            <input id="plant-position" class="form-control" placeholder="Foreground, midground, on wood…">
          </div>

          <div class="form-group">
            <label for="plant-note">Note (optional)</label>
            <textarea id="plant-note" rows="3" class="form-control"
                      placeholder="Special care notes, trimming schedule, etc."></textarea>
          </div>

          <div class="form-actions">
            <button id="btn-submit" type="submit" class="btn btn-primary">Add to tank</button>
            <button type="button" class="btn btn-secondary" id="btn-cancel">Cancel</button>
          </div>
        </form>
      </section>
    </div>
  </section>

  <script>
    const CSRF = @json(csrf_token());
    const alertBox = document.getElementById('page-alert');

    const plantTbody = document.getElementById('plant-tbody');
    const plantSearch = document.getElementById('plant-search');

    const selectedPlantLabel = document.getElementById('selected-plant');
    const selectedPlantIdInput = document.getElementById('selected-plant-id');

    const restoreHint = document.getElementById('restore-hint');
    const btnSubmit = document.getElementById('btn-submit');

    let trashedPlantIdSet = new Set();

    function showError(msg) {
      alertBox.style.display = 'block';
      alertBox.textContent = msg;
    }
    function hideError() {
      alertBox.style.display = 'none';
      alertBox.textContent = '';
    }

    function tankIdFromUrl() {
      const url = new URL(window.location.href);
      return url.searchParams.get('tank_id');
    }

    function escapeHtml(v) {
      return String(v ?? '').replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }

    function fmtPhRange(p) {
      const a = p?.ph_min ?? null;
      const b = p?.ph_max ?? null;
      if (a === null && b === null) return '-';
      if (a !== null && b !== null) return `${a} – ${b}`;
      return a !== null ? `${a}+` : `≤ ${b}`;
    }

    async function fetchPlants() {
      const res = await fetch('/api/plants', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load plant library.');
        return null;
      }
      return Array.isArray(json.data) ? json.data : [];
    }

    async function fetchTrashedTankPlants(tankId) {
      const res = await fetch(`/api/tanks/${tankId}/tank-plants?view=trash`, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) return [];
      const list = Array.isArray(json.data?.tank_plants) ? json.data.tank_plants : [];
      return list;
    }

    function renderPlantRows(plants) {
      if (!plants.length) {
        plantTbody.innerHTML = `<tr><td colspan="5" style="color:#6b7280;">No plants found.</td></tr>`;
        return;
      }

      plantTbody.innerHTML = plants.map(p => {
        const name = p?.name ?? `Plant #${p?.id ?? '-'}`;
        const diff = p?.difficulty ?? '-';
        const light = p?.light_level ?? '-';
        const ph = fmtPhRange(p);

        return `
          <tr data-plant-id="${p.id}" data-plant-name="${escapeHtml(name)}">
            <td>${escapeHtml(name)}</td>
            <td>${escapeHtml(diff)}</td>
            <td>${escapeHtml(light)}</td>
            <td>${escapeHtml(ph)}</td>
            <td><button type="button" class="btn btn-xs btn-secondary btn-select">Select</button></td>
          </tr>
        `;
      }).join('');

      document.querySelectorAll('.btn-select').forEach(btn => {
        btn.addEventListener('click', () => {
          const row = btn.closest('tr');
          const plantId = Number(row.getAttribute('data-plant-id'));
          const plantName = row.getAttribute('data-plant-name');

          selectedPlantIdInput.value = String(plantId);
          selectedPlantLabel.textContent = plantName;

          const isTrashed = trashedPlantIdSet.has(plantId);
          restoreHint.style.display = isTrashed ? 'block' : 'none';
          btnSubmit.textContent = isTrashed ? 'Restore to tank' : 'Add to tank';

          hideError();
        });
      });
    }

    function bindSearch(plants) {
      plantSearch.addEventListener('input', () => {
        const term = plantSearch.value.toLowerCase();
        const filtered = plants.filter(p => String(p?.name ?? '').toLowerCase().includes(term));
        renderPlantRows(filtered);
      });
    }

    async function attachPlant() {
      const tankId = tankIdFromUrl();
      if (!tankId) { showError('Missing tank_id on URL. Example: /tanks/add_plant_to_tank?tank_id=1'); return; }

      const plantId = selectedPlantIdInput.value;
      if (!plantId) { showError('Please select a plant first.'); return; }

      const plantedAt = document.getElementById('planted-at').value || null;
      const position = document.getElementById('plant-position').value.trim() || null;
      const note = document.getElementById('plant-note').value.trim() || null;

      const res = await fetch(`/api/tanks/${tankId}/plants`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          plant_id: Number(plantId),
          planted_at: plantedAt,
          position: position,
          note: note,
        }),
      });

      if (res.status === 401) { window.location.href = '/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Attach failed.');
        return;
      }

      window.location.href = `{{ route('tanks.tank_detail') }}?tank_id=${tankId}`;
    }

    async function init() {
      const tankId = tankIdFromUrl();
      document.getElementById('btn-cancel').onclick = () => {
        if (!tankId) window.location.href = `{{ route('tanks.my_tanks') }}`;
        else window.location.href = `{{ route('tanks.tank_detail') }}?tank_id=${tankId}`;
      };

      hideError();

      if (tankId) {
        const trash = await fetchTrashedTankPlants(tankId);
        trashedPlantIdSet = new Set(trash.map(x => Number(x.plant_id)).filter(x => Number.isFinite(x)));
      }

      const plants = await fetchPlants();
      if (!plants) return;

      renderPlantRows(plants);
      bindSearch(plants);

      document.getElementById('attach-form').addEventListener('submit', (e) => {
        e.preventDefault();
        hideError();
        attachPlant();
      });
    }

    document.addEventListener('DOMContentLoaded', init);
  </script>
@endsection
