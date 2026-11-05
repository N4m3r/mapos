/** Raiz do app: decide entre setup, login e as telas da área do usuário. */

import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { AppProvider, useApp } from './lib/store';
import { navigate, useRoute } from './lib/router';
import { homePathFor } from './lib/area';
import { AreaTabBar } from './components/AreaTabBar';
import { LoginScreen } from './screens/LoginScreen';
import { HomeScreen } from './screens/HomeScreen';
import { OsListScreen } from './screens/OsListScreen';
import { OsDetailScreen } from './screens/OsDetailScreen';
import { OsFormScreen } from './screens/OsFormScreen';
import { ClientesScreen } from './screens/ClientesScreen';
import { ClienteDetailScreen } from './screens/ClienteDetailScreen';
import { ClienteFormScreen } from './screens/ClienteFormScreen';
import { AgendaScreen } from './screens/AgendaScreen';
import { SettingsScreen } from './screens/SettingsScreen';
import { TecnicoHomeScreen } from './screens/tecnico/TecnicoHomeScreen';
import { TecnicoOsListScreen } from './screens/tecnico/TecnicoOsListScreen';
import { TecnicoAtendimentoScreen } from './screens/tecnico/TecnicoAtendimentoScreen';
import { TecnicoAtividadeScreen } from './screens/tecnico/TecnicoAtividadeScreen';
import { TecnicoNaoRealizadasScreen } from './screens/tecnico/TecnicoNaoRealizadasScreen';
import { TecnicoProjetosScreen } from './screens/tecnico/TecnicoProjetosScreen';
import { TecnicoProjetoScreen } from './screens/tecnico/TecnicoProjetoScreen';
import { TecnicoPontoScreen } from './screens/tecnico/TecnicoPontoScreen';
import { TecnicoCameraConfigScreen } from './screens/tecnico/TecnicoCameraConfigScreen';
import { TecnicoFilaScreen } from './screens/tecnico/TecnicoFilaScreen';
import { TecnicoPerfilScreen } from './screens/tecnico/TecnicoPerfilScreen';
import { ColaboradorHomeScreen } from './screens/colaborador/ColaboradorHomeScreen';
import { ColaboradorPontoScreen } from './screens/colaborador/ColaboradorPontoScreen';
import { ColaboradorEspelhoScreen } from './screens/colaborador/ColaboradorEspelhoScreen';
import { ColaboradorHoleriteScreen } from './screens/colaborador/ColaboradorHoleriteScreen';
import { ColaboradorSolicitacoesScreen } from './screens/colaborador/ColaboradorSolicitacoesScreen';
import { ColaboradorAusenciasScreen } from './screens/colaborador/ColaboradorAusenciasScreen';
import { ColaboradorDadosScreen } from './screens/colaborador/ColaboradorDadosScreen';

/** Telas em que a barra de abas não aparece (formulários de tela cheia). */
const SEM_TABS = new Set([
  'settings',
  'os-new',
  'cliente-new',
  'tec-atendimento',
  'tec-atividade',
  'tec-projeto',
  'tec-ponto',
  'tec-camera',
  'tec-nao-realizadas',
  'col-holerite',
]);

