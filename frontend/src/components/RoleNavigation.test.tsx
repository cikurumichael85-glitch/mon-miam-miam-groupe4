// Prise en main : Michael ; visibilité des actions propres aux trois parcours.
import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { Header } from './Layout';
import { useApp } from '../state/AppContext';
import type { Role } from '../domain/types';

vi.mock('../state/AppContext', () => ({ useApp: vi.fn() }));
afterEach(cleanup);

function show(role?: Role) {
  vi.mocked(useApp).mockReturnValue({
    user: { id: 1, name: 'Compte de test', email: 'test@example.test', phone: '', delivery_address: null, ranking_opt_in: false, public_alias: null, analytics_consent: false, memberships: role ? [{ id: 1, restaurant_id: 1, user_id: 1, role, active: true }] : [] },
    mode: 'api', cart: { restaurantId: null, lines: [] }, logout: vi.fn(), notify: vi.fn(),
  } as unknown as ReturnType<typeof useApp>);
  render(<Header page="accueil" openCart={vi.fn()} switchMode={vi.fn()} />);
}

describe('Navigation par rôle', () => {
  it('montre au client la carte, les commandes et son panier', () => {
    show();
    expect(screen.getByRole('link', { name: 'Commander' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Mon panier/ })).toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Mon restaurant' })).not.toBeInTheDocument();
  });
  it('réserve la navigation de gestion à l’administrateur', () => {
    show('restaurant_admin');
    expect(screen.getByRole('link', { name: 'Mon restaurant' })).toHaveAttribute('href', '#administration');
    expect(screen.queryByRole('button', { name: /Mon panier/ })).not.toBeInTheDocument();
  });
  it('présente au serveur son espace de service', () => {
    show('employee');
    expect(screen.getByRole('link', { name: 'Mon service' })).toHaveAttribute('href', '#service');
    expect(screen.queryByRole('link', { name: 'Mon restaurant' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /Mon panier/ })).not.toBeInTheDocument();
  });
});
