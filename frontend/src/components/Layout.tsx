// Prise en main : Michael ; relecture croisée du binôme B.
import { useEffect, useState } from 'react';
import type { Mode, Page, User } from '../domain/types';
import { workspaceFor } from '../domain/roles';
import { useApp } from '../state/AppContext';
import { Icon } from './Ui';
import { messageOf } from '../services/http';

const links: { page: Page; label: string }[] = [
  { page: 'accueil', label: 'Découvrir' },
  { page: 'carte', label: 'Commander' },
  { page: 'commandes', label: 'Mes commandes' },
  { page: 'fidelite', label: 'Mes privilèges' },
];

function navigationFor(user: User | null): { page: Page; label: string }[] {
  const workspace = workspaceFor(user);
  if (workspace === 'administration') {
    return [{ page: 'administration', label: 'Mon restaurant' }, { page: 'compte', label: 'Mon compte' }];
  }
  if (workspace === 'service') {
    return [{ page: 'service', label: 'Mon service' }, { page: 'compte', label: 'Mon compte' }];
  }
  return links;
}

export function Header({
  page,
  openCart,
  switchMode,
}: {
  page: Page;
  openCart: () => void;
  switchMode: (mode: Mode) => void;
}) {
  const { user, mode } = useApp();
  const [menuOpen, setMenuOpen] = useState(false);
  const workspace = workspaceFor(user);
  return (
    <>
      <div className="topline">
        <span>ULC-ICAM · RDC</span>
        <span>Le goût du partage, en francs congolais.</span>
        <label>
          Catalogue{' '}
          <select
            aria-label="Mode du catalogue"
            value={mode}
            onChange={(event) => switchMode(event.target.value as Mode)}
          >
            <option value="demo">Démonstration</option>
            <option value="api">Restaurants connectés</option>
          </select>
        </label>
      </div>
      <header className="header">
        <a className="brand" href={user ? `#${workspace}` : '#accueil'} aria-label="Mon Miam Miam, accueil">
          <span className="brand-mark">
            m<span>·</span>
          </span>
          <span>
            mon miam miam<small>LES SAVEURS D’ICI</small>
          </span>
        </a>
        <button
          className="menu-toggle secondary button"
          aria-expanded={menuOpen}
          aria-controls="main-navigation"
          onClick={() => setMenuOpen(!menuOpen)}
        >
          Menu
        </button>
        <nav id="main-navigation" aria-label="Navigation principale" className={menuOpen ? 'nav expanded' : 'nav'}>
          {navigationFor(user).map((link) => (
            <a
              key={link.page}
              href={`#${link.page}`}
              aria-current={page === link.page ? 'page' : undefined}
              onClick={() => setMenuOpen(false)}
            >
              {link.label}
            </a>
          ))}
        </nav>
        <HeaderActions openCart={openCart} />
      </header>
      <SessionBanner />
    </>
  );
}

function HeaderActions({ openCart }: { openCart: () => void }) {
  const { user, cart } = useApp();
  const count = cart.lines.reduce((sum, line) => sum + line.quantity, 0);
  return (
    <div className="header-actions">
      {!user && <a className="restaurant-entry" href="#equipe">Accès professionnel</a>}
      <a className="account-link" href="#compte" aria-label={user ? 'Ouvrir mon compte' : 'Se connecter'}><Icon name="user" /><span>{user ? user.name.split(' ')[0] : 'Se connecter'}</span></a>
      {workspaceFor(user) === 'carte' && <button className="cart-button" aria-label={`Mon panier (${count})`} onClick={openCart}><Icon name="bag" /><span>Mon panier</span><b>{count}</b></button>}
    </div>
  );
}

function SessionBanner() {
  const { user, mode, logout, notify } = useApp();
  const workspace = workspaceFor(user);
  if (mode === 'demo') {
    return <div className="demo-strip">Carte de démonstration · Restaurants fictifs et prix indicatifs · Aucune commande réelle</div>;
  }
  if (!user) {
    return <div className="session-bar"><span>Client : choisissez vos plats puis ouvrez le panier.</span><a href="#compte">Connexion / Inscription client</a><a href="#equipe">Connexion administrateur, serveur ou gérant</a></div>;
  }
  return (
    <div className="session-bar">
      <span>Bonjour, {user.name}</span>
      {workspace !== 'carte' && <a href={`#${workspace}`}>{workspace === 'service' ? 'Espace serveur' : 'Espace administrateur'}</a>}
      <button onClick={() => void logout().catch((error: unknown) => notify(messageOf(error)))}>Se déconnecter</button>
    </div>
  );
}

export function Footer() {
  return (
    <footer className="footer">
      <div>
        <a className="footer-brand" href="#accueil">
          mon miam miam<span>Un bon repas. Un vrai moment.</span>
        </a>
        <p>
          Les saveurs du Congo et les envies du monde,
          <br />
          réunies autour de la même table.
        </p>
      </div>
      <div>
        <h3>À votre service</h3>
        <a href="#carte">Découvrir la carte</a>
        <a href="#commandes">Suivre ma commande</a>
        <a href="#compte">Mon compte et mes données</a>
      </div>
      <div>
        <h3>Le projet</h3>
        <p>
          Groupe 4 · ULC-ICAM
          <br />
          République démocratique du Congo
        </p>
        <a href="#credits">Crédits photos et informations</a>
        <button
          className="text-button"
          onClick={() => window.dispatchEvent(new Event('mmm:cookies'))}
        >
          Préférences cookies
        </button>
      </div>
      <div className="footer-bottom">
        <span>Mon Miam Miam · 2026</span>
        <span>Les bons moments se partagent.</span>
        <span>CDF / FC</span>
      </div>
    </footer>
  );
}

export function CookieNotice() {
  const [visible, setVisible] = useState(() => {
    try {
      return localStorage.getItem('mmm-cookie-choice') === null;
    } catch {
      return true;
    }
  });
  useEffect(() => {
    const show = () => setVisible(true);
    window.addEventListener('mmm:cookies', show);
    return () => window.removeEventListener('mmm:cookies', show);
  }, []);
  function save(choice: string) {
    try {
      localStorage.setItem('mmm-cookie-choice', choice);
    } catch {
      // Le refus de stockage ne bloque pas la navigation.
    }
    setVisible(false);
  }
  if (!visible) {
    return null;
  }
  return (
    <aside className="cookie-notice" aria-label="Informations sur les cookies">
      <div>
        <strong>Votre pause, votre choix.</strong>
        <p>
          La connexion utilise des cookies nécessaires. Aucun outil de mesure ni cookie publicitaire
          n’est chargé. Votre choix est conservé sur ce navigateur.
        </p>
      </div>
      <button className="button secondary" onClick={() => save('necessary')}>
        Nécessaires uniquement
      </button>
      <button className="button" onClick={() => save('understood')}>
        J’ai compris
      </button>
    </aside>
  );
}
