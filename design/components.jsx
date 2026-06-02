/* global React */
// =====================================================================
// Health plugin — primitivas compartilhadas
// Plugin Filament genérico de health checks. Neutros/semânticos vêm do DS;
// nada de marca específica. Ícones = Heroicons (solid p/ status, outline p/ UI).
// =====================================================================
const { useState, useEffect, useRef } = React;

// ---- Heroicons OUTLINE (24px, traço 1.5–1.7) ------------------------
const ICONS = {
  sync:      'M16.02 9.35h4.5v-4.5m-15.04 9.8h-4.5v4.5M4.06 9a8.25 8.25 0 0 1 13.8-3.04L20.5 8.6M3.5 15.4l2.65 2.65A8.25 8.25 0 0 0 19.94 15',
  clock:     'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
  chevronD:  'm19.5 8.25-7.5 7.5-7.5-7.5',
  chevronU:  'm4.5 15.75 7.5-7.5 7.5 7.5',
  chevronR:  'm8.25 4.5 7.5 7.5-7.5 7.5',
  x:         'M6 18 18 6M6 6l12 12',
  arrowR:    'M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3',
  ellipsis:  'M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
  sun:       'M12 3v2.25m6.36.39-1.59 1.59M21 12h-2.25m-.39 6.36-1.59-1.59M12 18.75V21m-4.77-4.23-1.59 1.59M5.25 12H3m4.23-4.77L5.64 5.64M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z',
  moon:      'M21.75 15A9.72 9.72 0 0 1 18 15.75c-5.39 0-9.75-4.36-9.75-9.75 0-1.33.27-2.6.75-3.75A9.75 9.75 0 0 0 3 11.25C3 16.64 7.36 21 12.75 21a9.75 9.75 0 0 0 9-6Z',
  bell:      'M14.86 17.08a48 48 0 0 0 4.05-.52.75.75 0 0 0 .42-1.16 6.97 6.97 0 0 1-1.59-4.46V9.65a6.16 6.16 0 1 0-12.32 0v1.29a7 7 0 0 1-1.6 4.46.75.75 0 0 0 .43 1.16c1.33.23 2.68.4 4.05.52m6.13 0a24.2 24.2 0 0 1-6.13 0m6.13 0a3.07 3.07 0 0 1-6.13 0',
  search:    'm21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6Z',
  info:      'm11.25 11.25.04-.02a.75.75 0 0 1 1.06.85l-.7 2.84a.75.75 0 0 0 1.06.85l.04-.02M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.01v.01H12V8.25Z',
  doc:       'M19.5 14.25v-2.63c0-3.81-2.4-7.04-5.76-8.31a.6.6 0 0 0-.24-.06H6.75A2.25 2.25 0 0 0 4.5 5.5v13a2.25 2.25 0 0 0 2.25 2.25h10.5A2.25 2.25 0 0 0 19.5 18.5v-4.25Zm0 0h-3.38a1.13 1.13 0 0 1-1.12-1.13V9.75',
  history:   'M12 6v6l4 2m5.5-2a9.5 9.5 0 1 1-3.3-7.2M21.5 4v4.5H17',
  // domínio dos checks
  cpu:       'M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z',
  database:  'M20.25 6.38c0 2.27-3.7 4.12-8.25 4.12S3.75 8.65 3.75 6.38m16.5 0C20.25 4.1 16.55 2.25 12 2.25S3.75 4.1 3.75 6.38m16.5 0v11.25c0 2.27-3.7 4.12-8.25 4.12s-8.25-1.85-8.25-4.12V6.38m16.5 5.62c0 2.28-3.7 4.13-8.25 4.13S3.75 14.28 3.75 12',
  server:    'M21.75 17.25v-.23a4.5 4.5 0 0 0-.12-1.03l-2.27-9.08a3.75 3.75 0 0 0-3.64-2.84H8.28a3.75 3.75 0 0 0-3.64 2.84l-2.27 9.08a4.5 4.5 0 0 0-.12 1.03v.23m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.01v.01h-.01v-.01Zm-3 0h.01v.01h-.01v-.01Z',
  bolt:      'M3.75 13.5 14.25 3v6.75h6L9.75 21v-7.5h-6Z',
  queue:     'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.63 4.5h12.74a1.88 1.88 0 0 1 0 3.75H5.63a1.88 1.88 0 0 1 0-3.75Z',
  wrench:    'M11.42 15.17 17.25 21A2.65 2.65 0 1 0 21 17.25l-5.88-5.88m-3.7 3.8a4.5 4.5 0 0 1-1.42-7.37l2.86 2.86a.5.5 0 0 0 .7 0l2.5-2.5a.5.5 0 0 0 0-.7L13.4 4.6a4.5 4.5 0 0 1 6.06 5.43m-12.04 5.14L4.6 18a2.12 2.12 0 1 0 3 3l2.23-2.82',
  calendar:  'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
  archive:   'M20.25 7.5l-.63 10.63a2.25 2.25 0 0 1-2.24 2.12H6.62a2.25 2.25 0 0 1-2.24-2.12L3.75 7.5M10 11.25h4M3.38 7.5h17.25c.62 0 1.12-.5 1.12-1.13v-1.5c0-.62-.5-1.12-1.12-1.12H3.38c-.62 0-1.13.5-1.13 1.13v1.5c0 .62.5 1.12 1.13 1.12Z',
  shield:    'M9 12.75 11.25 15 15 9.75m-3-7.04a11.96 11.96 0 0 1-8.62 3.67 12.02 12.02 0 0 0 8.62 14.62 12.02 12.02 0 0 0 8.62-14.62A11.96 11.96 0 0 1 12 2.71Z',
  squares:   'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6Zm0 9.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25Zm9.75-9.75A2.25 2.25 0 0 1 15.75 3.75H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Zm0 9.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
  inbox:     'M2.25 13.5h3.86c.46 0 .89.32 1.06.78a3.75 3.75 0 0 0 7.06 0c.17-.46.6-.78 1.06-.78h3.86m-16.5 0V8.66c0-.51.1-1 .3-1.46l2.6-6.09m13 7.55V8.66c0-.51-.1-1-.3-1.46m0 0-2.6-6.09M4.85 1.11A1.5 1.5 0 0 1 6.23.18h11.54c.6 0 1.14.36 1.38.93m-14.3 0 .35.82',
  pulse:     'M3 12h4l2-6 4 12 2-6h6',
};

