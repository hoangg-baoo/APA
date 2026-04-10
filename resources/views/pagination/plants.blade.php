@if ($paginator->hasPages())
  <nav class="plants-pager" role="navigation" aria-label="Pagination">

    {{-- Prev --}}
    @if ($paginator->onFirstPage())
      <span class="plants-page disabled">Prev</span>
    @else
      <a class="plants-page" href="{{ $paginator->previousPageUrl() }}" rel="prev">Prev</a>
    @endif

    {{-- Pages --}}
    @foreach ($elements as $element)
      @if (is_string($element))
        <span class="plants-page dots">{{ $element }}</span>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <span class="plants-page active" aria-current="page">{{ $page }}</span>
          @else
            <a class="plants-page" href="{{ $url }}">{{ $page }}</a>
          @endif
        @endforeach
      @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
      <a class="plants-page" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
    @else
      <span class="plants-page disabled">Next</span>
    @endif

  </nav>
@endif
