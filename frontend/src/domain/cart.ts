// Prise en main : Michael ; relecture croisée du binôme B.
import type { Cart, CartLine, Dish } from './types';
import { decimal, minor } from './money';

export const emptyCart: Cart = { restaurantId: null, lines: [] };
export type CartAction =
  | { type: 'add'; item: Dish }
  | { type: 'quantity'; id: string; quantity: number }
  | { type: 'clear' };

function addLine(lines: CartLine[], item: Dish): CartLine[] {
  const existing = lines.find((line) => String(line.item.id) === String(item.id));
  if (!existing) {
    return lines.length >= 50 ? lines : [...lines, { item, quantity: 1 }];
  }
  return lines.map((line) =>
    line === existing ? { item, quantity: Math.min(99, line.quantity + 1) } : line,
  );
}

export function cartReducer(cart: Cart, action: CartAction): Cart {
  // L'identifiant du restaurant est lié au panier, pas au dernier onglet consulté.
  if (action.type === 'clear') {
    return emptyCart;
  }
  if (action.type === 'add') {
    const restaurantId = String(action.item.restaurant_id);
    if (!action.item.available || (cart.restaurantId && cart.restaurantId !== restaurantId)) {
      return cart;
    }
    return { restaurantId, lines: addLine(cart.lines, action.item) };
  }
  if (!Number.isInteger(action.quantity)) {
    return cart;
  }
  const quantity = Math.max(0, Math.min(99, action.quantity));
  const lines = cart.lines
    .map((line) => (String(line.item.id) === action.id ? { ...line, quantity } : line))
    .filter((line) => line.quantity > 0);
  return { restaurantId: lines.length ? cart.restaurantId : null, lines };
}

export function cartTotal(lines: CartLine[]): string {
  return decimal(lines.reduce((total, line) => total + minor(line.item.price) * line.quantity, 0));
}
