/* global React, Icon, Button, StatCard, Banner, CheckCard, HeatmapRow, DrilldownModal, DATASETS, STAT_SPARK, summarize, st */
// =====================================================================
// Health plugin — composição do painel
// =====================================================================
const { useState: useStateScr, useEffect: useEffectScr, useContext: useContextScr } = React;

const MAXW = 1280;

// ---- Cabeçalho da página -------------------------------------------
function PageHeader({ ds, onVerify }) {
  const [checking, setChecking] = useStateScr(false);
  const [justRan, setJustRan] = useStateScr(false);

  const label = justRan ? 'há instantes' : (ds.lastRanMins <= 1 ? 'há 1 min' : `há ${ds.lastRanMins} min`);
  const isStale = !justRan && (ds.stale || ds.lastRanMins > 5);

  const run = () => {
    if (checking) return;
    setChecking(true);
    setTimeout(() => { setChecking(false); setJustRan(true); onVerify && onVerify(); }, 1500);
  };
  useEffectScr(() => { setJustRan(false); }, [ds]);

  return (
    <div style={{ display: 'flex', alignItems: 'flex-end', justifyContent: 'space-between', gap: 24, flexWrap: 'wrap' }}>
      <div>
        <h1 style={{ margin: 0, fontFamily: 'var(--font-sans)', fontWeight: 800, fontSize: 30, letterSpacing: '-0.03em', color: 'var(--fg-1)' }}>Saúde</h1>
        <p style={{ margin: '6px 0 0', fontFamily: 'var(--font-sans)', fontSize: 14.5, color: 'var(--fg-2)', maxWidth: 560, lineHeight: 1.5 }}>
          Resultado das verificações de saúde da aplicação e da infraestrutura.
        </p>
        <div style={{ display: 'flex', alignItems: 'center', gap: 9, marginTop: 12 }}>
          <Icon name="clock" size={15} stroke={1.8} style={{ color: isStale ? 'var(--danger)' : 'var(--fg-3)' }} />
          <span style={{ fontFamily: 'var(--font-sans)', fontSize: 13, fontWeight: 600, color: isStale ? 'var(--danger)' : 'var(--fg-2)' }}>
            Última verificação: {label}
          </span>
          {isStale && (
            <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, padding: '2px 9px', borderRadius: 'var(--r-full)',
              background: 'var(--danger-bg)', color: 'var(--danger)', fontFamily: 'var(--font-sans)', fontSize: 11.5, fontWeight: 700 }}>desatualizado</span>
          )}
        </div>
      </div>
      <Button variant="primary" size="lg" icon="sync" spin={checking} onClick={run}>
        {checking ? 'Verificando…' : 'Verificar agora'}
      </Button>
    </div>
  );
}

// ---- Linha de stat cards -------------------------------------------
function StatRow({ counts }) {
  return (
    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(190px, 1fr))', gap: 16 }}>
      <StatCard label="Total" value={counts.total} accent="var(--fg-2)" spark={STAT_SPARK.total} dot={false}
        sub={counts.skip > 0 ? `${counts.skip} ignorado(s)` : 'verificações ativas'} />
      <StatCard label="OK" value={counts.ok} accent="var(--success)" spark={STAT_SPARK.ok} sub="saudáveis" />
      <StatCard label="Atenção" value={counts.warn} accent="var(--warning)" spark={STAT_SPARK.warn} sub="requer revisão" />
      <StatCard label="Falhando" value={counts.fail} accent="var(--danger)" spark={STAT_SPARK.fail} sub="ação imediata" />
    </div>
  );
}

