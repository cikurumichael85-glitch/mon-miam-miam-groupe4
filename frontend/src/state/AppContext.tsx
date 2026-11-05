// Prise en main : Assek ; relecture croisée du binôme B.
import { useEffect, useReducer, useState } from 'react';
import type { ReactNode } from 'react';
import { cartReducer, emptyCart } from '../domain/cart';
import type { Mode, Restaurant, User } from '../domain/types';
import { ApiError, refreshCsrf, request } from '../services/http';
import { AppContext } from './AppState';

export { useApp } from './AppState';

export function AppProvider({ mode, children }: { mode: Mode; children: ReactNode }) {
  // Le changement de mode remonte ce contexte : aucune donnée de démonstration ne devient réelle.
  const [user, setUser] = useState<User | null>(null);
  const [authLoading, setAuthLoading] = useState(mode === 'api');
  const [restaurant, setRestaurant] = useState<Restaurant | null>(null);
  const [cart, dispatch] = useReducer(cartReducer, emptyCart);
  const [notice, notify] = useState('');
  useEffect(() => {
    if (!notice) {
      return;
    }
    const timer = window.setTimeout(() => notify(''), 6500);
    return () => window.clearTimeout(timer);
  }, [notice]);
  useEffect(() => {
    const expire = () => {
      setUser(null);
      dispatch({ type: 'clear' });
    };
    window.addEventListener('mmm:session-expired', expire);
    return () => window.removeEventListener('mmm:session-expired', expire);
  }, []);
  useEffect(() => {
    if (mode === 'demo') {
      return;
    }
    const controller = new AbortController();
    void request<{ data: User }>('/me', 'GET', undefined, controller.signal)
      .then((result) => {
        if (!controller.signal.aborted) {
          setUser(result.data);
        }
      })
      .catch((error: unknown) => {
        if (!(error instanceof ApiError && error.status === 401) && !controller.signal.aborted) {
          notify(
            'Connexion au serveur indisponible. La carte de démonstration reste accessible en changeant de mode.',
          );
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setAuthLoading(false);
        }
      });
    return () => controller.abort();
  }, [mode]);
  async function logout() {
    await request('/auth/logout', 'POST');
    setUser(null);
    dispatch({ type: 'clear' });
    await refreshCsrf();
  }
  return (
    <AppContext.Provider
      value={{
        mode,
        user,
        setUser,
        authLoading,
        restaurant,
        setRestaurant,
        cart,
        dispatch,
        notice,
        notify,
        logout,
      }}
    >
      {children}
    </AppContext.Provider>
  );
}
