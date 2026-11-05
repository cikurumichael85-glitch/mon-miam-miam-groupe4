// Prise en main : Assek ; les informations restent dans le détail privé de la commande.
import type { Order } from '../domain/types';

export function OrderFulfillment({ order }: { order: Order }) {
  if (order.service_mode === 'delivery') {
    return <DeliveryDetails delivery={order.delivery_snapshot} />;
  }
  return (
    <section aria-label="Service sur place">
      <h3>Service sur place</h3>
      {order.arrival_at ? (
        <p>
          Arrivée prévue : {new Date(order.arrival_at).toLocaleString('fr-CD')}
          {' '}({Intl.DateTimeFormat().resolvedOptions().timeZone}, heure du navigateur).
        </p>
      ) : <p>Commande immédiate, sans arrivée planifiée.</p>}
    </section>
  );
}

function DeliveryDetails({ delivery }: { delivery: Order['delivery_snapshot'] }) {
  return (
    <section aria-label="Informations de livraison">
      <h3>Livraison</h3>
      {delivery ? (
        <dl className="totals">
          <div><dt>Adresse de livraison</dt><dd>{delivery.address}</dd></div>
          <div><dt>Téléphone de livraison</dt><dd>{delivery.phone}</dd></div>
          {delivery.instructions && (
            <div><dt>Indications du client</dt><dd>{delivery.instructions}</dd></div>
          )}
        </dl>
      ) : <p role="alert">Coordonnées de livraison indisponibles. Vérifiez la commande avant le départ.</p>}
    </section>
  );
}
