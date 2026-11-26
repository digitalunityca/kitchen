{{-- News: messages from the kid's teachers (emails, coming later). The list is ready for them. --}}
<div class="kz kz-news" data-screen="news">
  @include('modules.homework.kid._head', ['title' => 'News'])
  <ul class="kz-list kz-news-list" data-news></ul>
  <div class="kz-empty">
    <span class="kz-empty-icon">@include('modules.homework.kid._icon', ['name' => 'news', 'size' => 44])</span>
    <b>No messages yet</b>
    <p>Messages from your teachers will show here.</p>
  </div>
</div>
