/* global React */
// =====================================================================
// Health plugin — dados de exemplo (laravel-health)
// Estado herói = "misto". Derivamos "tudo ok" e "vazio" daqui; "stale" usa
// os mesmos dados (só a frescura muda). 30 dias de histórico por check.
// =====================================================================

// histórico: 30 dias, índice 0 = hoje. overrides keyed por "dias atrás".
function hist(base, overrides) {
  const a = Array(30).fill(base);
  Object.entries(overrides || {}).forEach(([k, v]) => { a[29 - Number(k)] = v; });
  return a;
}

const CHECKS_MIXED = [
  {
    id: 'cpu', label: 'Carga de CPU', icon: 'cpu', status: 'warning',
    summary: 'Carga média em 78% nos últimos 5 min.',
    okSummary: 'Carga média em 31% — dentro do limite.',
    lastRan: 'há 1 min',
    meta: { 'Carga (1 min)': '3,9', 'Carga (5 min)': '3,1', 'Núcleos': '4', 'Limite de atenção': '70%', 'Limite de falha': '90%' },
    message: 'A carga média de CPU está em 78% (limite de atenção: 70%). Picos recorrentes podem indicar processo travado ou fila acumulada.',
    actions: [{ label: 'Re-rodar', kind: 'rerun' }],
    history: hist('ok', { 0: 'warning', 1: 'warning', 4: 'warning', 9: 'warning', 12: 'failed', 13: 'warning' }),
  },
  {
    id: 'disk', label: 'Espaço em disco', icon: 'server', status: 'ok',
    summary: '412 GB livres de 1 TB (59% em uso).',
    okSummary: '412 GB livres de 1 TB (59% em uso).',
    lastRan: 'há 1 min',
    meta: { 'Em uso': '588 GB', 'Disponível': '412 GB', 'Total': '1 TB', 'Ponto de montagem': '/', 'Limite de atenção': '80%' },
    message: 'O volume raiz está com 59% de uso, abaixo do limite de atenção de 80%.',
    actions: [{ label: 'Re-rodar', kind: 'rerun' }],
    history: hist('ok', { 19: 'warning' }),
  },
  {
    id: 'db', label: 'Banco de dados (sqlsrv)', icon: 'database', status: 'ok',
    summary: 'Conexão respondeu em 14 ms.',
    okSummary: 'Conexão respondeu em 14 ms.',
    lastRan: 'há 1 min',
    meta: { 'Driver': 'sqlsrv', 'Host': 'db-prod-01', 'Latência': '14 ms', 'Conexões ativas': '23 / 200' },
    message: 'A conexão padrão respondeu ao SELECT 1 em 14 ms.',
    actions: [{ label: 'Re-rodar', kind: 'rerun' }],
    history: hist('ok', { 26: 'skipped' }),
  },
  {
    id: 'redis', label: 'Redis', icon: 'bolt', status: 'ok',
    summary: 'Respondeu ao PING em 2 ms.',
    okSummary: 'Respondeu ao PING em 2 ms.',
    lastRan: 'há 1 min',
    meta: { 'Host': 'redis-cache-01', 'Latência (PING)': '2 ms', 'Memória usada': '1,2 GB / 4 GB', 'Conexões': '48' },
    message: 'O servidor Redis respondeu ao PING em 2 ms.',
    actions: [{ label: 'Re-rodar', kind: 'rerun' }],
    history: hist('ok', {}),
  },
  {
    id: 'horizon', label: 'Horizon', icon: 'queue', status: 'failed',
    summary: 'Supervisor não está em execução.',
    okSummary: 'Ativo · 4 supervisores em execução.',
    lastRan: 'há 1 min',
    meta: { 'Status': 'inactive', 'Supervisores': '0 / 4', 'Última atividade': 'há 14 min', 'Ambiente': 'production' },
    message: 'O Horizon está marcado como inativo. Nenhum supervisor de fila foi detectado nos últimos 14 minutos — jobs podem estar acumulando.',
    actions: [{ label: 'Re-rodar', kind: 'rerun' }, { label: 'Reiniciar Horizon', kind: 'fix' }],
    history: hist('ok', { 0: 'failed', 1: 'failed', 2: 'failed', 3: 'warning', 11: 'warning' }),
  },
  {
    id: 'stale', label: 'Classes obsoletas', icon: 'wrench', status: 'warning',
    summary: '7 referências de classe desatualizadas no banco.',
    okSummary: 'Nenhuma referência de classe obsoleta encontrada.',
    lastRan: 'há 4 min',
    meta: { 'Referências': '7', 'Tabelas afetadas': '3', 'Auditoria': 'morphMap + polimórficos', 'Corrigíveis automaticamente': '5 de 7' },
    message: 'Foram encontradas 7 referências a classes que não existem mais (renomeadas ou removidas). Linhas com classe obsoleta quebram relacionamentos polimórficos. 5 das 7 têm sugestão de correção automática.',
    actions: [{ label: 'Re-rodar auditoria', kind: 'rerun' }, { label: 'Aplicar correções', kind: 'fix' }],
    history: hist('ok', { 0: 'warning', 2: 'warning', 7: 'warning', 8: 'warning', 15: 'warning', 16: 'warning' }),
    dataTable: {
      columns: ['Tabela', 'Coluna', 'Valor', 'Status', 'Sugestão'],
      rows: [
        { c: ['activity_log', 'subject_type', 'App\\Models\\Funcionario', 'obsoleta', 'App\\Models\\Servidor'], status: 'failed', fixable: true },
        { c: ['notifications', 'notifiable_type', 'App\\User', 'obsoleta', 'App\\Models\\User'], status: 'failed', fixable: true },
        { c: ['media', 'model_type', 'App\\Models\\Anexo', 'obsoleta', 'App\\Models\\Documento'], status: 'failed', fixable: true },
        { c: ['activity_log', 'causer_type', 'App\\Admin', 'obsoleta', 'App\\Models\\User'], status: 'failed', fixable: true },
        { c: ['taggables', 'taggable_type', 'App\\Models\\Post', 'obsoleta', 'App\\Models\\Artigo'], status: 'failed', fixable: true },
        { c: ['comments', 'commentable_type', 'App\\Models\\Ticket', 'sem sugestão', 'Revisar manualmente', ], status: 'warning', fixable: false },
        { c: ['audits', 'auditable_type', 'App\\Legacy\\Order', 'sem sugestão', 'Revisar manualmente'], status: 'warning', fixable: false },
      ],
    },
  },
  {
    id: 'schedule', label: 'Agendador', icon: 'calendar', status: 'ok',
    summary: 'Último heartbeat há 38 s.',
    okSummary: 'Último heartbeat há 38 s.',
    lastRan: 'há 1 min',
    meta: { 'Último heartbeat': 'há 38 s', 'Frequência esperada': 'a cada 1 min', 'Tarefas registradas': '17' },
    message: 'O agendador (schedule:run) registrou heartbeat há 38 segundos, dentro da janela esperada.',
    actions: [{ label: 'Re-rodar', kind: 'rerun' }],
    history: hist('ok', { 18: 'failed', 19: 'warning' }),
  },
  {
    id: 'backups', label: 'Backups', icon: 'archive', status: 'warning',
    summary: 'Último backup íntegro há 2 dias.',
    okSummary: 'Último backup íntegro há 6 h.',
    lastRan: 'há 6 min',
    meta: { 'Último backup': 'há 2 dias', 'Tamanho': '4,8 GB', 'Destino': 's3://crcmg-backups', 'Frequência esperada': 'diária', 'Cópias retidas': '7' },
    message: 'O backup mais recente tem 2 dias — acima da janela diária esperada. Verifique se a tarefa backup:run está sendo disparada pelo agendador.',
    actions: [{ label: 'Re-rodar', kind: 'rerun' }, { label: 'Rodar backup agora', kind: 'fix' }],
    history: hist('ok', { 0: 'warning', 1: 'warning', 5: 'failed', 6: 'warning' }),
  },
  {
    id: 'debug', label: 'Modo de depuração', icon: 'shield', status: 'ok',
    summary: 'APP_DEBUG desativado em produção.',
    okSummary: 'APP_DEBUG desativado em produção.',
    lastRan: 'há 1 min',
    meta: { 'APP_DEBUG': 'false', 'APP_ENV': 'production', 'Recomendado': 'false em produção' },
    message: 'O modo de depuração está corretamente desativado no ambiente de produção.',
    actions: [{ label: 'Re-rodar', kind: 'rerun' }],
    history: hist('ok', {}),
  },
  {
    id: 'queues', label: 'Filas', icon: 'squares', status: 'skipped',
    summary: 'Verificação ignorada neste ambiente.',
    okSummary: 'Fila padrão com 0 jobs pendentes.',
    lastRan: 'há 1 min',
    meta: { 'Motivo': 'desabilitado em production', 'Conexão': 'redis', 'Jobs pendentes': '—' },
    message: 'Esta verificação foi ignorada porque está desabilitada para o ambiente atual.',
    actions: [{ label: 'Re-rodar', kind: 'rerun' }],
    history: hist('skipped', { 1: 'ok', 2: 'ok', 8: 'ok', 14: 'ok', 20: 'ok' }),
  },
];