// ---- Heroicons SOLID — status (preenchidos) -------------------------
const SOLID = {
  ok:   'M2.25 12c0-5.39 4.36-9.75 9.75-9.75s9.75 4.36 9.75 9.75-4.36 9.75-9.75 9.75S2.25 17.39 2.25 12Zm13.36-1.81a.75.75 0 1 0-1.22-.87l-3.24 4.53-1.63-1.63a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.09l3.75-5.25Z',
  warn: 'M9.4 3c1.16-2 4.05-2 5.2 0l7.36 12.75c1.15 2-.29 4.5-2.6 4.5H4.65c-2.31 0-3.76-2.5-2.6-4.5L9.4 3ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z',
  fail: 'M12 2.25c-5.39 0-9.75 4.36-9.75 9.75s4.36 9.75 9.75 9.75 9.75-4.36 9.75-9.75S17.39 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z',
  skip: 'M12 2.25c-5.39 0-9.75 4.36-9.75 9.75s4.36 9.75 9.75 9.75 9.75-4.36 9.75-9.75S17.39 2.25 12 2.25ZM8.25 12a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Z',
  heart:'M11.65 21.18C6.7 18.56 2.25 14.65 2.25 9.94 2.25 6.6 4.86 4.5 7.5 4.5c1.6 0 3.18.78 4.5 2.34C13.32 5.28 14.9 4.5 16.5 4.5c2.64 0 5.25 2.1 5.25 5.44 0 4.71-4.45 8.62-9.4 11.24a.75.75 0 0 1-.7 0Z',
};