function Shell() {
  const { status, area, can } = useApp();
  const route = useRoute();

  // Um técnico não pode ficar preso numa rota administrativa (e vice-versa).
  useEffect(() => {
    if (status !== 'ready') return;
    const nome = route.name;
    const daAreaTecnico = nome.startsWith('tec-');
    const daAreaColaborador = nome.startsWith('col-');
    const daAreaAdmin = ['home', 'os-list', 'os-new', 'os-detail', 'os-edit', 'clientes', 'cliente-new', 'cliente-detail', 'agenda'].includes(nome);

    if (daAreaTecnico && area !== 'tecnico') navigate(homePathFor(area));
    else if (daAreaColaborador && area === 'tecnico') navigate(homePathFor(area));
    // A área do colaborador exige vínculo de RH: administrador não entra nela.
    else if (daAreaColaborador && area === 'admin') navigate('home');
    else if (daAreaAdmin && area !== 'admin') navigate(homePathFor(area));
  }, [status, area, route]);

  if (status === 'booting') {
    return (
      <div className="screen screen--plain" style={{ display: 'grid', placeItems: 'center' }}>
        <span className="spinner" style={{ width: 28, height: 28 }} />
      </div>
    );
  }

  if (status !== 'ready') {
    return <LoginScreen />;
  }

  let screen: ReactNode;
  switch (route.name) {
    // -------- Administrativo --------
    case 'home':
      screen = <HomeScreen />;
      break;
    case 'os-list':
      screen = <OsListScreen />;
      break;
    case 'os-new':
      screen = <OsFormScreen />;
      break;
    case 'os-detail':
      screen = <OsDetailScreen id={route.id} />;
      break;
    case 'os-edit':
      screen = <OsFormScreen id={route.id} />;
      break;
    case 'clientes':
      screen = <ClientesScreen />;
      break;
    case 'cliente-new':
      screen = <ClienteFormScreen />;
      break;
    case 'cliente-detail':
      screen = <ClienteDetailScreen id={route.id} />;
      break;
    case 'agenda':
      screen = <AgendaScreen />;
      break;
    case 'settings':
      screen = <SettingsScreen />;
      break;

    // -------- Área do técnico --------
    case 'tec-home':
      screen = <TecnicoHomeScreen />;
      break;
    case 'tec-os':
      screen = <TecnicoOsListScreen aba={route.aba} />;
      break;
    case 'tec-atendimento':
      screen = <TecnicoAtendimentoScreen id={route.id} />;
      break;
    case 'tec-atividade':
      screen = <TecnicoAtividadeScreen />;
      break;
    case 'tec-nao-realizadas':
      screen = <TecnicoNaoRealizadasScreen />;
      break;
    case 'tec-projetos':
      screen = <TecnicoProjetosScreen />;
      break;
    case 'tec-projeto':
      screen = <TecnicoProjetoScreen id={route.id} />;
      break;
    case 'tec-ponto':
      screen = <TecnicoPontoScreen obra={route.obra} id={route.id} />;
      break;
    case 'tec-camera':
      screen = <TecnicoCameraConfigScreen id={route.id} />;
      break;
    case 'tec-fila':
      screen = <TecnicoFilaScreen />;
      break;
    case 'tec-perfil':
      screen = <TecnicoPerfilScreen />;
      break;

    // -------- Área do colaborador --------
    case 'col-home':
      screen = <ColaboradorHomeScreen />;
      break;
    case 'col-ponto':
      screen = <ColaboradorPontoScreen />;
      break;
    case 'col-espelho':
      screen = <ColaboradorEspelhoScreen />;
      break;
    case 'col-holerite':
      screen = <ColaboradorHoleriteScreen />;
      break;
    case 'col-solicitacoes':
      screen = <ColaboradorSolicitacoesScreen />;
      break;
    case 'col-ausencias':
      screen = <ColaboradorAusenciasScreen />;
      break;
    case 'col-dados':
      screen = <ColaboradorDadosScreen />;
      break;

    default:
      screen = area === 'tecnico' ? <TecnicoHomeScreen /> : area === 'colaborador' ? <ColaboradorHomeScreen /> : <HomeScreen />;
  }

  const mostrarTabs = !SEM_TABS.has(route.name);

  return (
    <>
      {screen}
      {mostrarTabs ? (
        <AreaTabBar area={area} active={route.name} podeBaterPonto={can('baterPonto')} can={can} />
      ) : null}
    </>
  );
}

export function App() {
  return (
    <AppProvider>
      <Shell />
    </AppProvider>
  );
}

// se a URL entrar sem hash, cai no início
if (!window.location.hash) navigate('home');
