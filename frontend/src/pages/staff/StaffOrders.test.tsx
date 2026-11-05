// Recette du binôme B : requêtes simulées, aucun compte réel ni paiement.
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Order, OrderDetail } from '../../domain/types';
import { request } from '../../services/http';
import { StaffOrders } from './StaffOrders';

vi.mock('../../services/http', async (importOriginal) => ({
  ...await importOriginal<typeof import('../../services/http')>(),
  request: vi.fn(),
}));

const deliveryOrder: Order = {
  id: 7,
  restaurant_id: 1,
  status: 'ready',
  total: '7000.00',
  payment_state: 'unrecorded',
  service_mode: 'delivery',
  delivery_snapshot: { address: 'Adresse fictive de recette', phone: '000000000', instructions: 'Entrée de test' },
  created_at: '2026-10-26T10:00:00Z',
  version: 4,
};

function mockOrders(order: Order) {
  const detail: OrderDetail = {
    data: order,
    lines: [{ id: 1, name: 'Riz aux haricots', quantity: 2, unit_price: '3000.00' }],
    events: [],
    payment: null,
  };
  vi.mocked(request).mockImplementation(async (path, method) => {
    if (method === 'POST') {
      return { data: { ...order, version: order.version + 1 } };
    }
    return path.includes('?page=') ? { data: [order], last_page: 1 } : detail;
  });
}

async function openOrder() {
  fireEvent.click(await screen.findByRole('button', { name: /N° 7/ }));
  await screen.findByRole('combobox', { name: 'Étape suivante' });
}

beforeEach(() => vi.resetAllMocks());

describe('Prise en charge des commandes par le serveur', () => {
  it('affiche les coordonnées et propose le départ avant la remise en livraison', async () => {
    mockOrders(deliveryOrder);
    render(<StaffOrders restaurantId="1" manager={false} />);
    await openOrder();
    expect(screen.getByText('Adresse fictive de recette')).toBeInTheDocument();
    expect(screen.getByText('000000000')).toBeInTheDocument();
    expect(screen.getByText('Entrée de test')).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'En livraison' })).toBeInTheDocument();
    expect(screen.queryByRole('option', { name: 'Servie' })).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: 'Confirmer cette étape de service' }));
    await waitFor(() => expect(request).toHaveBeenCalledWith('/restaurants/1/orders/7/transitions', 'POST', {
      status: 'out_for_delivery', reason: '', expected_version: 4,
    }));
  });

  it('permet la remise sur place sans étape de livraison', async () => {
    mockOrders({ ...deliveryOrder, service_mode: 'on_site', delivery_snapshot: null });
    render(<StaffOrders restaurantId="1" manager={false} />);
    await openOrder();
    expect(screen.getByText('Commande immédiate, sans arrivée planifiée.')).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Servie' })).toBeInTheDocument();
    expect(screen.queryByRole('option', { name: 'En livraison' })).not.toBeInTheDocument();
    expect(screen.queryByText('Adresse fictive de recette')).not.toBeInTheDocument();
  });

  it('réserve l’annulation et le refus à la gestion', async () => {
    mockOrders({ ...deliveryOrder, status: 'pending', version: 1 });
    render(<StaffOrders restaurantId="1" manager={false} />);
    await openOrder();
    expect(screen.getByRole('option', { name: 'Acceptée' })).toBeInTheDocument();
    expect(screen.queryByRole('option', { name: 'Annulée' })).not.toBeInTheDocument();
    expect(screen.queryByRole('option', { name: 'Refusée' })).not.toBeInTheDocument();
  });

  it('affiche une erreur de version sans annoncer une réussite', async () => {
    mockOrders(deliveryOrder);
    render(<StaffOrders restaurantId="1" manager={false} />);
    await openOrder();
    vi.mocked(request).mockRejectedValueOnce(new Error('La commande a changé.'));
    fireEvent.click(screen.getByRole('button', { name: 'Confirmer cette étape de service' }));
    expect(await screen.findByRole('alert')).toHaveTextContent('La commande a changé.');
    expect(screen.queryByText('État actualisé par le serveur.')).not.toBeInTheDocument();
  });
});
