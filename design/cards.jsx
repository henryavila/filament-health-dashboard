/* global React, Icon, Button, StatusIcon, StatusBadge, st */
// =====================================================================
// Health plugin — cards de check (A/B), botão de ação, heatmap, modal
// =====================================================================
const { useState: useStateC } = React;

// ---- Botão de ação com confirmação (remediação) ---------------------
function ActionButton({ action, accent, size = 'xs', onRan }) {
  const [phase, setPhase] = useStateC('idle'); // idle | confirm | running | done
  const stop = (e) => { e.stopPropagation(); };

  if (action.kind === 'rerun') {
    return (
      <Button variant="secondary" size={size} icon="sync" spin={phase === 'running'}
        onClick={(e) => { stop(e); if (phase === 'running') return; setPhase('running'); setTimeout(() => { setPhase('idle'); onRan && onRan(); }, 1200); }}>
        {phase === 'running' ? 'Rodando…' : action.label}
      </Button>
    );
  }
  // kind === 'fix'
  if (phase === 'done') {
    return (
      <Button variant="secondary" size={size} icon="ok" iconSolid onClick={stop}
        style={{ color: 'var(--success)', borderColor: 'color-mix(in srgb, var(--success) 35%, transparent)' }}>
        Aplicado
      </Button>
    );
  }
  if (phase === 'running') {
    return <Button variant="fix" accent={accent} size={size} icon="sync" spin onClick={stop}>Aplicando…</Button>;
  }
  if (phase === 'confirm') {
    return (
      <span style={{ display: 'inline-flex', gap: 6 }} onClick={stop}>
        <Button variant="fix" accent={accent} size={size} icon="ok" iconSolid
          onClick={(e) => { stop(e); setPhase('running'); setTimeout(() => setPhase('done'), 1100); }}>Confirmar</Button>
        <Button variant="ghost" size={size} onClick={(e) => { stop(e); setPhase('idle'); }}>Cancelar</Button>
      </span>
    );
  }
  return (
    <Button variant="fix" accent={accent} size={size} icon="wrench"
      onClick={(e) => { stop(e); setPhase('confirm'); }}>{action.label}</Button>
  );
}

