// Prise en main : Assek ; séparation entre réponse et approbation.
import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { request } from '../../services/http';
import { useApp } from '../../state/AppContext';
import { StaffClaims } from './StaffClaims';

vi.mock('../../state/AppContext', () => ({ useApp: vi.fn() }));
vi.mock('../../services/http', async (importOriginal) => ({
  ...await importOriginal<typeof import('../../services/http')>(),
  request: vi.fn(),
}));

beforeEach(() => vi.resetAllMocks());

describe('Validation des réponses aux réclamations', () => {
  it.each([
    { role: 'serveur', manager: false, userId: 2, allowed: false },
    { role: 'gérant auteur', manager: true, userId: 1, allowed: false },
    { role: 'autre gérant', manager: true, userId: 2, allowed: true },
  ])('contrôle les actions du $role', async ({ manager, userId, allowed }) => {
    vi.mocked(useApp).mockReturnValue({ user: { id: userId } } as ReturnType<typeof useApp>);
    const claim = { id: 3, order_id: 5, subject: 'Réclamation fictive', message: 'Demande du client', status: 'awaiting_approval' };
    vi.mocked(request).mockImplementation(async (path) => path.includes('?page=')
      ? { data: [claim], last_page: 1 }
      : { data: claim, replies: [{ id: 4, author_id: 1, body: 'Réponse proposée', state: 'submitted' }] });
    render(<StaffClaims restaurantId="1" manager={manager} />);
    fireEvent.click(await screen.findByRole('button', { name: /Réclamation fictive/ }));
    await screen.findByText('Réponse proposée');
    if (allowed) {
      expect(screen.getByRole('button', { name: 'Approuver' })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'Refuser' })).toBeInTheDocument();
    } else {
      expect(screen.queryByRole('button', { name: 'Approuver' })).not.toBeInTheDocument();
      expect(screen.queryByRole('button', { name: 'Refuser' })).not.toBeInTheDocument();
    }
  });
});
