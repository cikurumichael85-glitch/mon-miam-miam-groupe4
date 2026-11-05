// Recette du binôme B : gestion de carte autorisée et accès serveur limité.
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { menu } from '../../services/catalogue';
import { request } from '../../services/http';
import { StaffCatalogue } from './StaffCatalogue';

vi.mock('../../services/catalogue', () => ({ menu: vi.fn() }));
vi.mock('../../services/http', async (importOriginal) => ({
  ...await importOriginal<typeof import('../../services/http')>(),
  request: vi.fn(),
}));

beforeEach(() => {
  vi.resetAllMocks();
  vi.mocked(menu).mockResolvedValue({
    categories: [{ id: 2, restaurant_id: 1, name: 'Plats' }],
    items: [{ id: 3, restaurant_id: 1, category_id: 2, name: 'Riz aux haricots', description: '', price: '3000.00', available: true, is_daily: false, version: 1 }],
  });
  vi.mocked(request).mockResolvedValue({});
});

describe('Carte administrateur et serveur', () => {
  it('permet à la gestion de créer un article au prix saisi', async () => {
    render(<StaffCatalogue restaurantId="1" manager />);
    await screen.findByText('Riz aux haricots');
    fireEvent.click(screen.getByRole('button', { name: 'Ajouter un article' }));
    fireEvent.change(screen.getByLabelText('Nom de l’article'), { target: { value: 'Fumbwa de recette' } });
    fireEvent.change(screen.getByLabelText('Prix en FC (exemple : 12500.00)'), { target: { value: '5000.00' } });
    fireEvent.click(screen.getByRole('button', { name: 'Enregistrer l’article' }));
    await waitFor(() => expect(request).toHaveBeenCalledWith('/restaurants/1/items', 'POST', {
      category_id: 2, name: 'Fumbwa de recette', description: '', price: '5000.00', available: true, is_daily: false,
    }));
  });

  it('archive uniquement après confirmation et avec la version courante', async () => {
    const confirmation = vi.spyOn(window, 'confirm').mockReturnValue(true);
    render(<StaffCatalogue restaurantId="1" manager />);
    fireEvent.click(await screen.findByRole('button', { name: 'Archiver' }));
    await waitFor(() => expect(request).toHaveBeenCalledWith('/restaurants/1/items/3/archive', 'POST', { expected_version: 1 }));
    expect(confirmation).toHaveBeenCalledOnce();
    confirmation.mockRestore();
  });

  it('autorise le serveur à signaler une rupture sans modifier les prix ni archiver', async () => {
    render(<StaffCatalogue restaurantId="1" manager={false} />);
    fireEvent.click(await screen.findByRole('button', { name: 'Marquer épuisé' }));
    expect(screen.queryByRole('button', { name: 'Ajouter un article' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Modifier' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Archiver' })).not.toBeInTheDocument();
    await waitFor(() => expect(request).toHaveBeenCalledWith('/restaurants/1/items/3/availability', 'PATCH', {
      available: false, is_daily: false, expected_version: 1,
    }));
  });
});
