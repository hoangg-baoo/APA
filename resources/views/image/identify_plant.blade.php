@extends('layouts.site')

@section('title', 'Identify plants from tank photo')
@section('nav_identify_active', 'active')

@section('content')

<section class="section">
  <style>
    .ir15-grid {
      display: grid;
      grid-template-columns: 1.15fr 1fr;
      gap: 18px;
      align-items: start;
    }
    .ir15-grid-bottom {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
      align-items: start;
      margin-top: 18px;
    }
    .ir15-soft {
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 14px;
    }
    .ir15-preview-wrap {
      width: 100%;
      border: 1px dashed #cbd5e1;
      border-radius: 14px;
      background: #f8fafc;
      min-height: 220px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .ir15-preview-wrap img {
      max-width: 100%;
      max-height: 420px;
      object-fit: contain;
      display: block;
    }
    .ir15-canvas-wrap {
      width: 100%;
      min-height: 260px;
      border: 1px dashed #cbd5e1;
      border-radius: 14px;
      background: #f8fafc;
      overflow: auto;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 8px;
    }
    #ir15-crop-canvas {
      display: block;
      max-width: 100%;
      cursor: crosshair;
      background: #fff;
      border-radius: 10px;
    }
    .ir15-region-list,
    .ir15-merged-list {
      display: grid;
      gap: 12px;
    }
    .ir15-region-card,
    .ir15-merged-card {
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 12px;
      background: #fff;
    }
    .ir15-region-card img {
      width: 100%;
      max-height: 160px;
      object-fit: cover;
      border-radius: 10px;
      border: 1px solid #e5e7eb;
      margin-bottom: 10px;
    }
    .ir15-match-pills {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 8px;
    }
    .ir15-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 999px;
      background: #eef2ff;
      color: #3730a3;
      padding: 6px 10px;
      font-size: 12px;
      text-decoration: none;
    }
    .ir15-pill strong {
      color: #111827;
      font-weight: 700;
    }
    .ir15-top-row {
      display: flex;
      gap: 12px;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
    }
    .ir15-selection-box {
      font-size: 13px;
      color: #475569;
    }
    .ir15-confirm-box {
      margin-top: 14px;
      padding: 12px;
      border-radius: 12px;
      background: #ecfeff;
      color: #155e75;
      font-size: 14px;
    }
    .ir15-empty {
      border: 1px dashed #cbd5e1;
      border-radius: 14px;
      background: #f8fafc;
      padding: 18px;
      color: #64748b;
    }
    .ir15-session-meta {
      display: grid;
      gap: 8px;
      font-size: 14px;
      color: #334155;
    }
    .ir15-badge {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 4px 10px;
      font-size: 12px;
      font-weight: 700;
    }
    .ir15-badge-auto {
      background: #eef2ff;
      color: #4338ca;
    }
    .ir15-badge-manual {
      background: #ecfeff;
      color: #0f766e;
    }
    @media (max-width: 980px) {
      .ir15-grid,
      .ir15-grid-bottom {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <div class="plants-header">
    <div>
      <h1 class="section-title">Identify multiple plants from a tank photo</h1>
      <p class="section-subtitle">
        Upload one full tank photo, let the system auto-detect candidate plant regions, remove wrong regions if needed,
        and still add manual crop regions for better accuracy.
      </p>
    </div>

    <div class="page-actions">
      <a class="btn btn-outline" href="{{ route('image.identify_history') }}">Identify history</a>
    </div>
  </div>

  <div id="ir15-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

  <div class="ir15-grid">
    <section class="card">
      <h2 class="card-title">1. Start identify session</h2>
      <p class="card-subtitle">
        Upload the original tank photo once. Then you can auto-detect regions or crop manually.
      </p>

      <form id="ir15-session-form" class="form-grid-2">
        <div class="form-group form-group-full">
          <label for="ir15-file">Tank image</label>
          <input id="ir15-file" name="image" type="file" accept="image/*" class="form-control">
        </div>

        <div class="form-group form-group-full">
          <label for="ir15-note">Short note (optional)</label>
          <textarea id="ir15-note" rows="3" class="form-control"
                    placeholder="e.g. full tank photo, foreground + stem plants, medium light"></textarea>
        </div>

        <div class="form-group form-group-full">
          <label>Source preview</label>
          <div class="ir15-preview-wrap">
            <img id="ir15-source-preview" alt="Tank preview" style="display:none;">
            <div id="ir15-source-empty" class="ir15-empty" style="width:100%; text-align:center;">
              No tank photo selected yet.
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button id="ir15-create-session" type="submit" class="btn btn-primary">Create identify session</button>
          <button id="ir15-reset-session-form" type="reset" class="btn btn-secondary">Reset form</button>
        </div>
      </form>

      <div id="ir15-session-info" class="ir15-soft" style="display:none; margin-top:14px;"></div>
    </section>

    <section class="card">
      <h2 class="card-title">2. Detect or crop plant regions</h2>
      <p class="card-subtitle">
        Use auto-detect first, then manually crop extra regions if some plants are still missing.
      </p>

      <div class="ir15-canvas-wrap">
        <canvas id="ir15-crop-canvas"></canvas>
      </div>

      <div id="ir15-selection-info" class="ir15-selection-box" style="margin-top:10px;">
        No region selected yet.
      </div>

      <div class="ir15-soft" style="margin-top:12px;">
        <div class="form-group" style="margin:0;">
          <label for="ir15-max-regions">Auto detect max regions</label>
          <input id="ir15-max-regions" type="number" min="1" max="12" value="6" class="form-control" style="max-width:120px;">
          <div class="metric-note" style="margin-top:8px;">
            Auto detect will create candidate regions. You can remove wrong regions later.
          </div>
        </div>
      </div>

      <div class="form-actions" style="margin-top:12px;">
        <button id="ir15-auto-detect" type="button" class="btn btn-primary">Auto detect regions</button>
        <button id="ir15-clear-selection" type="button" class="btn btn-secondary">Clear selection</button>
        <button id="ir15-crop-identify" type="button" class="btn btn-primary" disabled>Crop &amp; identify region</button>
        <button id="ir15-start-new" type="button" class="btn btn-secondary">Start new session</button>
      </div>
    </section>
  </div>

  <div class="ir15-grid-bottom">
    <section class="card">
      <div class="ir15-top-row">
        <div>
          <h2 class="card-title">3. Region results</h2>
          <p class="card-subtitle">Auto and manual regions are stored together.</p>
        </div>
      </div>

      <div id="ir15-regions-empty" class="ir15-empty">
        No regions yet. Create a session first, then auto detect or crop manually.
      </div>

      <div id="ir15-region-list" class="ir15-region-list" style="display:none;"></div>
    </section>

    <section class="card">
      <div class="ir15-top-row">
        <div>
          <h2 class="card-title">4. Top plants in tank photo</h2>
          <p class="card-subtitle">Merged from all identified regions.</p>
        </div>
      </div>

      <div class="ir15-soft" style="margin-bottom:12px;">
        <div class="ir-tank-row">
          <div style="font-weight:700;">Choose tank</div>
          <select id="ir15-tank-select" class="form-control" style="max-width:320px;">
            <option value="">-- Select your tank --</option>
          </select>
          <div style="font-size:12px;color:#047857;">
            Tick the suggested plants you want to add.
          </div>
        </div>
      </div>

      <div id="ir15-merged-empty" class="ir15-empty">
        No merged result yet. Add at least one region first.
      </div>

      <div id="ir15-merged-list" class="ir15-merged-list" style="display:none;"></div>

      <div class="form-actions" style="margin-top:14px;">
        <button id="ir15-add-selected" type="button" class="btn btn-primary" disabled>Add selected plants to tank</button>
      </div>

      <div id="ir15-confirmed-box" class="ir15-confirm-box" style="display:none;"></div>
    </section>
  </div>
</section>

<script>
  const CSRF = @json(csrf_token());

  const alertBox = document.getElementById('ir15-alert');

  const fileInput = document.getElementById('ir15-file');
  const noteInput = document.getElementById('ir15-note');
  const sessionForm = document.getElementById('ir15-session-form');
  const createSessionBtn = document.getElementById('ir15-create-session');
  const resetSessionFormBtn = document.getElementById('ir15-reset-session-form');

  const sourcePreview = document.getElementById('ir15-source-preview');
  const sourceEmpty = document.getElementById('ir15-source-empty');
  const sessionInfoBox = document.getElementById('ir15-session-info');

  const cropCanvas = document.getElementById('ir15-crop-canvas');
  const cropCtx = cropCanvas.getContext('2d');
  const selectionInfo = document.getElementById('ir15-selection-info');
  const clearSelectionBtn = document.getElementById('ir15-clear-selection');
  const cropIdentifyBtn = document.getElementById('ir15-crop-identify');
  const startNewBtn = document.getElementById('ir15-start-new');
  const autoDetectBtn = document.getElementById('ir15-auto-detect');
  const maxRegionsInput = document.getElementById('ir15-max-regions');

  const tankSelect = document.getElementById('ir15-tank-select');
  const regionsEmpty = document.getElementById('ir15-regions-empty');
  const regionList = document.getElementById('ir15-region-list');

  const mergedEmpty = document.getElementById('ir15-merged-empty');
  const mergedList = document.getElementById('ir15-merged-list');
  const addSelectedBtn = document.getElementById('ir15-add-selected');
  const confirmedBox = document.getElementById('ir15-confirmed-box');

  const state = {
    file: null,
    image: null,
    sessionId: null,
    session: null,
    selection: null,
    drawing: false,
    startX: 0,
    startY: 0,
    selectedPlants: new Set(),
  };

  function showError(msg) {
    alertBox.style.display = 'block';
    alertBox.textContent = msg || 'Something went wrong.';
  }

  function hideError() {
    alertBox.style.display = 'none';
    alertBox.textContent = '';
  }

  function safeUrl(path) {
    if (!path) return '';
    const p = String(path);
    if (p.startsWith('http://') || p.startsWith('https://')) return p;
    return '/' + p.replace(/^\/+/, '');
  }

  function resetSelectionOnly() {
    state.selection = null;
    cropIdentifyBtn.disabled = true;
    selectionInfo.textContent = 'No region selected yet.';
    drawCanvas();
  }

  function fullReset() {
    state.file = null;
    state.image = null;
    state.sessionId = null;
    state.session = null;
    state.selection = null;
    state.drawing = false;
    state.startX = 0;
    state.startY = 0;
    state.selectedPlants = new Set();

    sessionForm.reset();
    sourcePreview.src = '';
    sourcePreview.style.display = 'none';
    sourceEmpty.style.display = 'block';
    sessionInfoBox.style.display = 'none';
    sessionInfoBox.innerHTML = '';

    cropCanvas.width = 0;
    cropCanvas.height = 0;

    selectionInfo.textContent = 'No region selected yet.';
    cropIdentifyBtn.disabled = true;

    regionsEmpty.style.display = 'block';
    regionList.style.display = 'none';
    regionList.innerHTML = '';

    mergedEmpty.style.display = 'block';
    mergedList.style.display = 'none';
    mergedList.innerHTML = '';

    confirmedBox.style.display = 'none';
    confirmedBox.innerHTML = '';

    addSelectedBtn.disabled = true;
    hideError();
  }

  function loadSourcePreview(file) {
    state.file = file;

    if (!file) {
      sourcePreview.src = '';
      sourcePreview.style.display = 'none';
      sourceEmpty.style.display = 'block';
      state.image = null;
      drawCanvas();
      return;
    }

    const reader = new FileReader();
    reader.onload = e => {
      sourcePreview.src = e.target.result;
      sourcePreview.style.display = 'block';
      sourceEmpty.style.display = 'none';

      const img = new Image();
      img.onload = () => {
        state.image = img;
        drawCanvas();
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }

  function drawCanvas() {
    if (!state.image) {
      cropCanvas.width = 640;
      cropCanvas.height = 360;
      cropCtx.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
      cropCtx.fillStyle = '#f8fafc';
      cropCtx.fillRect(0, 0, cropCanvas.width, cropCanvas.height);
      cropCtx.fillStyle = '#64748b';
      cropCtx.font = '16px sans-serif';
      cropCtx.fillText('Upload a tank photo to start detection and cropping.', 24, 40);
      return;
    }

    const parentWidth = cropCanvas.parentElement.clientWidth || 760;
    const maxWidth = Math.min(parentWidth - 24, 760);
    const scale = Math.min(1, maxWidth / state.image.naturalWidth);

    const drawWidth = Math.max(1, Math.round(state.image.naturalWidth * scale));
    const drawHeight = Math.max(1, Math.round(state.image.naturalHeight * scale));

    cropCanvas.width = drawWidth;
    cropCanvas.height = drawHeight;

    cropCtx.clearRect(0, 0, drawWidth, drawHeight);
    cropCtx.drawImage(state.image, 0, 0, drawWidth, drawHeight);

    if (state.selection) {
      cropCtx.fillStyle = 'rgba(14, 165, 233, 0.18)';
      cropCtx.strokeStyle = '#0284c7';
      cropCtx.lineWidth = 2;
      cropCtx.fillRect(state.selection.x, state.selection.y, state.selection.w, state.selection.h);
      cropCtx.strokeRect(state.selection.x, state.selection.y, state.selection.w, state.selection.h);

      cropCtx.fillStyle = '#0369a1';
      cropCtx.font = '12px sans-serif';
      cropCtx.fillText(
        `${Math.round(state.selection.w)} × ${Math.round(state.selection.h)}`,
        state.selection.x + 6,
        Math.max(14, state.selection.y - 6)
      );
    }
  }

  function getCanvasPos(evt) {
    const rect = cropCanvas.getBoundingClientRect();
    return {
      x: evt.clientX - rect.left,
      y: evt.clientY - rect.top,
    };
  }

  function normalizeRect(x1, y1, x2, y2) {
    const x = Math.min(x1, x2);
    const y = Math.min(y1, y2);
    const w = Math.abs(x2 - x1);
    const h = Math.abs(y2 - y1);

    return { x, y, w, h };
  }

  function getSourceCropBox() {
    if (!state.image || !state.selection || cropCanvas.width <= 0 || cropCanvas.height <= 0) {
      return null;
    }

    const scaleX = state.image.naturalWidth / cropCanvas.width;
    const scaleY = state.image.naturalHeight / cropCanvas.height;

    return {
      x: Math.max(0, Math.round(state.selection.x * scaleX)),
      y: Math.max(0, Math.round(state.selection.y * scaleY)),
      w: Math.max(1, Math.round(state.selection.w * scaleX)),
      h: Math.max(1, Math.round(state.selection.h * scaleY)),
    };
  }

  function selectionToBlob() {
    return new Promise((resolve, reject) => {
      const crop = getSourceCropBox();
      if (!crop || !state.image) {
        reject(new Error('No valid crop selected.'));
        return;
      }

      const tempCanvas = document.createElement('canvas');
      tempCanvas.width = crop.w;
      tempCanvas.height = crop.h;

      const tempCtx = tempCanvas.getContext('2d');
      tempCtx.drawImage(
        state.image,
        crop.x, crop.y, crop.w, crop.h,
        0, 0, crop.w, crop.h
      );

      tempCanvas.toBlob(blob => {
        if (!blob) {
          reject(new Error('Failed to generate cropped image.'));
          return;
        }
        resolve({ blob, crop });
      }, 'image/png');
    });
  }

  function updateSelectionInfo() {
    const crop = getSourceCropBox();

    if (!state.selection || !crop) {
      selectionInfo.textContent = 'No region selected yet.';
      cropIdentifyBtn.disabled = true;
      return;
    }

    selectionInfo.textContent =
      `Selected region: x=${crop.x}, y=${crop.y}, w=${crop.w}, h=${crop.h}`;
    cropIdentifyBtn.disabled = !state.sessionId;
  }

  async function loadTanks() {
    const res = await fetch('/api/tanks', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });

    const json = await res.json().catch(() => null);
    if (!res.ok || !json || json.success !== true) return;

    const tanks = json.data || [];
    tankSelect.innerHTML = `<option value="">-- Select your tank --</option>`;

    tanks.forEach(t => {
      const opt = document.createElement('option');
      opt.value = t.id;
      opt.textContent = t.name || ('Tank #' + t.id);
      tankSelect.appendChild(opt);
    });
  }

  function renderSessionInfo(session) {
    sessionInfoBox.style.display = 'block';
    sessionInfoBox.innerHTML = `
      <div class="ir15-session-meta">
        <div><strong>Session ID:</strong> #${session.id}</div>
        <div><strong>Created:</strong> ${session.created_at ? new Date(session.created_at).toLocaleString() : '—'}</div>
        <div><strong>Regions added:</strong> ${session.regions_count ?? 0}</div>
        <div><strong>Tank:</strong> ${session.tank?.name || 'Not linked yet'}</div>
        <div><strong>Note:</strong> ${session.note || '—'}</div>
      </div>
    `;
  }

  function renderRegions(session) {
    const regions = session.regions || [];

    if (!regions.length) {
      regionsEmpty.style.display = 'block';
      regionList.style.display = 'none';
      regionList.innerHTML = '';
      return;
    }

    regionsEmpty.style.display = 'none';
    regionList.style.display = 'grid';
    regionList.innerHTML = '';

    regions.forEach(region => {
      const top = region.results || [];
      const badge = region.proposal_source === 'auto'
        ? `<span class="ir15-badge ir15-badge-auto">Auto</span>`
        : `<span class="ir15-badge ir15-badge-manual">Manual</span>`;

      const matches = top.map(item => {
        const percent = Math.round((item.score || 0) * 100);
        return `
          <a class="ir15-pill" href="/plant-library/${item.plant_id}">
            <strong>${item.name || 'Unknown'}</strong>
            <span>${percent}%</span>
          </a>
        `;
      }).join('');

      const proposalLine = region.proposal_source === 'auto' && region.proposal_score != null
        ? `<div style="font-size:12px; color:#475569; margin-top:6px;">Proposal score: ${Math.round(region.proposal_score * 100)}%</div>`
        : '';

      const card = document.createElement('article');
      card.className = 'ir15-region-card';

      card.innerHTML = `
        <img src="${safeUrl(region.crop_image_path)}" alt="Region crop">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
          <div style="font-weight:700;">Region #${region.id}</div>
          <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            ${badge}
            <button type="button" class="btn btn-secondary btn-xs btn-danger-soft" data-delete-region="${region.id}">Remove region</button>
          </div>
        </div>
        <div style="font-size:13px; color:#475569; margin-top:8px;">
          ${region.crop_box ? `x=${region.crop_box.x}, y=${region.crop_box.y}, w=${region.crop_box.w}, h=${region.crop_box.h}` : 'No crop box'}
        </div>
        ${proposalLine}
        <div class="ir15-match-pills">
          ${matches || '<div class="ir15-empty" style="width:100%;">No matches.</div>'}
        </div>
      `;

      regionList.appendChild(card);
    });
  }

  function renderMergedResults(session) {
    const merged = session.merged_results || [];
    const confirmed = session.confirmed_plants || [];

    if (!state.selectedPlants.size && confirmed.length) {
      confirmed.forEach(p => state.selectedPlants.add(String(p.plant_id)));
    }

    if (!merged.length) {
      mergedEmpty.style.display = 'block';
      mergedList.style.display = 'none';
      mergedList.innerHTML = '';
      addSelectedBtn.disabled = true;
      return;
    }

    mergedEmpty.style.display = 'none';
    mergedList.style.display = 'grid';
    mergedList.innerHTML = '';

    merged.forEach(item => {
      const avgPercent = Math.round((item.avg_score || 0) * 100);
      const bestPercent = Math.round((item.best_score || 0) * 100);
      const checked = state.selectedPlants.has(String(item.plant_id)) ? 'checked' : '';

      const card = document.createElement('article');
      card.className = 'ir15-merged-card';

      card.innerHTML = `
        <label style="display:flex; gap:12px; align-items:flex-start;">
          <input type="checkbox" class="ir15-plant-check" value="${item.plant_id}" ${checked} style="margin-top:4px;">
          <div style="flex:1;">
            <div style="font-weight:700; font-size:15px;">${item.name || 'Unknown'}</div>
            <div style="font-size:13px; color:#475569; margin-top:4px;">
              Appeared in <b>${item.appear_count}</b> region(s)
              • Avg score: <b>${avgPercent}%</b>
              • Best score: <b>${bestPercent}%</b>
            </div>
            <div style="font-size:13px; color:#475569; margin-top:4px;">
              Light: ${item.light_level || '-'} • Difficulty: ${item.difficulty || '-'}
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px;">
              <a href="/plant-library/${item.plant_id}" class="btn btn-secondary btn-xs">View plant details</a>
            </div>
          </div>
        </label>
      `;

      mergedList.appendChild(card);
    });

    mergedList.querySelectorAll('.ir15-plant-check').forEach(cb => {
      cb.addEventListener('change', () => {
        if (cb.checked) {
          state.selectedPlants.add(String(cb.value));
        } else {
          state.selectedPlants.delete(String(cb.value));
        }

        addSelectedBtn.disabled = state.selectedPlants.size === 0;
      });
    });

    addSelectedBtn.disabled = state.selectedPlants.size === 0;

    if (confirmed.length) {
      confirmedBox.style.display = 'block';
      confirmedBox.innerHTML = `
        <div style="font-weight:700; margin-bottom:8px;">Confirmed plants</div>
        <div class="ir15-match-pills">
          ${confirmed.map(p => `
            <a class="ir15-pill" href="/plant-library/${p.plant_id}">
              <strong>${p.name || 'Unknown'}</strong>
            </a>
          `).join('')}
        </div>
      `;
    } else {
      confirmedBox.style.display = 'none';
      confirmedBox.innerHTML = '';
    }
  }

  function renderSession(session) {
    state.session = session;
    state.sessionId = session.id;

    renderSessionInfo(session);
    renderRegions(session);
    renderMergedResults(session);
    updateSelectionInfo();
  }

  async function createSession() {
    hideError();

    if (!state.file) {
      showError('Please choose a tank image first.');
      return;
    }

    createSessionBtn.disabled = true;

    try {
      const fd = new FormData();
      fd.append('image', state.file);
      fd.append('note', noteInput.value || '');

      const res = await fetch('/api/identify/session', {
        method: 'POST',
        body: fd,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      const json = await res.json().catch(() => null);

      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to create identify session.');
        return;
      }

      state.selectedPlants = new Set();
      renderSession(json.data.session);
    } catch (err) {
      showError('Network error while creating session.');
    } finally {
      createSessionBtn.disabled = false;
    }
  }

  async function cropAndIdentify() {
    hideError();

    if (!state.sessionId) {
      showError('Please create an identify session first.');
      return;
    }

    if (!state.selection) {
      showError('Please drag to select a region first.');
      return;
    }

    cropIdentifyBtn.disabled = true;

    try {
      const { blob, crop } = await selectionToBlob();

      const fd = new FormData();
      fd.append('crop_image', blob, `region-${Date.now()}.png`);
      fd.append('top_k', '5');
      fd.append('crop_box', JSON.stringify(crop));

      const res = await fetch(`/api/identify/session/${state.sessionId}/regions`, {
        method: 'POST',
        body: fd,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      const json = await res.json().catch(() => null);

      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to identify selected region.');
        return;
      }

      renderSession(json.data.session);
      resetSelectionOnly();
    } catch (err) {
      showError(err.message || 'Failed to crop region.');
    } finally {
      cropIdentifyBtn.disabled = false;
      updateSelectionInfo();
    }
  }

  async function autoDetectRegions() {
    hideError();

    if (!state.sessionId) {
      showError('Please create an identify session first.');
      return;
    }

    autoDetectBtn.disabled = true;

    try {
      const res = await fetch(`/api/identify/session/${state.sessionId}/propose-regions`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          max_regions: Number(maxRegionsInput.value || 6),
        }),
      });

      const json = await res.json().catch(() => null);

      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to auto detect regions.');
        return;
      }

      renderSession(json.data.session);
      resetSelectionOnly();
    } catch (err) {
      showError('Network error while auto detecting regions.');
    } finally {
      autoDetectBtn.disabled = false;
    }
  }

  async function deleteRegion(regionId) {
    hideError();

    if (!state.sessionId) {
      showError('No active identify session.');
      return;
    }

    const ok = confirm('Remove this region from the session?');
    if (!ok) return;

    try {
      const res = await fetch(`/api/identify/session/${state.sessionId}/regions/${regionId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });

      const json = await res.json().catch(() => null);

      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to remove region.');
        return;
      }

      renderSession(json.data.session);
    } catch (err) {
      showError('Network error while removing region.');
    }
  }

  async function addSelectedPlantsToTank() {
    hideError();

    if (!state.sessionId) {
      showError('No active identify session.');
      return;
    }

    if (!tankSelect.value) {
      showError('Please select a tank first.');
      return;
    }

    const plants = Array.from(state.selectedPlants).map(v => Number(v));
    if (!plants.length) {
      showError('Please tick at least one plant.');
      return;
    }

    addSelectedBtn.disabled = true;

    try {
      const res = await fetch(`/api/identify/session/${state.sessionId}/add-to-tank`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          tank_id: Number(tankSelect.value),
          plants,
        }),
      });

      const json = await res.json().catch(() => null);

      if (!res.ok || !json || json.success !== true) {
        showError(json?.message || 'Failed to add selected plants to tank.');
        return;
      }

      renderSession(json.data.session);
      alert('Selected plants added to tank successfully!');
    } catch (err) {
      showError('Network error while adding selected plants.');
    } finally {
      addSelectedBtn.disabled = state.selectedPlants.size === 0;
    }
  }

  fileInput.addEventListener('change', function () {
    const file = this.files && this.files[0] ? this.files[0] : null;
    loadSourcePreview(file);
    state.sessionId = null;
    state.session = null;
    state.selectedPlants = new Set();
    sessionInfoBox.style.display = 'none';
    regionsEmpty.style.display = 'block';
    regionList.style.display = 'none';
    regionList.innerHTML = '';
    mergedEmpty.style.display = 'block';
    mergedList.style.display = 'none';
    mergedList.innerHTML = '';
    confirmedBox.style.display = 'none';
    confirmedBox.innerHTML = '';
    tankSelect.value = '';
    resetSelectionOnly();
    hideError();
  });

  sessionForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    await createSession();
  });

  resetSessionFormBtn.addEventListener('click', function () {
    setTimeout(() => {
      fullReset();
    }, 0);
  });

  clearSelectionBtn.addEventListener('click', () => {
    resetSelectionOnly();
  });

  startNewBtn.addEventListener('click', () => {
    fullReset();
  });

  cropIdentifyBtn.addEventListener('click', async () => {
    await cropAndIdentify();
  });

  autoDetectBtn.addEventListener('click', async () => {
    await autoDetectRegions();
  });

  addSelectedBtn.addEventListener('click', async () => {
    await addSelectedPlantsToTank();
  });

  regionList.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-delete-region]');
    if (!btn) return;

    await deleteRegion(btn.getAttribute('data-delete-region'));
  });

  cropCanvas.addEventListener('mousedown', (evt) => {
    if (!state.image) return;

    hideError();

    const pos = getCanvasPos(evt);
    state.drawing = true;
    state.startX = pos.x;
    state.startY = pos.y;
    state.selection = { x: pos.x, y: pos.y, w: 0, h: 0 };
    drawCanvas();
  });

  cropCanvas.addEventListener('mousemove', (evt) => {
    if (!state.image || !state.drawing) return;

    const pos = getCanvasPos(evt);
    state.selection = normalizeRect(state.startX, state.startY, pos.x, pos.y);
    drawCanvas();
    updateSelectionInfo();
  });

  window.addEventListener('mouseup', () => {
    if (!state.drawing) return;

    state.drawing = false;

    if (!state.selection || state.selection.w < 12 || state.selection.h < 12) {
      state.selection = null;
      showError('Selected region is too small. Please drag a larger area.');
    }

    drawCanvas();
    updateSelectionInfo();
  });

  window.addEventListener('resize', () => {
    drawCanvas();
    updateSelectionInfo();
  });

  document.addEventListener('DOMContentLoaded', async () => {
    await loadTanks();
    fullReset();
    drawCanvas();
  });
</script>

@endsection