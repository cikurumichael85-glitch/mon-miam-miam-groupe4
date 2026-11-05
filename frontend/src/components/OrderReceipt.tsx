// Prise en main : Michael ; conserver la confirmation même après vidage du panier.
import { useState } from 'react';
import type { Order, OrderDetail } from '../domain/types';
import { money } from '../domain/money';
import { messageOf, request } from '../services/http';
import { useApp } from '../state/AppContext';
import { PaymentActions } from './PaymentActions';

export function OrderReceipt({
  order,
  notice = '',
  close,
}: {
  order: Order;
  notice?: string;
  close: () => void;
}) {
  const { notify } = useApp();
  const [snapshot, setSnapshot] = useState<{ order: Order; payment: OrderDetail['payment'] }>({
    order,
    payment: null,
  });
  function refresh() {
    void request<OrderDetail>(`/me/orders/${order.id}`)
      .then((result) => setSnapshot({ order: result.data, payment: result.payment }))
      .catch((error: unknown) => notify(messageOf(error)));
  }
  return (
    <div className="order-receipt">
      <output className="success">Commande n° {order.id} transmise au restaurant.</output>
      {notice && <output className="info-box">{notice}</output>}
      <p>
        Montant : <strong>{money(order.total)}</strong>. L’équipe peut maintenant la consulter et la
        prendre en charge. La transmission ne signifie pas qu’un serveur l’a déjà acceptée.
      </p>
      <PaymentActions order={snapshot.order} payment={snapshot.payment} refresh={refresh} />
      <a className="button secondary" href="#commandes" onClick={close}>
        Suivre ma commande
      </a>
      <p className="small-note">
        La commande reste dans Mes commandes même si vous fermez cette fenêtre. Seule la
        vérification du prestataire peut confirmer le règlement.
      </p>
    </div>
  );
}
