// Prise en main : Michael ; relecture croisée du binôme B.
import { cartTotal } from '../domain/cart';
import { useState } from 'react';
import { money } from '../domain/money';
import { useApp } from '../state/AppContext';
import { dishPhoto } from '../services/catalogue';
import { Icon, Modal } from './Ui';
import { Checkout } from './Checkout';
import type { Order } from '../domain/types';
import { OrderReceipt } from './OrderReceipt';

function CartLines({ busy }: { busy: boolean }) {
  const app = useApp();
  return (
    <fieldset disabled={busy} className="cart-lines">
      {app.cart.lines.map((line) => (
        <div className="cart-line" key={line.item.id}>
          <img src={dishPhoto(line.item)} alt="" />
          <div>
            <strong>{line.item.name}</strong>
            <small>{money(line.item.price)} / unité</small>
            <div className="quantity-control">
              <button
                aria-label={`Diminuer ${line.item.name}`}
                onClick={() =>
                  app.dispatch({
                    type: 'quantity',
                    id: String(line.item.id),
                    quantity: line.quantity - 1,
                  })
                }
              >
                −
              </button>
              <span>{line.quantity}</span>
              <button
                disabled={line.quantity >= 99}
                aria-label={`Augmenter ${line.item.name}`}
                onClick={() =>
                  app.dispatch({
                    type: 'quantity',
                    id: String(line.item.id),
                    quantity: line.quantity + 1,
                  })
                }
              >
                +
              </button>
            </div>
          </div>
          <button
            className="icon-button"
            aria-label={`Retirer ${line.item.name}`}
            onClick={() =>
              app.dispatch({ type: 'quantity', id: String(line.item.id), quantity: 0 })
            }
          >
            <Icon name="close" />
          </button>
        </div>
      ))}
    </fieldset>
  );
}

function CartAction({
  close,
  busy,
  onConfirmed,
}: {
  close: () => void;
  busy: (value: boolean) => void;
  onConfirmed: (order: Order, notice?: string) => void;
}) {
  const app = useApp();
  if (app.mode === 'demo') {
    return (
      <div className="info-box">
        <strong>Sous-total indicatif : {money(cartTotal(app.cart.lines))}</strong>
        <p>
          Ce panier sert à découvrir l’interface. Aucune commande ni aucun paiement ne sont créés.
          Sélectionnez « Restaurants connectés » pour utiliser le serveur.
        </p>
      </div>
    );
  }
  if (!app.user) {
    return (
      <div className="info-box">
        <p>Connectez-vous pour demander un devis et confirmer votre commande.</p>
        <a href="#compte" className="button" onClick={close}>
          Me connecter
        </a>
      </div>
    );
  }
  return <Checkout key={JSON.stringify(app.cart.lines)} onConfirmed={onConfirmed} busy={busy} />;
}

export function CartSheet({ close }: { close: () => void }) {
  const app = useApp();
  const [busy, setBusy] = useState(false);
  const [confirmedOrder, setConfirmedOrder] = useState<Order | null>(null);
  const [paymentNotice, setPaymentNotice] = useState('');
  function rememberOrder(order: Order, notice = '') {
    setPaymentNotice(notice);
    setConfirmedOrder(order);
  }
  if (confirmedOrder) {
    return (
      <Modal title="Votre commande est enregistrée" close={close}>
        <OrderReceipt order={confirmedOrder} notice={paymentNotice} close={close} />
      </Modal>
    );
  }
  return (
    <Modal title="Votre prochaine bonne assiette" close={close}>
      {app.cart.lines.length === 0 ? (
        <div className="state">
          <Icon name="bag" size={40} />
          <h3>Votre panier attend ses saveurs.</h3>
          <p>Une moambe, un plat grillé, un jus bien frais… À vous de choisir.</p>
          <button
            className="button"
            onClick={() => {
              window.location.hash = 'carte';
              close();
            }}
          >
            Découvrir la carte
          </button>
        </div>
      ) : (
        <>
          <p className="small-note">
            Un panier, un restaurant. Changer de carte ne modifie pas vos articles.
          </p>
          <CartLines busy={busy} />
          <CartAction close={close} busy={setBusy} onConfirmed={rememberOrder} />
          <button
            className="text-button"
            disabled={busy}
            onClick={() => app.dispatch({ type: 'clear' })}
          >
            Vider le panier
          </button>
        </>
      )}
    </Modal>
  );
}
