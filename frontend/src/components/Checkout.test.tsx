// Prise en main : Michael ; vérifier le parcours immédiat sans inventer une date client.
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { Checkout } from './Checkout';
import { CartSheet } from './CartSheet';
import { request } from '../services/http';
import { useApp } from '../state/AppContext';
import { openHostedCheckout } from '../services/checkoutNavigation';

const capability = vi.hoisted(() => ({ paymentAvailable: false, confirmedNetworks: true }));

vi.mock('../services/http', async (importOriginal) => {
  const original = await importOriginal<typeof import('../services/http')>();
  return { ...original, request: vi.fn() };
});
vi.mock('../state/AppContext', () => ({ useApp: vi.fn() }));
vi.mock('../services/checkoutNavigation', async (importOriginal) => {
  const original = await importOriginal<typeof import('../services/checkoutNavigation')>();
  return { ...original, openHostedCheckout: vi.fn() };
});
vi.mock('../hooks/useResource', () => ({
  useResource: () => ({
    data: {
      restaurant_id: '1',
      timezone: 'Africa/Kinshasa',
      delivery_enabled: false,
      delivery_fee: null,
      hours: [{ weekday: 6, opens_at: '08:00', closes_at: '20:00' }],
      payment: {
        available: capability.paymentAvailable,
        test_mode: false,
        networks:
          capability.paymentAvailable && capability.confirmedNetworks
            ? ['orange_money', 'mpesa', 'airtel_money']
            : [],
      },
    },
    loading: false,
    error: '',
    reload: vi.fn(),
  }),
}));

beforeEach(() => {
  vi.resetAllMocks();
  capability.paymentAvailable = false;
  capability.confirmedNetworks = true;
  vi.mocked(useApp).mockReturnValue({
    mode: 'api',
    cart: {
      restaurantId: '1',
      lines: [{ item: { id: 1, name: 'Plat de test', price: '2500.00' }, quantity: 1 }],
    },
    user: { id: 1 },
    notify: vi.fn(),
    dispatch: vi.fn(),
  } as unknown as ReturnType<typeof useApp>);
});

