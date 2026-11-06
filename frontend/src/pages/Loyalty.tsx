// Prise en main : Assek ; relecture croisée du binôme B.
import { useState } from 'react';
import type { Identifier, LoyaltyAccount, Paginated } from '../domain/types';
import { useApp } from '../state/AppContext';
import { request } from '../services/http';
import { useResource } from '../hooks/useResource';
import { ActionForm, useAction } from '../components/Forms';
import { Modal, SectionTitle, State } from '../components/Ui';

function Ledger({ id, close }: { id: string; close: () => void }) {
  const [page, setPage] = useState(1);
  const entries = useResource(`ledger:${id}:${page}`, (signal) =>
    request<Paginated<{ id: Identifier; delta: number; kind: string; occurred_at: string }>>(
      `/me/loyalty-accounts/${id}/entries?page=${page}`,
      'GET',
      undefined,
      signal,
    ),
  );
  return (
    <Modal title="Votre journal de fidélité" close={close}>
      <State
        loading={entries.loading}
        error={entries.error}
        retry={entries.reload}
        empty={entries.data?.data.length === 0 ? 'Aucun mouvement.' : undefined}
      />
      <ul className="plain-list">
        {entries.data?.data.map((entry) => (
          <li key={entry.id}>
            <span>
              {entry.kind}
              <small>{new Date(entry.occurred_at).toLocaleDateString('fr-CD')}</small>
            </span>
            <strong>
              {entry.delta > 0 ? '+' : ''}
              {entry.delta} points
            </strong>
          </li>
        ))}
      </ul>
      <div className="pagination">
        <button disabled={page === 1} onClick={() => setPage(page - 1)}>
          Précédent
        </button>
        <span>{page}</span>
        <button disabled={page >= (entries.data?.last_page ?? 1)} onClick={() => setPage(page + 1)}>
          Suivant
        </button>
      </div>
    </Modal>
  );
}

function ReferralCode() {
  const app = useApp();
  const [restaurantId, setRestaurantId] = useState(app.restaurant?.id ?? '');
  const [code, setCode] = useState<{ restaurantId: string; value: string } | null>(null);
  const task = useAction();
  return (
    <div className="panel">
      <h3>Invitez quelqu’un à table.</h3>
      <p>Générez un code auprès d’un restaurant disposant d’un programme actif.</p>
      <label className="field">
        <span>Identifiant du restaurant connecté</span>
        <input
          value={restaurantId}
          inputMode="numeric"
          onChange={(event) => {
            setRestaurantId(event.target.value);
            setCode(null);
          }}
        />
      </label>
      <button
        className="button"
        disabled={task.pending || !/^\d+$/.test(restaurantId)}
        onClick={() =>
          void task.run(async () => {
            const result = await request<{ data: { code: string } }>(
              `/restaurants/${restaurantId}/referral-code`,
              'POST',
            );
            setCode({ restaurantId, value: result.data.code });
          })
        }
      >
        Générer mon code
      </button>
      {code?.restaurantId === restaurantId && (
        <div className="info-box">
          <p>Votre code à partager :</p>
          <code className="referral-code">{code.value}</code>
        </div>
      )}
      {task.error && (
        <p className="error" role="alert">
          {task.error}
        </p>
      )}
    </div>
  );
}

function ReferralJoin() {
  const referrals = useResource('referrals', (signal) =>
    request<{ data: { id: Identifier; rewarded_at: string | null }[] }>(
      '/me/referrals',
      'GET',
      undefined,
      signal,
    ),
  );
  const rows = referrals.data?.data ?? [];
  return (
    <div className="panel">
      <h3>Vous venez de la part de quelqu’un ?</h3>
      <p>Le rattachement doit précéder votre première commande du programme concerné.</p>
      <ActionForm
        fields={[{ name: 'code', label: 'Code de parrainage reçu' }]}
        submit="Associer ce code"
        action={async (values) => {
          await request('/me/referrals', 'POST', values);
          return 'Code associé à votre compte.';
        }}
      />
      <h3>Mes invitations</h3>
      <button className="button secondary" onClick={referrals.reload}>Actualiser les invitations</button>
      <State
        loading={referrals.loading}
        error={referrals.error}
        retry={referrals.reload}
        empty={rows.length === 0 ? 'Aucun parrainage pour le moment.' : undefined}
      />
      <ul className="plain-list">
        {rows.map((row) => (
          <li key={row.id}>
            <span>Invitation n° {row.id}</span>
            <strong>{row.rewarded_at ? 'Récompensée' : 'En attente d’éligibilité'}</strong>
          </li>
        ))}
      </ul>
    </div>
  );
}

export function Loyalty() {
  const [ledger, setLedger] = useState<string | null>(null);
  const accounts = useResource('loyalty', (signal) =>
    request<{ data: LoyaltyAccount[] }>('/me/loyalty-accounts', 'GET', undefined, signal),
  );
  const rows = accounts.data?.data ?? [];
  return (
    <section className="section">
      <SectionTitle eyebrow="LES BONS REPAS ONT DE LA SUITE" title="Votre fidélité a du goût." />
      <p>
        Les points et récompenses dépendent du programme approuvé de chaque restaurant. Aucun barème
        de démonstration n’est appliqué à votre compte.
      </p>
      <State
        loading={accounts.loading}
        error={accounts.error}
        retry={accounts.reload}
        empty={
          rows.length === 0 ? 'Votre compte ne possède pas encore de solde de fidélité.' : undefined
        }
      />
      <div className="three-columns">
        {rows.map((account) => (
          <article className="loyalty-card" key={account.id}>
            <p className="eyebrow">
              {account.program.restaurant_id
                ? `RESTAURANT N° ${account.program.restaurant_id}`
                : 'PROGRAMME GLOBAL'}
            </p>
            <strong>
              {account.balance}
              <small>points disponibles</small>
            </strong>
            <button className="button secondary" onClick={() => setLedger(String(account.id))}>
              Voir les mouvements
            </button>
          </article>
        ))}
      </div>
      <div className="two-columns">
        <ReferralCode />
        <ReferralJoin />
      </div>
      {ledger && <Ledger id={ledger} close={() => setLedger(null)} />}
    </section>
  );
}
