{{-- resources/views/plants/show.blade.php --}}
@extends('layouts.site')

@section('title', $plant->name)
@section('nav_plants_active', 'active')

@section('content')
<section class="section">
  <div class="plant-detail-top">
    <div>
      <h1 class="section-title">{{ $plant->name }}</h1>
      <p class="section-subtitle">
        Plant details and care parameters from the library.
      </p>
    </div>

    <div class="plant-detail-actions">
      <a class="btn btn-secondary" href="{{ $backUrl ?? route('plants.index') }}">Back to library</a>
      <a class="btn btn-outline" href="{{ route('image.identify_plant') }}">Identify from photo</a>
    </div>
  </div>

  <div class="plant-detail-layout">
    {{-- LEFT: images --}}
    <section class="card">
      <h2 class="card-title">Images</h2>
      <p class="card-subtitle">Gallery of this plant.</p>

      @php
        $images = collect([]);

        if ($plant->image_sample) {
          $images->push($plant->image_sample);
        }

        if ($plant->images && $plant->images->count()) {
          foreach ($plant->images as $img) {
            if (!empty($img->image_path)) $images->push($img->image_path);
          }
        }

        $images = $images->unique()->values();
      @endphp

      @if($images->count())
        <div class="plant-gallery">
          @foreach($images as $path)
            <div class="plant-gallery-item">
              <img src="{{ asset($path) }}" alt="{{ $plant->name }}">
            </div>
          @endforeach
        </div>
      @else
        <div class="card-soft">No images available.</div>
      @endif
    </section>

    {{-- RIGHT: info --}}
    <section class="card">
      <h2 class="card-title">Overview</h2>
      <p class="card-subtitle">
        Light, difficulty, pH and temperature ranges.
      </p>

      <div class="plant-info-grid">
        <div class="plant-info-row">
          <div class="plant-info-label">Difficulty</div>
          <div class="plant-info-value">{{ $plant->difficulty ?? '-' }}</div>
        </div>

        <div class="plant-info-row">
          <div class="plant-info-label">Light level</div>
          <div class="plant-info-value">{{ $plant->light_level ?? '-' }}</div>
        </div>

        <div class="plant-info-row">
          <div class="plant-info-label">pH range</div>
          <div class="plant-info-value">
            {{ $plant->ph_min ?? '-' }} — {{ $plant->ph_max ?? '-' }}
          </div>
        </div>

        <div class="plant-info-row">
          <div class="plant-info-label">Temperature range (°C)</div>
          <div class="plant-info-value">
            {{ $plant->temp_min ?? '-' }} — {{ $plant->temp_max ?? '-' }}
          </div>
        </div>
      </div>

      @if($plant->description)
        <div class="plant-block">
          <h3 class="plant-block-title">Description</h3>
          <div class="plant-block-text">{{ $plant->description }}</div>
        </div>
      @endif

      @if($plant->care_guide)
        <div class="plant-block">
          <h3 class="plant-block-title">Care guide</h3>
          <div class="plant-block-text">{{ $plant->care_guide }}</div>
        </div>
      @endif
    </section>
  </div>
</section>
@endsection