describe('Commander maintenant', () => {
  it('ne demande aucune date et transmet uniquement les articles au serveur', async () => {
    vi.mocked(request).mockResolvedValueOnce({
      data: {
        id: 'devis-test',
        expires_at: new Date(Date.now() + 600000).toISOString(),
        payload: {
          subtotal: '2500.00',
          discount: '0.00',
          delivery_fee: '0.00',
          total: '2500.00',
          currency: 'CDF',
        },
      },
    });
    render(<Checkout onConfirmed={vi.fn()} busy={vi.fn()} />);
    expect(screen.getByRole('combobox', { name: 'Quand souhaitez-vous commander ?' })).toHaveValue(
      'immediate',
    );
    expect(screen.queryByLabelText('Votre heure d’arrivée')).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: 'Vérifier le prix et continuer' }));
    await waitFor(() =>
      expect(request).toHaveBeenCalledWith('/restaurants/1/order-quotes', 'POST', {
        items: [{ item_id: 1, quantity: 1 }],
        service_mode: 'on_site',
        points: 0,
      }),
    );
    expect(await screen.findByRole('button', { name: /Confirmer ma commande/ })).toBeEnabled();
  });

  it('explique une arrivée planifiée passée sans appeler le serveur', async () => {
    render(<Checkout onConfirmed={vi.fn()} busy={vi.fn()} />);
    fireEvent.change(screen.getByRole('combobox', { name: 'Quand souhaitez-vous commander ?' }), {
      target: { value: 'scheduled' },
    });
    fireEvent.change(screen.getByLabelText('Votre heure d’arrivée'), {
      target: { value: '2020-01-01T12:00' },
    });
    fireEvent.click(screen.getByRole('button', { name: 'Vérifier le prix et continuer' }));
    expect(await screen.findByRole('alert')).toHaveTextContent('Choisissez Commander maintenant');
    expect(request).not.toHaveBeenCalled();
  });

  it('conserve la confirmation et le paiement après le vidage du panier', async () => {
    vi.mocked(request)
      .mockResolvedValueOnce({
        data: {
          id: 'devis-test',
          expires_at: new Date(Date.now() + 600000).toISOString(),
          payload: {
            subtotal: '2500.00',
            discount: '0.00',
            delivery_fee: '0.00',
            total: '2500.00',
            currency: 'CDF',
          },
        },
      })
      .mockResolvedValueOnce({
        data: {
          id: 3,
          restaurant_id: 1,
          status: 'pending',
          payment_state: 'unrecorded',
          service_mode: 'on_site',
          total: '2500.00',
          version: 1,
        },
        payment: { mode: 'cash', state: 'pending', verified_at: null },
      });
    render(<CartSheet close={vi.fn()} />);
    const context = vi.mocked(useApp).mock.results[0].value as ReturnType<typeof useApp>;
    vi.mocked(context.dispatch).mockImplementation((action) => {
      if (action.type === 'clear') {
        context.cart = { restaurantId: null, lines: [] };
      }
    });
    fireEvent.click(screen.getByRole('button', { name: 'Vérifier le prix et continuer' }));
    fireEvent.click(await screen.findByRole('button', { name: /Confirmer ma commande/ }));
    expect(await screen.findByText('Commande n° 3 transmise au restaurant.')).toBeInTheDocument();
    expect(context.cart.lines).toHaveLength(0);
    expect(screen.getByRole('heading', { name: 'Paiement en espèces' })).toBeInTheDocument();
    expect(
      screen.queryByRole('button', { name: 'Accéder au paiement sécurisé' }),
    ).not.toBeInTheDocument();
    expect(screen.queryByText('Votre panier attend ses saveurs.')).not.toBeInTheDocument();
  });

  it('ouvre EasyPay une seule fois après la confirmation de la commande créée', async () => {
    capability.paymentAvailable = true;
    const url = 'https://www.e-com-easypay.com/v1/payment/initialization?reference=TEST';
    vi.mocked(request)
      .mockResolvedValueOnce({
        data: {
          id: 'quote',
          expires_at: new Date(Date.now() + 600000).toISOString(),
          payload: {
            subtotal: '2500.00',
            discount: '0.00',
            delivery_fee: '0.00',
            total: '2500.00',
            currency: 'CDF',
          },
        },
      })
      .mockResolvedValueOnce({
        data: {
          id: 8,
          restaurant_id: 1,
          status: 'pending',
          payment_state: 'unrecorded',
          total: '2500.00',
        },
      })
      .mockResolvedValueOnce({ checkout_url: url });
    const confirmed = vi.fn();
    render(<Checkout onConfirmed={confirmed} busy={vi.fn()} />);
    fireEvent.change(screen.getByLabelText('Moyen de paiement'), { target: { value: 'easypay' } });
    fireEvent.click(screen.getByRole('button', { name: 'Vérifier le prix et continuer' }));
    fireEvent.click(
      await screen.findByRole('button', { name: /Confirmer ma commande et ouvrir EasyPay/ }),
    );
    await waitFor(() => expect(openHostedCheckout).toHaveBeenCalledWith(url));
    expect(openHostedCheckout).toHaveBeenCalledTimes(1);
    expect(request).toHaveBeenLastCalledWith('/me/orders/8/payment', 'POST');
    expect(confirmed).toHaveBeenCalledWith(
      expect.objectContaining({ id: 8, payment_state: 'unrecorded' }),
      expect.stringContaining('Ouverture'),
    );
  });

  it('ne prépare aucun paiement si aucun réseau marchand n’est confirmé', async () => {
    capability.paymentAvailable = true;
    capability.confirmedNetworks = false;
    vi.mocked(request)
      .mockResolvedValueOnce({
        data: {
          id: 'quote',
          expires_at: new Date(Date.now() + 600000).toISOString(),
          payload: {
            subtotal: '2500.00',
            discount: '0.00',
            delivery_fee: '0.00',
            total: '2500.00',
            currency: 'CDF',
          },
        },
      })
      .mockResolvedValueOnce({
        data: {
          id: 10,
          restaurant_id: 1,
          status: 'pending',
          payment_state: 'unrecorded',
          total: '2500.00',
        },
      });
    const confirmed = vi.fn();
    render(<Checkout onConfirmed={confirmed} busy={vi.fn()} />);
    fireEvent.click(screen.getByRole('button', { name: 'Vérifier le prix et continuer' }));
    fireEvent.click(await screen.findByRole('button', { name: /Confirmer ma commande/ }));
    await waitFor(() => expect(confirmed).toHaveBeenCalledTimes(1));
    expect(request).toHaveBeenCalledTimes(2);
    expect(openHostedCheckout).not.toHaveBeenCalled();
  });

  it('conserve le reçu sans annoncer payé lorsque la préparation EasyPay échoue', async () => {
    capability.paymentAvailable = true;
    vi.mocked(request)
      .mockResolvedValueOnce({
        data: {
          id: 'quote',
          expires_at: new Date(Date.now() + 600000).toISOString(),
          payload: {
            subtotal: '2500.00',
            discount: '0.00',
            delivery_fee: '0.00',
            total: '2500.00',
            currency: 'CDF',
          },
        },
      })
      .mockResolvedValueOnce({
        data: {
          id: 9,
          restaurant_id: 1,
          status: 'pending',
          payment_state: 'unrecorded',
          total: '2500.00',
        },
      })
      .mockRejectedValueOnce(new Error('Initialisation non confirmée par EasyPay.'));
    render(<CartSheet close={vi.fn()} />);
    fireEvent.change(screen.getByLabelText('Moyen de paiement'), { target: { value: 'easypay' } });
    fireEvent.click(screen.getByRole('button', { name: 'Vérifier le prix et continuer' }));
    fireEvent.click(
      await screen.findByRole('button', { name: /Confirmer ma commande et ouvrir EasyPay/ }),
    );
    expect(await screen.findByText('Commande n° 9 transmise au restaurant.')).toBeInTheDocument();
    expect(screen.getByText(/Commande enregistrée, paiement non confirmé/)).toBeInTheDocument();
    expect(openHostedCheckout).not.toHaveBeenCalled();
    expect(screen.queryByText(/Votre commande a été payée/)).not.toBeInTheDocument();
    expect(
      vi.mocked(request).mock.calls.filter(([path]) => path === '/restaurants/1/orders'),
    ).toHaveLength(1);
  });
});
