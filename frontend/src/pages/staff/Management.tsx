// Prise en main : Assek ; relecture croisée du binôme B.
import { useState } from 'react';
import type { Membership, Paginated, Campaign, Statistics } from '../../domain/types';
import { money } from '../../domain/money';
import { request } from '../../services/http';
import { useResource } from '../../hooks/useResource';
import { ActionForm, useAction } from '../../components/Forms';
import type { FieldSpec } from '../../components/Forms';
import { State } from '../../components/Ui';
import { ManagerAccess } from './ManagerAccess';
import { roleLabels } from '../../domain/roles';

export function StaffStatistics({
  restaurantId,
  manager,
}: {
  restaurantId: string;
  manager: boolean;
}) {
  const [period, setPeriod] = useState('week');
  const data = useResource(`statistics:${restaurantId}:${period}`, (signal) =>
    request<{ data: Statistics }>(
      `/restaurants/${restaurantId}/statistics?period=${period}`,
      'GET',
      undefined,
      signal,
    ),
  );
  const result = data.data?.data;
  return (
    <div>
      <div className="section-heading">
        <h3>Le restaurant en un regard</h3>
        <label className="field">
          <span>Période</span>
          <select value={period} onChange={(event) => setPeriod(event.target.value)}>
            {manager && <option value="day">Aujourd’hui</option>}
            <option value="week">Cette semaine</option>
            {manager && <option value="month">Ce mois-ci</option>}
          </select>
        </label>
        <button className="button secondary" onClick={data.reload}>
          Actualiser
        </button>
      </div>
      <State loading={data.loading} error={data.error} retry={data.reload} />
      {result && (
        <>
          <div className="stats-grid">
            <article>
              <span>Montant de toutes les commandes payées</span>
              <strong>{money(result.paid_orders_total)}</strong>
            </article>
            <article>
              <span>Espèces confirmées · {result.cash_paid_orders} commande(s)</span>
              <strong>{money(result.cash_paid_total)}</strong>
            </article>
            <article>
              <span>Commandes créées</span>
              <strong>{result.orders}</strong>
            </article>
            <article>
              <span>Commandes servies</span>
              <strong>{result.completed_orders}</strong>
            </article>
            <article>
              <span>Montant servi</span>
              <strong>{money(result.completed_total)}</strong>
            </article>
            <article>
              <span>Montant servi et payé</span>
              <strong>{money(result.paid_completed_total)}</strong>
            </article>
          </div>
          <div className="panel">
            <p>
              {result.points_awarded} points attribués · {result.referrals_rewarded} parrainages
              récompensés
            </p>
            <p className="small-note">
              La période porte sur la création des commandes, avec leur état de règlement actuel.
              Les espèces en attente sont exclues des montants encaissés.
            </p>
            <p className="small-note">
              Dernier calcul serveur : {new Date(result.generated_at).toLocaleString('fr-CD')}.
              Actualisation manuelle ; aucun chiffre simulé.
            </p>
          </div>
        </>
      )}
    </div>
  );
}

export function StaffTeam({ restaurantId, administrator }: { restaurantId: string; administrator: boolean }) {
  const team = useResource(`employees:${restaurantId}`, (signal) =>
    request<{ data: Membership[] }>(
      `/restaurants/${restaurantId}/employees`,
      'GET',
      undefined,
      signal,
    ),
  );
  const task = useAction();
  return (
    <div className="two-columns">
      <div className="panel">
        <h3>L’équipe de ce restaurant</h3>
        <State loading={team.loading} error={team.error} retry={team.reload} />
        {team.data?.data.map((row) => (
          <div className="catalogue-row" key={row.id}>
            <div>
              <strong>{row.user?.name}</strong>
              <p>{row.user?.email}</p>
              <small>
                {roleLabels[row.role]} · {row.active ? 'Actif' : 'Révoqué'}
              </small>
            </div>
            {row.role === 'employee' && (
              <button
                disabled={task.pending}
                onClick={() =>
                  void task.run(async () => {
                    await request(
                      `/restaurants/${restaurantId}/employees/${row.user_id}`,
                      'PATCH',
                      { active: !row.active },
                    );
                    team.reload();
                  })
                }
              >
                {row.active ? 'Révoquer l’accès' : 'Réactiver l’accès'}
              </button>
            )}
          </div>
        ))}
        {task.error && (
          <p className="error" role="alert">
            {task.error}
          </p>
        )}
      </div>
      <div className="panel">
        <h3>Accueillir un employé</h3>
        <p>
          Un nouveau compte reçoit un lien de récupération si l’envoi d’e-mails est configuré sur le
          serveur. Aucun mot de passe n’est défini ici.
        </p>
        <ActionForm
          fields={[
            { name: 'name', label: 'Nom complet', maxLength: 150 },
            { name: 'email', label: 'Adresse e-mail', type: 'email', maxLength: 255 },
          ]}
          submit="Créer le rattachement employé"
          action={async (values) => {
            await request(`/restaurants/${restaurantId}/employees`, 'POST', values);
            team.reload();
          }}
        />
        {administrator && <ManagerAccess restaurantId={restaurantId} saved={team.reload} />}
      </div>
    </div>
  );
}

