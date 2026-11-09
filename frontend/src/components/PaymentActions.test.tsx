// Prise en main : Michael ; contrôler la redirection sans appeler un marchand réel.
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { act, fireEvent, render, screen, waitFor } from '@testing-library/react';
import type { CheckoutOptions, Order } from '../domain/types';
import { request } from '../services/http';
import { openHostedCheckout } from '../services/checkoutNavigation';
import { PaymentActions } from './PaymentActions';

vi.mock('../services/http', async (importOriginal) => {
  const original = await importOriginal<typeof import('../services/http')>();
  return { ...original, request: vi.fn() };
});
vi.mock('../services/checkoutNavigation', async (importOriginal) => {
  const original = await importOriginal<typeof import('../services/checkoutNavigation')>();
  return { ...original, openHostedCheckout: vi.fn() };
});

const order: Order = {
  id: 1,
  restaurant_id: 1,
  status: 'pending',
  total: '2500.00',
  payment_state: 'unrecorded',
  service_mode: 'on_site',
  created_at: '2026-10-23T10:00:00Z',
  version: 1,
};
const options: CheckoutOptions = {
  restaurant_id: '1',
  timezone: 'Africa/Kinshasa',
  delivery_enabled: false,
  delivery_fee: null,
  hours: [],
  payment: {
    available: true,
    test_mode: false,
    networks: ['mpesa', 'airtel_money', 'orange_money'],
  },
};
const pending = { state: 'pending', verified_at: null, mode: 'v1' };
const checkoutUrl = 'https://www.e-com-easypay.com/v1/payment/initialization?reference=TEST';

beforeEach(() => vi.resetAllMocks());
afterEach(() => {
  vi.useRealTimers();
  vi.restoreAllMocks();
});

