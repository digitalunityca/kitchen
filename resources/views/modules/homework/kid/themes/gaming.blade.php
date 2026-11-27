{{-- Gaming and building-bricks theme (original drawing, realistic style): a desk in front of
     a glowing screen, a shaded game controller with ○ △ ✕ □ style buttons and 3D studded
     bricks. No brand logo, wordmark or figure likeness. --}}
<svg class="kz-theme-art" viewBox="0 0 1200 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="gaming-wall" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2E2A4F"/><stop offset="1" stop-color="#4B4473"/></linearGradient>
    <linearGradient id="gaming-desk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#C79A6B"/><stop offset="1" stop-color="#9A6E45"/></linearGradient>
    <linearGradient id="gaming-screen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#5BC0EB"/><stop offset=".5" stop-color="#9D7BEA"/><stop offset="1" stop-color="#F27EA9"/></linearGradient>
    <radialGradient id="gaming-glow" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#A68BF0" stop-opacity=".55"/><stop offset="1" stop-color="#A68BF0" stop-opacity="0"/></radialGradient>
    <linearGradient id="gaming-pad" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#F7F7FA"/><stop offset=".6" stop-color="#DCDDE6"/><stop offset="1" stop-color="#B3B5C4"/></linearGradient>
    <linearGradient id="gaming-pad-dark" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3A3B47"/><stop offset="1" stop-color="#1E1F27"/></linearGradient>
    <radialGradient id="gaming-stick" cx=".4" cy=".35" r=".7"><stop offset="0" stop-color="#5A5C6B"/><stop offset="1" stop-color="#1B1C23"/></radialGradient>
    <radialGradient id="gaming-shadow" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#3B2614" stop-opacity=".55"/><stop offset="1" stop-color="#3B2614" stop-opacity="0"/></radialGradient>
    <radialGradient id="gaming-bokeh" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#FFFFFF" stop-opacity=".7"/><stop offset="1" stop-color="#FFFFFF" stop-opacity="0"/></radialGradient>
    <filter id="gaming-far" x="-10%" y="-10%" width="120%" height="120%"><feGaussianBlur stdDeviation="6"/></filter>
    <filter id="gaming-soft" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="12"/></filter>
    {{-- One brick: top, front and side faces with studs; fill colours come from the CSS variables set on each use. --}}
    <symbol id="gaming-brick" viewBox="0 0 170 110" overflow="visible">
      <path d="M10 40L50 20H160L120 40z" fill="var(--top)"/>
      <path d="M10 40H120V100H10z" fill="var(--front)"/>
      <path d="M120 40L160 20V80L120 100z" fill="var(--side)"/>
      <g>
        <g transform="translate(36 32)"><ellipse cx="0" cy="6" rx="13" ry="6" fill="var(--side)"/><rect x="-13" y="-6" width="26" height="12" fill="var(--front)"/><ellipse cx="0" cy="-6" rx="13" ry="6" fill="var(--top)"/><ellipse cx="-3" cy="-7" rx="5" ry="2" fill="#FFFFFF" opacity=".45"/></g>
        <g transform="translate(80 32)"><ellipse cx="0" cy="6" rx="13" ry="6" fill="var(--side)"/><rect x="-13" y="-6" width="26" height="12" fill="var(--front)"/><ellipse cx="0" cy="-6" rx="13" ry="6" fill="var(--top)"/><ellipse cx="-3" cy="-7" rx="5" ry="2" fill="#FFFFFF" opacity=".45"/></g>
        <g transform="translate(64 22)"><ellipse cx="0" cy="6" rx="13" ry="6" fill="var(--side)"/><rect x="-13" y="-6" width="26" height="12" fill="var(--front)"/><ellipse cx="0" cy="-6" rx="13" ry="6" fill="var(--top)"/><ellipse cx="-3" cy="-7" rx="5" ry="2" fill="#FFFFFF" opacity=".45"/></g>
        <g transform="translate(108 22)"><ellipse cx="0" cy="6" rx="13" ry="6" fill="var(--side)"/><rect x="-13" y="-6" width="26" height="12" fill="var(--front)"/><ellipse cx="0" cy="-6" rx="13" ry="6" fill="var(--top)"/><ellipse cx="-3" cy="-7" rx="5" ry="2" fill="#FFFFFF" opacity=".45"/></g>
      </g>
      <path d="M10 40H120" stroke="#FFFFFF" stroke-width="2" opacity=".35"/>
    </symbol>
  </defs>

  {{-- Room and the glowing screen, out of focus --}}
  <rect width="1200" height="800" fill="url(#gaming-wall)"/>
  <g filter="url(#gaming-far)">
    <rect x="300" y="60" width="600" height="340" rx="18" fill="#14131F"/>
    <rect x="318" y="76" width="564" height="306" rx="8" fill="url(#gaming-screen)"/>
    <g opacity=".55"><circle cx="460" cy="230" r="60" fill="#FFFFFF" opacity=".35"/><path d="M560 300l70-110 70 110z" fill="#FFFFFF" opacity=".3"/><rect x="720" y="180" width="90" height="90" rx="10" fill="#FFFFFF" opacity=".25"/></g>
  </g>
  <ellipse cx="600" cy="240" rx="560" ry="300" fill="url(#gaming-glow)"/>
  <g filter="url(#gaming-soft)">
    <circle cx="140" cy="130" r="40" fill="url(#gaming-bokeh)"/><circle cx="1070" cy="170" r="54" fill="url(#gaming-bokeh)"/>
    <circle cx="980" cy="70" r="26" fill="url(#gaming-bokeh)"/><circle cx="230" cy="320" r="30" fill="url(#gaming-bokeh)"/>
  </g>

  {{-- The desk --}}
  <path d="M-50 470H1250V800H-50z" fill="url(#gaming-desk)"/>
  <g stroke="#7E5534" stroke-width="2" opacity=".25"><path d="M-50 520Q600 505 1250 530M-50 600Q600 585 1250 615M-50 690Q600 670 1250 705"/></g>
  <path d="M-50 470H1250" stroke="#E7C49C" stroke-width="4" opacity=".7"/>

  {{-- Bricks: a little stack on the left, loose ones on the right --}}
  <ellipse cx="200" cy="700" rx="190" ry="30" fill="url(#gaming-shadow)" filter="url(#gaming-soft)"/>
  <use href="#gaming-brick" x="60" y="590" width="255" height="165" style="--top:#FF6B6B;--front:#E04848;--side:#B83434"/>
  <use href="#gaming-brick" x="90" y="520" width="255" height="165" style="--top:#FFD43B;--front:#F2B705;--side:#C99604"/>
  <use href="#gaming-brick" x="70" y="452" width="200" height="130" style="--top:#4DABF7;--front:#2F8DDB;--side:#226DAD"/>
  <ellipse cx="1010" cy="735" rx="170" ry="24" fill="url(#gaming-shadow)" filter="url(#gaming-soft)"/>
  <use href="#gaming-brick" x="900" y="640" width="220" height="142" style="--top:#69DB7C;--front:#40C057;--side:#2F9E44"/>
  <use href="#gaming-brick" x="1060" y="600" width="150" height="97" style="--top:#B197FC;--front:#9775FA;--side:#7950F2" transform="rotate(-10 1135 650)"/>

  {{-- The controller, sharp, in the middle --}}
  <ellipse cx="610" cy="728" rx="260" ry="34" fill="url(#gaming-shadow)" filter="url(#gaming-soft)"/>
  <g transform="translate(610 610) rotate(-6)">
    <path d="M-150-70h300c58 0 92 52 104 112 12 66-6 108-48 108-36 0-56-30-80-62H-126c-24 32-44 62-80 62-42 0-60-42-48-108 12-60 46-112 104-112z" fill="url(#gaming-pad)"/>
    <path d="M-150-70h300c40 0 68 26 86 62H-236c18-36 46-62 86-62z" fill="#FFFFFF" opacity=".55"/>
    <path d="M-104-60h208c10 0 16 8 16 18v34h-240v-34c0-10 6-18 16-18z" fill="url(#gaming-pad-dark)" opacity=".9"/>
    <g transform="translate(-160 0)">
      <rect x="-14" y="-44" width="28" height="88" rx="6" fill="#2A2B35"/><rect x="-44" y="-14" width="88" height="28" rx="6" fill="#2A2B35"/>
      <rect x="-12" y="-42" width="24" height="40" rx="5" fill="#3D3E4B"/>
    </g>
    <g transform="translate(160 0)">
      <circle cx="0" cy="-42" r="20" fill="#2A2B35"/><path d="M-10-36h20l-10-17z" fill="none" stroke="#5FD3A0" stroke-width="4" stroke-linejoin="round"/>
      <circle cx="42" cy="0" r="20" fill="#2A2B35"/><circle cx="42" cy="0" r="9" fill="none" stroke="#F27E7E" stroke-width="4"/>
      <circle cx="0" cy="42" r="20" fill="#2A2B35"/><path d="M-8 34l16 16M8 34L-8 50" stroke="#7FB7F5" stroke-width="4" stroke-linecap="round"/>
      <circle cx="-42" cy="0" r="20" fill="#2A2B35"/><rect x="-50" y="-8" width="16" height="16" fill="none" stroke="#E4A1F0" stroke-width="4"/>
    </g>
    <circle cx="-76" cy="70" r="40" fill="#2A2B35"/><circle cx="-76" cy="70" r="30" fill="url(#gaming-stick)"/><ellipse cx="-86" cy="60" rx="10" ry="6" fill="#FFFFFF" opacity=".3"/>
    <circle cx="76" cy="70" r="40" fill="#2A2B35"/><circle cx="76" cy="70" r="30" fill="url(#gaming-stick)"/><ellipse cx="66" cy="60" rx="10" ry="6" fill="#FFFFFF" opacity=".3"/>
    <rect x="-40" y="10" width="24" height="10" rx="5" fill="#3D3E4B"/><rect x="16" y="10" width="24" height="10" rx="5" fill="#3D3E4B"/>
    <circle cx="0" cy="42" r="12" fill="#3D3E4B"/>
  </g>
</svg>
