{{-- resources/views/home.blade.php --}}
@extends('layouts.site')

@section('title', 'Aquatic Plant Advisor – Home')
@section('nav_home_active', 'active')

@section('content')
  <!-- HERO -->
  <section class="hero">
    <div class="hero-left">
      <p class="hero-kicker">Smart assistant for aquarium lovers</p>
      <h1 class="hero-title">
        Design your dream <span>aquascape</span> with real-time guidance
      </h1>
      <p class="hero-text">
        Track water parameters, check plant requirements, ask experts and
        identify unknown plants – all in a single web app designed for planted tanks.
      </p>

      <div class="hero-cta">
        <a href="{{ route('tanks.my_tanks') }}" class="btn-hero-primary">Create your tank</a>

        <a href="{{ route('image.identify_plant') }}" class="btn-hero-secondary">
          Try plant identification
        </a>
      </div>

      <p class="hero-note">
        No installation needed. Works in your browser and syncs across devices.
      </p>
    </div>

    <!-- HERO STATS CARD -->
    <div class="hero-right">
      <div class="hero-card">
        <div class="hero-card-header">
          <span class="card-dot"></span>
          <span class="card-header-text">Live tank health overview*</span>
        </div>

        <div class="hero-stats-grid">
          <div class="hero-stat">
            <div class="hero-stat-label">Active tanks</div>
            <div class="hero-stat-value">{{ number_format($activeTanks ?? 0) }}</div>
          </div>

          <div class="hero-stat">
            <div class="hero-stat-label">Plants in library</div>
            <div class="hero-stat-value">{{ number_format($plantsCount ?? 0) }}</div>
          </div>

          <div class="hero-stat">
            <div class="hero-stat-label">Water logs this week</div>
            <div class="hero-stat-value">{{ number_format($waterLogsWeek ?? 0) }}</div>
          </div>

          <div class="hero-stat">
            <div class="hero-stat-label">Community answers</div>
            <div class="hero-stat-value">{{ number_format($answersCount ?? 0) }}</div>
          </div>
        </div>

        <p class="hero-quote">
          “Since using Aquatic Plant Advisor, my tanks stay stable and I can fix issues
          before plants melt or algae explode.”
        </p>
        <p class="hero-footnote">*Numbers are loaded from database.</p>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section class="features">
    <h2 class="features-title">Everything you need for a healthy planted tank</h2>
    <p class="features-text">
      From community sharing to plant references and Q&amp;A – everything is connected around your tanks.
    </p>

    <div class="features-grid">

      {{-- CARD 1: COMMUNITY (bấm chuyển trang) --}}
      <a
        href="{{ route('community.posts_list') }}"
        class="feature-card"
        style="display:block; text-decoration:none; color:inherit;"
      >
        <h3>Community sharing</h3>
        <p>
          Explore aquascapes from other users, share your progress, and discuss ideas
          through posts and comments in the community.
        </p>
      </a>

      {{-- CARD 2: Plant library (bấm chuyển trang) --}}
      <a
        href="{{ route('plants.index') }}"
        class="feature-card"
        style="display:block; text-decoration:none; color:inherit;"
      >
        <h3>Plant library &amp; care guides</h3>
        <p>
          Browse curated plant profiles with light, CO₂ and water
          parameters, then attach them directly to your tanks.
        </p>
      </a>

      {{-- CARD 3: Q&A (bấm chuyển trang) --}}
      <a
        href="{{ route('qa.questions_list') }}"
        class="feature-card"
        style="display:block; text-decoration:none; color:inherit;"
      >
        <h3>Q&amp;A with experts</h3>
        <p>
          Ask questions about your tank, get answers from experienced aquascapers,
          and follow solutions with accepted/best answers.
        </p>
      </a>

    </div>
  </section>
@endsection
