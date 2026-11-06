// Prise en main : Michael ; relecture croisée du binôme B.
import { describe, expect, it } from 'vitest';
import { demoDishes } from '../data/demo';
import { cartReducer, cartTotal, emptyCart } from './cart';

const dish = demoDishes[0];
describe('Panier isolé par restaurant', () => {
  it('ajoute et regroupe le même article', () => {
    const first = cartReducer(emptyCart, { type: 'add', item: dish });
    const second = cartReducer(first, { type: 'add', item: dish });
    expect(second.lines).toHaveLength(1);
    expect(second.lines[0].quantity).toBe(2);
  });
  it('refuse de mélanger deux restaurants', () => {
    const cart = cartReducer(emptyCart, { type: 'add', item: dish });
    const foreign = demoDishes.find((row) => row.restaurant_id !== dish.restaurant_id)!;
    expect(cartReducer(cart, { type: 'add', item: foreign })).toEqual(cart);
  });
  it('refuse un article indisponible', () => {
    expect(cartReducer(emptyCart, { type: 'add', item: { ...dish, available: false } })).toEqual(
      emptyCart,
    );
  });
  it('borne la quantité à 99', () => {
    const cart = cartReducer(emptyCart, { type: 'add', item: dish });
    expect(
      cartReducer(cart, { type: 'quantity', id: String(dish.id), quantity: 1000 }).lines[0]
        .quantity,
    ).toBe(99);
  });
  it('refuse une quantité décimale et retire une ligne à zéro', () => {
    const cart = cartReducer(emptyCart, { type: 'add', item: dish });
    expect(cartReducer(cart, { type: 'quantity', id: String(dish.id), quantity: 1.5 })).toEqual(
      cart,
    );
    expect(cartReducer(cart, { type: 'quantity', id: String(dish.id), quantity: 0 })).toEqual(
      emptyCart,
    );
  });
  it('calcule le total indicatif en décimal CDF', () => {
    expect(cartTotal([{ item: { ...dish, price: '2500.50' }, quantity: 3 }])).toBe('7501.50');
  });
  it('vide explicitement le panier', () => {
    const cart = cartReducer(emptyCart, { type: 'add', item: dish });
    expect(cartReducer(cart, { type: 'clear' })).toEqual(emptyCart);
  });
});
