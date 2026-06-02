/* global React, Icon */
// =====================================================================
// Health plugin — casca genérica
// Top navigation no estilo Filament v4 (faked levemente p/ dar contexto),
// neutra e sem marca específica — o plugin roda em QUALQUER painel.
// O item "Saúde" carrega o badge com a contagem de checks falhando.
// =====================================================================
const { useState: useStateS, useContext: useContextS, createContext: createContextS, useRef: useRefS, useEffect: useEffectS } = React;

const ThemeCtx = createContextS({ theme: 'light', toggle: () => {} });
const TweakCtx = createContextS({});

// glyph neutro do painel (placeholder de marca do app hospedeiro)
function PanelGlyph({ size = 30 }) {
  return (
    <span style={{ display: 'inline-flex', alignItems: 'center', gap: 10 }}>
      <span style={{ width: size, height: size, borderRadius: 'var(--r-md)', background: 'var(--fg-1)', color: 'var(--bg-surface)',
        display: 'flex', alignItems: 'center', justifyContent: 'center', flex: 'none' }}>
        <Icon name="heart" solid size={size * 0.56} />
      </span>
      <span style={{ fontFamily: 'var(--font-sans)', fontWeight: 800, fontSize: 16, letterSpacing: '-0.02em', color: 'var(--fg-1)' }}>Painel</span>
    </span>
  );
}

function NavItem({ icon, children, active, badge }) {
  return (
    <button type="button" className="h-nav" style={{ position: 'relative', display: 'inline-flex', alignItems: 'center', gap: 8,
      padding: '8px 12px', borderRadius: 'var(--r-md)', border: 'none', cursor: 'pointer', whiteSpace: 'nowrap',
      background: active ? 'var(--bg-subtle)' : 'transparent', color: active ? 'var(--fg-1)' : 'var(--fg-2)',
      fontFamily: 'var(--font-sans)', fontWeight: active ? 700 : 600, fontSize: 13.5 }}>
      <Icon name={icon} size={18} stroke={active ? 2 : 1.7} />
      {children}
      {badge != null && badge > 0 && (
        <span className="tnum" style={{ minWidth: 19, height: 19, padding: '0 5px', borderRadius: 'var(--r-full)', background: 'var(--danger)', color: '#fff',
          display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontFamily: 'var(--font-sans)', fontWeight: 800, fontSize: 11, lineHeight: 1 }}>{badge}</span>
      )}
    </button>
  );
}

function UserMenu() {
  const [open, setOpen] = useStateS(false);
  const wrap = useRefS(null);
  useEffectS(() => {
    if (!open) return;
    const off = (e) => { if (wrap.current && !wrap.current.contains(e.target)) setOpen(false); };
    document.addEventListener('pointerdown', off, true);
    return () => document.removeEventListener('pointerdown', off, true);
  }, [open]);
  return (
    <div ref={wrap} style={{ position: 'relative' }}>
      <button type="button" onClick={() => setOpen((o) => !o)} className="h-iconbtn"
        style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '3px 8px 3px 3px', borderRadius: 'var(--r-full)' }}>
        <span style={{ width: 30, height: 30, borderRadius: '50%', background: 'var(--fg-1)', color: 'var(--bg-surface)',
          display: 'flex', alignItems: 'center', justifyContent: 'center', fontFamily: 'var(--font-sans)', fontWeight: 800, fontSize: 12 }}>OP</span>
        <Icon name="chevronD" size={14} stroke={2} style={{ color: 'var(--fg-3)' }} />
      </button>
      {open && (
        <div className="h-pop" style={{ position: 'absolute', top: '100%', right: 0, marginTop: 8, minWidth: 200, zIndex: 60,
          background: 'var(--bg-surface)', border: '1px solid var(--border)', borderRadius: 'var(--r-lg)', boxShadow: 'var(--shadow-lg)', padding: 6 }}>
          <div style={{ padding: '8px 10px 10px', borderBottom: '1px solid var(--border)', marginBottom: 6 }}>
            <div style={{ fontFamily: 'var(--font-sans)', fontSize: 13.5, fontWeight: 700, color: 'var(--fg-1)' }}>Equipe de operações</div>
            <div style={{ fontFamily: 'var(--font-sans)', fontSize: 11.5, color: 'var(--fg-3)', marginTop: 2 }}>ops@exemplo.app</div>
          </div>
          {['Configurar verificações', 'Documentação', 'Sair'].map((l, i) => (
            <button key={l} type="button" className="h-nav" style={{ display: 'flex', alignItems: 'center', gap: 10, width: '100%', padding: '8px 10px',
              border: 'none', borderRadius: 'var(--r-sm)', cursor: 'pointer', textAlign: 'left', background: 'transparent',
              fontFamily: 'var(--font-sans)', fontSize: 13, fontWeight: 500, color: i === 2 ? 'var(--danger)' : 'var(--fg-1)' }}>
              <Icon name={i === 0 ? 'wrench' : i === 1 ? 'doc' : 'arrowR'} size={16} stroke={1.8} style={{ color: i === 2 ? 'var(--danger)' : 'var(--fg-3)' }} />{l}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

function TopBar({ failingCount = 0 }) {
  const { theme, toggle } = useContextS(ThemeCtx);
  return (
    <header style={{ flex: 'none', height: 60, display: 'flex', alignItems: 'center', gap: 16, padding: '0 22px',
      background: 'color-mix(in srgb, var(--bg-surface) 82%, transparent)', backdropFilter: 'blur(12px)', WebkitBackdropFilter: 'blur(12px)',
      borderBottom: '1px solid var(--border)', position: 'sticky', top: 0, zIndex: 40 }}>
      <PanelGlyph size={30} />
      <div style={{ width: 1, height: 26, background: 'var(--border)' }} />
      <nav style={{ display: 'flex', alignItems: 'center', gap: 2 }}>
        <NavItem icon="squares">Início</NavItem>
        <NavItem icon="doc">Registros</NavItem>
        <NavItem icon="heart" active badge={failingCount}>Saúde</NavItem>
        <NavItem icon="wrench">Ajustes</NavItem>
      </nav>
      <div style={{ flex: 1 }} />
      <button type="button" onClick={toggle} className="h-iconbtn" title={theme === 'dark' ? 'Tema claro' : 'Tema escuro'}
        style={{ width: 38, height: 38, color: 'var(--fg-2)' }}>
        <Icon name={theme === 'dark' ? 'sun' : 'moon'} size={19} stroke={1.8} />
      </button>
      <button type="button" className="h-iconbtn" title="Notificações" style={{ width: 38, height: 38, color: 'var(--fg-2)' }}>
        <Icon name="bell" size={19} stroke={1.8} />
      </button>
      <div style={{ width: 1, height: 26, background: 'var(--border)' }} />
      <UserMenu />
    </header>
  );
}

function AppShell({ failingCount, children }) {
  const { theme } = useContextS(ThemeCtx);
  return (
    <div className={theme === 'dark' ? 'dark' : ''} style={{ height: '100vh', display: 'flex', flexDirection: 'column',
      background: 'var(--bg-canvas)', color: 'var(--fg-1)', fontFamily: 'var(--font-sans)', overflow: 'hidden' }}>
      <TopBar failingCount={failingCount} />
      <main className="h-scroll" style={{ flex: 1, minHeight: 0, overflowY: 'auto' }}>{children}</main>
    </div>
  );
}

Object.assign(window, { ThemeCtx, TweakCtx, PanelGlyph, NavItem, UserMenu, TopBar, AppShell });
