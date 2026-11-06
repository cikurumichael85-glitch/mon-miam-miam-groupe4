// Prise en main : Assek ; relecture croisée du binôme B.
import { useState } from 'react';
import type { ReactNode } from 'react';
import type { Membership } from '../domain/types';
import { activeMemberships, canAddRestaurant, firstRestaurant, roleLabels, workspaceFor } from '../domain/roles';
import { useApp } from '../state/AppContext';
import { SectionTitle } from '../components/Ui';
import { StaffOrders } from './staff/StaffOrders';
import { StaffCatalogue } from './staff/StaffCatalogue';
import { StaffClaims } from './staff/StaffClaims';
import { StaffCampaigns, StaffStatistics, StaffTeam } from './staff/Management';
import { StaffSettings } from './staff/RestaurantSettings';
import { Platform } from './staff/Platform';
import { StaffLoyalty } from './staff/StaffLoyalty';

const operational = [
  { id: 'orders', label: 'Commandes' },
  { id: 'catalogue', label: 'Carte' },
  { id: 'claims', label: 'Réclamations' },
  { id: 'statistics', label: 'Statistiques' },
];
const management = [
  { id: 'loyalty', label: 'Fidélité' },
  { id: 'team', label: 'Équipe' },
  { id: 'campaigns', label: 'Affiches' },
];

function isManager(membership?: Membership): boolean {
  return membership?.role === 'manager' || membership?.role === 'restaurant_admin';
}

function tabsFor(membership?: Membership) {
  const tabs = isManager(membership) ? [...operational, ...management] : [...operational];
  if (membership?.role === 'restaurant_admin') {
    tabs.push({ id: 'settings', label: 'Mon restaurant & horaires' });
  }
  return tabs;
}

function AccessDenied() {
  const { user } = useApp();
  return (
    <section className="section">
      <SectionTitle eyebrow="ESPACE RESTAURANT" title="Votre accès professionnel." />
      <p className="info-box">
        Votre compte ne possède pas le rôle nécessaire pour cet espace. Utilisez votre espace
        personnel ou demandez au responsable le rattachement approprié. L’inscription seule crée un compte client.
      </p>
      <a className="button" href={`#${workspaceFor(user)}`}>Ouvrir mon espace</a>
    </section>
  );
}

function StaffToolbar({ memberships, selected, select, platform, openPlatform }: {
  memberships: Membership[];
  selected: string;
  select: (id: string) => void;
  platform: boolean;
  openPlatform: () => void;
}) {
  return (
    <div className="staff-toolbar">
      <label className="field">
        <span>Restaurant de travail</span>
        <select value={selected} onChange={(event) => select(event.target.value)}>
          {memberships.map((row) => (
            <option key={row.id} value={String(row.restaurant_id)}>
              Restaurant n° {row.restaurant_id} · {roleLabels[row.role]}
            </option>
          ))}
        </select>
      </label>
      {platform && <button className="button secondary" onClick={openPlatform}>Administration plateforme</button>}
    </div>
  );
}

function WorkspaceGuide({ membership }: { membership?: Membership }) {
  const message = membership?.role === 'restaurant_admin'
    ? 'Administrateur : gérez la carte, le nom, les horaires et les accès des gérants de votre restaurant.'
    : 'Serveur : recevez les commandes, confirmez leur prise en charge puis suivez la préparation, le service sur place ou la livraison.';
  return <p className="info-box">{message}</p>;
}

function Workspace({ tab, membership }: { tab: string; membership?: Membership }) {
  if (tab === 'platform') {
    return <Platform />;
  }
  if (!membership) {
    return (
      <p className="info-box">
        Aucun rattachement actif. Demandez au responsable de vous attribuer l’accès approprié.
      </p>
    );
  }
  const restaurantId = String(membership.restaurant_id);
  const manager = isManager(membership);
  const administrator = membership.role === 'restaurant_admin';
  const screens = {
    orders: <StaffOrders restaurantId={restaurantId} manager={manager} />,
    catalogue: <StaffCatalogue restaurantId={restaurantId} manager={manager} />,
    claims: <StaffClaims restaurantId={restaurantId} manager={manager} />,
    statistics: <StaffStatistics restaurantId={restaurantId} manager={manager} />,
  };
  const managementScreens = {
    loyalty: <StaffLoyalty restaurantId={restaurantId} />,
    team: <StaffTeam restaurantId={restaurantId} administrator={administrator} />,
    campaigns: <StaffCampaigns restaurantId={restaurantId} />,
    ...(administrator ? { settings: <StaffSettings restaurantId={restaurantId} /> } : {}),
  };
  const permitted: Record<string, ReactNode> = manager
    ? { ...screens, ...managementScreens }
    : screens;
  return permitted[tab] ?? <p>Choisissez une rubrique accessible.</p>;
}

