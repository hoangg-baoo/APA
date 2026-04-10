@extends('layouts.site')

@section('title', 'Identify history')
@section('nav_identify_active', 'active')

@section('content')

<section class="section">
  <style>
    .irh-card {
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      background: #fff;
      padding: 14px;
      display: grid;
      grid-template-columns: 220px 1fr;
      gap: 14px;
    }
    .irh-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      background: #f8fafc;
    }
    .irh-meta {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      font-size: 13px;
      color: #475569;
      margin-top: 6px;
    }
    .irh-pills {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 10px;
    }
    .irh-pill {
      display: inline-flex;
      gap: 8px;
      align-items: center;
      border-radius: 999px;
      background: #eff6ff;
      color: #1d4ed8;
      padding: 6px 10px;
      font-size: 12px;
      text-decoration: none;
    }
    .irh-pill-confirmed {
      background: #ecfeff;
      color: #155e75;
    }
    .irh-detail {
      margin-top: 12px;
      border-top: 1px solid #e5e7eb;
      padding-top: 12px;
      display: none;
    }
    .irh-region-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 12px;
    }
    .irh-region-card {
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 10px;
      background: #f8fafc;
    }
    .irh-region-card img {
      width: 100%;
      height: 130px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 8px;
    }
    .irh-badge {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 4px 10px;
      font-size: 12px;
      font-weight: 700;
    }
    .irh-badge-auto {
      background: #eef2ff;
      color: #4338ca;
    }
    .irh-badge-manual {
      background: #ecfeff;
      color: #0f766e;
    }
    @media (max-width: 900px) {
      .irh-card {
        grid-template-columns: 1fr;
      }
      .irh-card img {
        height: 220px;
      }
    }
  </style>

  <div class="plants-header">
    <div>
      <h1 class="section-title">Identify history</h1>
      <p class="section-subtitle">
        Review previous multi-region identify sessions, auto/manual regions, merged plant results, and confirmed plants.
      </p>
    </div>

    <div class="page-actions">
      <a class="btn btn-outline" href="{{ route('image.identify_plant') }}">Back to Identify</a>
    </div>
  </div>

  <section class="card">
    <div id="hist-alert" class="alert alert-danger" style="display:none; margin-bottom:12px;"></div>

    <div class="ir-history-toolbar">
      <div class="form-group" style="min-width:260px; margin:0;">
        <label for="hist-tank" style="margin-bottom:6px;">Filter by tank</label>
        <select id="hist-tank" class="form-control">
          <option value="">All tanks</option>
        </select>
      </div>

      <div style="display:flex; gap:8px; align-items:flex-end;">
        <button id="hist-refresh" class="btn btn-primary" type="button">Refresh</button>
      </div>
    </div>

    <div id="hist-empty" class="card-soft" style="margin-top:12px;">
      No identify session yet. Go to Identify and create one from a tank photo.
    </div>

    <div id="hist-list" class="ir-history-grid" style="display:none;"></div>

    <div id="hist-pager" class="plants-pagination" style="display:none;">
      <div class="plants-pager">
        <button id="hist-prev" class="plants-page" type="button">Prev</button>
        <div id="hist-pageinfo" class="plants-page dots">1 / 1</div>
        <button id="hist-next" class="plants-page" type="button">Next</button>
      </div>
    </div>
  </section>
</section>

