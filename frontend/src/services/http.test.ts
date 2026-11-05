// Prise en main : Michael ; relecture croisée du binôme B.
import { afterEach, describe, expect, it, vi } from 'vitest';

afterEach(() => {
  vi.unstubAllGlobals();
  vi.resetModules();
});
function response(payload: unknown, status = 200) {
  return new Response(JSON.stringify(payload), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}
describe('Client Laravel et protection des mutations', () => {
  it.each([
    [401, 'Unauthenticated.', 'Connexion nécessaire'],
    [403, 'This action is unauthorized.', 'droits nécessaires'],
    [
      404,
      'No query results for model [App\\Models\\Order] 999',
      'Cet élément n’est pas disponible',
    ],
    [429, 'Too Many Attempts.', 'Trop de demandes'],
    [500, 'Server Error', 'Le serveur rencontre une difficulté'],
  ])(
    'présente une erreur %s compréhensible sans détail technique',
    async (status, message, expected) => {
      const fetchMock = vi.fn().mockResolvedValue(response({ message }, Number(status)));
      vi.stubGlobal('fetch', fetchMock);
      const { request } = await import('./http');
      await expect(request('/me/orders')).rejects.toMatchObject({
        status,
        message: expect.stringContaining(String(expected)),
      });
      expect(fetchMock).toHaveBeenCalledOnce();
    },
  );

  it('explique une erreur JSON mal formée sans rejouer la demande', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValue(
        new Response('{invalide', { status: 502, headers: { 'Content-Type': 'application/json' } }),
      );
    vi.stubGlobal('fetch', fetchMock);
    const { request } = await import('./http');
    await expect(request('/me/orders')).rejects.toMatchObject({
      status: 502,
      message: expect.stringContaining('réponse illisible'),
    });
    expect(fetchMock).toHaveBeenCalledOnce();
  });

  it('gère une erreur JSON null sans planter sur ses propriétés', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(response(null, 404)));
    const { request } = await import('./http');
    await expect(request('/me/orders')).rejects.toMatchObject({
      status: 404,
      message: expect.stringContaining('Cet élément'),
    });
  });

  it('conserve un message visible si le serveur renvoie une erreur sans texte', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(response({ message: '' }, 404)));
    const { request } = await import('./http');
    await expect(request('/me/orders')).rejects.toMatchObject({
      status: 404,
      message: expect.stringContaining('Cet élément'),
    });
  });

  it('explique une réponse HTML à la place de données API', async () => {
    vi.stubGlobal(
      'fetch',
      vi
        .fn()
        .mockResolvedValue(
          new Response('<html>Indisponible</html>', {
            status: 503,
            headers: { 'Content-Type': 'text/html' },
          }),
        ),
    );
    const { request } = await import('./http');
    await expect(request('/me/orders')).rejects.toMatchObject({
      status: 503,
      message: expect.stringContaining('Réponse API inattendue'),
    });
  });
  it('ne répète pas le message de validation déjà présent dans les champs', async () => {
    const { ApiError, messageOf } = await import('./http');
    const text = 'L’heure planifiée est déjà passée.';
    expect(messageOf(new ApiError(422, text, { arrival_at: [text, text] }))).toBe(text);
  });
  it('initialise le CSRF puis envoie les cookies et le jeton', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(response({ csrf_token: 'test-csrf' }))
      .mockResolvedValueOnce(response({ data: { id: 7 } }));
    vi.stubGlobal('fetch', fetchMock);
    const { request } = await import('./http');
    await request('/me/orders/7/claims', 'POST', { subject: 'Test', message: 'Message' });
    expect(fetchMock).toHaveBeenCalledTimes(2);
    expect(fetchMock.mock.calls[1][1]).toMatchObject({
      credentials: 'include',
      headers: { 'X-CSRF-TOKEN': 'test-csrf' },
    });
  });
  it('renouvelle un jeton devenu ancien avant le premier clic de paiement', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(response({ csrf_token: 'ancienne-session' }))
      .mockResolvedValueOnce(response({ csrf_token: 'session-actuelle' }))
      .mockResolvedValueOnce(
        response({
          checkout_url: 'https://www.e-com-easypay.com/v2/payment/initialization?reference=test',
        }),
      );
    vi.stubGlobal('fetch', fetchMock);
    const { refreshCsrf, request } = await import('./http');
    await refreshCsrf();
    await request('/me/orders/7/payment', 'POST');
    expect(fetchMock.mock.calls[2][1].headers['X-CSRF-TOKEN']).toBe('session-actuelle');
    expect(fetchMock.mock.calls.filter(([url]) => url.endsWith('/payment'))).toHaveLength(1);
  });
  it('renouvelle la session avant un ajout de restaurant sans répéter la création', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(response({ csrf_token: 'avant' }))
      .mockResolvedValueOnce(response({ data: {} }))
      .mockResolvedValueOnce(response({ csrf_token: 'apres' }))
      .mockResolvedValueOnce(response({ data: { id: 3 } }, 201));
    vi.stubGlobal('fetch', fetchMock);
    const { request } = await import('./http');
    await request('/auth/login', 'POST', {});
    await request('/me/restaurants', 'POST', { name: 'Restaurant fictif' });
    expect(fetchMock.mock.calls[3][1].headers['X-CSRF-TOKEN']).toBe('apres');
    expect(fetchMock.mock.calls.filter(([url]) => url.endsWith('/me/restaurants'))).toHaveLength(1);
  });
  it('n’envoie aucune mutation si la préparation de session échoue', async () => {
    const fetchMock = vi.fn().mockResolvedValue(response({ message: 'Indisponible' }, 503));
    vi.stubGlobal('fetch', fetchMock);
    const { request } = await import('./http');
    await expect(request('/me/orders/7/payment', 'POST')).rejects.toMatchObject({ status: 503 });
    expect(fetchMock).toHaveBeenCalledOnce();
  });
  it('ne rejoue pas une confirmation après une erreur 419', async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(response({ csrf_token: 'test' }))
      .mockResolvedValueOnce(response({ message: 'CSRF expiré' }, 419));
    vi.stubGlobal('fetch', fetchMock);
    const { request } = await import('./http');
    await expect(request('/restaurants/1/orders', 'POST', {})).rejects.toMatchObject({
      status: 419,
    });
    expect(fetchMock).toHaveBeenCalledTimes(2);
  });
  it('signale une session expirée', async () => {
    const listener = vi.fn();
    window.addEventListener('mmm:session-expired', listener);
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(response({ message: 'Non connecté' }, 401)));
    const { request } = await import('./http');
    await expect(request('/me')).rejects.toMatchObject({ status: 401 });
    expect(listener).toHaveBeenCalledOnce();
    window.removeEventListener('mmm:session-expired', listener);
  });
  it('traduit une erreur réseau sans passer aux données de démonstration', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')));
    const { request } = await import('./http');
    await expect(request('/restaurants')).rejects.toMatchObject({ status: 0 });
  });
});
