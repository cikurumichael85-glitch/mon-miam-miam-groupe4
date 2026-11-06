// Prise en main : Assek ; relecture croisée du binôme B.
import { ActionForm } from '../../components/Forms';
import { request } from '../../services/http';
import { restaurantFields, restaurantPayload } from './Management';

export function Platform() {
  return (
    <div className="panel">
      <h3>Administration de la plateforme</h3>
      <p>
        Accès réservé au responsable de plateforme ; le serveur contrôle chaque demande. Un rôle de
        gérant ne donne pas cet accès. Aucun rôle ne peut être choisi à l’inscription.
      </p>
      <details>
        <summary>Créer un restaurant</summary>
        <ActionForm
          fields={restaurantFields}
          submit="Créer un restaurant inactif"
          action={async (values) => {
            const result = await request<{ data: { id: number; settings_version: number } }>(
              '/platform/restaurants',
              'POST',
              restaurantPayload(values),
            );
            return `Restaurant créé : n° ${result.data.id}, version ${result.data.settings_version}. Il reste inactif jusqu’à validation de ses paramètres, de sa direction et de ses horaires.`;
          }}
        />
      </details>
      <details>
        <summary>Attribuer une direction</summary>
        <ActionForm
          fields={[
            { name: 'restaurant_id', label: 'Identifiant du restaurant', type: 'number', min: 1 },
            { name: 'user_id', label: 'Identifiant du compte existant', type: 'number', min: 1 },
            {
              name: 'role',
              label: 'Responsabilité locale',
              options: [
                { value: 'manager', label: 'Gérant' },
                { value: 'restaurant_admin', label: 'Administrateur local' },
              ],
            },
          ]}
          submit="Attribuer la responsabilité"
          confirmation="Confirmer l’attribution de ces droits au compte indiqué ?"
          action={async (values) => {
            await request(`/platform/restaurants/${values.restaurant_id}/managers`, 'POST', {
              user_id: Number(values.user_id),
              role: values.role,
              active: true,
            });
          }}
        />
      </details>
      <p className="small-note">
        La liste privée de tous les restaurants, leur activation assistée, l’instruction des
        demandes d’effacement, le rapprochement marchand et les barèmes globaux ne sont pas encore
        couverts par cette interface. Ils restent des tâches de finition, pas des fonctionnalités
        validées.
      </p>
    </div>
  );
}
