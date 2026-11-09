// Prise en main : Michael ; relecture croisée du binôme B.
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import type { CheckoutOptions, Order, OrderDetail, Quote } from '../domain/types';
import { cartTotal } from '../domain/cart';
import { money } from '../domain/money';
import { request } from '../services/http';
import { useApp } from '../state/AppContext';
import { Field, useAction } from './Forms';
import { useResource } from '../hooks/useResource';
import { canOrder, CheckoutInformation } from './CheckoutInformation';
import { checkoutAfterOrder, openHostedCheckout } from '../services/checkoutNavigation';

function isoDate(value: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    throw new Error('Choisissez une heure d’arrivée valide.');
  }
  if (date.getTime() <= Date.now()) {
    throw new Error(
      'L’heure planifiée est déjà passée. Choisissez Commander maintenant ou une heure future.',
    );
  }
  return date.toISOString().replace('.000Z', '+00:00');
}

function hasOnlinePayment(options: CheckoutOptions | null): boolean {
  return options?.payment.available === true && options.payment.networks.length > 0;
}

function ServiceFields({ service }: { service: string }) {
  const { user } = useApp();
  if (service === 'on_site') {
    return <ArrivalChoice />;
  }
  return (
    <>
      <Field
        name="address"
        label="Adresse complète de livraison"
        value={user?.delivery_address ?? ''}
      />
      <Field
        name="phone"
        label="Téléphone joignable (+243…)"
        type="tel"
        value={user?.phone}
        maxLength={32}
      />
      <Field
        name="instructions"
        label="Indications de livraison"
        required={false}
        maxLength={500}
      />
    </>
  );
}

function ArrivalChoice() {
  const [timing, setTiming] = useState('immediate');
  return (
    <>
      <label className="field">
        <span>Quand souhaitez-vous commander ?</span>
        <select
          aria-label="Quand souhaitez-vous commander ?"
          name="order_timing"
          value={timing}
          onChange={(event) => setTiming(event.target.value)}
        >
          <option value="immediate">Commander maintenant</option>
          <option value="scheduled">Planifier mon arrivée</option>
        </select>
      </label>
      {timing === 'scheduled' ? (
        <>
          <Field name="arrival_at" label="Votre heure d’arrivée" type="datetime-local" />
          <p className="small-note">
            Heure du navigateur : {Intl.DateTimeFormat().resolvedOptions().timeZone}. Choisissez une
            heure future dans les sept prochains jours.
          </p>
        </>
      ) : (
        <p className="info-box">
          Votre commande sera transmise au restaurant dès votre confirmation. Aucune date à saisir ;
          le serveur utilise l’heure actuelle et les horaires d’ouverture du restaurant.
        </p>
      )}
    </>
  );
}

function fulfillment(values: Record<string, string>, service: string) {
  if (service === 'delivery') {
    return {
      delivery: { address: values.address, phone: values.phone, instructions: values.instructions },
    };
  }
  return values.order_timing === 'scheduled' ? { arrival_at: isoDate(values.arrival_at) } : {};
}

function ConfirmQuote({
  quote,
  pending,
  confirm,
  onlinePayment,
}: {
  quote: Quote;
  pending: boolean;
  confirm: () => void;
  onlinePayment: boolean;
}) {
  const [now, setNow] = useState(() => Date.now());
  useEffect(() => {
    const timer = window.setInterval(() => setNow(Date.now()), 1000);
    return () => window.clearInterval(timer);
  }, []);
  const expired = new Date(quote.expires_at).getTime() <= now;
  return (
    <div className="quote-box">
      <dl className="totals">
        <div>
          <dt>Sous-total</dt>
          <dd>{money(quote.payload.subtotal)}</dd>
        </div>
        <div>
          <dt>Remise</dt>
          <dd>− {money(quote.payload.discount)}</dd>
        </div>
        <div>
          <dt>Livraison</dt>
          <dd>{money(quote.payload.delivery_fee)}</dd>
        </div>
        <div className="total-line">
          <dt>Total confirmé par le serveur</dt>
          <dd>{money(quote.payload.total)}</dd>
        </div>
      </dl>
      <p>
        {expired
          ? 'Devis expiré : demandez un nouveau calcul.'
          : `Valable jusqu’à ${new Date(quote.expires_at).toLocaleTimeString('fr-CD')}.`}
      </p>
      <button className="button full-width" disabled={expired || pending} onClick={confirm}>
        {pending
          ? 'Enregistrement et préparation…'
          : `Confirmer ma commande${onlinePayment ? ' et ouvrir EasyPay' : ''} · ${money(quote.payload.total)}`}
      </button>
      <p className="small-note">
        Cette confirmation transmet la commande au restaurant sans la marquer payée.
        {onlinePayment &&
          ' Le site prépare ensuite le lien EasyPay et ouvre son guichet sécurisé. Validez le règlement chez le prestataire.'}{' '}
        Si le paiement ne s’ouvre pas, la commande reste enregistrée dans Mes commandes.
      </p>
    </div>
  );
}

