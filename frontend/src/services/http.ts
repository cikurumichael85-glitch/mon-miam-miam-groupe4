// Prise en main : Michael ; relecture croisée du binôme B.
const configuredBase = import.meta.env.VITE_API_BASE_URL ?? '/api/v1';
export const apiBase = configuredBase.replace(/\/$/, '');
let csrfToken: string | null = null;

export class ApiError extends Error {
  status: number;
  fields: Record<string, string[]>;
  constructor(status: number, message: string, fields: Record<string, string[]> = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.fields = fields;
  }
}

interface ErrorPayload {
  message?: string;
  errors?: Record<string, string[]>;
  error?: { message?: string; fields?: Record<string, string[]> };
}

const statusMessages: Record<number, string> = {
  401: 'Connexion nécessaire. Connectez-vous ou reconnectez-vous pour continuer.',
  403: 'Votre compte ne possède pas les droits nécessaires pour cette action.',
  404: 'Cet élément n’est pas disponible ou vous n’y avez pas accès.',
  419: 'Session renouvelée nécessaire : reconnectez-vous puis réessayez.',
  422: 'Vérifiez les informations saisies avant de réessayer.',
  429: 'Trop de demandes. Patientez avant de réessayer.',
  500: 'Le serveur rencontre une difficulté. Réessayez ultérieurement.',
};
const frameworkMessages = new Set([
  'Unauthenticated.',
  'Forbidden',
  'This action is unauthorized.',
  'Not Found',
  'CSRF token mismatch.',
  'Too Many Attempts.',
  'Server Error',
]);

// Conserver les messages métier utiles, traduire les erreurs techniques usuelles.
function errorMessage(status: number, payload: ErrorPayload): string {
  const fallback = statusMessages[status] ?? 'La demande n’a pas abouti.';
  const supplied = payload.message ?? payload.error?.message;
  if (
    typeof supplied !== 'string' ||
    supplied.trim() === '' ||
    frameworkMessages.has(supplied) ||
    supplied.startsWith('No query results for model')
  ) {
    return fallback;
  }
  return supplied;
}

function failure(status: number, payload: ErrorPayload): ApiError {
  if (status === 401) {
    window.dispatchEvent(new Event('mmm:session-expired'));
  }
  return new ApiError(
    status,
    errorMessage(status, payload),
    payload.errors ?? payload.error?.fields,
  );
}

async function decode<T>(response: Response): Promise<T> {
  const contentType = response.headers.get('content-type') ?? '';
  if (!contentType.includes('application/json')) {
    throw new ApiError(response.status, 'Réponse API inattendue. Vérifiez l’adresse du serveur.');
  }
  let payload: unknown;
  try {
    payload = await response.json();
  } catch {
    throw new ApiError(
      response.status,
      'Le serveur a renvoyé une réponse illisible. Réessayez ultérieurement.',
    );
  }
  if (!response.ok) {
    const error = payload !== null && typeof payload === 'object' ? payload : {};
    throw failure(response.status, error as ErrorPayload);
  }
  return payload as T;
}

export async function refreshCsrf(): Promise<void> {
  const response = await fetch(`${apiBase}/auth/csrf-cookie`, {
    credentials: 'include',
    headers: { Accept: 'application/json' },
    cache: 'no-store',
  });
  const payload = await decode<{ csrf_token: string }>(response);
  csrfToken = payload.csrf_token;
}

// Aucune mutation n'est rejouée automatiquement après erreur réseau ou CSRF.
export async function request<T>(
  path: string,
  method = 'GET',
  body?: unknown,
  signal?: AbortSignal,
): Promise<T> {
  const mutation = method !== 'GET';
  // Une session peut être renouvelée dans un autre onglet ou après une connexion.
  // Obtenir son jeton avant l'envoi évite un premier refus, sans rejouer la mutation.
  if (mutation) {
    await refreshCsrf();
  }
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (mutation) {
    headers['Content-Type'] = 'application/json';
    headers['X-CSRF-TOKEN'] = csrfToken ?? '';
  }
  try {
    const response = await fetch(`${apiBase}${path}`, {
      method,
      headers,
      credentials: 'include',
      cache: 'no-store',
      body: body === undefined ? undefined : JSON.stringify(body),
      signal,
    });
    if (response.status === 419) {
      csrfToken = null;
    }
    return await decode<T>(response);
  } catch (error) {
    if (error instanceof TypeError) {
      throw new ApiError(0, 'Le serveur est injoignable. Vérifiez votre connexion et le backend.');
    }
    throw error;
  }
}

export function messageOf(error: unknown): string {
  if (error instanceof ApiError) {
    const details = [...new Set(Object.values(error.fields).flat())].filter(Boolean);
    return details.length > 0 ? details.join(' ') : error.message;
  }
  return error instanceof Error ? error.message : 'Une erreur est survenue.';
}
