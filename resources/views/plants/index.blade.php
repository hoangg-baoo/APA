{{-- resources/views/plants/index.blade.php --}}
@extends('layouts.site')

@section('title', 'Plant Library')
@section('nav_plants_active', 'active')

@section('content')
<section class="section">
  <div class="plants-header">
    <div>
      <h1 class="section-title">Plant Library</h1>
      <p class="section-subtitle">
        Browse the master plant library. Click a plant to view full care details.
      </p>
    </div>

    <form method="GET" action="{{ route('plants.index') }}" class="plants-search">
      @if(!empty($group))
        <input type="hidden" name="group" value="{{ $group }}">
      @endif

      <input
        type="text"
        name="q"
        value="{{ $q }}"
        class="form-control"
        placeholder="Search plant name..."
      >
      <button class="btn btn-primary" type="submit">Search</button>

      @if(($q ?? '') !== '' || !empty($group))
        <a class="btn btn-secondary" href="{{ route('plants.index') }}">Clear</a>
      @endif
    </form>
  </div>

  <div class="plants-grid">
    @forelse($plants as $p)
      @php
        $thumb = $p->image_sample ?: optional($p->images->first())->image_path;
        $thumbUrl = $thumb ? asset($thumb) : '';
      @endphp

      <a class="plant-card" href="{{ route('plants.show', $p->id) }}">
        <div class="plant-card-img">
          @if($thumbUrl)
            <img src="{{ $thumbUrl }}" alt="{{ $p->name }}">
          @else
            <div class="plant-card-img-empty">No image</div>
          @endif
        </div>

        <div class="plant-card-body">
          <div class="plant-card-title">{{ $p->name }}</div>

          <div class="plant-card-meta">
            <span class="plant-pill">Light: {{ $p->light_level ?? '-' }}</span>
            <span class="plant-pill">Difficulty: {{ $p->difficulty ?? '-' }}</span>
          </div>

          <div class="plant-card-cta">
            <span class="btn btn-secondary btn-xs">View details</span>
          </div>
        </div>
      </a>
    @empty
      <div class="card-soft">No plants found.</div>
    @endforelse
  </div>

  <div class="plants-pagination">
    {{ $plants->onEachSide(1)->links('pagination.plants') }}
  </div>
</section>
@endsection