<script>
  const alertBox = document.getElementById('hist-alert');
  const tankSelect = document.getElementById('hist-tank');
  const listEl = document.getElementById('hist-list');
  const emptyEl = document.getElementById('hist-empty');

  const pager = document.getElementById('hist-pager');
  const btnPrev = document.getElementById('hist-prev');
  const btnNext = document.getElementById('hist-next');
  const pageInfo = document.getElementById('hist-pageinfo');
  const btnRefresh = document.getElementById('hist-refresh');

  let currentPage = 1;
  let lastPage = 1;

  function showError(msg) {
    alertBox.style.display = 'block';
    alertBox.textContent = msg;
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

  async function loadTanks() {
    const res = await fetch('/api/tanks', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
    });

    const json = await res.json().catch(() => null);
    if (!res.ok || !json || json.success !== true) return;

    const tanks = json.data || [];
    tankSelect.innerHTML = `<option value="">All tanks</option>`;
    tanks.forEach(t => {
      const opt = document.createElement('option');
      opt.value = t.id;
      opt.textContent = t.name || ('Tank #' + t.id);
      tankSelect.appendChild(opt);
    });
  }

  function renderHistoryItems(items) {
    listEl.innerHTML = '';

    if (!items || items.length === 0) {
      listEl.style.display = 'none';
      emptyEl.style.display = 'block';
      pager.style.display = 'none';
      return;
    }

    emptyEl.style.display = 'none';
    listEl.style.display = 'grid';

    items.forEach(it => {
      const merged = it.merged_results || [];
      const confirmed = it.confirmed_plants || [];
      const createdAt = it.created_at ? new Date(it.created_at).toLocaleString() : '—';

      const mergedHtml = merged.length
        ? merged.map(r => {
            const percent = Math.round((r.avg_score || 0) * 100);
            return `
              <a class="irh-pill" href="/plant-library/${r.plant_id}">
                <strong>${r.name || 'Unknown'}</strong>
                <span>${percent}%</span>
                <span>x${r.appear_count || 1}</span>
              </a>
            `;
          }).join('')
        : `<div class="nav-dropdown-empty">No merged result yet.</div>`;

      const confirmedHtml = confirmed.length
        ? confirmed.map(r => `
            <a class="irh-pill irh-pill-confirmed" href="/plant-library/${r.plant_id}">
              <strong>${r.name || 'Unknown'}</strong>
            </a>
          `).join('')
        : `<div style="font-size:13px; color:#64748b; margin-top:8px;">No confirmed plants yet.</div>`;

      const card = document.createElement('article');
      card.className = 'irh-card';

      card.innerHTML = `
        <div>
          <img src="${safeUrl(it.source_image_path)}" alt="Identify session source">
        </div>

        <div>
          <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
            <div>
              <div style="font-weight:700; font-size:16px;">Identify Session #${it.id}</div>
              <div class="irh-meta">
                <span>Tank: <b>${it.tank?.name || 'Not linked yet'}</b></span>
                <span>•</span>
                <span>${createdAt}</span>
                <span>•</span>
                <span>${it.regions_count || 0} region(s)</span>
              </div>
            </div>

            <div style="display:flex; gap:8px; flex-wrap:wrap;">
              <button type="button" class="btn btn-secondary btn-xs" data-view-session="${it.id}">View details</button>
            </div>
          </div>

          ${it.note ? `<div style="margin-top:10px; color:#334155;">${it.note}</div>` : ''}

          <div style="margin-top:12px;">
            <div style="font-weight:700; font-size:14px;">Merged top plants</div>
            <div class="irh-pills">${mergedHtml}</div>
          </div>

          <div style="margin-top:12px;">
            <div style="font-weight:700; font-size:14px;">Confirmed plants</div>
            <div class="irh-pills">${confirmedHtml}</div>
          </div>

          <div class="irh-detail" id="hist-detail-${it.id}"></div>
        </div>
      `;

      listEl.appendChild(card);
    });

    listEl.querySelectorAll('[data-view-session]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const sessionId = btn.getAttribute('data-view-session');
        const detailBox = document.getElementById(`hist-detail-${sessionId}`);

        if (detailBox.dataset.loaded === '1') {
          detailBox.style.display = detailBox.style.display === 'none' ? 'block' : 'none';
          return;
        }

        btn.disabled = true;

        try {
          const res = await fetch(`/api/identify/history/${sessionId}`, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
          });

          const json = await res.json().catch(() => null);

          if (!res.ok || !json || json.success !== true) {
            showError(json?.message || 'Failed to load session detail.');
            return;
          }

          const session = json.data?.session;
          const regions = session?.regions || [];

          detailBox.innerHTML = `
            <div style="font-weight:700; margin-bottom:10px;">Region details</div>
            <div class="irh-region-grid">
              ${regions.map(region => {
                const top = region.results || [];
                const badge = region.proposal_source === 'auto'
                  ? `<span class="irh-badge irh-badge-auto">Auto</span>`
                  : `<span class="irh-badge irh-badge-manual">Manual</span>`;

                const proposalLine = region.proposal_source === 'auto' && region.proposal_score != null
                  ? `<div style="font-size:12px; color:#475569; margin-bottom:8px;">Proposal score: ${Math.round(region.proposal_score * 100)}%</div>`
                  : '';

                return `
                  <div class="irh-region-card">
                    <img src="${safeUrl(region.crop_image_path)}" alt="Region">
                    <div style="display:flex; justify-content:space-between; gap:8px; align-items:center; margin-bottom:8px;">
                      <div style="font-weight:700;">Region #${region.id}</div>
                      ${badge}
                    </div>
                    <div style="font-size:13px; color:#475569; margin-bottom:8px;">
                      ${region.crop_box ? `x=${region.crop_box.x}, y=${region.crop_box.y}, w=${region.crop_box.w}, h=${region.crop_box.h}` : 'No crop box'}
                    </div>
                    ${proposalLine}
                    <div class="irh-pills">
                      ${top.map(r => {
                        const percent = Math.round((r.score || 0) * 100);
                        return `
                          <a class="irh-pill" href="/plant-library/${r.plant_id}">
                            <strong>${r.name || 'Unknown'}</strong>
                            <span>${percent}%</span>
                          </a>
                        `;
                      }).join('')}
                    </div>
                  </div>
                `;
              }).join('')}
            </div>
          `;

          detailBox.dataset.loaded = '1';
          detailBox.style.display = 'block';
        } catch (err) {
          showError('Network error while loading detail.');
        } finally {
          btn.disabled = false;
        }
      });
    });
  }

  async function loadHistory(page = 1) {
    hideError();

    const tankId = tankSelect.value;
    const qs = new URLSearchParams();
    qs.set('page', String(page));
    if (tankId) qs.set('tank_id', tankId);

    const res = await fetch('/api/identify/history?' + qs.toString(), {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
    });

    const json = await res.json().catch(() => null);

    if (!res.ok || !json || json.success !== true) {
      showError(json?.message || 'Failed to load history.');
      return;
    }

    const data = json.data;
    const items = data?.data || [];

    currentPage = data?.current_page || 1;
    lastPage = data?.last_page || 1;

    renderHistoryItems(items);

    if (lastPage > 1) {
      pager.style.display = 'flex';
      pageInfo.textContent = `${currentPage} / ${lastPage}`;
      btnPrev.disabled = currentPage <= 1;
      btnNext.disabled = currentPage >= lastPage;
      btnPrev.classList.toggle('disabled', btnPrev.disabled);
      btnNext.classList.toggle('disabled', btnNext.disabled);
    } else {
      pager.style.display = 'none';
    }
  }

  btnPrev.addEventListener('click', () => {
    if (currentPage > 1) loadHistory(currentPage - 1);
  });

  btnNext.addEventListener('click', () => {
    if (currentPage < lastPage) loadHistory(currentPage + 1);
  });

  btnRefresh.addEventListener('click', () => loadHistory(1));
  tankSelect.addEventListener('change', () => loadHistory(1));

  document.addEventListener('DOMContentLoaded', async () => {
    await loadTanks();
    await loadHistory(1);
  });
</script>

@endsection