// Prise en main : Assek ; barèmes fictifs et approbation explicite.
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { request } from '../../services/http';
import { StaffLoyalty } from './StaffLoyalty';

vi.mock('../../services/http', async (importOriginal) => ({
  ...await importOriginal<typeof import('../../services/http')>(),
  request: vi.fn(),
}));

function fillProgram() {
  fireEvent.change(screen.getByLabelText('Montant dépensé pour gagner un point (FC)'), { target: { value: '1000.00' } });
  fireEvent.change(screen.getByLabelText('Points nécessaires par remise'), { target: { value: '15' } });
  fireEvent.change(screen.getByLabelText('Valeur de cette remise (FC)'), { target: { value: '1000.00' } });
  fireEvent.change(screen.getByLabelText('Points offerts au parrain'), { target: { value: '3' } });
  fireEvent.change(screen.getByLabelText('Durée de validité des points (jours)'), { target: { value: '30' } });
  fireEvent.click(screen.getByRole('checkbox', { name: /J’approuve explicitement/ }));
}

beforeEach(() => vi.resetAllMocks());
afterEach(() => vi.restoreAllMocks());

describe('Paramètres de fidélité', () => {
  it('ne propose aucun barème chiffré ni activation automatique', async () => {
    vi.mocked(request).mockResolvedValue({ data: null });
    render(<StaffLoyalty restaurantId="2" />);
    expect(await screen.findByLabelText('Programme actif')).toHaveValue('false');
    expect(screen.getByLabelText('Montant dépensé pour gagner un point (FC)')).toHaveValue('');
    expect(screen.getByRole('checkbox', { name: /J’approuve explicitement/ })).not.toBeChecked();
    expect(request).toHaveBeenCalledTimes(1);
  });

  it('envoie seulement les règles saisies et approuvées au bon restaurant', async () => {
    vi.mocked(request).mockResolvedValue({ data: null });
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    render(<StaffLoyalty restaurantId="2" />);
    await screen.findByLabelText('Programme actif');
    fillProgram();
    fireEvent.click(screen.getByRole('button', { name: 'Enregistrer le programme de fidélité' }));
    await waitFor(() => expect(request).toHaveBeenCalledWith('/restaurants/2/loyalty-program', 'POST', {
      active: false, approval: true,
      rules: { earn_amount: '1000.00', redeem_points: 15, redeem_value: '1000.00', referral_points: 3, expiry_days: 30, require_paid: true },
    }));
  });

  it('laisse le formulaire intact si la confirmation est annulée', async () => {
    vi.mocked(request).mockResolvedValue({ data: null });
    vi.spyOn(window, 'confirm').mockReturnValue(false);
    render(<StaffLoyalty restaurantId="2" />);
    await screen.findByLabelText('Programme actif');
    fillProgram();
    fireEvent.click(screen.getByRole('button', { name: 'Enregistrer le programme de fidélité' }));
    expect(request).toHaveBeenCalledTimes(1);
    expect(screen.getByLabelText('Points nécessaires par remise')).toHaveValue(15);
  });
});
