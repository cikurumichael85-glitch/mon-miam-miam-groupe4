// Prise en main : Assek ; aucun barème d'exemple activé automatiquement.
import { ActionForm } from '../../components/Forms';
import { State } from '../../components/Ui';
import { useResource } from '../../hooks/useResource';
import { request } from '../../services/http';

interface Program {
  active: boolean;
  version: number;
  approved_at: string | null;
  rules: {
    earn_amount: string;
    redeem_points: number;
    redeem_value: string;
    referral_points: number;
    expiry_days: number;
    require_paid: boolean;
  };
}

const booleanOptions = [{ value: 'false', label: 'Non' }, { value: 'true', label: 'Oui' }];

function programFields(program: Program | null) {
  const rules = program?.rules ?? {
    earn_amount: '', redeem_points: '', redeem_value: '', referral_points: '', expiry_days: '', require_paid: true,
  };
  return [
    { name: 'active', label: 'Programme actif', options: booleanOptions, value: String(program?.active ?? false) },
    { name: 'earn_amount', label: 'Montant dépensé pour gagner un point (FC)', value: rules.earn_amount },
    { name: 'redeem_points', label: 'Points nécessaires par remise', type: 'number', min: 1, max: 1000000, value: String(rules.redeem_points) },
    { name: 'redeem_value', label: 'Valeur de cette remise (FC)', value: rules.redeem_value },
    { name: 'referral_points', label: 'Points offerts au parrain', type: 'number', min: 0, max: 1000000, value: String(rules.referral_points) },
    { name: 'expiry_days', label: 'Durée de validité des points (jours)', type: 'number', min: 1, max: 3650, value: String(rules.expiry_days) },
    { name: 'require_paid', label: 'Exiger un paiement confirmé avant attribution', options: booleanOptions, value: String(rules.require_paid) },
  ];
}

function programPayload(values: Record<string, string>) {
  return {
    active: values.active === 'true',
    approval: values.approval === 'on',
    rules: {
      earn_amount: values.earn_amount,
      redeem_points: Number(values.redeem_points),
      redeem_value: values.redeem_value,
      referral_points: Number(values.referral_points),
      expiry_days: Number(values.expiry_days),
      require_paid: values.require_paid === 'true',
    },
  };
}

export function StaffLoyalty({ restaurantId }: { restaurantId: string }) {
  const endpoint = `/restaurants/${restaurantId}/loyalty-program`;
  const program = useResource(endpoint, (signal) => request<{ data: Program | null }>(endpoint, 'GET', undefined, signal));
  return (
    <div className="panel">
      <h3>Programme de fidélité du restaurant</h3>
      <p>Les montants sont en FC. Aucun barème n’est appliqué sans votre approbation. Les points sont attribués après service ; selon le choix ci-dessous, un paiement confirmé est aussi nécessaire.</p>
      <p className="info-box">Un barème déjà utilisé ne peut pas être réécrit ici : les commandes et soldes conservent leurs règles. Vous pouvez conserver ses valeurs et désactiver le programme. Le programme global éventuel reste distinct.</p>
      <State loading={program.loading} error={program.error} retry={program.reload} />
      {program.data && (
        <ActionForm
          key={program.data.data?.version ?? 'new'}
          fields={programFields(program.data.data)}
          submit="Enregistrer le programme de fidélité"
          confirmation="Confirmez-vous ce barème pour ce restaurant ? Les règles seront enregistrées avec votre approbation."
          action={async (values) => {
            await request(endpoint, 'POST', programPayload(values));
            program.reload();
            return 'Programme enregistré par le serveur.';
          }}
        >
          <label className="toggle">
            <input type="checkbox" name="approval" required />
            J’approuve explicitement les règles saisies pour ce restaurant.
          </label>
        </ActionForm>
      )}
    </div>
  );
}
