// Prise en main : Michael ; relecture croisée du binôme B.
import { useEffect, useRef, useState } from 'react';
import type { Mode, Page } from './domain/types';
import { AppProvider, useApp } from './state/AppContext';
import { CartSheet } from './components/CartSheet';
import { CookieNotice, Footer, Header } from './components/Layout';
import { State } from './components/Ui';
import { CartShortcut } from './components/CartShortcut';
import { Explore } from './pages/Explore';
import { Auth } from './pages/Auth';
import { Account } from './pages/Account';
import { Orders } from './pages/Orders';
import { Loyalty } from './pages/Loyalty';
import { Staff } from './pages/Staff';
import { Credits } from './pages/Credits';
import { NewRestaurant } from './pages/NewRestaurant';
import { ClientWelcome } from './components/ClientWelcome';
import { workspaceFor } from './domain/roles';

const pages = new Set<Page>([
  'accueil',
  'carte',
  'commandes',
  'fidelite',
  'compte',
  'equipe',
  'credits',
  'administration',
  'service',
  'restaurants',
]);
function currentPage(): Page {
  const value = window.location.hash.slice(1) as Page;
  if (window.location.pathname === '/payment-return' && !value) {
    return 'commandes';
  }
  if (new URLSearchParams(window.location.search).has('token') && !value) {
    return 'compte';
  }
  return pages.has(value) ? value : 'accueil';
}

function Content({ page }: { page: Page }) {
  const app = useApp();
  if (page === 'accueil' || page === 'carte') {
    return <HomeContent page={page} />;
  }
  if (page === 'credits') {
    return <Credits />;
  }
  if (app.authLoading) {
    return <State loading />;
  }
  if (!app.user || app.mode === 'demo') {
    return <Auth />;
  }
  const components = {
    compte: <Account />,
    commandes: <Orders />,
    fidelite: <Loyalty />,
    equipe: <Staff />,
    administration: <Staff workspace="administration" />,
    service: <Staff workspace="service" />,
    restaurants: <NewRestaurant />,
  };
  return <div key={`${app.user.id}:${page}`}>{components[page]}</div>;
}

function HomeContent({ page }: { page: Page }) {
  const app = useApp();
  const workspace = workspaceFor(app.user);
  if (app.authLoading) {
    return <State loading />;
  }
  if (workspace !== 'carte') {
    return <Staff workspace={workspace} />;
  }
  return <>{page === 'accueil' && <ClientWelcome />}<Explore home={page === 'accueil'} /></>;
}

function Shell({ switchMode }: { switchMode: (mode: Mode) => void }) {
  const app = useApp();
  const customer = workspaceFor(app.user) === 'carte';
  const [page, setPage] = useState(currentPage);
  const [cartOpen, setCartOpen] = useState(false);
  const firstNavigation = useRef(true);
  const heading = useRef<HTMLElement>(null);
  useEffect(() => {
    const change = () => setPage(currentPage());
    window.addEventListener('hashchange', change);
    return () => window.removeEventListener('hashchange', change);
  }, []);
  useEffect(() => {
    if (firstNavigation.current) {
      firstNavigation.current = false;
      return;
    }
    window.scrollTo({ top: 0, behavior: 'instant' });
    heading.current?.focus({ preventScroll: true });
  }, [page]);
  function changeMode(mode: Mode) {
    if (mode === app.mode) {
      return;
    }
    if (
      app.cart.lines.length > 0 &&
      !window.confirm('Changer de catalogue vide le panier actuel. Continuer ?')
    ) {
      return;
    }
    switchMode(mode);
  }
  return (
    <>
      <a
        className="skip-link"
        href="#contenu"
        onClick={(event) => {
          event.preventDefault();
          heading.current?.focus();
        }}
      >
        Aller au contenu
      </a>
      <Header page={page} openCart={() => setCartOpen(true)} switchMode={changeMode} />
      <main id="contenu" ref={heading} tabIndex={-1}>
        <Content page={page} />
      </main>
      <Footer />
      {!cartOpen && customer && <CartShortcut open={() => setCartOpen(true)} />}
      <CookieNotice />
      {cartOpen && customer && <CartSheet close={() => setCartOpen(false)} />}
      <output className={`toast ${app.cart.lines.length > 0 ? 'toast-with-cart' : ''}`} aria-live="polite">
        {app.notice}
      </output>
    </>
  );
}

export default function App() {
  const [mode, setMode] = useState<Mode>(() =>
    import.meta.env.VITE_INITIAL_MODE === 'demo' && window.location.pathname !== '/payment-return'
      ? 'demo'
      : 'api',
  );
  return (
    <AppProvider mode={mode} key={mode}>
      <Shell switchMode={setMode} />
    </AppProvider>
  );
}