// ---- Painel de histórico (heatmap, colapsável) ----------------------
function HistoryPanel({ checks }) {
  const [open, setOpen] = useStateScr(true);
  const legend = [
    ['OK', 'var(--success)'], ['Atenção', 'var(--warning)'], ['Falha', 'var(--danger)'], ['Ignorado', 'var(--gray-300)'],
  ];
  return (
    <div className="h-card" style={{ padding: 0, overflow: 'hidden' }}>
      <button type="button" onClick={() => setOpen((o) => !o)} className="h-nav"
        style={{ width: '100%', display: 'flex', alignItems: 'center', gap: 12, padding: '15px 20px', border: 'none', background: 'transparent', cursor: 'pointer', textAlign: 'left' }}>
        <Icon name={open ? 'chevronD' : 'chevronR'} size={18} stroke={2} style={{ color: 'var(--fg-3)', flex: 'none' }} />
        <Icon name="history" size={18} stroke={1.8} style={{ color: 'var(--fg-2)', flex: 'none' }} />
        <span style={{ fontFamily: 'var(--font-sans)', fontWeight: 700, fontSize: 15, color: 'var(--fg-1)' }}>Histórico — últimos 30 dias</span>
        <span style={{ fontFamily: 'var(--font-sans)', fontSize: 12.5, color: 'var(--fg-3)' }}>· detectar flapping</span>
        <span style={{ flex: 1 }} />
        <span style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          {legend.map(([l, c]) => (
            <span key={l} style={{ display: 'inline-flex', alignItems: 'center', gap: 5, fontFamily: 'var(--font-sans)', fontSize: 11.5, fontWeight: 500, color: 'var(--fg-3)' }}>
              <span style={{ width: 9, height: 9, borderRadius: 2, background: c }} />{l}
            </span>
          ))}
        </span>
      </button>
      {open && (
        <div style={{ padding: '4px 20px 18px' }}>
          <div style={{ display: 'grid', gridTemplateColumns: '180px 1fr', gap: 14, padding: '0 0 8px' }}>
            <span />
            <div style={{ display: 'flex', justifyContent: 'space-between', fontFamily: 'var(--font-mono)', fontSize: 10.5, color: 'var(--fg-3)' }}>
              <span>30 dias atrás</span><span>hoje</span>
            </div>
          </div>
          {checks.map((c) => <HeatmapRow key={c.id} check={c} />)}
        </div>
      )}
    </div>
  );
}

// ---- Empty state ----------------------------------------------------
function EmptyState() {
  return (
    <div className="h-card" style={{ padding: '64px 32px', display: 'flex', flexDirection: 'column', alignItems: 'center', textAlign: 'center', gap: 6 }}>
      <span className="h-chip" style={{ width: 64, height: 64, background: 'var(--bg-subtle)', color: 'var(--fg-3)', marginBottom: 8 }}>
        <Icon name="pulse" size={30} stroke={1.8} />
      </span>
      <h3 style={{ margin: 0, fontFamily: 'var(--font-sans)', fontWeight: 700, fontSize: 19, color: 'var(--fg-1)' }}>Nenhuma verificação registrada</h3>
      <p style={{ margin: '2px 0 0', fontFamily: 'var(--font-sans)', fontSize: 14, color: 'var(--fg-2)', maxWidth: 420, lineHeight: 1.55 }}>
        Registre o primeiro health check para começar a monitorar a aplicação. As verificações aparecem aqui assim que forem definidas.
      </p>
      <div style={{ display: 'flex', gap: 10, marginTop: 18 }}>
        <Button variant="secondary" icon="doc">Ver documentação</Button>
        <Button variant="primary" icon="wrench">Adicionar verificação</Button>
      </div>
    </div>
  );
}

// ---- Tela principal -------------------------------------------------
function HealthScreen() {
  const tw = useContextScr(TweakCtx);
  const stateKey = tw.state || 'mixed';
  const treatment = tw.treatment || 'A';
  const showHeatmap = tw.heatmap !== false;

  const ds = DATASETS[stateKey] || DATASETS.mixed;
  const checks = ds.checks;
  const counts = summarize(checks);
  const empty = checks.length === 0;

  const [selected, setSelected] = useStateScr(null);
  // fecha modal ao trocar de estado
  useEffectScr(() => { setSelected(null); }, [stateKey]);
  useEffectScr(() => {
    const esc = (e) => { if (e.key === 'Escape') setSelected(null); };
    window.addEventListener('keydown', esc);
    return () => window.removeEventListener('keydown', esc);
  }, []);

  return (
    <div style={{ maxWidth: MAXW, margin: '0 auto', padding: '28px 32px 56px', display: 'flex', flexDirection: 'column', gap: 24 }}>
      <PageHeader ds={ds} />

      {ds.stale && (
        <Banner tone="warn" icon="warn" title="Resultados desatualizados"
          action={<Button variant="secondary" size="sm" icon="sync">Verificar agora</Button>}>
          A última verificação foi há {ds.lastRanMins} min e o cache expirou (janela: 5 min). Os dados abaixo podem não refletir o estado atual.
        </Banner>
      )}

      {empty ? (
        <EmptyState />
      ) : (
        <React.Fragment>
          <StatRow counts={counts} />
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(330px, 1fr))', gap: 16, alignItems: 'stretch' }}>
            {checks.map((c, i) => (
              <CheckCard key={c.id} check={c} treatment={treatment} index={i} onOpen={setSelected} />
            ))}
          </div>
          {showHeatmap && <HistoryPanel checks={checks} />}
        </React.Fragment>
      )}

      <DrilldownModal check={selected} onClose={() => setSelected(null)} />
    </div>
  );
}

Object.assign(window, { HealthScreen, PageHeader, StatRow, HistoryPanel, EmptyState });