describe('Paiement hébergé EasyPay', () => {
  it('présente les espèces en attente sans ouvrir ni vérifier EasyPay', async () => {
    vi.mocked(request).mockResolvedValue(options);
    const refresh = vi.fn();
    render(
      <PaymentActions
        order={order}
        payment={{ mode: 'cash', state: 'pending', verified_at: null }}
        refresh={refresh}
      />,
    );
    expect(screen.getByText(/Votre règlement reste en attente/)).toBeInTheDocument();
    expect(
      screen.queryByRole('button', { name: 'Accéder au paiement sécurisé' }),
    ).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: 'Actualiser le règlement' }));
    expect(refresh).toHaveBeenCalledTimes(1);
    expect(openHostedCheckout).not.toHaveBeenCalled();
    expect(request).not.toHaveBeenCalledWith('/me/orders/1/payment/verify', 'POST');
  });

  it('enregistre le choix espèces puis recharge la commande sans la déclarer payée', async () => {
    vi.mocked(request)
      .mockResolvedValueOnce(options)
      .mockResolvedValueOnce({ data: { mode: 'cash', state: 'pending' } });
    const refresh = vi.fn();
    render(<PaymentActions order={order} payment={null} refresh={refresh} />);
    fireEvent.click(await screen.findByRole('button', { name: 'Choisir les espèces' }));
    await waitFor(() => expect(refresh).toHaveBeenCalledTimes(1));
    expect(request).toHaveBeenCalledWith('/me/orders/1/cash-payment', 'POST');
    expect(openHostedCheckout).not.toHaveBeenCalled();
  });
  it.each(['Orange Money', 'M-Pesa', 'Airtel Money'])(
    'le bouton $0 ouvre EasyPay après la préparation serveur',
    async (label) => {
      vi.mocked(request)
        .mockResolvedValueOnce(options)
        .mockResolvedValueOnce({ data: pending, checkout_url: checkoutUrl });
      render(<PaymentActions order={order} payment={null} refresh={vi.fn()} />);
      const network = await screen.findByRole('button', {
        name: `Payer avec ${label} via EasyPay`,
      });
      fireEvent.click(network);
      await waitFor(() => expect(openHostedCheckout).toHaveBeenCalledWith(checkoutUrl));
      expect(request).toHaveBeenCalledWith('/me/orders/1/payment', 'POST');
      expect(screen.queryByLabelText('Numéro Mobile Money')).not.toBeInTheDocument();
      expect(screen.queryByText(/Votre commande a été payée/)).not.toBeInTheDocument();
    },
  );

  it('le bouton général ouvre le même guichet et conserve un lien de secours', async () => {
    vi.mocked(request)
      .mockResolvedValueOnce(options)
      .mockResolvedValueOnce({ data: pending, checkout_url: checkoutUrl });
    const refresh = vi.fn();
    render(<PaymentActions order={order} payment={null} refresh={refresh} />);
    const button = await screen.findByRole('button', { name: 'Accéder au paiement sécurisé' });
    await waitFor(() => expect(button).toBeEnabled());
    fireEvent.click(button);
    await waitFor(() => expect(openHostedCheckout).toHaveBeenCalledWith(checkoutUrl));
    expect(screen.getByRole('link', { name: 'Ouvrir le guichet EasyPay' })).toHaveAttribute(
      'href',
      checkoutUrl,
    );
    expect(refresh).not.toHaveBeenCalled();
  });

  it('bloque les boutons lorsque le marchand est absent', async () => {
    vi.mocked(request).mockResolvedValueOnce({
      ...options,
      payment: { available: false, test_mode: false, networks: [] },
    });
    render(<PaymentActions order={order} payment={null} refresh={vi.fn()} />);
    await screen.findByText(/n’a pas encore activé son compte marchand/);
    expect(screen.getByRole('button', { name: 'Accéder au paiement sécurisé' })).toBeDisabled();
  });

  it.each(['unknown', 'pending', 'declined', 'cancelled'])(
    'ne présente jamais une tentative %s comme payée',
    async (state) => {
      vi.mocked(request).mockResolvedValueOnce(options);
      render(<PaymentActions order={order} payment={{ ...pending, state }} refresh={vi.fn()} />);
      await screen.findByRole('button', { name: 'Accéder au paiement sécurisé' });
      expect(screen.queryByText(/Votre commande a été payée/)).not.toBeInTheDocument();
      expect(openHostedCheckout).not.toHaveBeenCalled();
    },
  );

  it('affiche le règlement confirmé uniquement pour le paiement vérifié par le serveur', async () => {
    vi.mocked(request).mockResolvedValueOnce(options);
    render(
      <PaymentActions
        order={{ ...order, payment_state: 'paid' }}
        payment={{ ...pending, state: 'paid', verified_at: '2026-10-26T12:00:00Z' }}
        refresh={vi.fn()}
      />,
    );
    expect(await screen.findByText(/Votre commande a été payée/)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Accéder au paiement sécurisé' })).toBeDisabled();
    expect(openHostedCheckout).not.toHaveBeenCalled();
  });

  it.each([
    { state: 'paid', verified_at: null, mode: 'v1' },
    { state: 'paid', verified_at: '2026-10-26T12:00:00Z', mode: 'sandbox' },
  ])('ne confirme pas un règlement réel pour une preuve insuffisante', async (payment) => {
    vi.mocked(request).mockResolvedValueOnce(options);
    render(<PaymentActions order={order} payment={payment} refresh={vi.fn()} />);
    await screen.findByRole('button', { name: 'Accéder au paiement sécurisé' });
    expect(screen.queryByText(/Votre commande a été payée/)).not.toBeInTheDocument();
    expect(openHostedCheckout).not.toHaveBeenCalled();
  });

  it.each(['Orange Money', 'M-Pesa', 'Airtel Money'])(
    'le bouton $0 ouvre un paiement réel pour la même ancienne commande de test',
    async (label) => {
      vi.mocked(request)
        .mockResolvedValueOnce(options)
        .mockResolvedValueOnce({ data: pending, checkout_url: checkoutUrl });
      render(
        <PaymentActions
          order={order}
          payment={{ ...pending, mode: 'sandbox' }}
          refresh={vi.fn()}
        />,
      );
      expect(await screen.findByText('Passer au paiement réel')).toBeInTheDocument();
      const button = screen.getByRole('button', { name: `Payer avec ${label} via EasyPay` });
      expect(button).toBeEnabled();
      expect(request).toHaveBeenCalledTimes(1);
      fireEvent.click(button);
      await waitFor(() => expect(openHostedCheckout).toHaveBeenCalledWith(checkoutUrl));
      expect(request).toHaveBeenCalledWith('/me/orders/1/payment', 'POST');
      expect(
        screen.queryByRole('button', { name: 'Vérifier le paiement' }),
      ).not.toBeInTheDocument();
      expect(
        screen.queryByRole('link', { name: 'Ouvrir la carte pour une nouvelle commande' }),
      ).not.toBeInTheDocument();
    },
  );
  it('reprend le guichet de la tentative en attente du mode courant', async () => {
    vi.mocked(request)
      .mockResolvedValueOnce(options)
      .mockResolvedValueOnce({ data: pending, checkout_url: checkoutUrl });
    render(<PaymentActions order={order} payment={pending} refresh={vi.fn()} />);
    const button = await screen.findByRole('button', { name: 'Accéder au paiement sécurisé' });
    await waitFor(() => expect(button).toBeEnabled());
    fireEvent.click(button);
    await waitFor(() => expect(openHostedCheckout).toHaveBeenCalledWith(checkoutUrl));
    expect(request).toHaveBeenCalledWith('/me/orders/1/payment', 'POST');
  });

  it('refuse une redirection étrangère au prestataire', async () => {
    vi.mocked(request)
      .mockResolvedValueOnce(options)
      .mockResolvedValueOnce({ checkout_url: 'https://example.test/faux-paiement' });
    render(<PaymentActions order={order} payment={null} refresh={vi.fn()} />);
    const button = await screen.findByRole('button', { name: 'Accéder au paiement sécurisé' });
    await waitFor(() => expect(button).toBeEnabled());
    fireEvent.click(button);
    expect(await screen.findByRole('alert')).toHaveTextContent('Adresse de paiement inattendue');
    expect(openHostedCheckout).not.toHaveBeenCalled();
    expect(
      screen.queryByRole('link', { name: 'Ouvrir le guichet EasyPay' }),
    ).not.toBeInTheDocument();
  });

  it('conserve une erreur de préparation et ne propose aucune relance automatique', async () => {
    vi.mocked(request)
      .mockResolvedValueOnce(options)
      .mockRejectedValueOnce(new Error('Initialisation incertaine.'));
    render(<PaymentActions order={order} payment={null} refresh={vi.fn()} />);
    const button = await screen.findByRole('button', { name: 'Accéder au paiement sécurisé' });
    await waitFor(() => expect(button).toBeEnabled());
    fireEvent.click(button);
    expect(await screen.findByRole('alert')).toHaveTextContent('Initialisation incertaine');
    expect(button).toBeDisabled();
    expect(openHostedCheckout).not.toHaveBeenCalled();
  });

  it('contrôle automatiquement une tentative existante puis arrête après six réponses en attente', async () => {
    vi.mocked(request).mockImplementation(async (path) =>
      path.endsWith('checkout-options') ? options : { data: pending },
    );
    const refresh = vi.fn();
    const view = render(<PaymentActions order={order} payment={pending} refresh={refresh} />);
    await screen.findByText(/Six vérifications automatiques/);
    // Remonter sous fausse horloge pour contrôler le délai du suivi sans attendre réellement.
    view.unmount();
    vi.spyOn(document, 'hidden', 'get').mockReturnValue(false);
    vi.useFakeTimers();
    render(<PaymentActions order={order} payment={pending} refresh={refresh} />);
    await act(async () => {
      await Promise.resolve();
    });
    for (let index = 0; index < 6; index += 1) {
      await act(async () => {
        await vi.advanceTimersByTimeAsync(10000);
      });
    }
    const checks = vi
      .mocked(request)
      .mock.calls.filter(([path]) => path.endsWith('/payment/verify'));
    expect(checks).toHaveLength(6);
    await act(async () => {
      await vi.advanceTimersByTimeAsync(30000);
    });
    expect(
      vi.mocked(request).mock.calls.filter(([path]) => path.endsWith('/payment/verify')),
    ).toHaveLength(6);
    expect(refresh).not.toHaveBeenCalled();
    expect(screen.getByRole('status')).toHaveTextContent('Toujours sans confirmation');
  });
});