export function Checkout({
  onConfirmed,
  busy,
}: {
  onConfirmed: (order: Order, notice?: string) => void;
  busy: (value: boolean) => void;
}) {
  const app = useApp();
  const options = useResource(`checkout:${app.cart.restaurantId}`, (signal) =>
    request<CheckoutOptions>(
      `/restaurants/${app.cart.restaurantId}/checkout-options`,
      'GET',
      undefined,
      signal,
    ),
  );
  const onlinePayment = hasOnlinePayment(options.data);
  const [service, setService] = useState('on_site');
  const [paymentMethod, setPaymentMethod] = useState('cash');
  const [quote, setQuote] = useState<Quote | null>(null);
  const confirmationKey = useRef('');
  const task = useAction();
  useEffect(() => {
    busy(task.pending);
    return () => busy(false);
  }, [busy, task.pending]);
  function createQuote(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const values = Object.fromEntries(new FormData(event.currentTarget)) as Record<string, string>;
    void task.run(async () => {
      const input = {
        items: app.cart.lines.map((line) => ({ item_id: line.item.id, quantity: line.quantity })),
        service_mode: service,
        points: Number(values.points),
      };
      const result = await request<{ data: Quote }>(
        `/restaurants/${app.cart.restaurantId}/order-quotes`,
        'POST',
        { ...input, ...fulfillment(values, service) },
      );
      // Une nouvelle clé appartient à ce devis ; une relance conserve ensuite la même clé.
      confirmationKey.current = crypto.randomUUID();
      setQuote(result.data);
    });
  }
  function confirm() {
    // Le serveur revérifie aussi l'expiration, les versions, les droits et le montant.
    if (!quote || new Date(quote.expires_at).getTime() <= Date.now()) {
      return;
    }
    void task.run(async () => {
      const result = await request<{ data: Order; payment?: OrderDetail['payment'] }>(
        `/restaurants/${app.cart.restaurantId}/orders`,
        'POST',
        {
          quote_id: quote.id,
          idempotency_key: confirmationKey.current,
          payment_method: paymentMethod,
        },
      );
      const payment = await checkoutAfterOrder(
        result.data,
        onlinePayment && paymentMethod === 'easypay',
      );
      onConfirmed({ ...result.data, payment: result.payment }, payment.notice);
      app.dispatch({ type: 'clear' });
      app.notify(`Commande n° ${result.data.id} transmise au restaurant.`);
      if (payment.url) {
        openHostedCheckout(payment.url);
      }
    });
  }
  return (
    <>
      <CheckoutInformation
        options={options.data}
        loading={options.loading}
        error={options.error}
        retry={options.reload}
      />
      <p className="order-total">Sous-total indicatif : {money(cartTotal(app.cart.lines))}</p>
      <form className="form-grid" onSubmit={createQuote} onChange={() => setQuote(null)}>
        <fieldset disabled={task.pending}>
          <label className="field">
            <span>Comment souhaitez-vous savourer ?</span>
            <select value={service} onChange={(event) => setService(event.target.value)}>
              <option value="on_site">Sur place</option>
              <option value="delivery" disabled={!options.data?.delivery_enabled}>
                Livraison{options.data?.delivery_enabled ? '' : ' indisponible'}
              </option>
            </select>
          </label>
          <ServiceFields service={service} />
          <label className="field">
            <span>Moyen de paiement</span>
            <select
              value={paymentMethod}
              onChange={(event) => setPaymentMethod(event.target.value)}
            >
              <option value="cash">Espèces auprès du serveur du restaurant</option>
              <option value="easypay" disabled={!onlinePayment}>
                EasyPay / Mobile Money
              </option>
            </select>
          </label>
          <p className="small-note">
            En espèces, remettez le total au serveur. Il confirmera la réception du règlement dans
            votre commande.
          </p>
          <Field
            name="points"
            label="Points de fidélité à utiliser"
            type="number"
            min={0}
            max={1000000}
            value="0"
          />
        </fieldset>
        <button
          type="submit"
          className="button secondary"
          disabled={task.pending || !canOrder(options.data)}
        >
          Vérifier le prix et continuer
        </button>
      </form>
      {quote && (
        <ConfirmQuote
          key={quote.id}
          quote={quote}
          pending={task.pending}
          confirm={confirm}
          onlinePayment={
            onlinePayment && paymentMethod === 'easypay' && quote.payload.total !== '0.00'
          }
        />
      )}
      {task.error && (
        <p role="alert" className="error">
          {task.error}
        </p>
      )}
    </>
  );
}
