// Prise en main : Michael ; relecture croisée du binôme B.
import { useState } from 'react';
import type { User } from '../domain/types';
import { workspaceFor } from '../domain/roles';
import { refreshCsrf, request } from '../services/http';
import { useApp } from '../state/AppContext';
import { ActionForm } from '../components/Forms';
import type { FieldSpec } from '../components/Forms';

type AuthView = 'login' | 'register' | 'forgot' | 'reset';
const email: FieldSpec = { name: 'email', label: 'Adresse e-mail', type: 'email', maxLength: 255 };
const password: FieldSpec = {
  name: 'password',
  label: 'Mot de passe',
  type: 'password',
  maxLength: 72,
};
const fields: Record<AuthView, FieldSpec[]> = {
  login: [email, password],
  register: [
    { name: 'name', label: 'Nom complet', maxLength: 150 },
    email,
    { name: 'phone', label: 'Téléphone (+243…)', type: 'tel', maxLength: 32 },
    { name: 'delivery_address', label: 'Adresse de livraison (facultative)', required: false },
    password,
    {
      name: 'password_confirmation',
      label: 'Confirmer le mot de passe',
      type: 'password',
      maxLength: 72,
    },
  ],
  forgot: [email],
  reset: [
    email,
    { name: 'token', label: 'Jeton reçu dans le lien de récupération' },
    password,
    {
      name: 'password_confirmation',
      label: 'Confirmer le mot de passe',
      type: 'password',
      maxLength: 72,
    },
  ],
};
const labels: Record<AuthView, string> = {
  login: 'Me connecter',
  register: 'Créer mon compte',
  forgot: 'Recevoir un lien',
  reset: 'Changer le mot de passe',
};
const endpoints: Record<AuthView, string> = {
  login: 'login',
  register: 'register',
  forgot: 'forgot-password',
  reset: 'reset-password',
};

export function Auth() {
  const app = useApp();
  const params = new URLSearchParams(window.location.search);
  const [view, setView] = useState<AuthView>(params.has('token') ? 'reset' : 'login');
  const initialFields = fields[view].map((field) => ({
    ...field,
    value: ['token', 'email'].includes(field.name)
      ? (params.get(field.name) ?? undefined)
      : undefined,
  }));
  return (
    <section className="auth-layout section">
      <div className="auth-story">
        <img src="/images/fish.webp" alt="Poisson grillé, citron et légumes" />
        <div>
          <p className="eyebrow">BIENVENUE À TABLE</p>
          <h1>
            Les bons moments
            <br />
            commencent <em>ici.</em>
          </h1>
          <p>
            Un compte pour commander, retrouver vos envies et profiter des programmes de vos
            restaurants.
          </p>
        </div>
      </div>
      <div className="auth-form">
        <p className="eyebrow">VOTRE ESPACE</p>
        <p>
          Clients, administrateurs et gérants utilisent leur adresse e-mail et leur mot de passe
          personnel. Les droits sont attribués au compte par le restaurant ; ils ne se choisissent
          pas à l’inscription.
        </p>
        <h2>{view === 'login' ? 'Heureux de vous retrouver.' : labels[view]}</h2>
        {app.mode === 'demo' ? (
          <div className="info-box">
            <p>
              La carte de démonstration ne crée aucun compte. Sélectionnez « Restaurants connectés »
              en haut de la page pour utiliser votre vrai compte.
            </p>
          </div>
        ) : (
          <>
            <div className="category-tabs">
              {(['login', 'register'] as AuthView[]).map((tab) => (
                <button
                  key={tab}
                  className={view === tab ? 'active' : ''}
                  onClick={() => setView(tab)}
                >
                  {labels[tab]}
                </button>
              ))}
            </div>
            <ActionForm
              key={view}
              fields={initialFields}
              submit={labels[view]}
              action={async (values) => {
                const result = await request<{ data?: User; message?: string }>(
                  `/auth/${endpoints[view]}`,
                  'POST',
                  values,
                );
                if (result.data) {
                  app.setUser(result.data);
                  await refreshCsrf();
                  // L'inscription ne charge pas les rattachements ; /me est la source des rôles.
                  const profile = await request<{ data: User }>('/me');
                  app.setUser(profile.data);
                  window.location.hash = workspaceFor(profile.data);
                }
                return result.message;
              }}
            />
            <p className="small-note">
              À l’inscription : au moins 5 caractères, majuscule, minuscule et chiffre ; 72 octets
              maximum. La validation définitive est faite par le serveur.
            </p>
            <div className="inline-actions">
              <button className="text-button" onClick={() => setView('forgot')}>
                Mot de passe oublié ?
              </button>
              <button className="text-button" onClick={() => setView('reset')}>
                J’ai reçu un lien de récupération
              </button>
            </div>
          </>
        )}
      </div>
    </section>
  );
}
