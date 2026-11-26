@php($qr = \App\Modules\Homework\WorksheetQrCode::make($kid['id'], $date, $task))
<a href="{{ $qr['link'] }}" aria-label="Ouvrir la confirmation de ce devoir">
  <img src="{{ $qr['image'] }}" alt="Code QR de confirmation" aria-label="Code QR de confirmation">
</a>
