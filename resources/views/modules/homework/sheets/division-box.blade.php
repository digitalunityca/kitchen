{{-- One division in square cells, sized from BuildDivisionSheet::layout():
     a facts line (56 ÷ 8 = ▢▢) or the Québec crochet layout (dividend left, divisor
     right behind the bracket, quotient under the line, 2 rows per quotient digit).
     Shared by the PDF (dompdf) and the on-screen preview. --}}
@php
    $cell = $layout['cell'];
    $font = round($cell * .62);
    $td = 'width: '.$cell.'pt; height: '.$cell.'pt; padding: 0; text-align: center; vertical-align: middle; font-family: DejaVu Sans Mono, ui-monospace, monospace; font-size: '.$font.'pt; line-height: 1;';
    $answer = ($answers ?? false) ? (string) $p['answer'] : '';
@endphp
@if ($layout['kind'] === 'fact')
  {{-- The numbers stay whole (merged cells); one answer box per quotient digit. --}}
  @php
    $boxes = str_split(str_pad($answer, $layout['quotient'], ' ', STR_PAD_LEFT));
  @endphp
  <table role="presentation" style="border-collapse: collapse; margin: 0 auto">
    <tr>
      <td colspan="{{ $layout['dividend'] }}" style="{{ $td }} width: {{ round($cell * $layout['dividend'], 2) }}pt; text-align: right; padding-right: 3pt">{{ $p['top'] }}</td>
      <td style="{{ $td }}">÷</td>
      <td colspan="{{ $layout['divisor'] }}" style="{{ $td }} width: {{ round($cell * $layout['divisor'], 2) }}pt; text-align: left; padding-left: 3pt">{{ $p['bottom'] }}</td>
      <td style="{{ $td }}">=</td>
      @foreach ($boxes as $c)<td style="{{ $td }} border: .6pt solid #9a9a9a">{{ trim($c) }}</td>@endforeach
    </tr>
  </table>
@else
  @php
    $left = $layout['left'];
    $right = $layout['right'];
    $dividend = (string) $p['top'].(($p['decimals'] ?? 0) > 0 ? ','.str_repeat('0', $p['decimals']) : '');
    $top = array_merge(array_fill(0, $left - strlen($dividend), ''), str_split($dividend));
    $divisor = array_merge(str_split((string) $p['bottom']), array_fill(0, $right - strlen((string) $p['bottom']), ''));
    $quotient = array_merge(mb_str_split($answer === '' ? '' : (string) preg_replace('/ reste .*/', '', $answer)), array_fill(0, $right, ''));
  @endphp
  <table role="presentation" style="border-collapse: collapse; margin: 0 auto">
    @for ($r = 0; $r < $layout['height']; $r++)
      <tr>
        @for ($c = 0; $c < $left; $c++)
          <td style="{{ $td }} border: .4pt solid #d0d0d0">{{ $r === 0 ? $top[$c] : '' }}</td>
        @endfor
        @for ($c = 0; $c < $right; $c++)
          <td style="{{ $td }} border: .4pt solid #d0d0d0; {{ $c === 0 ? 'border-left: 1.6pt solid #000;' : '' }} {{ $r === 0 ? 'border-bottom: 1.6pt solid #000;' : '' }}">{{ $r === 0 ? $divisor[$c] : ($r === 1 ? ($quotient[$c] ?? '') : '') }}</td>
        @endfor
      </tr>
    @endfor
  </table>
@endif
