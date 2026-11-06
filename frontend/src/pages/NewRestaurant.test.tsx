// Prise en main : Assek ; création simulée, aucune écriture de restaurant réel.
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { Role, User } from '../domain/types';
import { useApp } from '../state/AppContext';
import { request } from '../services/http';
import { NewRestaurant } from './NewRestaurant';

vi.mock('../state/AppContext', () => ({ useApp: vi.fn() }));
vi.mock('../services/http', async (importOriginal) => ({
  ...(await importOriginal<typeof import('../services/http')>()),
  request: vi.fn(),
}));

function contextFor(role?: Role) {
  const user: User = {
    id: 1,
    name: 'Compte fictif',
    email: 'test@example.test',
    phone: '',
    delivery_address: null,
    ranking_opt_in: false,
    public_alias: null,
    analytics_consent: false,
    memberships: role ? [{ id: 1, restaurant_id: 1, user_id: 1, role, active: true }] : [],
  };
  const context = { user, setUser: vi.fn(), setRestaurant: vi.fn(), notify: vi.fn() };
  vi.mocked(useApp).mockReturnValue(context as unknown as ReturnType<typeof useApp>);
  return context;
}

function fillRestaurant() {
  fireEvent.change(screen.getByLabelText('Nom du restaurant'), {
    target: { value: 'Nouvelle table fictive' },
  });
  fireEvent.change(screen.getByLabelText('Adresse complète'), {
    target: { value: 'Adresse fictive B' },
  });
  fireEvent.change(screen.getByLabelText('Fuseau horaire'), {
    target: { value: 'Africa/Lubumbashi' },
  });
}

beforeEach(() => vi.resetAllMocks());
afterEach(() => {
  window.location.hash = '';
});

describe('Ajout d’un restaurant', () => {
  it.each([undefined, 'employee', 'manager'] as const)(
    'refuse le formulaire au rôle %s',
    (role) => {
      contextFor(role);
      render(<NewRestaurant />);
      expect(
        screen.queryByRole('button', { name: 'Créer mon nouveau restaurant' }),
      ).not.toBeInTheDocument();
      expect(request).not.toHaveBeenCalled();
    },
  );

  it('sélectionne le nouveau restaurant et conserve les rattachements existants', async () => {
    const context = contextFor('restaurant_admin');
    const membership = {
      id: 2,
      restaurant_id: 2,
      user_id: 1,
      role: 'restaurant_admin' as const,
      active: true,
    };
    const restaurant = {
      id: 2,
      name: 'Nouvelle table fictive',
      timezone: 'Africa/Lubumbashi',
      status: 'active',
      delivery_enabled: false,
    };
    vi.mocked(request)
      .mockResolvedValueOnce({ data: context.user })
      .mockResolvedValueOnce({ data: restaurant, membership });
    render(<NewRestaurant />);
    fillRestaurant();
    fireEvent.click(screen.getByRole('button', { name: 'Créer mon nouveau restaurant' }));
    await waitFor(() =>
      expect(request).toHaveBeenCalledWith('/me/restaurants', 'POST', {
        name: restaurant.name,
        address: 'Adresse fictive B',
        timezone: restaurant.timezone,
        delivery_enabled: false,
        delivery_fee: '0.00',
      }),
    );
    await waitFor(() =>
      expect(context.setUser).toHaveBeenCalledWith({
        ...context.user,
        memberships: [...context.user.memberships!, membership],
      }),
    );
    expect(context.setRestaurant).toHaveBeenCalledWith({ ...restaurant, id: '2' });
    expect(window.location.hash).toBe('#administration');
  });

  it('reste sur le formulaire si le serveur refuse la création', async () => {
    const context = contextFor('restaurant_admin');
    vi.mocked(request)
      .mockResolvedValueOnce({ data: context.user })
      .mockRejectedValueOnce(new Error('Autorisation de création refusée.'));
    render(<NewRestaurant />);
    fillRestaurant();
    fireEvent.click(screen.getByRole('button', { name: 'Créer mon nouveau restaurant' }));
    expect(await screen.findByRole('alert')).toHaveTextContent('Autorisation de création refusée.');
    expect(context.setRestaurant).not.toHaveBeenCalled();
    expect(context.setUser).toHaveBeenCalledWith(context.user);
  });

  it('refuse de créer si un autre onglet a connecté un client', async () => {
    const context = contextFor('restaurant_admin');
    const client = { ...context.user, email: 'client@example.test', memberships: [] };
    vi.mocked(request).mockResolvedValueOnce({ data: client });
    render(<NewRestaurant />);
    fillRestaurant();
    fireEvent.click(screen.getByRole('button', { name: 'Créer mon nouveau restaurant' }));
    expect(await screen.findByRole('alert')).toHaveTextContent('client@example.test');
    expect(request).toHaveBeenCalledOnce();
    expect(context.setUser).toHaveBeenCalledWith(client);
    expect(context.setRestaurant).not.toHaveBeenCalled();
  });
});
