// Prise en main : Assek ; relecture croisée du binôme B.
import { useState } from 'react';
import type { Claim, ClaimDetail, Paginated } from '../../domain/types';
import { request } from '../../services/http';
import { useResource } from '../../hooks/useResource';
import { useApp } from '../../state/AppContext';
import { ActionForm, useAction } from '../../components/Forms';
import { Modal, State } from '../../components/Ui';

function ClaimWindow({
  restaurantId,
  id,
  manager,
  close,
}: {
  restaurantId: string;
  id: string;
  manager: boolean;
  close: () => void;
}) {
  // Le bouton d'approbation est réservé à une autre personne ; Laravel impose aussi cette règle.
  const app = useApp();
  const base = `/restaurants/${restaurantId}/claims/${id}`;
  const detail = useResource(base, (signal) =>
    request<ClaimDetail>(base, 'GET', undefined, signal),
  );
  const task = useAction();
  return (
    <Modal title={`Réclamation n° ${id}`} close={close}>
      <State loading={detail.loading} error={detail.error} retry={detail.reload} />
      {detail.data && (
        <>
          <h3>{detail.data.data.subject}</h3>
          <p>{detail.data.data.message}</p>
          <span className="status-pill">{detail.data.data.status}</span>
          {detail.data.replies.map((reply) => (
            <div className="panel" key={reply.id}>
              <p>{reply.body}</p>
              <small>{reply.state}</small>
              {manager &&
                reply.state === 'submitted' &&
                String(reply.author_id) !== String(app.user?.id) && (
                  <div className="inline-actions">
                    {['approved', 'rejected'].map((decision) => (
                      <button
                        key={decision}
                        disabled={task.pending}
                        onClick={() =>
                          void task.run(async () => {
                            await request(`${base}/replies/${reply.id}/review`, 'POST', {
                              decision,
                            });
                            detail.reload();
                          })
                        }
                      >
                        {decision === 'approved' ? 'Approuver' : 'Refuser'}
                      </button>
                    ))}
                  </div>
                )}
            </div>
          ))}
          {detail.data.data.status !== 'resolved' && (
            <ActionForm
              fields={[
                {
                  name: 'body',
                  label: 'Réponse à soumettre à une autre personne',
                  maxLength: 5000,
                },
              ]}
              submit="Soumettre la réponse"
              action={async (values) => {
                await request(`${base}/replies`, 'POST', values);
                detail.reload();
                return 'Réponse en attente d’une approbation par une autre personne de la direction.';
              }}
            />
          )}
          {task.error && (
            <p className="error" role="alert">
              {task.error}
            </p>
          )}
        </>
      )}
    </Modal>
  );
}

export function StaffClaims({ restaurantId, manager }: { restaurantId: string; manager: boolean }) {
  const [selected, setSelected] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const claims = useResource(`staff-claims:${restaurantId}:${page}`, (signal) =>
    request<Paginated<Claim>>(
      `/restaurants/${restaurantId}/claims?page=${page}`,
      'GET',
      undefined,
      signal,
    ),
  );
  return (
    <div className="panel">
      <h3>Écouter et répondre</h3>
      <p>
        Une réponse devient visible au client après approbation par une autre personne de la
        direction.
      </p>
      <State
        loading={claims.loading}
        error={claims.error}
        retry={claims.reload}
        empty={
          claims.data?.data.length === 0 ? 'Aucune réclamation dans ce restaurant.' : undefined
        }
      />
      {claims.data?.data.map((row) => (
        <button className="list-button" key={row.id} onClick={() => setSelected(String(row.id))}>
          <span>
            {row.subject}
            <small>Commande n° {row.order_id}</small>
          </span>
          <span>{row.status}</span>
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
      {selected && (
        <ClaimWindow
          restaurantId={restaurantId}
          id={selected}
          manager={manager}
          close={() => {
            setSelected(null);
            claims.reload();
          }}
        />
      )}
    </div>
  );
}
