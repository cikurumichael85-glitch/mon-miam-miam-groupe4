// Prise en main : Assek ; relecture croisée du binôme B.
import { useEffect, useState } from 'react';
import type { Order, OrderDetail, Paginated } from '../../domain/types';
import { money } from '../../domain/money';
import { request } from '../../services/http';
import { useResource } from '../../hooks/useResource';
import { ActionForm } from '../../components/Forms';
import { Modal, State } from '../../components/Ui';
import { statusLabels } from '../Orders';
import { OrderFulfillment } from '../../components/OrderFulfillment';

function transitions(order: Order, manager: boolean): string[] {
  const next: Record<string, string[]> = {
    pending: ['accepted'],
    accepted: ['preparing'],
    preparing: ['ready'],
    ready: [order.service_mode === 'delivery' ? 'out_for_delivery' : 'completed'],
    out_for_delivery: ['completed'],
  };
  const statuses = [...(next[order.status] ?? [])];
  if (manager && ['pending', 'accepted'].includes(order.status)) {
    statuses.push('cancelled');
  }
  if (manager && order.status === 'pending') {
    statuses.push('rejected');
  }
  return statuses;
}

function StaffOrderWindow({
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
  const detail = useResource(`staff-order:${restaurantId}:${id}`, (signal) =>
    request<OrderDetail>(`/restaurants/${restaurantId}/orders/${id}`, 'GET', undefined, signal),
  );
  const options = detail.data
    ? transitions(detail.data.data, manager).map((status) => ({
        value: status,
        label: statusLabels[status],
      }))
    : [];
  return (
    <Modal title={`Commande n° ${id}`} close={close}>
      <State loading={detail.loading} error={detail.error} retry={detail.reload} />
      {detail.data && (
        <>
          <span className="status-pill">{statusLabels[detail.data.data.status]}</span>
          <ul className="plain-list">
            {detail.data.lines.map((line) => (
              <li key={line.id}>
                {line.quantity} × {line.name}
                <strong>{money(line.unit_price)} / unité</strong>
              </li>
            ))}
          </ul>
          <p>Total : {money(detail.data.data.total)}</p>
          <OrderFulfillment order={detail.data.data} />
          {options.length > 0 ? (
            <ActionForm
              key={detail.data.data.version}
              fields={[
                { name: 'status', label: 'Étape suivante', options },
                {
                  name: 'reason',
                  label: 'Motif (obligatoire pour annuler ou refuser)',
                  required: false,
                },
              ]}
              submit="Confirmer cette étape de service"
              action={async (values) => {
                await request(`/restaurants/${restaurantId}/orders/${id}/transitions`, 'POST', {
                  ...values,
                  expected_version: detail.data?.data.version,
                });
                detail.reload();
                return 'État actualisé par le serveur.';
              }}
            />
          ) : (
            <p>Cette commande est dans un état final.</p>
          )}
        </>
      )}
    </Modal>
  );
}

export function StaffOrders({ restaurantId, manager }: { restaurantId: string; manager: boolean }) {
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState<string | null>(null);
  const orders = useResource(`staff-orders:${restaurantId}:${page}`, (signal) =>
    request<Paginated<Order>>(
      `/restaurants/${restaurantId}/orders?page=${page}`,
      'GET',
      undefined,
      signal,
    ),
  );
  const reload = orders.reload;
  useEffect(() => {
    const timer = window.setInterval(() => {
      if (document.visibilityState === 'visible') {
        reload();
      }
    }, 15000);
    return () => window.clearInterval(timer);
  }, [reload]);
  return (
    <div className="panel">
      <div className="section-heading">
        <h3>Le service en cours</h3>
        <button className="button secondary" onClick={orders.reload}>
          Actualiser
        </button>
      </div>
      <p className="small-note">Actualisation toutes les 15 secondes quand cet onglet est visible. La confirmation d’une étape reste une action volontaire.</p>
      <State
        loading={orders.loading}
        error={orders.error}
        retry={orders.reload}
        empty={orders.data?.data.length === 0 ? 'Aucune commande dans ce restaurant.' : undefined}
      />
      {orders.data?.data.map((order) => (
        <button
          className="list-button"
          key={order.id}
          onClick={() => setSelected(String(order.id))}
        >
          <span>
            N° {order.id} · {statusLabels[order.status]}
            <small>{order.service_mode === 'delivery' ? 'Livraison' : 'Sur place'}</small>
          </span>
          <strong>{money(order.total)}</strong>
        </button>
      ))}
      <div className="pagination">
        <button disabled={page === 1} onClick={() => setPage(page - 1)}>
          Précédent
        </button>
        <span>{page}</span>
        <button disabled={page >= (orders.data?.last_page ?? 1)} onClick={() => setPage(page + 1)}>
          Suivant
        </button>
      </div>
      {selected && (
        <StaffOrderWindow
          restaurantId={restaurantId}
          id={selected}
          manager={manager}
          close={() => {
            setSelected(null);
            orders.reload();
          }}
        />
      )}
    </div>
  );
}
