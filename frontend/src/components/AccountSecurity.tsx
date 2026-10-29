// Prise en main : Assek ; le titulaire saisit seul ses nouveaux secrets.
import { ActionForm } from './Forms';
import { refreshCsrf, request } from '../services/http';
import { useApp } from '../state/AppContext';

export function AccountSecurity() {
  const app = useApp();
  return (
    <details className="panel account-security">
      <summary>Modifier mon e-mail et mon mot de passe</summary>
      <p>Ces changements concernent uniquement votre compte. Votre rôle et vos restaurants sont conservés.</p>
      <p>Le nouveau mot de passe est facultatif : au moins 5 caractères, avec majuscule, minuscule et chiffre, dans la limite de 72 octets. Après enregistrement, reconnectez-vous.</p>
      <ActionForm
        fields={[
          { name: 'email', label: 'Adresse e-mail de connexion', type: 'email', value: app.user?.email, maxLength: 255, autoComplete: 'username' },
          { name: 'current_password', label: 'Mot de passe actuel', type: 'password', maxLength: 72, autoComplete: 'current-password' },
          { name: 'password', label: 'Nouveau mot de passe (facultatif)', type: 'password', required: false, maxLength: 72, autoComplete: 'new-password' },
          { name: 'password_confirmation', label: 'Confirmer le nouveau mot de passe', type: 'password', required: false, maxLength: 72, autoComplete: 'new-password' },
        ]}
        submit="Enregistrer mes nouveaux identifiants"
        action={async (values) => {
          const payload: Record<string, string> = { email: values.email, current_password: values.current_password };
          if (values.password) {
            payload.password = values.password;
            payload.password_confirmation = values.password_confirmation;
          }
          const result = await request<{ message: string }>('/me/credentials', 'PATCH', payload);
          app.setUser(null);
          app.dispatch({ type: 'clear' });
          app.notify(result.message);
          await refreshCsrf();
          window.location.hash = 'compte';
        }}
      />
    </details>
  );
}