// "Tudo ok" — deriva do misto: tudo verde, sumário saudável, histórico limpo.
const CHECKS_OK = CHECKS_MIXED.map((c) => ({
  ...c, status: 'ok', summary: c.okSummary, dataTable: undefined,
  history: hist('ok', {}),
  actions: c.actions.filter((a) => a.kind === 'rerun'),
}));

// Sparklines dos stat cards (30 dias)
const STAT_SPARK = {
  total: [10,10,10,10,9,10,10,10,10,10,10,10,10,10,9,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10],
  ok:    [9,9,8,9,9,9,8,9,9,9,9,8,7,9,9,9,8,9,9,8,9,9,9,8,9,8,7,6,6,5],
  warn:  [1,0,1,1,0,1,1,1,0,1,0,1,2,1,0,1,1,0,1,2,1,1,0,1,1,2,2,3,2,3],
  fail:  [0,0,1,0,0,0,1,0,0,0,1,1,0,0,0,0,1,0,0,0,0,0,0,1,0,1,1,1,1,1],
};

const DATASETS = {
  mixed: { checks: CHECKS_MIXED, lastRanMins: 3,  stale: false },
  ok:    { checks: CHECKS_OK,    lastRanMins: 1,  stale: false },
  stale: { checks: CHECKS_MIXED, lastRanMins: 14, stale: true  },
  empty: { checks: [],           lastRanMins: 0,  stale: false },
};

function summarize(checks) {
  const c = { total: checks.length, ok: 0, warn: 0, fail: 0, skip: 0 };
  checks.forEach((k) => {
    const key = (window.st ? window.st(k.status).key : k.status);
    if (key === 'ok') c.ok++; else if (key === 'warn') c.warn++;
    else if (key === 'fail') c.fail++; else c.skip++;
  });
  return c;
}

Object.assign(window, { CHECKS_MIXED, CHECKS_OK, DATASETS, STAT_SPARK, summarize, hist });
