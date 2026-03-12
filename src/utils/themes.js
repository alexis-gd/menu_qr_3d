// Source of truth for the app's visual themes.
// Used by Dashboard.vue (theme selector) and currentThemeData (QR card, accent color).
// MenuPublico.vue applies themes via :class="`tema-${theme}`" — no need to import this there.

export const THEMES = [
  { id: 'calido',  nombre: 'Cálido',  desc: 'Bistró, tacos, casero',  bg: '#fdf6f0', cardBg: '#fff',    text: '#3d2c1e', accent: '#d4691e', headerBg: 'linear-gradient(135deg,#b5451b,#e8841f)', headerText: '#fff'    },
  { id: 'oscuro',  nombre: 'Oscuro',  desc: 'Bar, premium, elegante', bg: '#1a1a2e', cardBg: '#1e1e2e', text: '#e8e8f0', accent: '#f0c040', headerBg: 'linear-gradient(135deg,#0a0a1a,#1a1a38)', headerText: '#f0c040' },
  { id: 'moderno', nombre: 'Moderno', desc: 'Saludable, minimalista', bg: '#f4f4f4', cardBg: '#fff',    text: '#111111', accent: '#1a7f5a', headerBg: '#fff',                                   headerText: '#111'    },
  { id: 'rapida',  nombre: 'Express', desc: 'Rápido, cafetería',      bg: '#fffbf0', cardBg: '#fff',    text: '#1a1a1a', accent: '#d43f2e', headerBg: 'linear-gradient(135deg,#c0392b,#e74c3c)', headerText: '#fff'    },
  { id: 'rosa',    nombre: 'Rosa',    desc: 'Romántico, suave',       bg: '#FFEFEF', cardBg: '#fff',    text: '#5a2030', accent: '#FF8276', headerBg: 'linear-gradient(135deg,#FF8276,#EA9087)', headerText: '#fff'    },
]

// Extra data used in the QR card only (not needed in the public menu)
export const THEMES_EXTRA = {
  calido:  { decoColor: 'rgba(255,255,255,0.15)', canvasH1: '#b5451b', canvasH2: '#e8841f' },
  oscuro:  { decoColor: 'rgba(240,192,64,0.1)',   canvasH1: '#0a0a1a', canvasH2: '#1a1a38' },
  moderno: { decoColor: 'rgba(26,127,90,0.08)',   canvasH1: '#f0f8f4', canvasH2: '#e0f5ec' },
  rapida:  { decoColor: 'rgba(255,255,255,0.15)', canvasH1: '#c0392b', canvasH2: '#e74c3c' },
  rosa:    { decoColor: 'rgba(255,255,255,0.18)', canvasH1: '#FF8276', canvasH2: '#EA9087' },
}