function Icon({ name, size = 20, stroke = 1.7, solid = false, className = '', style = {} }) {
  if (solid) {
    return (
      <svg className={className} width={size} height={size} viewBox="0 0 24 24" fill="currentColor"
        style={style} aria-hidden="true"><path d={SOLID[name]} /></svg>
    );
  }
  return (
    <svg className={className} width={size} height={size} viewBox="0 0 24 24" fill="none"
      stroke="currentColor" strokeWidth={stroke} strokeLinecap="round" strokeLinejoin="round"
      style={style} aria-hidden="true"><path d={ICONS[name]} /></svg>
  );
}

// ---- Mapa de status — fonte única de verdade ------------------------
// crashed→fail, unknown→skip
const STATUS = {
  ok:      { key: 'ok',   label: 'OK',        chip: 'OK',        icon: 'ok',   color: 'var(--st-ok)',   bg: 'var(--st-ok-bg)' },
  warning: { key: 'warn', label: 'Atenção',   chip: 'Atenção',   icon: 'warn', color: 'var(--st-warn)', bg: 'var(--st-warn-bg)' },
  failed:  { key: 'fail', label: 'Falhando',  chip: 'Falhou',    icon: 'fail', color: 'var(--st-fail)', bg: 'var(--st-fail-bg)' },
  crashed: { key: 'fail', label: 'Falhando',  chip: 'Crashou',   icon: 'fail', color: 'var(--st-fail)', bg: 'var(--st-fail-bg)' },
  skipped: { key: 'skip', label: 'Ignorado',  chip: 'Ignorado',  icon: 'skip', color: 'var(--st-skip)', bg: 'var(--st-skip-bg)' },
  unknown: { key: 'skip', label: 'Desconhecido', chip: 'Desconhecido', icon: 'skip', color: 'var(--st-skip)', bg: 'var(--st-skip-bg)' },
};
const st = (s) => STATUS[s] || STATUS.unknown;

// ---- Button ---------------------------------------------------------
function Button({ children, variant = 'secondary', size = 'md', icon, iconRight, iconSolid = false,
                  spin = false, onClick, type = 'button', accent, full, title, style = {} }) {
  const sizes = {
    xs: { padding: '5px 10px', fontSize: 12.5, gap: 5 },
    sm: { padding: '7px 13px', fontSize: 13, gap: 6 },
    md: { padding: '9px 16px', fontSize: 13.5 },
    lg: { padding: '11px 20px', fontSize: 14.5 },
  };
  const isz = size === 'xs' ? 14 : size === 'sm' ? 15 : 17;
  return (
    <button type={type} onClick={onClick} title={title}
      className={`h-btn h-btn--${variant}`}
      style={{ ...sizes[size], width: full ? '100%' : 'auto', ...(accent ? { '--accent': accent } : {}), ...style }}>
      {icon && <Icon name={icon} size={isz} stroke={2} solid={iconSolid} className={spin ? 'h-spin' : ''} />}
      {children}
      {iconRight && <Icon name={iconRight} size={isz} stroke={2} />}
    </button>
  );
}

// ---- Ícone de status em chip ----------------------------------------
function StatusIcon({ status, size = 38, iconSize }) {
  const s = st(status);
  return (
    <span className="h-chip" style={{ width: size, height: size, background: s.bg, color: s.color }}>
      <Icon name={s.icon} solid size={iconSize || Math.round(size * 0.58)} />
    </span>
  );
}

// ---- Status badge (pill) --------------------------------------------
function StatusBadge({ status, size = 'md' }) {
  const s = st(status);
  const pad = size === 'sm' ? '3px 9px 3px 7px' : '4px 11px 4px 8px';
  const fs = size === 'sm' ? 11.5 : 12;
  return (
    <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: pad, borderRadius: 'var(--r-full)',
      background: s.bg, color: s.color, fontFamily: 'var(--font-sans)', fontWeight: 700, fontSize: fs, lineHeight: 1, whiteSpace: 'nowrap' }}>
      <Icon name={s.icon} solid size={fs + 3} />
      {s.label}
    </span>
  );
}

