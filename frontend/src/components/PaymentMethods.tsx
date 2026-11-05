// Prise en main : Michael ; relecture Assek et binôme C pour le contrat de paiement.
import type { PaymentAvailability } from '../domain/types';

const networks = [
  { id: 'orange_money', label: 'Orange Money', style: 'orange' },
  { id: 'mpesa', label: 'M-Pesa', style: 'mpesa' },
  { id: 'airtel_money', label: 'Airtel Money', style: 'airtel' },
];

function NetworkControl({ network, payment, onPrepare, checkoutUrl, disabled }: {
  network: typeof networks[number];
  payment: PaymentAvailability;
  onPrepare?: () => void;
  checkoutUrl?: string;
  disabled: boolean;
}) {
  const available = payment.available && payment.networks.includes(network.id);
  const actionLabel = onPrepare ? 'Ouvrir le paiement sécurisé EasyPay' : 'Disponible après confirmation de la commande';
  const content = <><strong>{network.label}</strong><small>{available ? actionLabel : 'Activation à confirmer'}</small></>;
  const className = `payment-network ${network.style}`;
  if (!onPrepare) {
    return <div className={className}>{content}</div>;
  }
  if (checkoutUrl && available && !disabled) {
    return <a className={className} href={checkoutUrl} target="_blank" rel="noopener noreferrer" aria-label={`Payer avec ${network.label} via EasyPay`}>{content}</a>;
  }
  return <button type="button" className={className} disabled={disabled || !available} onClick={onPrepare} aria-label={`Payer avec ${network.label} via EasyPay`}>{content}</button>;
}

export function PaymentMethods({ payment, onPrepare, checkoutUrl, disabled = false }: {
  payment: PaymentAvailability;
  onPrepare?: () => void;
  checkoutUrl?: string;
  disabled?: boolean;
}) {
  return (
    <div className="payment-methods">
      <h3>{onPrepare ? 'Choisissez votre moyen de paiement' : 'Moyens proposés après confirmation'}</h3>
      <div className="payment-networks">
        {networks.map((network) => (
          <NetworkControl key={network.id} network={network} payment={payment} onPrepare={onPrepare} checkoutUrl={checkoutUrl} disabled={disabled} />
        ))}
      </div>
      <p>
        {payment.available
          ? 'Après confirmation de la commande, ces boutons ouvrent le guichet sécurisé EasyPay. Vous y choisissez le moyen disponible et saisissez votre numéro ; la validation du paiement se fait ensuite chez EasyPay et votre opérateur.'
          : 'Ce restaurant n’a pas encore activé son compte marchand. Vous pouvez enregistrer une commande non réglée ; aucun encaissement Mobile Money n’est actuellement possible.'}
      </p>
      {payment.test_mode && <p className="info-box">Mode test EasyPay : aucun règlement réel n’est confirmé par un test.</p>}
      <p className="small-note">Votre code PIN Mobile Money est saisi uniquement dans le parcours sécurisé de l’opérateur, jamais dans cette application.</p>
    </div>
  );
}
