// Prise en main : Assek ; fermeture volontaire et contrôle des versions.
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { request } from '../../services/http';
import { HoursEditor } from './HoursEditor';

vi.mock('../../services/http', async (importOriginal) => ({
  ...await importOriginal<typeof import('../../services/http')>(),
  request: vi.fn(),
}));

beforeEach(() => vi.resetAllMocks());
afterEach(() => vi.restoreAllMocks());

describe('Édition des horaires', () => {
  it('enregistre les horaires locaux et la version du bon restaurant', async () => {
    vi.mocked(request).mockResolvedValue({});
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const saved = vi.fn();
    render(<HoursEditor restaurantId="2" version={4} initial={[{ weekday: 7, opens_at: '13:00', closes_at: '20:00' }]} saved={saved} />);
    fireEvent.click(screen.getByRole('button', { name: 'Enregistrer les horaires' }));
    await waitFor(() => expect(request).toHaveBeenCalledWith('/restaurants/2/hours', 'PATCH', {
      expected_version: 4, hours: [{ weekday: 7, opens_at: '13:00', closes_at: '20:00' }],
    }));
    expect(saved).toHaveBeenCalledOnce();
  });

  it('ne ferme pas les commandes si l’utilisateur annule la confirmation', () => {
    vi.spyOn(window, 'confirm').mockReturnValue(false);
    render(<HoursEditor restaurantId="2" version={4} initial={[]} saved={vi.fn()} />);
    fireEvent.click(screen.getByRole('button', { name: 'Enregistrer les horaires' }));
    expect(request).not.toHaveBeenCalled();
  });
});
