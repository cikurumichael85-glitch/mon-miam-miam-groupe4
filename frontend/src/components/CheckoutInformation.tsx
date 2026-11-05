// Prise en main : Michael ; relecture croisée du binôme B.
import type { CheckoutOptions } from '../domain/types';
import { PaymentMethods } from './PaymentMethods';
import { State } from './Ui';

const days = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

function ServiceAvailability({ options }: { options: CheckoutOptions }) {
  return (
    <>
      {options.hours.length === 0 && (
        <p className="info-box">
          Les horaires du restaurant ne sont pas encore renseignés : les commandes sont fermées.
          L’administrateur doit ouvrir « Mon restaurant &amp; horaires » pour les configurer.
        </p>
      )}
      <details className="panel">
        <summary>Horaires de commande · {options.timezone}</summary>
        <ul>
          {options.hours.map((slot) => (
            <li key={`${slot.weekday}-${slot.opens_at}`}>
              {days[slot.weekday]} : {slot.opens_at}–{slot.closes_at}
            </li>
          ))}
        </ul>
      </details>
      <PaymentMethods payment={options.payment} />
    </>
  );
}

export function CheckoutInformation({ options, loading, error, retry }: {
  options: CheckoutOptions | null;
  loading: boolean;
  error: string;
  retry: () => void;
}) {
  return (
    <>
      <ol className="checkout-steps">
        <li>Votre panier</li>
        <li>Sur place ou livraison</li>
        <li>Confirmer la commande</li>
        <li>Régler et suivre</li>
      </ol>
      <State loading={loading} error={error} retry={retry} />
      {options && <ServiceAvailability options={options} />}
    </>
  );
}

export function canOrder(options: CheckoutOptions | null): boolean {
  return options !== null && options.hours.length > 0;
}
