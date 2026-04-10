{{-- resources/views/admin/dashboard_admin.blade.php --}}
@extends('layouts.admin')

@section('title', 'Admin Dashboard – Aquatic Plant Advisor')
@section('sidebar_dashboard_active', 'active')

@section('content')
  <div class="page-header">
    <div>
      <h1 class="page-title">Admin dashboard</h1>
      <p class="page-subtitle">
        Overview of users, tanks and community activity across the system.
      </p>
    </div>
  </div>

  <div class="page-content">
    <!-- Hàng card số liệu -->
    <section class="grid-4">
      <article class="card">
        <div class="card-title">Total users</div>
        <div class="card-metric">{{ number_format($stats['total_users'] ?? 0) }}</div>
        <p class="card-subtitle">
          including {{ number_format($stats['admin_count'] ?? 0) }} admins &amp; {{ number_format($stats['expert_count'] ?? 0) }} experts
        </p>
      </article>

      <article class="card">
        <div class="card-title">Active tanks</div>
        <div class="card-metric">{{ number_format($stats['active_tanks'] ?? 0) }}</div>
        <p class="card-subtitle">tanks with logs in the last 30 days</p>
      </article>

      <article class="card">
        <div class="card-title">Questions</div>
        <div class="card-metric">{{ number_format($stats['questions_total'] ?? 0) }}</div>
        <p class="card-subtitle">
          {{ number_format($stats['questions_open'] ?? 0) }} open, {{ number_format($stats['questions_resolved'] ?? 0) }} resolved
        </p>
      </article>

      <article class="card">
        <div class="card-title">Community posts</div>
        <div class="card-metric">{{ number_format($stats['posts_total'] ?? 0) }}</div>
        <p class="card-subtitle">
          {{ number_format($stats['posts_pending_or_flagged'] ?? 0) }} pending review / flagged
        </p>
      </article>
    </section>

    <!-- 2 cột: Q&A & Community cần review -->
    <section class="grid-2" style="margin-top:16px;">
      <!-- Q&A -->
      <article class="card">
        <div class="card-header card-header-inline">
          <div>
            <h2 class="card-title">Questions needing attention</h2>
            <p class="card-subtitle">Open questions or flagged for moderation.</p>
          </div>
          <div>
            <a href="{{ url('/admin/qa') }}" class="btn btn-secondary btn-xs">View all</a>
          </div>
        </div>

        <div class="table-wrapper">
          <table class="table">
            <thead>
            <tr>
              <th>Title</th>
              <th>Owner</th>
              <th>Status</th>
              <th>Answers</th>
            </tr>
            </thead>
            <tbody>
            @forelse($questionsNeedingAttention as $q)
              <tr>
                <td>
                  <a href="{{ url('/admin/qa/' . $q->id) }}" class="question-title-link">
                    {{ $q->title }}
                  </a>
                </td>
                <td>{{ $q->user?->name ?? 'Unknown' }}</td>
                <td>
                  @if($q->status === 'open')
                    <span class="badge badge-open">Open</span>
                  @elseif($q->status === 'resolved')
                    <span class="badge badge-resolved">Resolved</span>
                  @else
                    <span class="badge badge-soft">{{ $q->status }}</span>
                  @endif
                </td>
                <td>{{ number_format($q->answers_count ?? 0) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center" style="padding:14px;">
                  No questions found.
                </td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </article>

      <!-- Community -->
      <article class="card">
        <div class="card-header card-header-inline">
          <div>
            <h2 class="card-title">Posts / comments flagged</h2>
            <p class="card-subtitle">Content reported by users for review.</p>
          </div>
          <div>
            <a href="{{ url('/admin/community-posts') }}" class="btn btn-secondary btn-xs">View all</a>
          </div>
        </div>

        <div class="table-wrapper">
          <table class="table">
            <thead>
            <tr>
              <th>Post</th>
              <th>Author</th>
              <th>Reason</th>
              <th>Reports</th>
            </tr>
            </thead>
            <tbody>
            @forelse($postsFlagged as $p)
              <tr>
                <td>
                  <a href="{{ url('/admin/community-posts/' . $p->id) }}" class="question-title-link">
                    {{ $p->title }}
                  </a>
                </td>
                <td>{{ $p->user?->name ?? 'Unknown' }}</td>
                <td>
                  @if($p->status === 'pending')
                    Pending review
                  @elseif($p->status === 'rejected')
                    Rejected
                  @else
                    {{ $p->status }}
                  @endif
                </td>
                <td>0</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center" style="padding:14px;">
                  No flagged / pending posts.
                </td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </article>
    </section>

  </div>
@endsection
