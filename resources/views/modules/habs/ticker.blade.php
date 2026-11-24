{{-- The Canadiens strip: one or two items, repeated so the marquee loops seamlessly. Empty when there is no game in range. --}}
@if ($items !== [])
@php($segment = function () use ($items) {
    ob_start();
    foreach ($items as $item) {
        echo view('modules.habs.ticker-item', ['item' => $item])->render();
    }
    return ob_get_clean();
})
@php($html = $segment())
<button type="button" class="habs-strip habs-state-{{ $items[0]['kind'] }}" id="habs-strip" aria-label="Canadiens: open scores and stats">
  <span class="habs-track">
    <span class="habs-run">{!! $html !!}</span>
    <span class="habs-run" aria-hidden="true">{!! $html !!}</span>
  </span>
</button>
@endif