// ---- Sparkline (tendência 30 dias) ----------------------------------
function Sparkline({ data, color = 'var(--fg-3)', width = 132, height = 30, fill = true }) {
  if (!data || data.length < 2) return null;
  const max = Math.max(...data), min = Math.min(...data);
  const span = max - min || 1;
  const stepX = width / (data.length - 1);
  const y = (v) => height - 3 - ((v - min) / span) * (height - 6);
  const pts = data.map((v, i) => [i * stepX, y(v)]);
  const line = pts.map((p, i) => `${i === 0 ? 'M' : 'L'}${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ');
  const area = `${line} L${width} ${height} L0 ${height} Z`;
  const gid = 'spk' + Math.random().toString(36).slice(2, 8);
  const last = pts[pts.length - 1];
  return (
    <svg width={width} height={height} viewBox={`0 0 ${width} ${height}`} style={{ display: 'block', overflow: 'visible' }} aria-hidden="true">
      <defs>
        <linearGradient id={gid} x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stopColor={color} stopOpacity="0.18" />
          <stop offset="1" stopColor={color} stopOpacity="0" />
        </linearGradient>
      </defs>
      {fill && <path d={area} fill={`url(#${gid})`} />}
      <path d={line} fill="none" stroke={color} strokeWidth="1.6" strokeLinejoin="round" strokeLinecap="round" />
      <circle cx={last[0]} cy={last[1]} r="2.4" fill={color} />
    </svg>
  );
}

// ---- Stat card ------------------------------------------------------
function StatCard({ label, value, accent = 'var(--fg-2)', spark, sub, dot = true }) {
  return (
    <div className="h-card" style={{ padding: '16px 18px 14px', display: 'flex', flexDirection: 'column', gap: 2, position: 'relative', overflow: 'hidden' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 7 }}>
        {dot && <span style={{ width: 8, height: 8, borderRadius: '50%', background: accent, flex: 'none' }} />}
        <span className="arch-overline" style={{ color: 'var(--fg-3)' }}>{label}</span>
      </div>
      <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', gap: 12, marginTop: 4 }}>
        <span className="tnum" style={{ fontFamily: 'var(--font-sans)', fontWeight: 900, fontSize: 40, lineHeight: 1, letterSpacing: '-0.03em', color: 'var(--fg-1)' }}>{value}</span>
        {spark && <span style={{ flex: 'none', paddingBottom: 3 }}><Sparkline data={spark} color={accent} width={108} height={30} /></span>}
      </div>
      {sub && <span style={{ marginTop: 8, fontFamily: 'var(--font-sans)', fontSize: 12, fontWeight: 500, color: 'var(--fg-3)' }}>{sub}</span>}
    </div>
  );
}

// ---- Banner (stale / aviso) -----------------------------------------
function Banner({ tone = 'warn', icon = 'info', title, children, action }) {
  const tones = {
    warn: { bg: 'var(--st-warn-bg)', fg: 'var(--st-warn)', bd: 'color-mix(in srgb, var(--st-warn) 28%, transparent)' },
    fail: { bg: 'var(--st-fail-bg)', fg: 'var(--st-fail)', bd: 'color-mix(in srgb, var(--st-fail) 28%, transparent)' },
    info: { bg: 'var(--info-bg)', fg: 'var(--info)', bd: 'color-mix(in srgb, var(--info) 26%, transparent)' },
  };
  const t = tones[tone];
  return (
    <div role="status" style={{ display: 'flex', alignItems: 'center', gap: 13, padding: '13px 16px', borderRadius: 'var(--r-lg)',
      background: t.bg, border: `1px solid ${t.bd}` }}>
      <Icon name={icon} solid={tone !== 'info'} size={20} style={{ color: t.fg, flex: 'none' }} />
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{ fontFamily: 'var(--font-sans)', fontWeight: 700, fontSize: 13.5, color: t.fg }}>{title}</div>
        {children && <div style={{ fontFamily: 'var(--font-sans)', fontSize: 13, color: 'var(--fg-2)', marginTop: 1 }}>{children}</div>}
      </div>
      {action}
    </div>
  );
}

Object.assign(window, { Icon, ICONS, SOLID, STATUS, st, Button, StatusIcon, StatusBadge, Sparkline, StatCard, Banner });
