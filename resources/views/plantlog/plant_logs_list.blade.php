{{-- resources/views/plantlog/plant_logs_list.blade.php --}}
@extends('layouts.site')

@section('title', 'Plant growth log')
@section('nav_tanks_active', 'active')

@section('content')

  <section class="section" style="margin-top: 8px;">
    <p class="section-subtitle" style="margin-bottom: 4px;">
      Tank: <strong id="plTankName">...</strong> ·
      Plant: <strong id="plPlantName">...</strong>
    </p>
  </section>

  <section class="section">
    <h1 class="section-title">Plant growth log</h1>
    <p class="section-subtitle">
      Track height and health status of each plant in your tank over time.
    </p>

    <div id="pl-alert" class="alert alert-danger" style="display:none;margin-bottom:12px;"></div>

    <div class="admin-toolbar">
      <div class="admin-toolbar-left">
        <select id="tankPlantSelect" class="form-control" style="max-width: 360px;"></select>
      </div>
      <div class="admin-toolbar-right" style="display:flex;gap:10px;align-items:center;">
        <select id="plRange" class="form-control form-control-sm" style="width: 150px;">
          <option value="30" selected>Last 30 days</option>
          <option value="90">Last 90 days</option>
          <option value="all">All logs</option>
        </select>

        <select id="plView" class="form-control form-control-sm" style="width: 140px;">
          <option value="active" selected>Active</option>
          <option value="trash">Trash</option>
        </select>

        <button id="btnAddLog" class="btn btn-primary">
          + Add log
        </button>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="card-soft">
      <h2 class="card-title" style="margin-bottom: 6px;">Recent logs</h2>
      <p class="card-subtitle">
        Use this log to see how the plant responds to changes in light, CO₂ and fertilizing.
      </p>

      <div class="table-wrapper" style="margin-top: 10px;">
        <table class="table">
          <thead>
          <tr>
            <th>Date</th>
            <th>Height (cm)</th>
            <th>Status</th>
            <th>Note</th>
            <th>Photo</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody id="plTbody"></tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="card-soft" style="display:flex;justify-content:space-between;align-items:center;">
      <div>
        <div class="card-title">Back to tank monitoring</div>
        <p class="card-subtitle">Switch to water parameters history for this tank.</p>
      </div>
      <div>
        <button id="btnBackWater" class="btn btn-secondary">← Water logs</button>
        <button id="btnBackTank" class="btn btn-primary">View tank overview</button>
      </div>
    </div>
  </section>

  <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    function qs(name) { return new URLSearchParams(location.search).get(name); }
    const tankId = qs('tank_id');
    const preselectTankPlantId = qs('tank_plant_id');

    const alertBox = document.getElementById('pl-alert');
    function showErr(msg) { alertBox.style.display='block'; alertBox.textContent = msg || 'Error'; }
    function hideErr() { alertBox.style.display='none'; alertBox.textContent = ''; }

    function escapeHtml(v) {
      return String(v ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }

    async function fetchTankPlants() {
      const res = await fetch(`/api/tanks/${tankId}/tank-plants`, {
        headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) throw new Error(json?.message || 'Load tank plants failed');
      return json.data;
    }

    async function fetchPlantLogs(tankPlantId, rangeVal, viewVal) {
      const res = await fetch(`/api/tank-plants/${tankPlantId}/plant-logs?range=${encodeURIComponent(rangeVal)}&view=${encodeURIComponent(viewVal)}`, {
        headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) throw new Error(json?.message || 'Load logs failed');
      return json.data;
    }

    async function deletePlantLog(id) {
      const res = await fetch(`/api/plant-logs/${id}`, {
        method: 'DELETE',
        headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) throw new Error(json?.message || 'Delete failed');
      return true;
    }

    async function restorePlantLog(id) {
      const res = await fetch(`/api/plant-logs/${id}/restore`, {
        method: 'PATCH',
        headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF },
        credentials: 'same-origin',
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) throw new Error(json?.message || 'Restore failed');
      return true;
    }

    function renderStatusChip(status) {
      const s = (status || '').toLowerCase();
      let cls = 'pl-status-healthy';
      if (s.includes('slow')) cls = 'pl-status-warning';
      if (s.includes('melt')) cls = 'pl-status-poor';
      if (s.includes('algae')) cls = 'pl-status-warning';
      return `<span class="pl-status-chip ${cls}">${escapeHtml(status || '')}</span>`;
    }

    function renderTable(logs) {
      const tbody = document.getElementById('plTbody');
      tbody.innerHTML = '';

      if (!logs.length) {
        tbody.innerHTML = `<tr><td colspan="6" style="color:#9ca3af;">No logs.</td></tr>`;
        return;
      }

      for (const l of logs) {
        const isDeleted = !!l.deleted_at;

        const img = l.image_url
          ? `<img src="${escapeHtml(l.image_url)}" class="table-img" alt="plant">`
          : '';

        const actionBtn = isDeleted
          ? `<button class="btn btn-secondary btn-xs" data-restore="${escapeHtml(l.id)}">Restore</button>`
          : `<button class="btn btn-secondary btn-xs btn-danger-soft" data-del="${escapeHtml(l.id)}">Delete</button>`;

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>
            ${escapeHtml(l.logged_at ?? '')}
            ${isDeleted ? `<span style="margin-left:8px;color:#f59e0b;">(deleted)</span>` : ``}
          </td>
          <td>${escapeHtml(l.height ?? '')}</td>
          <td>${renderStatusChip(l.status)}</td>
          <td>${escapeHtml(l.note ?? '')}</td>
          <td>${img}</td>
          <td class="table-actions">${actionBtn}</td>
        `;
        tbody.appendChild(tr);
      }
    }

    async function reloadLogs() {
      hideErr();
      const sel = document.getElementById('tankPlantSelect');
      const tankPlantId = sel.value;
      const rangeVal = document.getElementById('plRange').value;
      const viewVal = document.getElementById('plView').value;

      const data = await fetchPlantLogs(tankPlantId, rangeVal, viewVal);
      if (!data) return;

      renderTable(data.logs || []);
    }

    document.addEventListener('DOMContentLoaded', async () => {
      const btnBackWater = document.getElementById('btnBackWater');
      const btnBackTank = document.getElementById('btnBackTank');

      if (btnBackWater) {
        btnBackWater.addEventListener('click', () => {
          if (!tankId) return;
          window.location.href = `{{ route('monitoring.water_logs') }}?tank_id=${encodeURIComponent(tankId)}`;
        });
      }

      if (btnBackTank) {
        btnBackTank.addEventListener('click', () => {
          if (!tankId) return;
          window.location.href = `{{ route('tanks.tank_detail') }}?tank_id=${encodeURIComponent(tankId)}`;
        });
      }

      if (!tankId) { showErr('Missing tank_id in URL. Example: /plantlog/plant_logs_list?tank_id=1'); return; }

      try {
        const data = await fetchTankPlants();
        if (!data) return;

        document.getElementById('plTankName').textContent = data?.tank?.name || `Tank #${tankId}`;
        const list = Array.isArray(data?.tank_plants) ? data.tank_plants : [];

        const sel = document.getElementById('tankPlantSelect');
        sel.innerHTML = '';

        for (const tp of list) {
          const opt = document.createElement('option');
          opt.value = tp.id;
          const plantName = tp.plant_name || ('Plant #' + (tp.plant_id ?? ''));
          opt.textContent = `${plantName} (planted: ${tp.planted_at || '—'})`;
          sel.appendChild(opt);
        }

        if (!sel.value) { showErr('This tank has no plants attached yet.'); return; }

        if (preselectTankPlantId && list.some(x => String(x.id) === String(preselectTankPlantId))) {
          sel.value = String(preselectTankPlantId);
        }

        const picked0 = list.find(x => String(x.id) === String(sel.value));
        document.getElementById('plPlantName').textContent = picked0?.plant_name || '...';

        sel.addEventListener('change', () => {
          const picked = list.find(x => String(x.id) === String(sel.value));
          document.getElementById('plPlantName').textContent = picked?.plant_name || '...';
          reloadLogs();
        });

        document.getElementById('plRange').addEventListener('change', reloadLogs);
        document.getElementById('plView').addEventListener('change', reloadLogs);

        document.getElementById('btnAddLog').addEventListener('click', () => {
          window.location.href =
            `{{ route('plantlog.plant_log_form') }}?tank_id=${encodeURIComponent(tankId)}&tank_plant_id=${encodeURIComponent(sel.value)}`;
        });

        document.getElementById('plTbody').addEventListener('click', async (e) => {
          const delBtn = e.target.closest('[data-del]');
          const restoreBtn = e.target.closest('[data-restore]');

          try {
            if (delBtn) {
              const id = delBtn.getAttribute('data-del');
              if (!confirm('Delete this plant log?')) return;
              await deletePlantLog(id);
              await reloadLogs();
            }

            if (restoreBtn) {
              const id = restoreBtn.getAttribute('data-restore');
              if (!confirm('Restore this plant log?')) return;
              await restorePlantLog(id);
              await reloadLogs();
            }
          } catch (err) {
            showErr(err.message);
          }
        });

        await reloadLogs();
      } catch (err) {
        showErr(err.message);
      }
    });
  </script>

@endsection