// =====================================================================
// CheckCard — tratamento A ("Destacado") e B ("Compacto")
// =====================================================================
function CheckCard({ check, treatment = 'A', onOpen, index = 0 }) {
  const s = st(check.status);
  const ranRow = (
    <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, color: 'var(--fg-3)', fontSize: 12, fontWeight: 500, fontFamily: 'var(--font-sans)' }}>
      <Icon name="clock" size={13} stroke={1.8} /> Verificado {check.lastRan}
    </span>
  );
  const actions = (check.actions || []).map((a, i) => (
    <ActionButton key={i} action={a} accent={s.color} />
  ));
  const open = () => onOpen && onOpen(check);
  const keydown = (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } };

  if (treatment === 'B') {
    return (
      <div className="h-card h-card--int" tabIndex={0} role="button" onClick={open} onKeyDown={keydown}
        style={{ display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>
        <div style={{ padding: '14px 16px 12px', display: 'flex', flexDirection: 'column', gap: 8, flex: 1 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <StatusIcon status={check.status} size={30} />
            <span style={{ flex: 1, minWidth: 0, fontFamily: 'var(--font-sans)', fontWeight: 700, fontSize: 14.5, color: 'var(--fg-1)', letterSpacing: '-0.01em', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{check.label}</span>
            <StatusBadge status={check.status} size="sm" />
          </div>
          <p style={{ margin: 0, fontFamily: 'var(--font-sans)', fontSize: 13, lineHeight: 1.45, color: 'var(--fg-2)',
            display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>{check.summary}</p>
        </div>
        <div style={{ borderTop: '1px solid var(--border)', padding: '9px 14px', display: 'flex', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'space-between', gap: 8, rowGap: 8 }}>
          <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6, color: 'var(--fg-3)', fontSize: 11.5, fontWeight: 500, fontFamily: 'var(--font-sans)' }}>
            <Icon name={check.icon} size={14} stroke={1.7} /> {check.lastRan}
          </span>
          <span style={{ display: 'inline-flex', flexWrap: 'wrap', justifyContent: 'flex-end', gap: 6 }} onClick={(e) => e.stopPropagation()}>{actions}</span>
        </div>
      </div>
    );
  }

  // Tratamento A
  return (
    <div className="h-card h-card--int" tabIndex={0} role="button" onClick={open} onKeyDown={keydown}
      style={{ position: 'relative', overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
      <span className="h-accent" style={{ '--accent': s.color }} />
      <div style={{ padding: 'var(--card-pad)', paddingLeft: 'calc(var(--card-pad) + 4px)', display: 'flex', flexDirection: 'column', gap: 12, flex: 1 }}>
        <div style={{ display: 'flex', alignItems: 'flex-start', gap: 13 }}>
          <span className="h-chip" style={{ width: 42, height: 42, background: s.bg, color: s.color }}>
            <Icon name={check.icon} size={22} stroke={1.8} />
          </span>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontFamily: 'var(--font-sans)', fontWeight: 700, fontSize: 16, color: 'var(--fg-1)', letterSpacing: '-0.01em' }}>{check.label}</div>
            <div style={{ marginTop: 3 }}>{ranRow}</div>
          </div>
          <StatusBadge status={check.status} />
        </div>
        <p style={{ margin: 0, fontFamily: 'var(--font-sans)', fontSize: 14, lineHeight: 1.5, color: 'var(--fg-2)',
          display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden', minHeight: 42 }}>{check.summary}</p>
      </div>
      <div style={{ borderTop: '1px solid var(--border)', padding: '11px 16px 11px calc(var(--card-pad) + 4px)',
        display: 'flex', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'space-between', gap: 8, rowGap: 8 }}>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4, fontFamily: 'var(--font-sans)', fontSize: 13, fontWeight: 600, color: 'var(--fg-2)' }}>
          Detalhes <Icon name="chevronR" size={14} stroke={2.2} />
        </span>
        <span style={{ display: 'inline-flex', flexWrap: 'wrap', justifyContent: 'flex-end', gap: 6 }} onClick={(e) => e.stopPropagation()}>{actions}</span>
      </div>
    </div>
  );
}

// =====================================================================
// Heatmap — uma linha por check, 30 células (status por dia)
// =====================================================================
function heatColor(status) {
  const k = st(status).key;
  if (k === 'ok') return 'var(--success)';
  if (k === 'warn') return 'var(--warning)';
  if (k === 'fail') return 'var(--danger)';
  return 'var(--gray-300)';
}
function HeatmapRow({ check }) {
  return (
    <div style={{ display: 'grid', gridTemplateColumns: '180px 1fr', alignItems: 'center', gap: 14, padding: '5px 0' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 8, minWidth: 0 }}>
        <Icon name={check.icon} size={15} stroke={1.7} style={{ color: 'var(--fg-3)', flex: 'none' }} />
        <span style={{ fontFamily: 'var(--font-sans)', fontSize: 12.5, fontWeight: 600, color: 'var(--fg-2)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{check.label}</span>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(30, 1fr)', gap: 3 }}>
        {check.history.map((d, i) => (
          <div key={i} className="h-cell" title={`${i === 29 ? 'hoje' : `há ${29 - i} dia(s)`} · ${st(d).label}`}
            style={{ height: 15, background: heatColor(d), opacity: st(d).key === 'ok' ? 0.85 : 1 }} />
        ))}
      </div>
    </div>
  );
}

// =====================================================================
// Drill-down modal
// =====================================================================
function MiniBadge({ status, children }) {
  const s = st(status);
  return (
    <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, padding: '2px 8px', borderRadius: 'var(--r-full)',
      background: s.bg, color: s.color, fontFamily: 'var(--font-sans)', fontSize: 11.5, fontWeight: 700, whiteSpace: 'nowrap' }}>
      <span style={{ width: 6, height: 6, borderRadius: '50%', background: s.color }} />{children}
    </span>
  );
}

function DrilldownModal({ check, onClose }) {
  if (!check) return null;
  const s = st(check.status);
  const stop = (e) => e.stopPropagation();
  return (
    <div className="h-scrim" onClick={onClose}>
      <div style={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 24 }}>
        <div className="h-modal" role="dialog" aria-modal="true" onClick={stop}
          style={{ width: 'min(720px, 100%)', maxHeight: '88vh', display: 'flex', flexDirection: 'column',
            background: 'var(--bg-surface)', border: '1px solid var(--border)', borderRadius: 'var(--r-xl)', boxShadow: 'var(--shadow-xl)', overflow: 'hidden' }}>
          {/* header */}
          <div style={{ display: 'flex', alignItems: 'flex-start', gap: 14, padding: '20px 22px', borderBottom: '1px solid var(--border)' }}>
            <span className="h-chip" style={{ width: 46, height: 46, background: s.bg, color: s.color, flex: 'none' }}>
              <Icon name={check.icon} size={24} stroke={1.8} />
            </span>
            <div style={{ flex: 1, minWidth: 0 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 10, flexWrap: 'wrap' }}>
                <span style={{ fontFamily: 'var(--font-sans)', fontWeight: 800, fontSize: 19, color: 'var(--fg-1)', letterSpacing: '-0.02em' }}>{check.label}</span>
                <StatusBadge status={check.status} />
              </div>
              <div style={{ marginTop: 4, display: 'inline-flex', alignItems: 'center', gap: 5, color: 'var(--fg-3)', fontSize: 12.5, fontWeight: 500 }}>
                <Icon name="clock" size={13} stroke={1.8} /> Verificado {check.lastRan}
                <span style={{ margin: '0 4px', color: 'var(--border-strong)' }}>·</span>
                <span style={{ fontFamily: 'var(--font-mono)', fontSize: 11.5 }}>{check.id}</span>
              </div>
            </div>
            <button className="h-iconbtn" onClick={onClose} aria-label="Fechar" style={{ width: 34, height: 34, flex: 'none', color: 'var(--fg-3)' }}>
              <Icon name="x" size={20} stroke={2} />
            </button>
          </div>

          {/* body */}
          <div className="h-scroll" style={{ padding: '20px 22px', overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: 20 }}>
            {/* notificação */}
            <div style={{ display: 'flex', gap: 11, padding: '13px 15px', borderRadius: 'var(--r-lg)', background: s.bg,
              border: `1px solid color-mix(in srgb, ${s.color} 22%, transparent)` }}>
              <Icon name={s.icon} solid size={19} style={{ color: s.color, flex: 'none', marginTop: 1 }} />
              <p style={{ margin: 0, fontFamily: 'var(--font-sans)', fontSize: 13.5, lineHeight: 1.55, color: 'var(--fg-1)' }}>{check.message}</p>
            </div>

            {/* meta key/value */}
            <div>
              <div className="arch-overline" style={{ marginBottom: 9 }}>Metadados</div>
              <div style={{ border: '1px solid var(--border)', borderRadius: 'var(--r-lg)', overflow: 'hidden' }}>
                {Object.entries(check.meta).map(([k, v], i, arr) => (
                  <div key={k} style={{ display: 'grid', gridTemplateColumns: '200px 1fr', gap: 12, padding: '10px 14px',
                    borderBottom: i < arr.length - 1 ? '1px solid var(--border)' : 'none', background: i % 2 ? 'var(--bg-subtle)' : 'transparent' }}>
                    <span style={{ fontFamily: 'var(--font-sans)', fontSize: 13, fontWeight: 600, color: 'var(--fg-2)' }}>{k}</span>
                    <span className="tnum" style={{ fontFamily: 'var(--font-mono)', fontSize: 12.5, color: 'var(--fg-1)', overflowWrap: 'anywhere' }}>{v}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* tabela de dados (ex.: stale classes) */}
            {check.dataTable && (
              <div>
                <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', marginBottom: 9 }}>
                  <span className="arch-overline">Referências encontradas</span>
                  <span style={{ fontFamily: 'var(--font-sans)', fontSize: 12, color: 'var(--fg-3)' }}>{check.dataTable.rows.length} linhas</span>
                </div>
                <div className="h-scroll" style={{ border: '1px solid var(--border)', borderRadius: 'var(--r-lg)', overflow: 'auto' }}>
                  <table style={{ width: '100%', borderCollapse: 'collapse', fontFamily: 'var(--font-sans)', fontSize: 12.5, minWidth: 560 }}>
                    <thead>
                      <tr style={{ background: 'var(--bg-subtle)' }}>
                        {check.dataTable.columns.map((c) => (
                          <th key={c} style={{ textAlign: 'left', padding: '9px 12px', fontWeight: 700, fontSize: 11, letterSpacing: '0.04em', textTransform: 'uppercase', color: 'var(--fg-3)', borderBottom: '1px solid var(--border)', whiteSpace: 'nowrap' }}>{c}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {check.dataTable.rows.map((r, ri) => (
                        <tr key={ri} className="h-row-hover" style={{ borderBottom: ri < check.dataTable.rows.length - 1 ? '1px solid var(--border)' : 'none' }}>
                          <td style={{ padding: '9px 12px', fontFamily: 'var(--font-mono)', fontSize: 11.5, color: 'var(--fg-2)', whiteSpace: 'nowrap' }}>{r.c[0]}</td>
                          <td style={{ padding: '9px 12px', fontFamily: 'var(--font-mono)', fontSize: 11.5, color: 'var(--fg-2)', whiteSpace: 'nowrap' }}>{r.c[1]}</td>
                          <td style={{ padding: '9px 12px', fontFamily: 'var(--font-mono)', fontSize: 11.5, color: 'var(--fg-1)', whiteSpace: 'nowrap' }}>{r.c[2]}</td>
                          <td style={{ padding: '9px 12px' }}><MiniBadge status={r.status}>{r.c[3]}</MiniBadge></td>
                          <td style={{ padding: '9px 12px', color: r.fixable ? 'var(--fg-1)' : 'var(--fg-3)', fontFamily: r.fixable ? 'var(--font-mono)' : 'var(--font-sans)', fontSize: r.fixable ? 11.5 : 12.5, fontStyle: r.fixable ? 'normal' : 'italic', whiteSpace: 'nowrap' }}>{r.c[4]}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}
          </div>

          {/* footer ações */}
          {check.actions && check.actions.length > 0 && (
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: 8, padding: '14px 22px', borderTop: '1px solid var(--border)', background: 'var(--bg-subtle)' }}>
              {check.actions.map((a, i) => <ActionButton key={i} action={a} accent={s.color} size="sm" />)}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { ActionButton, CheckCard, HeatmapRow, DrilldownModal, MiniBadge, heatColor });
