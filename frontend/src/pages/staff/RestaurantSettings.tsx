// Prise en main : Assek ; relecture croisée du binôme B.
import type { RestaurantSettings } from '../../domain/types';
import { ActionForm } from '../../components/Forms';
import { State } from '../../components/Ui';
import { useResource } from '../../hooks/useResource';
import { request } from '../../services/http';
import { useApp } from '../../state/AppContext';
import { restaurantFields, restaurantPayload } from './Management';
import { HoursEditor } from './HoursEditor';

export function StaffSettings({ restaurantId }: { restaurantId: string }) {
  const app = useApp();
  const settings = useResource(`settings:${restaurantId}`, (signal) => request<RestaurantSettings>(`/restaurants/${restaurantId}/settings`, 'GET', undefined, signal));
  const record = settings.data?.data;
  if (!record) {
    return <State loading={settings.loading} error={settings.error} retry={settings.reload} />;
  }
  const values: Record<string, string> = {
    name: record.name,
    address: record.address ?? '',
    timezone: record.timezone,
    delivery_enabled: String(record.delivery_enabled),
    delivery_fee: record.delivery_fee ?? '',
  };
  function saved() {
    app.notify('Paramètres enregistrés. Les clients utiliseront les nouvelles informations.');
    settings.reload();
  }
  return (
    <div className="two-columns">
      <div className="panel">
        <div className="section-heading">
          <h3>Nom et informations du restaurant</h3>
          <button className="button secondary" onClick={settings.reload}>Actualiser</button>
        </div>
        <p>Modifiez ici le nom affiché aux clients, l’adresse, le fuseau et les frais de livraison.</p>
        <ActionForm
          key={record.settings_version}
          fields={restaurantFields.map((field) => ({ ...field, value: values[field.name] }))}
          submit="Enregistrer les informations du restaurant"
          action={async (input) => {
            const result = await request<{ data: RestaurantSettings['data'] }>(`/restaurants/${restaurantId}/settings`, 'PATCH', { ...restaurantPayload(input), expected_version: record.settings_version });
            if (app.restaurant && String(app.restaurant.id) === restaurantId) {
              app.setRestaurant({ ...app.restaurant, name: result.data.name, timezone: result.data.timezone, delivery_enabled: result.data.delivery_enabled });
            }
            saved();
          }}
        />
      </div>
      <div className="panel">
        <HoursEditor key={record.settings_version} restaurantId={restaurantId} version={record.settings_version} initial={settings.data?.hours ?? []} saved={saved} />
      </div>
    </div>
  );
}
