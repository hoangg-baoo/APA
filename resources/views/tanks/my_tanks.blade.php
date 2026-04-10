{{-- resources/views/tanks/my_tanks.blade.php --}}
@extends('layouts.site')

@section('title', 'My Tanks – Aquatic Plant Advisor')
@section('nav_tanks_active', 'active')

@section('content')
  <section class="section">
    <h1 class="section-title">My tanks</h1>
    <p class="section-subtitle">
      Manage all your planted tanks in one place. Create a tank, attach plants and track their water parameters over time.
    </p>

    <div class="page-actions" style="margin-bottom: 12px;">
      <button class="btn btn-primary" onclick="window.location.href='{{ route('tanks.tank_form') }}'">
        + Create new tank
      </button>
    </div>

    <section class="card">
      <div class="card-header card-header-inline">
        <div>
          <h2 class="card-title">Tank list</h2>
          <p class="card-subtitle">Quick overview of all tanks you are monitoring.</p>
        </div>
        <div>
          <input id="tank-search" class="form-control form-control-sm" placeholder="Search by name…">
        </div>
      </div>

      <div class="table-wrapper">
        <table class="table">
          <thead>
          <tr>
            <th style="width: 26%">Name</th>
            <th style="width: 18%">Size</th>
            <th style="width: 14%">Volume (L)</th>
            <th style="width: 14%">Created</th>
            <th style="width: 10%">Plants</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody id="tank-table-body">
            <tr id="tank-loading-row">
              <td colspan="6" style="color:#6b7280;">Loading tanks...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>

  <script>
    const API_TANKS = '/api/tanks';
    const csrfToken = @json(csrf_token());

    const tankSearch = document.getElementById('tank-search');
    const tankTbody  = document.getElementById('tank-table-body');

    function fmtDate(iso) {
      if (!iso) return '';
      const d = new Date(iso);
      if (isNaN(d.getTime())) return iso;
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const dd = String(d.getDate()).padStart(2, '0');
      return `${yyyy}-${mm}-${dd}`;
    }

    function num(v) {
      if (v === null || v === undefined || v === '') return '';
      const n = Number(v);
      if (isNaN(n)) return v;
      return n % 1 === 0 ? String(n) : n.toFixed(1);
    }

    function getPlantsCount(t) {
      // Laravel withCount('tankPlants') -> tank_plants_count
      if (typeof t.tank_plants_count === 'number') return t.tank_plants_count;
      if (typeof t.tankPlants_count === 'number') return t.tankPlants_count;
      if (Array.isArray(t.tank_plants)) return t.tank_plants.length;
      if (Array.isArray(t.tankPlants)) return t.tankPlants.length;
      return 0;
    }

    function clearRows() {
      while (tankTbody.firstChild) tankTbody.removeChild(tankTbody.firstChild);
    }

    function renderEmpty() {
      clearRows();
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = 6;
      td.style.color = '#6b7280';
      td.textContent = 'No tanks yet. Click "Create new tank" to add one.';
      tr.appendChild(td);
      tankTbody.appendChild(tr);
    }

    function renderRows(tanks) {
      clearRows();

      if (!tanks || tanks.length === 0) {
        renderEmpty();
        return;
      }

      tanks.forEach(t => {
        const tr = document.createElement('tr');

        const nameTd = document.createElement('td');
        nameTd.textContent = t.name ?? '';
        tr.appendChild(nameTd);

        const sizeTd = document.createElement('td');
        sizeTd.textContent = t.size ?? '';
        tr.appendChild(sizeTd);

        const volTd = document.createElement('td');
        volTd.textContent = num(t.volume_liters);
        tr.appendChild(volTd);

        const createdTd = document.createElement('td');
        createdTd.textContent = fmtDate(t.created_at);
        tr.appendChild(createdTd);

        const plantsTd = document.createElement('td');
        const badge = document.createElement('span');
        badge.className = 'badge badge-soft-green';
        const c = getPlantsCount(t);
        badge.textContent = `${c} plant${c === 1 ? '' : 's'}`;
        plantsTd.appendChild(badge);
        tr.appendChild(plantsTd);

        const actionsTd = document.createElement('td');
        const wrap = document.createElement('div');
        wrap.className = 'table-actions';

        const viewBtn = document.createElement('button');
        viewBtn.className = 'btn btn-xs btn-secondary';
        viewBtn.textContent = 'View';
        viewBtn.onclick = () => {
          window.location.href = `{{ route('tanks.tank_detail') }}?tank_id=${encodeURIComponent(t.id)}`;
        };

        const editBtn = document.createElement('button');
        editBtn.className = 'btn btn-xs btn-secondary';
        editBtn.textContent = 'Edit';
        editBtn.onclick = () => {
          window.location.href = `{{ route('tanks.tank_form') }}?tank_id=${encodeURIComponent(t.id)}`;
        };

        const addPlantBtn = document.createElement('button');
        addPlantBtn.className = 'btn btn-xs btn-secondary';
        addPlantBtn.textContent = 'Add plant';
        addPlantBtn.onclick = () => {
          window.location.href = `{{ route('tanks.add_plant_to_tank') }}?tank_id=${encodeURIComponent(t.id)}`;
        };

        const delBtn = document.createElement('button');
        delBtn.className = 'btn btn-xs btn-secondary';
        delBtn.textContent = 'Delete';
        delBtn.onclick = async () => {
          const ok = confirm(`Delete tank "${t.name}"?`);
          if (!ok) return;

          delBtn.disabled = true;
          try {
            const res = await fetch(`${API_TANKS}/${t.id}`, {
              method: 'DELETE',
              headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
              },
              credentials: 'same-origin',
            });

            if (res.status === 401) {
              window.location.href = '/login';
              return;
            }

            const json = await res.json().catch(() => null);

            if (!res.ok || !json || json.success !== true) {
              alert(json?.message || 'Delete failed.');
              return;
            }

            await loadTanks(); // reload list
          } catch (e) {
            alert('Network error.');
          } finally {
            delBtn.disabled = false;
          }
        };

        wrap.appendChild(viewBtn);
        wrap.appendChild(editBtn);
        wrap.appendChild(addPlantBtn);
        wrap.appendChild(delBtn);

        actionsTd.appendChild(wrap);
        tr.appendChild(actionsTd);

        tankTbody.appendChild(tr);
      });
    }

    let currentTanks = [];

    async function loadTanks() {
      // show loading
      clearRows();
      const tr = document.createElement('tr');
      const td = document.createElement('td');
      td.colSpan = 6;
      td.style.color = '#6b7280';
      td.textContent = 'Loading tanks...';
      tr.appendChild(td);
      tankTbody.appendChild(tr);

      try {
        const res = await fetch(API_TANKS, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });

        if (res.status === 401) {
          window.location.href = '/login';
          return;
        }

        const json = await res.json();

        if (!res.ok || json?.success !== true) {
          alert(json?.message || 'Failed to load tanks.');
          renderEmpty();
          return;
        }

        currentTanks = Array.isArray(json.data) ? json.data : [];
        renderRows(currentTanks);
        applySearchFilter();
      } catch (e) {
        alert('Network error.');
        renderEmpty();
      }
    }

    function applySearchFilter() {
      const term = (tankSearch?.value || '').toLowerCase().trim();
      if (!term) {
        renderRows(currentTanks);
        return;
      }
      const filtered = currentTanks.filter(t => (t.name || '').toLowerCase().includes(term));
      renderRows(filtered);
    }

    if (tankSearch) {
      tankSearch.addEventListener('input', applySearchFilter);
    }

    document.addEventListener('DOMContentLoaded', loadTanks);
  </script>
@endsection
