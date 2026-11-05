// Prise en main : Michael ; relecture croisée du binôme B et du binôme C.
import { useEffect, useRef, useState } from 'react';

import type { CheckoutOptions, Order, OrderDetail } from '../domain/types';

import { useResource } from '../hooks/useResource';
import { messageOf, request } from '../services/http';
import { openHostedCheckout, prepareHostedCheckout } from '../services/checkoutNavigation';
import { useAction } from './Forms';
import { PaymentMethods } from './PaymentMethods';
import { State } from './Ui';

type PaymentRecord = OrderDetail['payment'];
type PaymentResponse = {
  data: NonNullable<PaymentRecord>;
  notification_requested: boolean;
  checkout_url: string | null;
};
const paymentStates: Record<string, string> = {
  initializing: 'Demande en cours de préparation. Ne la répétez pas.',
  unknown:
    'Votre commande est enregistrée, mais EasyPay ne confirme pas le paiement. Contactez le restaurant avant tout nouvel essai ; aucun règlement n’est déclaré réussi.',
  pending:
    'Paiement en attente de confirmation. Suivez les instructions du guichet EasyPay ; aucun règlement réussi ne peut être déduit de cette attente.',
  paid: 'Votre commande a été payée. Le règlement a été vérifié auprès d’EasyPay par le serveur.',
  review: 'Ce paiement nécessite un rapprochement par le responsable de la plateforme.',
  declined: 'Paiement refusé. Contactez le restaurant avant toute nouvelle tentative.',
  cancelled: 'Paiement annulé. Contactez le restaurant avant toute nouvelle tentative.',
};

function differentMode(payment: PaymentRecord, options: CheckoutOptions | null): boolean {
  if (!payment?.mode || !options) {
    return false;
  }
  return payment.mode !== (options.payment.test_mode ? 'sandbox' : 'v1');
}

function replaceableTest(payment: PaymentRecord, options: CheckoutOptions | null): boolean {
  if (payment?.mode !== 'sandbox' || options?.payment.test_mode !== false) {
    return false;
  }
  return ['pending', 'unknown', 'declined', 'cancelled'].includes(payment.state);
}

function openableAttempt(payment: PaymentRecord, replaceTest: boolean): boolean {
  return replaceTest || payment === null || payment.state === 'pending';
}

function paymentLabel(payment: PaymentRecord): string {
  if (payment?.state === 'paid' && (!payment.verified_at || payment.mode !== 'v1')) {
    return 'Aucun règlement réel confirmé : cette tentative ne possède pas de confirmation vérifiée en production.';
  }
  return (
    paymentStates[payment?.state ?? ''] ??
    'Aucun paiement confirmé. Choisissez votre moyen de paiement pour ouvrir EasyPay.'
  );
}

function canPay(order: Order, options: CheckoutOptions | null): boolean {
  if (!options) {
    return false;
  }
  return (
    options.payment.available &&
    options.payment.networks.length > 0 &&
    order.total !== '0.00' &&
    order.payment_state !== 'paid' &&
    !['cancelled', 'rejected'].includes(order.status)
  );
}

function usePaymentFollowup(orderId: string | number, enabled: boolean, refresh: () => void) {
  const latestRefresh = useRef(refresh);
  const [note, setNote] = useState('');
  useEffect(() => {
    latestRefresh.current = refresh;
  }, [refresh]);
  useEffect(() => {
    if (!enabled) {
      return;
    }
    const controller = new AbortController();
    let attempts = 0;
    let timer: ReturnType<typeof setTimeout>;
    async function check() {
      if (document.hidden) {
        timer = setTimeout(() => void check(), 10000);
        return;
      }
      attempts += 1;
      try {
        const result = await request<PaymentResponse>(
          `/me/orders/${orderId}/payment/verify`,
          'POST',
          undefined,
          controller.signal,
        );
        if (controller.signal.aborted) {
          return;
        }
        if (result.data.state !== 'pending') {
          latestRefresh.current();
          return;
        }
      } catch (error: unknown) {
        if (!controller.signal.aborted) {
          setNote(messageOf(error));
        }
        return;
      }
      if (attempts < 6) {
        timer = setTimeout(() => void check(), 10000);
        return;
      }
      setNote(
        'Toujours sans confirmation. Utilisez Vérifier le paiement ou contactez le restaurant ; aucune nouvelle demande ne sera envoyée automatiquement.',
      );
    }
    timer = setTimeout(() => void check(), 10000);
    return () => {
      controller.abort();
      clearTimeout(timer);
    };
  }, [orderId, enabled]);
  return enabled ? note : '';
}

