<?php
/**
 * Camera dome das telas de login (mapos/login.php e conecte/login.php).
 * .fov (cone de visao) e .cabeca (lente) sao animados por assets/css/login-cftv.css
 * e seguem o ponteiro via assets/js/login-cftv.js.
 */
?>
<svg class="cftv-domo" viewBox="0 0 200 200" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="cftvCone" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#22d3ee" stop-opacity=".4"/>
            <stop offset="1" stop-color="#22d3ee" stop-opacity="0"/>
        </linearGradient>
        <linearGradient id="cftvCorpo" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#f1f5f9"/>
            <stop offset="1" stop-color="#94a3b8"/>
        </linearGradient>
        <radialGradient id="cftvVidro" cx=".4" cy=".3" r=".9">
            <stop offset="0" stop-color="#334155" stop-opacity=".9"/>
            <stop offset="1" stop-color="#020617" stop-opacity=".95"/>
        </radialGradient>
        <radialGradient id="cftvLente" cx=".35" cy=".35" r=".8">
            <stop offset="0" stop-color="#38bdf8"/>
            <stop offset=".45" stop-color="#0c4a6e"/>
            <stop offset="1" stop-color="#020617"/>
        </radialGradient>
    </defs>
    <g class="fov"><path d="M100 104 L44 230 L156 230 Z" fill="url(#cftvCone)"/></g>
    <rect x="58" y="22" width="84" height="12" rx="4" fill="url(#cftvCorpo)"/>
    <rect x="90" y="34" width="20" height="18" fill="#cbd5e1"/>
    <rect x="44" y="50" width="112" height="14" rx="5" fill="url(#cftvCorpo)"/>
    <path d="M50 64 H150 A50 50 0 0 1 50 64 Z" fill="url(#cftvVidro)" stroke="#cbd5e1" stroke-width="2"/>
    <g class="cabeca">
        <circle cx="100" cy="92" r="19" fill="#0f172a" stroke="#475569" stroke-width="2"/>
        <circle cx="100" cy="92" r="13" fill="url(#cftvLente)"/>
        <circle cx="100" cy="92" r="5" fill="#020617"/>
        <ellipse cx="94" cy="86" rx="4" ry="2.5" fill="#fff" opacity=".7"/>
    </g>
    <path d="M58 70 Q66 98 92 108" fill="none" stroke="#fff" stroke-opacity=".18" stroke-width="3" stroke-linecap="round"/>
    <circle class="led" cx="140" cy="57" r="3"/>
</svg>
