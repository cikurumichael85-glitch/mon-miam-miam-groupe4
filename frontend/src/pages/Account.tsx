// Prise en main : Assek ; relecture croisée du binôme B.
import { useState } from 'react';
import type { Claim, ClaimDetail, Paginated, User } from '../domain/types';
import { useApp } from '../state/AppContext';
import { request } from '../services/http';
import { ActionForm } from '../components/Forms';
import { useResource } from '../hooks/useResource';
import { Modal, SectionTitle, State } from '../components/Ui';
import { AccountSecurity } from '../components/AccountSecurity';

function MyClaim({ id, close }: { id: string; close: () => void }) {
  const detail = useResource(`claim:${id}`, (signal) =>
    request<ClaimDetail>(`/me/claims/${id}`, 'GET', undefined, signal),
  );
  return (
    <Modal title={`Réclamation n° ${id}`} close={close}>
      <State loading={detail.loading} error={detail.error} retry={detail.reload} />
      {detail.data && (
        <>
          <h3>{detail.data.data.subject}</h3>
          <p>{detail.data.data.message}</p>
          <h3>Réponses approuvées</h3>
          {detail.data.replies.length === 0 && (
            <p>Votre demande attend une réponse approuvée par la direction.</p>
          )}
          {detail.data.replies.map((reply) => (
            <blockquote className="panel" key={reply.id}>
              {reply.body}
            </blockquote>
          ))}
        </>
      )}
    </Modal>
  );
}

function MyClaims() {
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState<string | null>(null);
  const claims = useResource(`my-claims:${page}`, (signal) =>
    request<Paginated<Claim>>(`/me/claims?page=${page}`, 'GET', undefined, signal),
  );
  return (
    <div className="panel">
      <h3>Mes réclamations</h3>
      <State
        loading={claims.loading}
        error={claims.error}
        retry={claims.reload}
        empty={claims.data?.data.length === 0 ? 'Aucune réclamation.' : undefined}
      />
      {claims.data?.data.map((row) => (
        <button className="list-button" key={row.id} onClick={() => setSelected(String(row.id))}>
          <span>{row.subject}</span>
          <small>{row.status}</small>
        </button>
      ))}
      <div className="pagination">
        <button disabled={page === 1} onClick={() => setPage(page - 1)}>
          Précédent
        </button>
        <span>{page}</span>
        <button disabled={page >= (claims.data?.last_page ?? 1)} onClick={() => setPage(page + 1)}>
          Suivant
        </button>
      </div>
      {selected && <MyClaim id={selected} close={() => setSelected(null)} />}
    </div>
  );
}

export function Account() {
  const app = useApp();
  const user = app.user;
  if (!user) {
    return null;
  }
  return (
    <section className="section">
      <SectionTitle eyebrow="VOTRE PLACE À TABLE" title={`Bonjour, ${user.name}.`} />
      <div className="two-columns">
        <div className="panel">
          <h3>Mes informations</h3>
          <p>{user.email}</p>
          <ActionForm
            key={String(user.id)}
            fields={[
              { name: 'name', label: 'Nom complet', value: user.name, maxLength: 150 },
              { name: 'phone', label: 'Téléphone', type: 'tel', value: user.phone, maxLength: 32 },
              {
                name: 'delivery_address',
                label: 'Adresse de livraison',
                value: user.delivery_address ?? '',
                required: false,
              },
              {
                name: 'public_alias',
                label: 'Pseudonyme public (si classement activé)',
                value: user.public_alias ?? '',
                required: false,
                maxLength: 50,
              },
              {
                name: 'ranking_opt_in',
                label: 'Apparaître au classement public',
                value: String(user.ranking_opt_in),
                options: [
                  { value: 'false', label: 'Non, rester privé' },
                  { value: 'true', label: 'Oui, avec mon pseudonyme' },
                ],
              },
            ]}
            submit="Enregistrer mon profil"
            action={async (values) => {
              const result = await request<{ data: User }>('/me/profile', 'PATCH', {
                ...values,
                ranking_opt_in: values.ranking_opt_in === 'true',
                analytics_consent: false,
              });
              app.setUser({ ...user, ...result.data });
              return 'Votre profil a été mis à jour.';
            }}
          />
        </div>
        <div>
          <AccountSecurity />
          <MyClaims />
          <details className="panel">
            <summary>Mes données personnelles</summary>
            <p>
              Aucun outil publicitaire ou de mesure d’audience n’est chargé par cette interface. Le
              compte et les commandes sont traités par le serveur pour fournir le service. Les
              durées et mentions légales restent à approuver avant production.
            </p>
            <h3>Exporter mes données</h3>
            <ActionForm
              fields={[{ name: 'password', label: 'Mot de passe actuel', type: 'password' }]}
              submit="Télécharger mes données"
              action={async (values) => {
                const payload = await request('/me/data-export', 'POST', values);
                const url = URL.createObjectURL(
                  new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' }),
                );
                const anchor = document.createElement('a');
                anchor.href = url;
                anchor.download = 'mon-miam-miam-mes-donnees.json';
                anchor.click();
                window.setTimeout(() => URL.revokeObjectURL(url), 1000);
                return 'Export préparé pour le téléchargement.';
              }}
            />
            <h3>Demander l’effacement</h3>
            <p>
              Une demande est instruite par un responsable. Elle n’entraîne pas une suppression
              immédiate de toutes les données.
            </p>
            <ActionForm
              fields={[{ name: 'password', label: 'Mot de passe actuel', type: 'password' }]}
              submit="Envoyer ma demande d’effacement"
              confirmation="Confirmer l’envoi de votre demande d’effacement au responsable ?"
              action={async (values) => {
                const result = await request<{ message: string }>(
                  '/me/erasure-requests',
                  'POST',
                  values,
                );
                return result.message;
              }}
            />
          </details>
        </div>
      </div>
    </section>
  );
}
