{{-- One stacked addition as a borderless grid sized for handwriting: a carry row,
     one row per number (the + sign on the last one) and the answer row under the
     line, one column per character so the place values line up.
     Shared by the PDF (dompdf) and the on-screen preview. --}}
@php
    $cols = $layout['chars'];
    $terms = $p['terms'] ?? [$p['top'], $p['bottom']];
    $digitsRow = function (string $value) use ($cols): array {
        $chars = str_split($value);
        return array_merge(array_fill(0, max(0, $cols - count($chars)), ''), $chars);
    };
    $cellStyle = 'width: '.$layout['digit'].'pt; padding: 0; text-align: center; vertical-align: bottom; font-family: DejaVu Sans Mono, ui-monospace, monospace; font-size: '.$layout['font'].'pt; line-height: 1;';
@endphp
<table class="abox" role="presentation" style="border-collapse: collapse; margin: 0 auto">
  <tr>@for ($i = 0; $i < $cols; $i++)<td style="{{ $cellStyle }} height: {{ $layout['carry'] }}pt"></td>@endfor</tr>
  @foreach ($terms as $n => $term)
    @php($cells = $digitsRow((string) $term))
    @if ($n === count($terms) - 1) @php($cells[0] = '+') @endif
    <tr>@foreach ($cells as $c)<td style="{{ $cellStyle }} height: {{ $layout['row'] }}pt">{{ $c }}</td>@endforeach</tr>
  @endforeach
  <tr>@foreach (($answers ?? false) ? $digitsRow($p['answer']) : array_fill(0, $cols, '') as $c)<td style="{{ $cellStyle }} height: {{ $layout['row'] }}pt; border-top: 1.6pt solid #000; font-weight: bold">{{ $c }}</td>@endforeach</tr>
</table>
