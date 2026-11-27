{{-- Soccer theme (original drawing, realistic style): an evening stadium out of focus,
     a mown pitch in perspective, a goal with its net and a shaded ball on the grass.
     No club, brand or player likeness. --}}
<svg class="kz-theme-art" viewBox="0 0 1200 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="soccer-sky" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#7FB2E5"/><stop offset=".55" stop-color="#B9D7F2"/><stop offset="1" stop-color="#E6F0F8"/>
    </linearGradient>
    <linearGradient id="soccer-stand" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#5D6B7D"/><stop offset="1" stop-color="#3F4B5A"/>
    </linearGradient>
    <linearGradient id="soccer-grass" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#5E9E52"/><stop offset="1" stop-color="#3F7F3A"/>
    </linearGradient>
    <radialGradient id="soccer-light" cx=".5" cy=".5" r=".5">
      <stop offset="0" stop-color="#FFFBE6"/><stop offset=".35" stop-color="#FFF4C2" stop-opacity=".8"/><stop offset="1" stop-color="#FFF4C2" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="soccer-ball" cx=".38" cy=".32" r=".75">
      <stop offset="0" stop-color="#FFFFFF"/><stop offset=".6" stop-color="#E9ECEF"/><stop offset="1" stop-color="#9AA3AD"/>
    </radialGradient>
    <radialGradient id="soccer-shadow" cx=".5" cy=".5" r=".5">
      <stop offset="0" stop-color="#1E3A1C" stop-opacity=".55"/><stop offset="1" stop-color="#1E3A1C" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="soccer-vignette" cx=".5" cy=".55" r=".75">
      <stop offset=".6" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#0B1A10" stop-opacity=".35"/>
    </radialGradient>
    <linearGradient id="soccer-post" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="#C9CFD6"/><stop offset=".45" stop-color="#FFFFFF"/><stop offset="1" stop-color="#AEB6BF"/>
    </linearGradient>
    <filter id="soccer-far" x="-5%" y="-5%" width="110%" height="110%"><feGaussianBlur stdDeviation="5"/></filter>
    <filter id="soccer-mid" x="-5%" y="-5%" width="110%" height="110%"><feGaussianBlur stdDeviation="1.6"/></filter>
    <filter id="soccer-soft" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="10"/></filter>
    <clipPath id="soccer-pitch"><path d="M-200 380H1400L1700 800H-500z"/></clipPath>
  </defs>

  {{-- Sky and the stadium far away, out of focus --}}
  <rect width="1200" height="800" fill="url(#soccer-sky)"/>
  <g filter="url(#soccer-far)">
    <path d="M-20 250Q600 170 1220 250V400H-20z" fill="url(#soccer-stand)"/>
    <path d="M-20 238Q600 158 1220 238l0 18Q600 176-20 256z" fill="#2F3946"/>
    <g fill="#C8D2DE" opacity=".55">
      <path d="M0 300Q600 230 1200 300v12Q600 242 0 312z"/>
      <path d="M0 336Q600 270 1200 336v10Q600 280 0 346z"/>
    </g>
    <g fill="#E8B04A" opacity=".5"><circle cx="120" cy="320" r="5"/><circle cx="260" cy="300" r="4"/><circle cx="420" cy="290" r="5"/><circle cx="700" cy="292" r="4"/><circle cx="880" cy="302" r="5"/><circle cx="1050" cy="318" r="4"/></g>
    <g fill="#D9534F" opacity=".45"><circle cx="190" cy="342" r="4"/><circle cx="560" cy="318" r="5"/><circle cx="980" cy="338" r="4"/></g>
    <path d="M150 260V60M1050 260V60" stroke="#46525F" stroke-width="10"/>
    <rect x="110" y="40" width="80" height="34" rx="4" fill="#59656F"/>
    <rect x="1010" y="40" width="80" height="34" rx="4" fill="#59656F"/>
  </g>
  <circle cx="150" cy="56" r="120" fill="url(#soccer-light)"/>
  <circle cx="1050" cy="56" r="120" fill="url(#soccer-light)"/>

  {{-- The pitch in perspective, with mowing stripes --}}
  <path d="M-200 380H1400L1700 800H-500z" fill="url(#soccer-grass)"/>
  <g clip-path="url(#soccer-pitch)" opacity=".22" fill="#BDE3A8">
    <path d="M-60 380h120L-80 800h-300z"/><path d="M180 380h120L220 800H-60z"/><path d="M420 380h120l40 420H340z"/>
    <path d="M660 380h120l240 420H760z"/><path d="M900 380h120l420 420h-280z"/><path d="M1140 380h120l600 420h-280z"/>
  </g>
  <g filter="url(#soccer-mid)" fill="none" stroke="#F4F8F1" stroke-width="5" opacity=".85">
    <path d="M-120 420H1320"/>
    <path d="M600 420V800" stroke-width="6"/>
    <ellipse cx="600" cy="560" rx="230" ry="62"/>
    <ellipse cx="600" cy="560" rx="6" ry="3" fill="#F4F8F1"/>
  </g>

  {{-- The goal on the left, with its net --}}
  <g filter="url(#soccer-mid)">
    <path d="M40 300L60 452 330 470 300 300z" fill="#FFFFFF" opacity=".12"/>
    <g stroke="#FFFFFF" stroke-width="1.6" opacity=".55">
      <path d="M50 312L64 456M90 310L102 458M130 309L141 461M170 308L180 463M210 306L219 465M250 305L258 467M290 304L296 469"/>
      <path d="M44 330L302 322M48 360L306 354M52 390L312 386M56 420L318 418M59 446L324 448"/>
    </g>
    <path d="M300 300V470" stroke="url(#soccer-post)" stroke-width="12"/>
    <path d="M24 290L300 300" stroke="url(#soccer-post)" stroke-width="12" stroke-linecap="round"/>
    <path d="M30 290L42 452" stroke="url(#soccer-post)" stroke-width="10"/>
    <ellipse cx="190" cy="478" rx="170" ry="10" fill="url(#soccer-shadow)" opacity=".6"/>
  </g>

  {{-- The ball in front, sharp, with its shadow on the grass --}}
  <ellipse cx="1065" cy="735" rx="105" ry="20" fill="url(#soccer-shadow)" filter="url(#soccer-soft)"/>
  <g transform="translate(1050 655) scale(.85)">
    <circle r="96" fill="url(#soccer-ball)"/>
    <g fill="#2B3138">
      <path d="M0-30l29 21-11 34h-36l-11-34z"/>
      <path d="M-58-78l26 6 4 26-24 14-22-16c4-12 9-22 16-30z"/>
      <path d="M58-78c7 8 12 18 16 30l-22 16-24-14 4-26z"/>
      <path d="M-92 18c-4-10-5-22-4-34l24-6 16 22-12 26z"/>
      <path d="M92 18l-24 8-12-26 16-22 24 6c1 12 0 24-4 34z"/>
      <path d="M-36 86l6-26 30-4 30 4 6 26c-12 6-24 9-36 9s-24-3-36-9z" opacity=".9"/>
    </g>
    <g fill="none" stroke="#7D8792" stroke-width="2.2" opacity=".75">
      <path d="M0-30V-70M29-9l38-14M18 25l26 32M-18 25l-26 32M-29-9l-38-14"/>
    </g>
    <circle r="96" fill="none" stroke="#6B7580" stroke-width="2" opacity=".5"/>
    <ellipse cx="-32" cy="-44" rx="34" ry="20" fill="#FFFFFF" opacity=".55" transform="rotate(-28 -32 -44)"/>
  </g>

  <rect width="1200" height="800" fill="url(#soccer-vignette)"/>
</svg>
