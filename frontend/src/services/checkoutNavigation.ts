// Prise en main : Michael ; aucune redirection vers un domaine de paiement arbitraire.
import type { Order } from '../domain/types';
import { messageOf, request } from './http';

export function secureCheckoutUrl(value: string): string {
  const url = new URL(value);
  if (
    url.protocol !== 'https:' ||
    url.hostname !== 'www.e-com-easypay.com' ||
    url.username ||
    url.password ||
    url.port
  ) {
    throw new Error('Adresse de paiement inattendue. Contactez le restaurant.');
  }
  return url.href;
}

export function openHostedCheckout(value: string): void {
  window.location.assign(secureCheckoutUrl(value));
}

export async function prepareHostedCheckout(orderId: string | number): Promise<string> {
  const result = await request<{ checkout_url: string | null }>(
    `/me/orders/${orderId}/payment`,
    'POST',
  );
  if (!result.checkout_url) {
    throw new Error('Aucun lien EasyPay confirmé. Actualisez la tentative avant de réessayer.');
  }
  return secureCheckoutUrl(result.checkout_url);
}

// Une panne de paiement ne doit jamais masquer une commande déjà enregistrée.
export async function checkoutAfterOrder(order: Order, available: boolean) {
  if (!available || order.total === '0.00') {
    return {
      url: null,
      notice: 'Commande enregistrée. Retrouvez son état de règlement dans Mes commandes.',
    };
  }
  try {
    const url = await prepareHostedCheckout(order.id);
    return { url, notice: 'Commande enregistrée. Ouverture du paiement sécurisé EasyPay.' };
  } catch (error: unknown) {
    return {
      url: null,
      notice: `Commande enregistrée, paiement non confirmé. ${messageOf(error)} Consultez Mes commandes ou contactez le restaurant avant tout nouvel essai.`,
    };
  }
}