export function StaffCampaigns({ restaurantId }: { restaurantId: string }) {
  const campaigns = useResource(`manage-campaigns:${restaurantId}`, (signal) =>
    request<Paginated<Campaign>>(
      `/restaurants/${restaurantId}/campaigns`,
      'GET',
      undefined,
      signal,
    ),
  );
  const task = useAction();
  return (
    <div className="two-columns">
      <div className="panel">
        <h3>Les affiches actives</h3>
        <State
          loading={campaigns.loading}
          error={campaigns.error}
          retry={campaigns.reload}
          empty={campaigns.data?.data.length === 0 ? 'Aucune publication active.' : undefined}
        />
        {campaigns.data?.data.map((row) => (
          <article className="catalogue-row" key={row.id}>
            <div>
              <h4>{row.title}</h4>
              <p>{row.description}</p>
            </div>
            <button
              disabled={task.pending}
              onClick={() =>
                void task.run(async () => {
                  await request(
                    `/restaurants/${restaurantId}/campaigns/${row.id}/deactivate`,
                    'POST',
                  );
                  campaigns.reload();
                })
              }
            >
              Désactiver
            </button>
          </article>
        ))}
        {task.error && (
          <p className="error" role="alert">
            {task.error}
          </p>
        )}
      </div>
      <div className="panel">
        <h3>Publier une affiche</h3>
        <p>
          Cette interface crée des promotions et événements. La création de quiz avec barème de
          récompense reste à compléter ; leur participation publique est prévue.
        </p>
        <ActionForm
          fields={[
            {
              name: 'kind',
              label: 'Type',
              options: [
                { value: 'promotion', label: 'Promotion (affiche informative)' },
                { value: 'event', label: 'Événement' },
              ],
            },
            { name: 'title', label: 'Titre', maxLength: 150 },
            { name: 'description', label: 'Description', maxLength: 5000 },
            { name: 'starts_at', label: 'Début', type: 'datetime-local' },
            { name: 'ends_at', label: 'Fin', type: 'datetime-local' },
          ]}
          submit="Publier"
          action={async (values) => {
            await request(`/restaurants/${restaurantId}/campaigns`, 'POST', {
              ...values,
              starts_at: new Date(values.starts_at).toISOString().replace('.000Z', '+00:00'),
              ends_at: new Date(values.ends_at).toISOString().replace('.000Z', '+00:00'),
              active: true,
            });
            campaigns.reload();
          }}
        />
      </div>
    </div>
  );
}

export const restaurantFields: FieldSpec[] = [
  { name: 'name', label: 'Nom du restaurant', maxLength: 150 },
  { name: 'address', label: 'Adresse complète', maxLength: 2000 },
  {
    name: 'timezone',
    label: 'Fuseau horaire',
    options: [
      { value: 'Africa/Kinshasa', label: 'Kinshasa (UTC+1)' },
      { value: 'Africa/Lubumbashi', label: 'Lubumbashi (UTC+2)' },
    ],
  },
  {
    name: 'delivery_enabled',
    label: 'Livraison',
    options: [
      { value: 'true', label: 'Activée' },
      { value: 'false', label: 'Désactivée' },
    ],
  },
  { name: 'delivery_fee', label: 'Frais de livraison en FC (0.00 si gratuits)', required: false },
];

export function restaurantPayload(values: Record<string, string>) {
  return {
    ...values,
    delivery_enabled: values.delivery_enabled === 'true',
    delivery_fee: values.delivery_fee || null,
  };
}
