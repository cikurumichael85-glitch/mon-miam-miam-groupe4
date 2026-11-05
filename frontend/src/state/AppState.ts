// Prise en main : Assek ; contexte stable même lors des mises à jour du fournisseur en développement.
import { createContext, useContext } from 'react';
import type { Dispatch } from 'react';
import type { CartAction } from '../domain/cart';
import type { Cart, Mode, Restaurant, User } from '../domain/types';

interface AppContextValue {
  mode: Mode;
  user: User | null;
  authLoading: boolean;
  setUser: (user: User | null) => void;
  restaurant: Restaurant | null;
  setRestaurant: (restaurant: Restaurant) => void;
  cart: Cart;
  dispatch: Dispatch<CartAction>;
  notice: string;
  notify: (message: string) => void;
  logout: () => Promise<void>;
}

export const AppContext = createContext<AppContextValue | null>(null);

export function useApp(): AppContextValue {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error('Contexte de l’application absent.');
  }
  return context;
}
