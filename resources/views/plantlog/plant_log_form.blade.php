{{-- resources/views/plantlog/plant_log_form.blade.php --}}
@extends('layouts.site')

@section('title', 'Add plant growth log')

@section('content')
  <section class="section">
    <h1 class="section-title">Add plant growth log</h1>
    <p class="section-subtitle">
      Log the current height, health status and a quick photo of your plant.
    </p>

    <div class="card-soft" style="margin-top: 12px;">
      <div class="pl-header" style="margin-bottom: 10px;">
        <h2 id="plHeaderTitle">...</h2>
        <p class="pl-meta" id="plHeaderMeta">...</p>
      </div>

      <div id="plf-alert" class="alert alert-danger" style="display:none;margin-bottom:12px;"></div>

      <form id="plantLogForm" enctype="multipart/form-data">
        <div class="grid-2">
          <div class="form-group">
            <label for="log_date">Date *</label>
            <input type="date" id="log_date" class="form-control" required>
          </div>

          <div class="form-group">
            <label for="log_height">Height (cm)</label>
            <input type="number" step="0.1" id="log_height"
                   class="form-control" placeholder="e.g. 4.5">
          </div>

          <div class="form-group">
            <label for="log_status">Status *</label>
            <select id="log_status" class="form-control" required>
              <option value="">-- Select status --</option>
              <option value="Healthy">Healthy / spreading well</option>
              <option value="Slow growth">Slow growth</option>
              <option value="Melting">Melting / yellowing</option>
              <option value="Affected by algae">Affected by algae</option>
            </select>
          </div>

          <div class="form-group">
            <label for="log_image">Photo (optional)</label>
            <input type="file" id="log_image" class="form-control" accept="image/*">
          </div>
        </div>

        <div class="form-group">
          <label for="log_note">Note</label>
          <textarea id="log_note" rows="4" class="form-control"
                    placeholder="Trimming, replanting, fertilizer changes, etc."></textarea>
        </div>

        <div class="form-actions">
          <button id="btnSavePL" type="submit" class="btn btn-primary">Save log</button>
          <button type="button" class="btn btn-secondary" id="btnCancelPL">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </section>

  <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    function qs(name) { return new URLSearchParams(location.search).get(name); }

    const tankId = qs('tank_id');
    const tankPlantId = qs('tank_plant_id');

    const alertBox = document.getElementById('plf-alert');
    function showErr(msg) { alertBox.style.display='block'; alertBox.textContent = msg || 'Error'; }
    function hideErr() { alertBox.style.display='none'; alertBox.textContent=''; }

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

    async function createPlantLog(fd) {
      const res = await fetch(`/api/tank-plants/${tankPlantId}/plant-logs`, {
        method: 'POST',
        headers: {
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: fd,
      });

      if (res.status === 401) { window.location.href = '/login'; return null; }

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) throw new Error(json?.message || 'Create failed');
      return json.data;
    }

    document.addEventListener('DOMContentLoaded', async () => {
      if (!tankId || !tankPlantId) {
        showErr('Missing tank_id or tank_plant_id in URL.');
        return;
      }

      // default date: today (YYYY-MM-DD)
      document.getElementById('log_date').value = new Date().toISOString().slice(0,10);

      document.getElementById('btnCancelPL').addEventListener('click', () => {
        window.location.href = `{{ route('plantlog.plant_logs_list') }}?tank_id=${encodeURIComponent(tankId)}`;
      });

      try {
        const data = await fetchTankPlants();
        if (!data) return;

        const tankName = data?.tank?.name || `Tank #${tankId}`;
        const list = Array.isArray(data?.tank_plants) ? data.tank_plants : [];
        const tp = list.find(x => String(x.id) === String(tankPlantId));

        if (!tp) {
          showErr('tank_plant_id is not found in this tank. Please go back and pick a plant again.');
          return;
        }

        const plantName = tp?.plant_name || `Plant #${tp?.plant_id || ''}`;

        document.getElementById('plHeaderTitle').textContent = `${tankName} — ${plantName}`;
        document.getElementById('plHeaderMeta').textContent = `TankPlant ID: ${tankPlantId}`;
      } catch (err) {
        showErr(err.message);
      }

      document.getElementById('plantLogForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        hideErr();

        const btn = document.getElementById('btnSavePL');
        btn.disabled = true;

        try {
          const dateVal = document.getElementById('log_date').value;
          const statusVal = document.getElementById('log_status').value;

          if (!dateVal) { showErr('Date is required.'); return; }
          if (!statusVal) { showErr('Status is required.'); return; }

          const fd = new FormData();
          fd.append('logged_at', dateVal);
          fd.append('status', statusVal);

          const hRaw = document.getElementById('log_height').value;
          if (hRaw !== '' && hRaw !== null && hRaw !== undefined) {
            fd.append('height', String(Number(hRaw)));
          }

          const noteVal = document.getElementById('log_note').value;
          if (noteVal && noteVal.trim() !== '') {
            fd.append('note', noteVal.trim());
          }

          const file = document.getElementById('log_image').files[0];
          if (file) fd.append('image', file);

          await createPlantLog(fd);

          window.location.href =
            `{{ route('plantlog.plant_logs_list') }}?tank_id=${encodeURIComponent(tankId)}&tank_plant_id=${encodeURIComponent(tankPlantId)}`;
        } catch (err) {
          showErr(err.message);
        } finally {
          btn.disabled = false;
        }
      });
    });
  </script>
@endsection
