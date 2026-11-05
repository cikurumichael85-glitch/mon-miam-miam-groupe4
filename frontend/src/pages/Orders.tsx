// Prise en main : Michael ; relecture croisée du binôme B.
import { useState } from 'react';
import type { Order, OrderDetail, Paginated } from '../domain/types';
import { money } from '../domain/money';
import { request } from '../services/http';
import { useResource } from '../hooks/useResource';
import { ActionForm } from '../components/Forms';
import { Modal, SectionTitle, State } from '../components/Ui';
import { PaymentActions } from '../components/PaymentActions';
import { OrderFulfillment } from '../components/OrderFulfillment';

export const statusLabels: Record<string, string> = {
  pending: 'En attente',
  accepted: 'Acceptée',
  preparing: 'En préparation',
  ready: 'Prête',
  out_for_delivery: 'En livraison',
  completed: 'Servie',
  cancelled: 'Annulée',
  rejected: 'Refusée',
};
const paymentLabels: Record<string, string> = {
  unpaid: 'Non réglée',
  unrecorded: 'Non réglée',
  pending: 'Paiement en attente',
  paid: 'Payé',
  failed: 'Paiement échoué',
  uncertain: 'À vérifier',
};

function OrderWindow({ id, close }: { id: string; close: () => void }) {
  const detail = useResource(`order:${id}`, (signal) =>
    request<OrderDetail>(`/me/orders/${id}`, 'GET', undefined, signal),
  );
  return (
    <Modal title={`Commande n° ${id}`} close={close}>
      <State loading={detail.loading} error={detail.error} retry={detail.reload} />
      {detail.data && (
        <>
          <p className="status-pill">
            {statusLabels[detail.data.data.status]} ·{' '}
            {paymentLabels[detail.data.data.payment_state] ?? detail.data.data.payment_state}
          </p>
          <ul className="plain-list">
            {detail.data.lines.map((line) => (
              <li key={line.id}>
                <span>
                  {line.quantity} × {line.name}
                </span>
                <span>{money(line.unit_price)} / unité</span>
              </li>
            ))}
          </ul>
          <p className="order-total">Total : {money(detail.data.data.total)}</p>
          <OrderFulfillment order={detail.data.data} />
          <h3>Suivi de préparation</h3>
          <ol className="timeline">
            {detail.data.events.map((event) => (
              <li key={event.id}>
                <strong>{statusLabels[event.to_status] ?? 'Mise à jour de la commande'}</strong>
                <small>{new Date(event.occurred_at).toLocaleString('fr-CD')}</small>
              </li>
            ))}
          </ol>
          <PaymentActions order={detail.data.data} payment={detail.data.payment ?? null} refresh={detail.reload} />
          {detail.data.data.status === 'completed' && (
            <details className="panel">
              <summary>Donner mon avis</summary>
              <ActionForm
                fields={[
                  { name: 'rating', label: 'Votre note', type: 'number', min: 1, max: 5 },
                  { name: 'comment', label: 'Votre commentaire', maxLength: 2000 },
                ]}
                submit="Publier mon avis"
                action={async (values) => {
                  await request(`/me/orders/${id}/review`, 'POST', {
                    rating: Number(values.rating),
                    comment: values.comment,
                  });
                  return 'Votre avis a été publié.';
                }}
              />
            </details>
          )}
          <details className="panel">
            <summary>Signaler un problème</summary>
            <ActionForm
              fields={[
                { name: 'subject', label: 'Objet de la réclamation', maxLength: 150 },
                { name: 'message', label: 'Décrivez le problème', maxLength: 5000 },
              ]}
              submit="Envoyer ma réclamation"
              action={async (values) => {
                await request(`/me/orders/${id}/claims`, 'POST', values);
                return 'Réclamation transmise. Suivez les réponses dans Mon compte.';
              }}
            />
          </details>
        </>
      )}
    </Modal>
  );
}

export function Orders() {
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState<string | null>(null);
  const orders = useResource(`orders:${page}`, (signal) =>
    request<Paginated<Order>>(`/me/orders?page=${page}`, 'GET', undefined, signal),
  );
  const lastPage = orders.data?.last_page ?? 1;
  return (
    <section className="section">
      <SectionTitle eyebrow="VOTRE HISTOIRE À TABLE" title="Vos commandes, tout simplement.">
        <button className="button secondary" onClick={orders.reload}>
          Actualiser
        </button>
      </SectionTitle>
      <p className="small-note">
        Le suivi s’actualise à votre demande. Après un paiement, ouvrez la commande puis choisissez
        « Vérifier le paiement ».
      </p>
      <State
        loading={orders.loading}
        error={orders.error}
        retry={orders.reload}
        empty={
          orders.data?.data.length === 0
            ? 'Votre première bonne assiette vous attend. Découvrez la carte pour commencer.'
            : undefined
        }
      />
      <div className="orders-grid">
        {orders.data?.data.map((order) => (
          <article className="panel order-card" key={order.id}>
            <div className="dish-bottom">
              <p className="eyebrow">N° {order.id}</p>
              <span className="status-pill">{statusLabels[order.status]}</span>
            </div>
            <h3>{order.service_mode === 'delivery' ? 'À votre porte' : 'À savourer sur place'}</h3>
            <p>{new Date(order.created_at).toLocaleString('fr-CD')}</p>
            <p>{paymentLabels[order.payment_state] ?? order.payment_state}</p>
            <div className="dish-bottom">
              <strong>{money(order.total)}</strong>
              <button className="button secondary" onClick={() => setSelected(String(order.id))}>
                Voir / payer
              </button>
            </div>
          </article>
        ))}
      </div>
      <div className="pagination">
        <button
          className="button secondary"
          disabled={page === 1 || orders.loading}
          onClick={() => setPage(page - 1)}
        >
          Précédent
        </button>
        <span>
          Page {page} / {lastPage}
        </span>
        <button
          className="button secondary"
          disabled={page >= lastPage || orders.loading}
          onClick={() => setPage(page + 1)}
        >
          Suivant
        </button>
      </div>
      {selected && <OrderWindow id={selected} close={() => setSelected(null)} />}
    </section>
  );
}
