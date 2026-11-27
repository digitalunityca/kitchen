{{-- Soccer theme (original drawing): sky, a pitch with its lines, a goal net, a ball and a
     generic sky-blue/white striped #10 jersey. No club, brand or player likeness. --}}
<svg class="kz-theme-art" viewBox="0 0 1200 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="soccer-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#DDEFFF"/><stop offset="1" stop-color="#F4FAFF"/></linearGradient>
    <linearGradient id="soccer-grass" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#BFE6C3"/><stop offset="1" stop-color="#A7DBAE"/></linearGradient>
    <pattern id="soccer-net" width="22" height="22" patternUnits="userSpaceOnUse"><path d="M0 0h22M0 0v22" stroke="#FFFFFF" stroke-width="2" opacity=".8"/></pattern>
    <pattern id="soccer-stripes" width="44" height="10" patternUnits="userSpaceOnUse"><rect width="22" height="10" fill="#9FD3F7"/><rect x="22" width="22" height="10" fill="#FFFFFF"/></pattern>
  </defs>
  <rect width="1200" height="800" fill="url(#soccer-sky)"/>
  <circle cx="1040" cy="120" r="60" fill="#FFF3C4"/>
  <path d="M120 150c20-26 70-26 90 0 26-6 46 14 40 34H90c-8-20 8-38 30-34z" fill="#FFFFFF"/>
  <path d="M760 90c18-22 60-22 78 0 22-5 40 12 34 29H732c-7-17 7-33 28-29z" fill="#FFFFFF"/>
  <rect y="430" width="1200" height="370" fill="url(#soccer-grass)"/>
  <path d="M0 520h1200M0 610h1200M0 700h1200" stroke="#B5E0BA" stroke-width="40" opacity=".55"/>
  <g fill="none" stroke="#FFFFFF" stroke-width="6" opacity=".9">
    <path d="M600 430v370"/>
    <ellipse cx="600" cy="640" rx="190" ry="80"/>
    <path d="M0 470h170v260H0M1200 470h-170v260h170"/>
  </g>
  <g transform="translate(70 300)">
    <rect width="230" height="170" fill="url(#soccer-net)" stroke="#FFFFFF" stroke-width="10"/>
  </g>
  <g transform="translate(860 520)">
    <circle r="62" fill="#FFFFFF" stroke="#C9CED6" stroke-width="4"/>
    <path d="M0-22l21 15-8 25h-26l-8-25z" fill="#5B6573"/>
    <path d="M0-62v40M21-7l40-10M13 18l26 34M-13 18l-26 34M-21-7l-40-10" stroke="#C9CED6" stroke-width="4"/>
  </g>
  <g transform="translate(930 230) rotate(8)">
    <path d="M-80-60l40-30h80l40 30 40 40-40 40-20-15V110H-60V15l-20 15-40-40z" fill="url(#soccer-stripes)" stroke="#8CC6EE" stroke-width="5" stroke-linejoin="round"/>
    <text x="0" y="55" text-anchor="middle" font-family="system-ui, sans-serif" font-size="70" font-weight="700" fill="#4F8FC0">10</text>
  </g>
</svg>
