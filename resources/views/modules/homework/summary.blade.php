<section class="hw-summary" data-homework-summary aria-labelledby="hw-summary-heading">
  <div class="hw-list-head">
    <h3 id="hw-summary-heading" class="hw-h" tabindex="-1">Homework summary</h3>
    @if ($tasks !== [])
      <span class="hw-summary-count">{{ count($tasks) }} {{ count($tasks) === 1 ? 'worksheet' : 'worksheets' }}</span>
    @endif
  </div>

  @if ($tasks !== [])
    <ul class="hw-list">
      @foreach (collect($tasks)->countBy('template') as $topic => $count)
        <li class="hw-task">
          <span class="hw-text"><b>{{ \App\Modules\Homework\HomeworkModule::TEMPLATES[$topic]['name'] }}</b></span>
          <span class="hw-summary-count">× {{ $count }}</span>
        </li>
      @endforeach
    </ul>

    <form method="post" class="hw-summary-print" data-mac-print
      action="{{ route('modules.homework.packet.print', [$kid['id'], $date, '00000000-0000-0000-0000-000000000000'], absolute: false) }}"
      @if ($needsGeneration) data-generate="{{ route('modules.homework.packet.generate', [$kid['id'], $date], absolute: false) }}" @endif>
      @csrf
      <button type="submit" class="hw-btn" @disabled($needsGeneration)>Print all</button>
      <p role="status" class="hw-summary-status" data-print-status>@if ($needsGeneration) Generating worksheets… @endif</p>
    </form>
  @else
    <p class="hw-summary-status">There is no homework to print.</p>
  @endif
</section>
