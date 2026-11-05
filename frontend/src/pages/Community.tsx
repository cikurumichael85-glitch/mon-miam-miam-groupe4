// Prise en main : Assek ; relecture croisée du binôme B.
import { useState } from 'react';
import type { Campaign, Identifier, Paginated } from '../domain/types';
import { request } from '../services/http';
import { useResource } from '../hooks/useResource';
import { useApp } from '../state/AppContext';
import { ActionForm } from '../components/Forms';
import { SectionTitle, State } from '../components/Ui';

function CampaignCard({ campaign, restaurantId }: { campaign: Campaign; restaurantId: string }) {
  const app = useApp();
  return (
    <article className="panel">
      <p className="eyebrow">{campaign.kind === 'promotion' ? 'À L’AFFICHE' : 'LE RENDEZ-VOUS'}</p>
      <h3>{campaign.title}</h3>
      <p>{campaign.description}</p>
      {campaign.rules?.question && (
        <p>
          <strong>{campaign.rules.question}</strong>
        </p>
      )}
      {campaign.kind !== 'promotion' && app.user && (
        <ActionForm
          fields={
            campaign.kind === 'quiz'
              ? [
                  {
                    name: 'answer_index',
                    label: 'Votre réponse',
                    options: (campaign.rules?.choices ?? []).map((choice, index) => ({
                      value: String(index),
                      label: choice,
                    })),
                  },
                ]
              : []
          }
          submit="Participer"
          action={async (values) => {
            await request(
              `/restaurants/${restaurantId}/campaigns/${campaign.id}/participate`,
              'POST',
              campaign.kind === 'quiz' ? { answer_index: Number(values.answer_index) } : {},
            );
            return 'Participation enregistrée par le serveur. Les récompenses dépendent des règles approuvées.';
          }}
        />
      )}
    </article>
  );
}

export function Community({ restaurantId }: { restaurantId: string }) {
  const app = useApp();
  const [period, setPeriod] = useState('week');
  const campaigns = useResource(`campaigns:${restaurantId}:${app.mode}`, async (signal) =>
    app.mode === 'demo'
      ? { data: [] as Campaign[] }
      : request<Paginated<Campaign>>(
          `/restaurants/${restaurantId}/campaigns`,
          'GET',
          undefined,
          signal,
        ),
  );
  const ranking = useResource(`ranking:${restaurantId}:${period}:${app.mode}`, async (signal) =>
    app.mode === 'demo'
      ? { data: [] as { public_alias: string; orders_count: number }[] }
      : request<{ data: { public_alias: string; orders_count: number }[] }>(
          `/restaurants/${restaurantId}/ranking?period=${period}`,
          'GET',
          undefined,
          signal,
        ),
  );
  const reviews = useResource(`reviews:${restaurantId}:${app.mode}`, async (signal) =>
    app.mode === 'demo'
      ? { data: [] as { id: Identifier; rating: number; comment: string }[] }
      : request<Paginated<{ id: Identifier; rating: number; comment: string }>>(
          `/restaurants/${restaurantId}/reviews`,
          'GET',
          undefined,
          signal,
        ),
  );
  return (
    <section className="section">
      <SectionTitle eyebrow="AUTOUR DE LA TABLE" title="Plus qu’un bon repas." />
      <div className="two-columns">
        <div>
          <h3>Promotions & rendez-vous</h3>
          <State
            loading={campaigns.loading}
            error={campaigns.error}
            retry={campaigns.reload}
            empty={
              campaigns.data?.data.length === 0
                ? 'Les prochaines affiches arrivent bientôt.'
                : undefined
            }
          />
          {campaigns.data?.data.map((row) => (
            <CampaignCard campaign={row} restaurantId={restaurantId} key={row.id} />
          ))}
        </div>
        <div className="panel">
          <p className="eyebrow">LA COMMUNAUTÉ</p>
          <h3>Les habitués à l’honneur</h3>
          <label className="field">
            <span>Période du classement</span>
            <select value={period} onChange={(event) => setPeriod(event.target.value)}>
              <option value="day">Aujourd’hui</option>
              <option value="week">Cette semaine</option>
              <option value="month">Ce mois-ci</option>
            </select>
          </label>
          <State
            loading={ranking.loading}
            error={ranking.error}
            retry={ranking.reload}
            empty={
              ranking.data?.data.length === 0
                ? 'Aucun classement publié. Seuls les clients ayant donné leur accord y apparaissent.'
                : undefined
            }
          />
          <ol className="ranking-list">
            {ranking.data?.data.map((row) => (
              <li key={row.public_alias}>
                <span>{row.public_alias}</span>
                <strong>{row.orders_count} commandes</strong>
              </li>
            ))}
          </ol>
        </div>
      </div>
      <h3>Les avis à table</h3>
      <State
        loading={reviews.loading}
        error={reviews.error}
        retry={reviews.reload}
        empty={reviews.data?.data.length === 0 ? 'Aucun avis publié pour le moment.' : undefined}
      />
      <div className="three-columns">
        {reviews.data?.data.map((review) => (
          <blockquote className="panel" key={review.id}>
            <strong>{review.rating} / 5</strong>
            <p>{review.comment}</p>
          </blockquote>
        ))}
      </div>
      <p className="small-note">
        Les affiches et avis affichés ici correspondent à la première page disponible ; aucun avis
        ni classement fictif n’est inventé.
      </p>
    </section>
  );
}
