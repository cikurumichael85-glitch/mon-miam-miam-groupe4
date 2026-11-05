// Prise en main : Assek ; journal et parrainage simulés, aucun solde réel modifié.
import { act, fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useApp } from '../state/AppContext';
import { request } from '../services/http';
import { Loyalty } from './Loyalty';

vi.mock('../state/AppContext', () => ({ useApp: vi.fn() }));
vi.mock('../services/http', async (importOriginal) => ({
  ...await importOriginal<typeof import('../services/http')>(),
  request: vi.fn(),
}));

beforeEach(() => {
  vi.resetAllMocks();
  vi.mocked(useApp).mockReturnValue({ restaurant: { id: '1' } } as ReturnType<typeof useApp>);
  vi.mocked(request).mockImplementation(async (path) => {
    if (path.includes('/entries')) {
      return { data: [{ id: 1, delta: 5, kind: 'earn', occurred_at: '2026-10-24T12:00:00Z' }], last_page: 1 };
    }
    if (path.endsWith('/referral-code')) {
      return { data: { code: 'code-fictif-restaurant-1' } };
    }
    if (path === '/me/loyalty-accounts') {
      return { data: [{ id: 1, balance: 5, program: { restaurant_id: 1, rules: {} } }] };
    }
    return { data: [] };
  });
});

describe('Fidélité et parrainage', () => {
  it('affiche la date occurred_at réellement renvoyée par le journal API', async () => {
    render(<Loyalty />);
    fireEvent.click(await screen.findByRole('button', { name: 'Voir les mouvements' }));
    expect(await screen.findByText(new Date('2026-10-24T12:00:00Z').toLocaleDateString('fr-CD'))).toBeInTheDocument();
    expect(screen.queryByText('Invalid Date')).not.toBeInTheDocument();
    expect(screen.getByText('+5 points')).toBeInTheDocument();
  });

  it('retire le code affiché si le client change de restaurant', async () => {
    render(<Loyalty />);
    fireEvent.click(screen.getByRole('button', { name: 'Générer mon code' }));
    expect(await screen.findByText('code-fictif-restaurant-1')).toBeInTheDocument();
    fireEvent.change(screen.getByLabelText('Identifiant du restaurant connecté'), { target: { value: '2' } });
    expect(screen.queryByText('code-fictif-restaurant-1')).not.toBeInTheDocument();
  });

  it('ignore un code reçu tardivement pour le restaurant précédent', async () => {
    let resolveCode: (value: unknown) => void = () => {};
    const pendingCode = new Promise((resolve) => { resolveCode = resolve; });
    vi.mocked(request).mockImplementation(async (path) => path.endsWith('/referral-code') ? pendingCode : { data: [] });
    render(<Loyalty />);
    fireEvent.click(screen.getByRole('button', { name: 'Générer mon code' }));
    fireEvent.change(screen.getByLabelText('Identifiant du restaurant connecté'), { target: { value: '2' } });
    await act(async () => resolveCode({ data: { code: 'ancien-code-tardif' } }));
    expect(screen.queryByText('ancien-code-tardif')).not.toBeInTheDocument();
  });
});
