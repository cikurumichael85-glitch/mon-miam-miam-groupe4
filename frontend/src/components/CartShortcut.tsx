// Prise en main : Michael ; relecture croisée du binôme B.
import { cartTotal } from '../domain/cart';
import { money } from '../domain/money';
import { useApp } from '../state/AppContext';

export function CartShortcut({ open }: { open: () => void }) {
  const { cart, mode } = useApp();
  if (cart.lines.length === 0) {
    return null;
  }
  const count = cart.lines.reduce((sum, line) => sum + line.quantity, 0);
  return (
    <aside className="cart-shortcut" aria-label="Votre commande">
      <span>{count} article(s) · {money(cartTotal(cart.lines))}</span>
      <button className="button gold" onClick={open}>
        {mode === 'demo' ? 'Voir le panier de démonstration' : 'Passer ma commande'}
      </button>
    </aside>
  );
}
