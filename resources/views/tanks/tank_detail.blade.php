{{-- resources/views/tanks/tank_detail.blade.php --}}
@extends('layouts.site')

@section('title', 'Tank detail – Aquatic Plant Advisor')
@section('nav_tanks_active', 'active')

@section('content')
  <section class="section">
    <h1 id="tank-title" class="section-title">Tank detail</h1>
    <p id="tank-subtitle" class="section-subtitle">
      Loading...
    </p>

    <div id="page-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <!-- Tank summary card -->
    <section class="card-soft" style="margin-bottom: 18px;">
      <div class="grid-2" style="grid-template-columns: 1.8fr 1.2fr;">
        <div>
          <h3 class="card-title">Tank setup</h3>
          <p class="card-subtitle">Basic configuration and notes.</p>
          <ul class="simple-list" style="margin-top: 6px;">
            <li><strong>Size:</strong> <span id="tank-size">-</span></li>
            <li><strong>Substrate:</strong> <span id="tank-substrate">-</span></li>
            <li><strong>Light:</strong> <span id="tank-light">-</span></li>
            <li><strong>CO₂:</strong> <span id="tank-co2">-</span></li>
          </ul>

          <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
            <button id="btn-edit" class="btn btn-secondary btn-xs">Edit</button>
            <button id="btn-back" class="btn btn-secondary btn-xs">Back to My Tanks</button>
          </div>
        </div>

        <div>
          <h3 class="card-title">Health snapshot</h3>
          <p class="card-subtitle">Demo (you can improve later).</p>
          <div class="metrics-row">
            <div class="metric-item">
              <div class="metric-label">Plants</div>
              <div id="metric-plants" class="metric-value">0</div>
              <span class="metric-chip ok">Attached plants</span>
            </div>
            <div class="metric-item">
              <div class="metric-label">Created</div>
              <div id="metric-created" class="metric-value">-</div>
              <span class="metric-chip ok">Tank created date</span>
            </div>
            <div class="metric-item">
              <div class="metric-label">Volume</div>
              <div id="metric-volume" class="metric-value">-</div>
              <span class="metric-chip ok">Liters</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab-link active" data-tab="plants">Plants in this tank</button>
      <button class="tab-link" data-tab="water">Water overview</button>
    </div>

    <!-- Tab: plants -->
    <section id="tab-plants" class="tab-panel active">
      <section class="card">
        <div class="card-header card-header-inline">
          <div>
            <h2 class="card-title">Plants in this tank</h2>
            <p class="card-subtitle">
              Attached from the master plant library. Use this list to track requirements and growth.
            </p>
          </div>
          <div class="page-actions" style="display:flex;gap:10px;align-items:center;">
            <select id="tpView" class="form-control form-control-sm" style="width:140px;">
              <option value="active" selected>Active</option>
              <option value="trash">Trash</option>
            </select>
            <button id="btn-add-plant" class="btn btn-primary">+ Add plant</button>
          </div>
        </div>

        <div class="table-wrapper">
          <table class="table">
            <thead>
            <tr>
              <th style="width: 24%;">Plant</th>
              <th style="width: 16%;">Position</th>
              <th style="width: 14%;">Difficulty</th>
              <th style="width: 18%;">Added</th>
              <th>Notes</th>
              <th style="width: 14%;">Actions</th>
            </tr>
            </thead>
            <tbody id="plants-tbody">
              <tr><td colspan="6" style="color:#6b7280;">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>

    <!-- Tab: water -->
    <section id="tab-water" class="tab-panel">
      <section class="card">
        <div class="card-header card-header-inline">
          <div>
            <h2 class="card-title">Water logs &amp; charts</h2>
            <p class="card-subtitle">
              View the detailed table and chart of this tank’s water parameters.
            </p>
          </div>
          <div class="page-actions">
            <button id="btn-plant-logs" class="btn btn-secondary">
              Plant growth log
            </button>
            <button id="btn-water-monitoring" class="btn btn-primary">
              Open water monitoring
            </button>
          </div>
        </div>

        <p style="font-size: 14px; color:#4b5563; margin-bottom:10px;">
          If your API returns waterLogs in tank detail, we will show them here. If not, this table will be empty.
        </p>

        <div class="table-wrapper">
          <table class="table">
            <thead>
            <tr>
              <th>Measured at</th>
              <th>pH</th>
              <th>Temp (°C)</th>
              <th>NO₃ (ppm)</th>
              <th>Note</th>
            </tr>
            </thead>
            <tbody id="water-tbody">
              <tr><td colspan="5" style="color:#6b7280;">No data</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </section>

  <script>
    const CSRF = @json(csrf_token());
    const API_TANKS = '/api/tanks';

    const alertBox = document.getElementById('page-alert');
    const plantsTbody = document.getElementById('plants-tbody');
    const waterTbody = document.getElementById('water-tbody');

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

    function fmtDate(s) {
      if (!s) return '-';
      return String(s).slice(0, 10);
    }

    function escapeHtml(v) {
      return String(v ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }

    function getTankPlants(tank) {
      if (Array.isArray(tank?.tankPlants)) return tank.tankPlants;
      if (Array.isArray(tank?.tank_plants)) return tank.tank_plants;
      return [];
    }

    function getTankPlantsCount(tank) {
      if (typeof tank?.tankPlants_count === 'number') return tank.tankPlants_count;
      if (typeof tank?.tank_plants_count === 'number') return tank.tank_plants_count;
      return getTankPlants(tank).length;
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

    async function fetchTankPlantsByView(tankId, viewVal) {
      const res = await fetch(`/api/tanks/${tankId}/tank-plants?view=${encodeURIComponent(viewVal)}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to load tank plants.');
        return null;
      }
      return json.data?.tank_plants || [];
    }

    async function restoreTankPlant(id) {
      const res = await fetch(`/api/tank-plants/${id}/restore`, {
        method: 'PATCH',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return false; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Restore failed.');
        return false;
      }
      return true;
    }

    function renderTank(t) {
      document.getElementById('tank-title').textContent = t?.name ?? 'Tank detail';
      document.getElementById('tank-subtitle').textContent = t?.description ? t.description : 'Tank overview';

      const sizeText = t?.size ? t.size : '-';
      const volText = (t?.volume_liters ?? null) !== null ? `${t.volume_liters} L` : '-';

      document.getElementById('tank-size').textContent = `${sizeText}${volText !== '-' ? ` (${volText})` : ''}`;
      document.getElementById('tank-substrate').textContent = t?.substrate ?? '-';
      document.getElementById('tank-light').textContent = t?.light ?? '-';
      document.getElementById('tank-co2').textContent = t?.co2 ? 'Yes' : 'No';

      document.getElementById('metric-plants').textContent = String(getTankPlantsCount(t));
      document.getElementById('metric-created').textContent = fmtDate(t?.created_at);
      document.getElementById('metric-volume').textContent = (t?.volume_liters ?? '-') === null ? '-' : (t?.volume_liters ?? '-');
    }

    function renderPlantsActiveFromTank(tank) {
      const list = getTankPlants(tank);

      if (!list.length) {
        plantsTbody.innerHTML = `<tr><td colspan="6" style="color:#6b7280;">No plants attached yet.</td></tr>`;
        return;
      }

      plantsTbody.innerHTML = list.map(tp => {
        const plantName = tp?.plant?.name ?? `Plant #${tp?.plant_id ?? '-'}`;
        const difficulty = tp?.plant?.difficulty ?? '-';
        const added = fmtDate(tp?.planted_at);
        const position = tp?.position ?? '-';
        const note = tp?.note ?? '';
        const tpId = tp?.id;

        return `
          <tr>
            <td>${escapeHtml(plantName)}</td>
            <td>${escapeHtml(position)}</td>
            <td>${escapeHtml(difficulty)}</td>
            <td>${escapeHtml(added)}</td>
            <td>${escapeHtml(note)}</td>
            <td>
              <div class="table-actions">
                <button class="btn btn-xs btn-secondary" onclick="removeTankPlant(${tpId})">Remove</button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    function renderPlantsTrash(list) {
      if (!list.length) {
        plantsTbody.innerHTML = `<tr><td colspan="6" style="color:#6b7280;">Trash is empty.</td></tr>`;
        return;
      }

      plantsTbody.innerHTML = list.map(tp => {
        const plantName = tp?.plant?.name ?? `Plant #${tp?.plant_id ?? '-'}`;
        const difficulty = tp?.plant?.difficulty ?? '-';
        const added = fmtDate(tp?.planted_at);
        const position = tp?.position ?? '-';
        const note = tp?.note ?? '';
        const tpId = tp?.id;

        return `
          <tr>
            <td>${escapeHtml(plantName)} <span style="color:#f59e0b;">(deleted)</span></td>
            <td>${escapeHtml(position)}</td>
            <td>${escapeHtml(difficulty)}</td>
            <td>${escapeHtml(added)}</td>
            <td>${escapeHtml(note)}</td>
            <td>
              <div class="table-actions">
                <button class="btn btn-xs btn-secondary" data-restore="${tpId}">Restore</button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    function renderWaterLogs(tank) {
      const logs = Array.isArray(tank?.waterLogs) ? tank.waterLogs : (Array.isArray(tank?.water_logs) ? tank.water_logs : []);

      if (!logs.length) {
        waterTbody.innerHTML = `<tr><td colspan="5" style="color:#6b7280;">No water logs.</td></tr>`;
        return;
      }

      waterTbody.innerHTML = logs.slice(0, 20).map(l => {
        const measured = l.logged_at ? String(l.logged_at).replace('T',' ').slice(0, 16) : '-';
        const ph = (l.ph ?? '-') === null ? '-' : (l.ph ?? '-');
        const temp = (l.temperature ?? '-') === null ? '-' : (l.temperature ?? '-');
        const no3 = (l.no3 ?? '-') === null ? '-' : (l.no3 ?? '-');
        const note =
          (l.other_params && (l.other_params.note || l.other_params['note'])) ? (l.other_params.note || l.other_params['note']) :
          (l.note ?? '');

        return `
          <tr>
            <td>${escapeHtml(measured)}</td>
            <td>${escapeHtml(ph)}</td>
            <td>${escapeHtml(temp)}</td>
            <td>${escapeHtml(no3)}</td>
            <td>${escapeHtml(note)}</td>
          </tr>
        `;
      }).join('');
    }

    window.removeTankPlant = async function (tankPlantId) {
      if (!tankPlantId) return;
      if (!confirm('Remove this plant from tank?')) return;

      hideError();

      const res = await fetch(`/api/tank-plants/${tankPlantId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Remove failed.');
        return;
      }

      await reloadPlantsPanel();
      const tankId = tankIdFromUrl();
      const tank = await fetchTank(tankId);
      if (tank) {
        renderTank(tank);
        renderWaterLogs(tank);
      }
    }

    async function reloadPlantsPanel() {
      const tankId = tankIdFromUrl();
      const viewVal = document.getElementById('tpView').value;

      if (viewVal === 'active') {
        const tank = await fetchTank(tankId);
        if (!tank) return;
        renderPlantsActiveFromTank(tank);
        return;
      }

      const trash = await fetchTankPlantsByView(tankId, 'trash');
      if (!trash) return;
      renderPlantsTrash(trash);
    }

    async function init() {
      const tankId = tankIdFromUrl();
      if (!tankId) {
        showError('Missing tank_id on URL. Example: /tanks/tank_detail?tank_id=1');
        plantsTbody.innerHTML = `<tr><td colspan="6" style="color:#6b7280;">No tank selected.</td></tr>`;
        return;
      }

      document.getElementById('btn-edit').onclick = () => {
        window.location.href = `{{ route('tanks.tank_form') }}?tank_id=${encodeURIComponent(tankId)}`;
      };
      document.getElementById('btn-back').onclick = () => {
        window.location.href = `{{ route('tanks.my_tanks') }}`;
      };
      document.getElementById('btn-add-plant').onclick = () => {
        window.location.href = `{{ route('tanks.add_plant_to_tank') }}?tank_id=${encodeURIComponent(tankId)}`;
      };

      document.getElementById('tpView').addEventListener('change', reloadPlantsPanel);

      const btnPlantLogs = document.getElementById('btn-plant-logs');
      if (btnPlantLogs) {
        btnPlantLogs.onclick = () => {
          window.location.href = `{{ route('plantlog.plant_logs_list') }}?tank_id=${encodeURIComponent(tankId)}`;
        };
      }

      const btnWaterMonitoring = document.getElementById('btn-water-monitoring');
      if (btnWaterMonitoring) {
        btnWaterMonitoring.onclick = () => {
          window.location.href = `{{ route('monitoring.water_logs') }}?tank_id=${encodeURIComponent(tankId)}`;
        };
      }

      const tank = await fetchTank(tankId);
      if (!tank) return;

      hideError();
      renderTank(tank);
      renderWaterLogs(tank);
      renderPlantsActiveFromTank(tank);

      plantsTbody.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-restore]');
        if (!btn) return;

        const id = btn.getAttribute('data-restore');
        if (!confirm('Restore this plant to tank?')) return;

        const ok = await restoreTankPlant(id);
        if (!ok) return;

        await reloadPlantsPanel();
        const tank2 = await fetchTank(tankId);
        if (tank2) renderTank(tank2);
      });
    }

    const tabButtons = document.querySelectorAll('.tab-link');
    const tabPanels  = {
      plants: document.getElementById('tab-plants'),
      water:  document.getElementById('tab-water')
    };

    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab;

        tabButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        Object.keys(tabPanels).forEach(key => {
          tabPanels[key].classList.toggle('active', key === target);
        });
      });
    });

    document.addEventListener('DOMContentLoaded', init);
  </script>
@endsection
