/* global React, ReactDOM, ThemeCtx, TweakCtx, AppShell, HealthScreen, DATASETS, summarize, useTweaks, TweaksPanel, TweakSection, TweakRadio, TweakToggle */
// =====================================================================
// Health plugin — raiz: tema (claro/escuro) + Tweaks (estado/cartão/heatmap)
// =====================================================================
const { useState: useStateApp, useEffect: useEffectApp } = React;

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "state": "mixed",
  "treatment": "B",
  "heatmap": true
}/*EDITMODE-END*/;

function App() {
  const [theme, setTheme] = useStateApp(() => {
    try { return localStorage.getItem('health-theme') || 'light'; } catch (e) { return 'light'; }
  });
  const toggle = () => setTheme((t) => (t === 'light' ? 'dark' : 'light'));
  useEffectApp(() => { try { localStorage.setItem('health-theme', theme); } catch (e) {} }, [theme]);

  const [t, setTweak] = useTweaks(TWEAK_DEFAULTS);

  const ds = DATASETS[t.state] || DATASETS.mixed;
  const failingCount = summarize(ds.checks).fail;

  return (
    <ThemeCtx.Provider value={{ theme, toggle }}>
      <TweakCtx.Provider value={t}>
        <AppShell failingCount={failingCount}>
          <HealthScreen />
        </AppShell>
        <TweaksPanel>
          <TweakSection label="Demonstração" />
          <TweakRadio label="Estado" value={t.state}
            options={[
              { value: 'mixed', label: 'Misto (herói)' },
              { value: 'ok', label: 'Tudo OK' },
              { value: 'stale', label: 'Desatualizado' },
              { value: 'empty', label: 'Vazio' },
            ]}
            onChange={(v) => setTweak('state', v)} />
          <TweakSection label="Cartão de check" />
          <TweakRadio label="Tratamento" value={t.treatment}
            options={[{ value: 'A', label: 'Destacado' }, { value: 'B', label: 'Compacto' }]}
            onChange={(v) => setTweak('treatment', v)} />
          <TweakSection label="Painel" />
          <TweakToggle label="Histórico (heatmap)" value={t.heatmap !== false}
            onChange={(v) => setTweak('heatmap', v)} />
          <TweakToggle label="Modo escuro" value={theme === 'dark'}
            onChange={(v) => setTheme(v ? 'dark' : 'light')} />
        </TweaksPanel>
      </TweakCtx.Provider>
    </ThemeCtx.Provider>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
