// Prise en main : Assek ; le serveur attribue le restaurant à son créateur.
import type { Membership, Restaurant, User } from '../domain/types';
import { canAddRestaurant } from '../domain/roles';
import { ActionForm } from '../components/Forms';
import { SectionTitle } from '../components/Ui';
import { request } from '../services/http';
import { useApp } from '../state/AppContext';
import { restaurantFields, restaurantPayload } from './staff/Management';

export function NewRestaurant() {
  const app = useApp();
  const user = app.user;
  if (!user || !canAddRestaurant(user)) {
    return (
      <section className="section">
        <SectionTitle eyebrow="RESTAURANTS" title="Ajouter un établissement." />
        <p className="info-box">
          Cette opération est réservée aux administrateurs de restaurant. Un compte client ou
          serveur ne peut pas créer un établissement.
        </p>
      </section>
    );
  }
  const fields = restaurantFields.map((field) => {
    const defaults: Record<string, string> = { delivery_enabled: 'false', delivery_fee: '0.00' };
    return { ...field, value: defaults[field.name] };
  });
  return (
    <section className="section workspace-admin">
      <SectionTitle
        eyebrow="DÉVELOPPER VOTRE ACTIVITÉ"
        title="Une nouvelle table, une nouvelle histoire."
      />
      <div className="two-columns">
        <div className="panel">
          <h3>Ajouter un restaurant</h3>
          <p>
            Compte affiché : {user.email}. La connexion est partagée entre les onglets du même
            navigateur.
          </p>
          <p>
            Vous en devenez l’administrateur. La carte, les horaires, les employés et les paiements
            restent propres à chaque restaurant.
          </p>
          <ActionForm
            fields={fields}
            submit="Créer mon nouveau restaurant"
            action={async (values) => {
              // Relire la session : un autre onglet peut avoir connecté le client ou le serveur.
              const profile = await request<{ data: User }>('/me');
              app.setUser(profile.data);
              if (!canAddRestaurant(profile.data)) {
                throw new Error(
                  `Le compte actuellement connecté (${profile.data.email}) n’est pas administrateur de restaurant. Reconnectez le compte administrateur puis ouvrez Ajouter un restaurant.`,
                );
              }
              const result = await request<{ data: Restaurant; membership: Membership }>(
                '/me/restaurants',
                'POST',
                restaurantPayload(values),
              );
              app.setUser({
                ...profile.data,
                memberships: [...(profile.data.memberships ?? []), result.membership],
              });
              app.setRestaurant({ ...result.data, id: String(result.data.id) });
              app.notify(
                'Restaurant créé. Ajoutez sa carte et ses horaires avant les premières commandes.',
              );
              window.location.hash = 'administration';
            }}
          />
        </div>
        <aside className="panel onboarding-guide">
          <h3>Les prochaines étapes</h3>
          <ol>
            <li>Créer les catégories, plats et boissons dans « Carte ».</li>
            <li>Enregistrer les horaires dans « Mon restaurant & horaires ».</li>
            <li>Rattacher les serveurs dans « Équipe ».</li>
            <li>Configurer séparément le compte marchand si le paiement en ligne est souhaité.</li>
          </ol>
          <p>
            Le restaurant apparaît sur le site avec une carte vide. Les commandes nécessitent des
            articles disponibles et des horaires. Aucune clé EasyPay ni équipe n’est copiée d’un
            autre restaurant.
          </p>
        </aside>
      </div>
    </section>
  );
}
