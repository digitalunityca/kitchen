{{-- Inside the dashboard's Settings sheet (an iframe), "back to the dashboard" closes the sheet instead of loading the dashboard in it. --}}
<script>
  (function () {
    if (window.top === window) return;
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[href]');
      if (a && a.getAttribute('href').replace(location.origin, '') === '/') { e.preventDefault(); window.top.postMessage('settings:close', location.origin); }
    });
  })();
</script>
