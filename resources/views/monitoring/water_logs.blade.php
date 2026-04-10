{{-- resources/views/monitoring/water_logs.blade.php --}}
@extends('layouts.site')

@section('title', 'Water monitoring')
@section('nav_tanks_active', 'active')

@section('content')

  <section class="section" style="margin-top: 8px;">
    <p class="section-subtitle" style="margin-bottom: 4px;">
      Tank: <strong id="tankName">...</strong> – live water parameter tracking.
    </p>
  </section>

  <section class="section">
    <div class="page-header">
      <div>
        <h1 class="page-title">Water monitoring</h1>
        <p class="page-subtitle">
          Log pH, temperature and nutrients, then review history and charts.
        </p>
      </div>
      <div class="page-actions">
        <button id="btnBackTank" class="btn btn-secondary">← Back to tank</button>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="grid-2">
      <section class="card">
        <div class="card-header">
          <h2 class="card-title">Add water log</h2>
          <p class="card-subtitle">Record today’s water parameters for this tank.</p>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin:0 16px 12px;">
          <button type="button" id="btnPresetMorning" class="btn btn-secondary">Morning check</button>
          <button type="button" id="btnPresetWeekly" class="btn btn-secondary">Weekly check</button>
          <button type="button" id="btnUseLastLog" class="btn btn-secondary">Use last log</button>
        </div>

        <div id="wl-alert" class="alert alert-danger" style="display:none;margin:0 16px 12px;"></div>

        <form id="form-water-log" class="form-grid-2" data-live="1">
          <div class="form-group">
            <label for="logged_at">Measured at</label>
            <input
              type="datetime-local"
              id="logged_at"
              class="form-control"
              required
            >
            <div class="metric-note">Cannot be in the future.</div>
          </div>

          <div class="form-group">
            <label for="ph">pH</label>
            <input
              type="number"
              step="0.1"
              min="0"
              max="14"
              id="ph"
              class="form-control"
              placeholder="e.g. 6.5"
              required
            >
            <div class="metric-note">Range: 0 – 14</div>
          </div>

          <div class="form-group">
            <label for="temp">Temperature (°C)</label>
            <input
              type="number"
              step="0.1"
              min="0"
              max="40"
              id="temp"
              class="form-control"
              placeholder="e.g. 24.5"
              required
            >
            <div class="metric-note">Range: 0 – 40 °C</div>
          </div>

          <div class="form-group">
            <label for="no3">NO₃ (ppm)</label>
            <input
              type="number"
              step="0.1"
              min="0"
              max="200"
              id="no3"
              class="form-control"
              placeholder="e.g. 10"
              required
            >
            <div class="metric-note">Range: 0 – 200 ppm</div>
          </div>

          <div class="form-group">
            <label for="gh">GH (optional)</label>
            <input
              type="number"
              step="1"
              min="0"
              max="30"
              id="gh"
              class="form-control"
              placeholder="e.g. 6"
            >
            <div class="metric-note">Range: 0 – 30</div>
          </div>

          <div class="form-group">
            <label for="kh">KH (optional)</label>
            <input
              type="number"
              step="1"
              min="0"
              max="20"
              id="kh"
              class="form-control"
              placeholder="e.g. 3"
            >
            <div class="metric-note">Range: 0 – 20</div>
          </div>

          <div class="form-group">
            <label for="tds">TDS (optional)</label>
            <input
              type="number"
              step="1"
              min="0"
              max="5000"
              id="tds"
              class="form-control"
              placeholder="e.g. 145"
            >
            <div class="metric-note">Range: 0 – 5000 ppm</div>
          </div>

          <div class="form-group">
            <label for="ec">EC (optional)</label>
            <input
              type="number"
              step="0.01"
              min="0"
              max="20"
              id="ec"
              class="form-control"
              placeholder="e.g. 0.31"
            >
            <div class="metric-note">Range: 0 – 20 mS/cm</div>
          </div>

          <div class="form-group form-group-full">
            <label for="note">Note (optional)</label>
            <textarea
              id="note"
              rows="2"
              class="form-control"
              maxlength="2000"
              placeholder="Water change, fertilizing, etc."
            ></textarea>
          </div>

          <div class="form-actions">
            <button id="btnSaveWL" type="submit" class="btn btn-primary">Save log</button>
            <button type="reset" class="btn btn-secondary">Reset</button>
          </div>
        </form>
      </section>

      <section class="card card-metrics">
        <div class="card-header">
          <h2 class="card-title">Current status</h2>
          <p class="card-subtitle">Summary from recent logs.</p>
        </div>

        <div class="metrics-row">
          <div class="metric-item">
            <div class="metric-label">Avg pH (7 days)</div>
            <div class="metric-value" id="statAvgPh">—</div>
          </div>
          <div class="metric-item">
            <div class="metric-label">Temp range (7 days)</div>
            <div class="metric-value" id="statTempRange">—</div>
          </div>
          <div class="metric-item">
            <div class="metric-label">NO₃ latest</div>
            <div class="metric-value" id="statNo3">—</div>
          </div>
        </div>

        <div style="margin-top:12px;">
          <div class="card-title" style="font-size:14px;">Advisor suggestions</div>
          <div id="advisorBox" style="margin-top:6px; font-size:14px; color:#4b5563;">
            —
          </div>
        </div>
      </section>
    </div>
  </section>

  <section class="section">
    <section class="card">
      <div class="card-header card-header-inline">
        <div>
          <h2 class="card-title">Latest sensor feed</h2>
          <p class="card-subtitle">
            Telemetry from your connected ESP32 or demo Postman payload.
          </p>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <button type="button" id="btnRefreshSensor" class="btn btn-secondary">Refresh sensor</button>
          <button type="button" id="btnUseSensor" class="btn btn-primary">Auto-fill from sensor</button>
        </div>
      </div>

      <div style="margin:0 16px 12px;padding:10px 12px;border-radius:10px;background:#f8fafc;color:#334155;font-size:14px;">
        Device(s): <strong id="sensorDeviceText">—</strong>
        <span style="margin:0 8px;">•</span>
        Last telemetry: <strong id="sensorRecordedAt">—</strong>
      </div>

      <div class="metrics-row">
        <div class="metric-item">
          <div class="metric-label">Sensor temp</div>
          <div class="metric-value" id="sensorTemp">—</div>
        </div>
        <div class="metric-item">
          <div class="metric-label">Sensor pH</div>
          <div class="metric-value" id="sensorPh">—</div>
        </div>
        <div class="metric-item">
          <div class="metric-label">Sensor NO₃</div>
          <div class="metric-value" id="sensorNo3">—</div>
        </div>
        <div class="metric-item">
          <div class="metric-label">Sensor TDS</div>
          <div class="metric-value" id="sensorTds">—</div>
        </div>
        <div class="metric-item">
          <div class="metric-label">Sensor EC</div>
          <div class="metric-value" id="sensorEc">—</div>
        </div>
      </div>

      <div style="margin:12px 16px 0;font-size:13px;color:#64748b;">
        Tip: press <strong>Auto-fill from sensor</strong> to copy the latest telemetry into the water log form.
      </div>
    </section>
  </section>

  <section class="section">
    <div class="grid-2">
      <section class="card">
        <div class="card-header">
          <h2 class="card-title">Import water logs from CSV</h2>
          <p class="card-subtitle">
            Useful when you already track measurements in spreadsheets or device exports.
          </p>
        </div>

        <div id="wlImportResult" style="display:none;margin:0 16px 12px;padding:10px 12px;border-radius:10px;background:#ecfeff;color:#155e75;font-size:14px;"></div>

        <form id="form-import-water-logs" style="padding:0 16px 16px;">
          <div class="form-group">
            <label for="waterLogCsv">CSV file</label>
            <input type="file" id="waterLogCsv" class="form-control" accept=".csv,.txt" required>
            <div class="metric-note" style="margin-top:6px;">
              Accepted headers:
              <code>logged_at</code>,
              <code>ph</code>,
              <code>temperature</code> or <code>temp</code>,
              <code>no3</code>,
              <code>gh</code>,
              <code>kh</code>,
              <code>tds</code>,
              <code>ec</code>,
              <code>note</code>
            </div>
          </div>

          <div class="form-actions" style="margin-top:12px;">
            <button id="btnImportWL" type="submit" class="btn btn-primary">Import CSV</button>
          </div>
        </form>
      </section>

      <section class="card">
        <div class="card-header">
          <h2 class="card-title">Schedule reminders</h2>
          <p class="card-subtitle">Set a simple measurement routine for this tank.</p>
        </div>

        <div id="wlReminderResult" style="display:none;margin:0 16px 12px;padding:10px 12px;border-radius:10px;background:#eff6ff;color:#1d4ed8;font-size:14px;"></div>

        <form id="form-water-reminder" class="form-grid-2" style="padding-top:0;">
          <div class="form-group form-group-full">
            <label style="display:flex;align-items:center;gap:8px;">
              <input type="checkbox" id="reminder_enabled">
              Enable water log reminder
            </label>
          </div>

          <div class="form-group">
            <label for="reminder_frequency">Frequency</label>
            <select id="reminder_frequency" class="form-control">
              <option value="daily">Daily</option>
              <option value="every_3_days">Every 3 days</option>
              <option value="weekly">Weekly</option>
              <option value="biweekly">Every 2 weeks</option>
            </select>
          </div>

          <div class="form-group">
            <label for="reminder_time">Preferred time</label>
            <input type="time" id="reminder_time" class="form-control">
          </div>

          <div class="form-group">
            <label for="reminder_start_date">Start date</label>
            <input type="date" id="reminder_start_date" class="form-control">
          </div>

          <div class="form-group">
            <label>Next due</label>
            <div id="reminderNextDue" class="form-control" style="display:flex;align-items:center;background:#f9fafb;">—</div>
          </div>

          <div class="form-actions">
            <button id="btnSaveReminder" type="submit" class="btn btn-primary">Save reminder</button>
          </div>
        </form>
      </section>
    </div>
  </section>

  <section class="section">
    <section class="card">
      <div class="card-header card-header-inline">
        <div>
          <h2 class="card-title">Water log history</h2>
          <p class="card-subtitle">Last measurements for this tank.</p>
        </div>
        <div class="waterlog-filters" style="display:flex;gap:10px;align-items:center;">
          <select id="wlView" class="form-control form-control-sm" style="width:140px;">
            <option value="active" selected>Active</option>
            <option value="trash">Trash</option>
          </select>

          <select id="wlRange" class="form-control form-control-sm">
            <option value="7">Last 7 days</option>
            <option value="30" selected>Last 30 days</option>
            <option value="all">All logs</option>
          </select>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="table">
          <thead>
          <tr>
            <th>Measured at</th>
            <th>pH</th>
            <th>Temp (°C)</th>
            <th>NO₃ (ppm)</th>
            <th>GH</th>
            <th>KH</th>
            <th>Note</th>
            <th style="width:140px;">Actions</th>
          </tr>
          </thead>
          <tbody id="wlTbody"></tbody>
        </table>
      </div>
    </section>
  </section>

  <section class="section">
    <section class="card card-chart">
      <div class="card-header">
        <h2 class="card-title">Water parameters chart</h2>
        <p class="card-subtitle">pH, temperature, NO₃ over time.</p>
      </div>
      <div class="chart-container" style="height:320px;">
        <canvas id="waterlogChart" data-live="1"></canvas>
      </div>
    </section>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function qs(name) { return new URLSearchParams(location.search).get(name); }
    const tankId = qs('tank_id');

    const LIMITS = {
      ph:   { min: 0,    max: 14 },
      temp: { min: 0,    max: 40 },
      no3:  { min: 0,    max: 200 },
      gh:   { min: 0,    max: 30 },
      kh:   { min: 0,    max: 20 },
      tds:  { min: 0,    max: 5000 },
      ec:   { min: 0,    max: 20 },
    };

    let chart = null;
    let latestActiveLog = null;
    let latestSensorPayload = null;

    const alertBox = document.getElementById('wl-alert');

    function showErr(msg) {
      alertBox.style.display = 'block';
      alertBox.textContent = msg || 'Something went wrong.';
    }

    function hideErr() {
      alertBox.style.display = 'none';
      alertBox.textContent = '';
    }

    function showPanelMessage(id, msg, isError = false) {
      const box = document.getElementById(id);
      if (!box) return;

      box.style.display = 'block';
      box.textContent = msg || '';

      if (isError) {
        box.style.background = '#fef2f2';
        box.style.color = '#991b1b';
      } else {
        box.style.background = '#ecfeff';
        box.style.color = '#155e75';
      }
    }

    function escapeHtml(v) {
      return String(v ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'", '&#039;');
    }

    function toDateTimeLocalValue(d) {
      const pad = n => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function fmtLoggedAt(v) {
      if (!v) return '';
      const s = String(v);
      return s.replace('T', ' ').slice(0, 16);
    }

    function setNowToDateInput() {
      const dt = document.getElementById('logged_at');
      if (!dt) return;

      const now = new Date();
      dt.value = toDateTimeLocalValue(now);
      dt.max = toDateTimeLocalValue(now);
    }

    function parseNum(id) {
      const el = document.getElementById(id);
      const raw = (el?.value ?? '').trim();
      if (raw === '') return null;
      const n = Number(raw);
      return Number.isFinite(n) ? n : null;
    }

    function validateRange(label, value, min, max) {
      if (value == null) return null;
      if (value < min || value > max) return `${label} must be between ${min} and ${max}.`;
      return null;
    }

    function validateForm() {
      const loggedAt = (document.getElementById('logged_at')?.value ?? '').trim();
      if (!loggedAt) return 'Measured at is required.';

      const chosen = new Date(loggedAt);
      if (isNaN(chosen.getTime())) return 'Measured at is invalid.';

      const now = new Date();
      if (chosen.getTime() > now.getTime() + 60_000) return 'Measured at cannot be in the future.';

      const ph = parseNum('ph');
      const temp = parseNum('temp');
      const no3 = parseNum('no3');

      if (ph == null) return 'pH is required.';
      if (temp == null) return 'Temperature is required.';
      if (no3 == null) return 'NO₃ is required.';

      let err = null;

      err = validateRange('pH', ph, LIMITS.ph.min, LIMITS.ph.max); if (err) return err;
      err = validateRange('Temperature', temp, LIMITS.temp.min, LIMITS.temp.max); if (err) return err;
      err = validateRange('NO₃', no3, LIMITS.no3.min, LIMITS.no3.max); if (err) return err;

      const gh = parseNum('gh');
      const kh = parseNum('kh');
      const tds = parseNum('tds');
      const ec = parseNum('ec');

      err = validateRange('GH', gh, LIMITS.gh.min, LIMITS.gh.max); if (err) return err;
      err = validateRange('KH', kh, LIMITS.kh.min, LIMITS.kh.max); if (err) return err;
      err = validateRange('TDS', tds, LIMITS.tds.min, LIMITS.tds.max); if (err) return err;
      err = validateRange('EC', ec, LIMITS.ec.min, LIMITS.ec.max); if (err) return err;

      return null;
    }

    function getWaterLogPayload() {
      return {
        logged_at: (document.getElementById('logged_at').value || null),
        ph: Number(document.getElementById('ph').value),
        temperature: Number(document.getElementById('temp').value),
        no3: Number(document.getElementById('no3').value),
        gh: parseNum('gh'),
        kh: parseNum('kh'),
        tds: parseNum('tds'),
        ec: parseNum('ec'),
        note: (document.getElementById('note').value || '').trim() || null,
      };
    }

    function fillFormFromLog(log) {
      if (!log) return;

      setNowToDateInput();
      document.getElementById('ph').value = log.ph ?? '';
      document.getElementById('temp').value = log.temperature ?? '';
      document.getElementById('no3').value = log.no3 ?? '';
      document.getElementById('gh').value = log.gh ?? '';
      document.getElementById('kh').value = log.kh ?? '';
      document.getElementById('tds').value = log.tds ?? '';
      document.getElementById('ec').value = log.ec ?? '';
      document.getElementById('note').value = log.note ?? '';
    }

    function applyPreset(type) {
      setNowToDateInput();

      const noteEl = document.getElementById('note');
      const currentNote = (noteEl.value || '').trim();

      if (type === 'morning' && !currentNote) {
        noteEl.value = 'Morning check';
      }

      if (type === 'weekly' && !currentNote) {
        noteEl.value = 'Weekly check';
      }

      document.getElementById('ph').focus();
    }

    function renderChart(logsAsc) {
      const ctx = document.getElementById('waterlogChart');
      if (!ctx) return;

      const labels = logsAsc.map(x => {
        const s = fmtLoggedAt(x.logged_at);
        return s ? s.slice(5, 16) : '';
      });

      const dataPh = logsAsc.map(x => x.ph);
      const dataTemp = logsAsc.map(x => x.temperature);
      const dataNo3 = logsAsc.map(x => x.no3);

      if (chart) chart.destroy();

      chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [
            { label: 'pH', data: dataPh, borderWidth: 2, tension: 0.35 },
            { label: 'Temp (°C)', data: dataTemp, borderWidth: 2, tension: 0.35 },
            { label: 'NO₃ (ppm)', data: dataNo3, borderWidth: 2, tension: 0.35 }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
        }
      });
    }

    function renderStats(stats) {
      document.getElementById('statAvgPh').textContent =
        (stats?.avg_ph_7d == null) ? '—' : Number(stats.avg_ph_7d).toFixed(2);

      if (stats?.temp_min_7d == null || stats?.temp_max_7d == null) {
        document.getElementById('statTempRange').textContent = '—';
      } else {
        document.getElementById('statTempRange').textContent =
          `${Number(stats.temp_min_7d).toFixed(1)} – ${Number(stats.temp_max_7d).toFixed(1)} °C`;
      }

      document.getElementById('statNo3').textContent =
        (stats?.latest_no3 == null) ? '—' : `${Number(stats.latest_no3).toFixed(1)} ppm`;
    }

    function renderAdvice(advice) {
      const box = document.getElementById('advisorBox');
      if (!box) return;

      if (!advice || !Array.isArray(advice.items) || advice.items.length === 0) {
        box.textContent = '—';
        return;
      }

      const rows = advice.items.map(it => {
        const level = it.level || 'info';
        const tag =
          level === 'danger'  ? '⛔' :
          level === 'warning' ? '⚠️' :
          level === 'ok'      ? '✅' : 'ℹ️';

        return `<div style="margin-top:6px;"><strong>${tag} ${escapeHtml(it.title || '')}</strong><div>${escapeHtml(it.message || '')}</div></div>`;
      }).join('');

      box.innerHTML = rows;
    }

    function renderTable(logs, viewVal) {
      const tbody = document.getElementById('wlTbody');
      tbody.innerHTML = '';

      if (!logs.length) {
        tbody.innerHTML = `<tr><td colspan="8" style="color:#9ca3af;">No logs.</td></tr>`;
        return;
      }

      for (const l of logs) {
        const tr = document.createElement('tr');

        const actionHtml = (viewVal === 'trash')
          ? `<button class="btn btn-secondary btn-xs" data-restore="${escapeHtml(l.id)}">Restore</button>`
          : `<button class="btn btn-secondary btn-xs btn-danger-soft" data-del="${escapeHtml(l.id)}">Delete</button>`;

        tr.innerHTML = `
          <td>${escapeHtml(fmtLoggedAt(l.logged_at))}</td>
          <td>${escapeHtml(l.ph ?? '')}</td>
          <td>${escapeHtml(l.temperature ?? '')}</td>
          <td>${escapeHtml(l.no3 ?? '')}</td>
          <td>${escapeHtml(l.gh ?? '')}</td>
          <td>${escapeHtml(l.kh ?? '')}</td>
          <td>${escapeHtml(l.note ?? '')}</td>
          <td>${actionHtml}</td>
        `;
        tbody.appendChild(tr);
      }
    }

    function toggleReminderFields() {
      const enabled = document.getElementById('reminder_enabled').checked;
      document.getElementById('reminder_frequency').disabled = !enabled;
      document.getElementById('reminder_time').disabled = !enabled;
      document.getElementById('reminder_start_date').disabled = !enabled;
    }

    function renderReminder(data) {
      const rawTime = data?.preferred_time || '08:00';
      const normalizedTime = String(rawTime).slice(0, 5);

      document.getElementById('reminder_enabled').checked = !!data?.enabled;
      document.getElementById('reminder_frequency').value = data?.frequency || 'weekly';
      document.getElementById('reminder_time').value = normalizedTime;
      document.getElementById('reminder_start_date').value = data?.start_date || new Date().toISOString().slice(0, 10);
      document.getElementById('reminderNextDue').textContent = data?.next_due_at_text || 'Disabled';
      toggleReminderFields();
    }

    function sensorText(value, digits = 1, suffix = '') {
      if (value == null) return '—';
      return `${Number(value).toFixed(digits)}${suffix}`;
    }

    function renderSensorSnapshot(payload) {
      latestSensorPayload = payload;

      const snap = payload?.snapshot || {};
      const values = snap.values || {};

      document.getElementById('sensorDeviceText').textContent = snap.devices_text || 'No device registered';
      document.getElementById('sensorRecordedAt').textContent = snap.recorded_at_text || 'No telemetry yet';

      document.getElementById('sensorTemp').textContent = sensorText(values.temperature, 1, ' °C');
      document.getElementById('sensorPh').textContent   = sensorText(values.ph, 2, '');
      document.getElementById('sensorNo3').textContent  = sensorText(values.no3, 1, ' ppm');
      document.getElementById('sensorTds').textContent  = sensorText(values.tds, 0, ' ppm');
      document.getElementById('sensorEc').textContent   = sensorText(values.ec, 2, ' mS/cm');

      document.getElementById('btnUseSensor').disabled = !snap.has_data;
    }

    function autoFillFromSensor() {
      hideErr();

      const snap = latestSensorPayload?.snapshot;
      if (!snap || !snap.has_data) {
        showErr('No sensor telemetry available yet.');
        return;
      }

      const values = snap.values || {};

      if (snap.recorded_at) {
        const dt = new Date(snap.recorded_at);
        if (!isNaN(dt.getTime())) {
          document.getElementById('logged_at').value = toDateTimeLocalValue(dt);
        } else {
          setNowToDateInput();
        }
      } else {
        setNowToDateInput();
      }

      if (values.ph != null) {
        document.getElementById('ph').value = Number(values.ph).toFixed(2);
      }
      if (values.temperature != null) {
        document.getElementById('temp').value = Number(values.temperature).toFixed(1);
      }
      if (values.no3 != null) {
        document.getElementById('no3').value = Number(values.no3).toFixed(1);
      }
      if (values.tds != null) {
        document.getElementById('tds').value = Number(values.tds).toFixed(0);
      }
      if (values.ec != null) {
        document.getElementById('ec').value = Number(values.ec).toFixed(2);
      }

      const noteEl = document.getElementById('note');
      const extra = [];

      if (values.tds != null) extra.push(`TDS ${Number(values.tds).toFixed(0)} ppm`);
      if (values.ec != null) extra.push(`EC ${Number(values.ec).toFixed(2)} mS/cm`);

      const autoNote = extra.length
        ? `Auto-filled from sensor: ${extra.join(', ')}`
        : 'Auto-filled from sensor';

      const currentNote = (noteEl.value || '').trim();
      if (!currentNote) {
        noteEl.value = autoNote;
      } else if (!currentNote.includes('Auto-filled from sensor')) {
        noteEl.value = `${currentNote} | ${autoNote}`;
      }
    }

    async function fetchWaterLogs(rangeVal, viewVal) {
      const res = await fetch(`/api/tanks/${tankId}/water-logs?range=${encodeURIComponent(rangeVal)}&view=${encodeURIComponent(viewVal)}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        throw new Error(json?.message || 'Failed to load logs.');
      }

      return json.data;
    }

    async function fetchLatestSensor() {
      const res = await fetch(`/api/iot/tanks/${tankId}/latest`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        throw new Error(json?.message || 'Failed to load latest sensor data.');
      }

      return json.data;
    }

    async function createWaterLog(payload) {
      const res = await fetch(`/api/tanks/${tankId}/water-logs`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
      });

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        throw new Error(json?.message || 'Create failed.');
      }

      return json.data;
    }

    async function importWaterLogs(file) {
      const formData = new FormData();
      formData.append('file', file);

      const res = await fetch(`/api/tanks/${tankId}/water-logs/import`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: formData,
      });

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        throw new Error(json?.message || 'Import failed.');
      }

      return json.data;
    }

    async function fetchReminder() {
      const res = await fetch(`/api/tanks/${tankId}/water-log-reminder`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        throw new Error(json?.message || 'Failed to load reminder.');
      }

      return json.data;
    }

    async function saveReminder(payload) {
      const res = await fetch(`/api/tanks/${tankId}/water-log-reminder`, {
        method: 'PUT',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
      });

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        throw new Error(json?.message || 'Failed to save reminder.');
      }

      return json.data;
    }

    async function deleteWaterLog(id) {
      const res = await fetch(`/api/water-logs/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF
        },
        credentials: 'same-origin',
      });

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        throw new Error(json?.message || 'Delete failed.');
      }

      return true;
    }

    async function restoreWaterLog(id) {
      const res = await fetch(`/api/water-logs/${id}/restore`, {
        method: 'PATCH',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF
        },
        credentials: 'same-origin',
      });

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        throw new Error(json?.message || 'Restore failed.');
      }

      return true;
    }

    async function reload() {
      hideErr();

      if (!tankId) {
        showErr('Missing tank_id in URL. Example: /monitoring/water_logs?tank_id=1');
        return;
      }

      const rangeVal = document.getElementById('wlRange').value;
      const viewVal  = document.getElementById('wlView').value;

      const data = await fetchWaterLogs(rangeVal, viewVal);

      document.getElementById('tankName').textContent = data?.tank?.name || `Tank #${tankId}`;
      renderStats(data.stats);
      renderTable(data.logs || [], viewVal);

      if (viewVal === 'active') {
        latestActiveLog = (data.logs && data.logs.length) ? data.logs[0] : null;
        const asc = [...(data.logs || [])].reverse();
        renderChart(asc);
      } else {
        renderChart([]);
      }
    }

    async function loadReminderSection() {
      if (!tankId) return;
      const data = await fetchReminder();
      renderReminder(data);
    }

    async function loadSensorSection() {
      if (!tankId) return;
      const data = await fetchLatestSensor();
      renderSensorSnapshot(data);
    }

    document.addEventListener('DOMContentLoaded', async () => {
      const btnBack = document.getElementById('btnBackTank');
      if (btnBack) {
        btnBack.addEventListener('click', () => {
          if (!tankId) return;
          window.location.href = `{{ route('tanks.tank_detail') }}?tank_id=${encodeURIComponent(tankId)}`;
        });
      }

      setNowToDateInput();

      document.getElementById('form-water-log').addEventListener('reset', () => {
        setTimeout(() => setNowToDateInput(), 0);
      });

      document.getElementById('wlRange').addEventListener('change', reload);
      document.getElementById('wlView').addEventListener('change', reload);

      document.getElementById('btnPresetMorning').addEventListener('click', () => applyPreset('morning'));
      document.getElementById('btnPresetWeekly').addEventListener('click', () => applyPreset('weekly'));

      document.getElementById('btnUseLastLog').addEventListener('click', () => {
        hideErr();

        if (!latestActiveLog) {
          showErr('No active water log found yet to duplicate.');
          return;
        }

        fillFormFromLog(latestActiveLog);
      });

      document.getElementById('btnRefreshSensor').addEventListener('click', async () => {
        try {
          await loadSensorSection();
        } catch (err) {
          showErr(err.message);
        }
      });

      document.getElementById('btnUseSensor').addEventListener('click', autoFillFromSensor);

      document.getElementById('form-water-log').addEventListener('submit', async (e) => {
        e.preventDefault();
        hideErr();

        const vErr = validateForm();
        if (vErr) {
          showErr(vErr);
          return;
        }

        const btn = document.getElementById('btnSaveWL');
        btn.disabled = true;

        try {
          const created = await createWaterLog(getWaterLogPayload());
          renderAdvice(created?.advice);

          e.target.reset();
          setNowToDateInput();

          document.getElementById('wlView').value = 'active';
          await reload();
        } catch (err) {
          showErr(err.message);
        } finally {
          btn.disabled = false;
        }
      });

      document.getElementById('form-import-water-logs').addEventListener('submit', async (e) => {
        e.preventDefault();

        const input = document.getElementById('waterLogCsv');
        const file = input.files?.[0];
        if (!file) {
          showPanelMessage('wlImportResult', 'Please choose a CSV file first.', true);
          return;
        }

        const btn = document.getElementById('btnImportWL');
        btn.disabled = true;

        try {
          const result = await importWaterLogs(file);

          let msg = `Imported ${result.inserted_count} row(s).`;
          if (result.failed_count > 0) {
            const failedText = result.failed_rows
              .slice(0, 5)
              .map(r => `Line ${r.line}: ${r.errors.join(' | ')}`)
              .join(' || ');

            msg += ` Failed ${result.failed_count} row(s). ${failedText}`;
          }

          showPanelMessage('wlImportResult', msg, false);

          input.value = '';
          document.getElementById('wlView').value = 'active';
          await reload();
        } catch (err) {
          showPanelMessage('wlImportResult', err.message, true);
        } finally {
          btn.disabled = false;
        }
      });

      document.getElementById('reminder_enabled').addEventListener('change', toggleReminderFields);

      document.getElementById('form-water-reminder').addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('btnSaveReminder');
        btn.disabled = true;

        try {
          const payload = {
            enabled: document.getElementById('reminder_enabled').checked,
            frequency: document.getElementById('reminder_frequency').value,
            preferred_time: document.getElementById('reminder_time').value || '08:00',
            start_date: document.getElementById('reminder_start_date').value || new Date().toISOString().slice(0, 10),
          };

          const saved = await saveReminder(payload);
          renderReminder(saved);
          showPanelMessage('wlReminderResult', 'Reminder settings saved.', false);
        } catch (err) {
          showPanelMessage('wlReminderResult', err.message, true);
        } finally {
          btn.disabled = false;
        }
      });

      document.getElementById('wlTbody').addEventListener('click', async (e) => {
        const delBtn = e.target.closest('[data-del]');
        const resBtn = e.target.closest('[data-restore]');

        try {
          if (delBtn) {
            const id = delBtn.getAttribute('data-del');
            if (!confirm('Delete this water log?')) return;
            await deleteWaterLog(id);
            await reload();
          }

          if (resBtn) {
            const id = resBtn.getAttribute('data-restore');
            if (!confirm('Restore this water log?')) return;
            await restoreWaterLog(id);
            await reload();
          }
        } catch (err) {
          showErr(err.message);
        }
      });

      try {
        await reload();
        await loadReminderSection();
        await loadSensorSection();
      } catch (err) {
        showErr(err.message);
      }
    });
  </script>

@endsection