export function Staff({ workspace }: { workspace?: 'administration' | 'service' }) {
  const app = useApp();
  const view = workspace ?? (workspaceFor(app.user) === 'service' ? 'service' : 'administration');
  const memberships = activeMemberships(app.user).filter((row) =>
    view === 'service' ? row.role === 'employee' : row.role !== 'employee',
  );
  const [restaurantId, setRestaurantId] = useState(() => firstRestaurant(memberships, app.restaurant?.id));
  const [tab, setTab] = useState('orders');
  const membership = memberships.find((row) => String(row.restaurant_id) === restaurantId);
  const tabs = tabsFor(membership);
  const platform = app.user?.platform_access === true;
  if (!membership && !platform) {
    return <AccessDenied />;
  }
  return (
    <section className={`section workspace-${view}`}>
      <WorkspaceHeading service={view === 'service'} create={canAddRestaurant(app.user)} administrator={membership?.role === 'restaurant_admin'} select={setTab} />
      <StaffToolbar memberships={memberships} selected={restaurantId} platform={platform} openPlatform={() => setTab('platform')} select={(id) => {
        setRestaurantId(id);
        setTab('orders');
      }} />
      <p className="context-strip">Contexte : restaurant n° {restaurantId || 'non attribué'}.</p>
      <WorkspaceGuide membership={membership} />
      <div className="category-tabs">
        {tabs.map((item) => (
          <button
            key={item.id}
            className={tab === item.id ? 'active' : ''}
            onClick={() => setTab(item.id)}
          >
            {item.label}
          </button>
        ))}
      </div>
      <div key={`${restaurantId}:${tab}`}>
        <Workspace tab={tab} membership={membership} />
      </div>
    </section>
  );
}

function WorkspaceHeading({ service, create, administrator, select }: { service: boolean; create: boolean; administrator: boolean; select: (tab: string) => void }) {
  return (
    <>
      <SectionTitle eyebrow={service ? 'ESPACE SERVEUR' : 'ESPACE ADMINISTRATEUR'} title={service ? 'Votre service, en un regard.' : 'Pilotez votre restaurant.'} />
      <WorkspaceOverview service={service} administrator={administrator} select={select} />
      {!service && create && <a className="button add-restaurant" href="#restaurants">Ajouter un restaurant</a>}
    </>
  );
}

function WorkspaceOverview({ service, administrator, select }: { service: boolean; administrator: boolean; select: (tab: string) => void }) {
  const shortcuts = service
    ? [
        { tab: 'orders', title: 'Recevoir et prendre en charge', text: 'Consultez les nouvelles commandes et acceptez celles que le restaurant peut préparer.' },
        { tab: 'orders', title: 'Préparer et remettre au client', text: 'Signalez la préparation, le départ en livraison puis la remise effective.' },
        { tab: 'catalogue', title: 'Informer sur les disponibilités', text: 'Indiquez les plats épuisés et le plat du jour, selon vos droits.' },
      ]
    : [
        { tab: 'orders', title: 'Superviser les commandes', text: 'Suivez les commandes et les actions de votre équipe.' },
        { tab: 'catalogue', title: 'Composer la carte', text: 'Ajoutez les plats et boissons, modifiez les prix ou archivez un article.' },
        administrator
          ? { tab: 'settings', title: 'Personnaliser le restaurant', text: 'Gérez le nom, les horaires et les conditions de livraison.' }
          : { tab: 'team', title: 'Organiser l’équipe', text: 'Organisez le service et les accès des employés.' },
      ];
  return (
    <div className="workspace-overview" aria-label={service ? 'Actions de service' : 'Actions de gestion'}>
      {shortcuts.map((shortcut) => (
        <button key={shortcut.title} className="workspace-shortcut" onClick={() => select(shortcut.tab)}>
          <strong>{shortcut.title}</strong><span>{shortcut.text}</span>
        </button>
      ))}
    </div>
  );
}