function HostedCheckout({
  order,
  payment,
  options,
  allowed,
  replaceTest,
  refresh,
}: {
  order: Order;
  payment: PaymentRecord;
  options: CheckoutOptions;
  allowed: boolean;
  replaceTest: boolean;
  refresh: () => void;
}) {
  const task = useAction();
  const [url, setUrl] = useState('');
  const [attempted, setAttempted] = useState(false);
  const canOpen = allowed && openableAttempt(payment, replaceTest);
  const disabled = task.pending || !canOpen || attempted;
  function start() {
    if (url) {
      openHostedCheckout(url);
      return;
    }
    void task.run(async () => {
      setAttempted(true);
      const checkout = await prepareHostedCheckout(order.id);
      setUrl(checkout);
      setAttempted(false);
      // Navigation dans le même onglet : aucun nouvel encaissement lors du retour.
      openHostedCheckout(checkout);
    });
  }
  return (
    <>
      <PaymentMethods
        payment={options.payment}
        onPrepare={start}
        checkoutUrl={url}
        disabled={disabled}
      />
      <button className="button" disabled={disabled} onClick={start}>
        {task.pending ? 'Ouverture du paiement…' : 'Accéder au paiement sécurisé'}
      </button>
      {url && canOpen && (
        <a href={url} target="_blank" rel="noopener noreferrer" className="button secondary">
          Ouvrir le guichet EasyPay
        </a>
      )}
      {task.error && (
        <p className="error" role="alert">
          {task.error}
        </p>
      )}
      {task.error && (
        <button className="button secondary" onClick={refresh}>
          Actualiser la tentative sans la relancer
        </button>
      )}
    </>
  );
}
function PaymentModeNotice({ replaceTest }: { replaceTest: boolean }) {
  if (replaceTest) {
    return (
      <div className="info-box">
        <h3>Passer au paiement réel</h3>
        <p>
          L’ancienne tentative de test est conservée. Cliquez sur un moyen de paiement ci-dessus
          pour préparer une nouvelle tentative en production pour cette même commande et ouvrir
          EasyPay.
        </p>
        <p>
          La préparation seule ne confirme aucun règlement. Vérifiez le montant et les frais chez
          EasyPay avant toute validation.
        </p>
      </div>
    );
  }
  return (
    <div className="payment-box">
      <h3>Ancienne commande de test</h3>
      <p className="info-box">
        Cette commande possède une ancienne tentative de test. Elle ne peut pas être transformée en
        paiement réel. Aucune nouvelle demande n’a été envoyée sur votre téléphone.
      </p>
      <p>
        Pour utiliser le paiement en production, commencez une nouvelle commande depuis la carte.
        Cette commande et sa tentative de test sont conservées dans votre historique.
      </p>
      <a className="button" href="#carte">
        Ouvrir la carte pour une nouvelle commande
      </a>
      <p className="small-note">
        Cette action ouvre seulement la carte : elle ne crée pas de commande et ne déclenche aucun
        paiement.
      </p>
    </div>
  );
}

function PaymentProgress({
  order,
  payment,
  allowed,
  refresh,
}: {
  order: Order;
  payment: PaymentRecord;
  allowed: boolean;
  refresh: () => void;
}) {
  const task = useAction();
  const pending = payment?.state === 'pending' && allowed;
  const followup = usePaymentFollowup(order.id, pending, refresh);
  function verify() {
    void task.run(async () => {
      await request(`/me/orders/${order.id}/payment/verify`, 'POST');
      refresh();
    });
  }
  return (
    <>
      <p>{paymentLabel(payment)}</p>
      <button className="button secondary" disabled={task.pending || !pending} onClick={verify}>
        Vérifier le paiement
      </button>
      {pending && (
        <p className="small-note">
          Six vérifications automatiques sont prévues pendant que cette page est visible. Elles
          contrôlent la tentative existante et ne déclenchent aucun débit.
        </p>
      )}

      {followup && <output>{followup}</output>}
      {task.error && (
        <p className="error" role="alert">
          {task.error}
        </p>
      )}
    </>
  );
}

export function PaymentActions({
  order,
  payment,
  refresh,
}: {
  order: Order;
  payment: PaymentRecord;
  refresh: () => void;
}) {
  const options = useResource(`payment-options:${order.restaurant_id}`, (signal) =>
    request<CheckoutOptions>(
      `/restaurants/${order.restaurant_id}/checkout-options`,
      'GET',
      undefined,
      signal,
    ),
  );
  const mismatch = differentMode(payment, options.data);
  const replaceTest = replaceableTest(payment, options.data);
  const allowed = canPay(order, options.data) && (!mismatch || replaceTest);

  return (
    <div className="payment-box">
      <h3>Payer ma commande</h3>
      <State loading={options.loading} error={options.error} retry={options.reload} />
      {options.data && (
        <HostedCheckout
          key={`${order.id}:${payment?.mode ?? 'new'}:${options.data.payment.test_mode}`}
          order={order}
          payment={payment}
          options={options.data}
          allowed={allowed}
          replaceTest={replaceTest}
          refresh={refresh}
        />
      )}

      {mismatch ? (
        <PaymentModeNotice replaceTest={replaceTest} />
      ) : (
        <PaymentProgress order={order} payment={payment} allowed={allowed} refresh={refresh} />
      )}
    </div>
  );
}
