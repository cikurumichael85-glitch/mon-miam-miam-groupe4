// Prise en main : Assek ; relecture croisée du binôme B.
import { ActionForm } from '../../components/Forms';
import { request } from '../../services/http';

export function ManagerAccess({ restaurantId, saved }: { restaurantId: string; saved: () => void }) {
  return (
    <div className="panel">
      <h3>Attribuer ou retirer un accès de gérant</h3>
      <p>Le gérant crée d’abord son compte avec son adresse e-mail et son mot de passe personnel. Rattachez ensuite cette même adresse à votre restaurant. Il garde son mot de passe ; aucun compte ni e-mail n’est créé par ce formulaire.</p>
      <ActionForm
        fields={[
          { name: 'email', label: 'Adresse e-mail du compte du gérant', type: 'email', maxLength: 255 },
          { name: 'active', label: 'Accès au restaurant', options: [{ value: 'true', label: 'Autoriser comme gérant' }, { value: 'false', label: 'Retirer son accès de gérant' }] },
        ]}
        submit="Enregistrer les droits du gérant"
        confirmation="Modifier les droits de gérant du compte indiqué dans ce restaurant ? Le gérant pourra superviser les commandes, la carte, les employés et les réclamations."
        action={async (values) => {
          await request(`/restaurants/${restaurantId}/managers`, 'POST', { email: values.email, active: values.active === 'true' });
          saved();
          return 'Accès mis à jour. Le gérant peut se reconnecter puis ouvrir Espace restaurant.';
        }}
      />
    </div>
  );
}
