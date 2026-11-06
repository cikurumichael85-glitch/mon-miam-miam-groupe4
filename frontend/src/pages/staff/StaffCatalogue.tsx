// Prise en main : Assek ; relecture croisée du binôme B.
import { useState } from 'react';
import type { Dish } from '../../domain/types';
import { money } from '../../domain/money';
import { menu } from '../../services/catalogue';
import { request } from '../../services/http';
import { useResource } from '../../hooks/useResource';
import { ActionForm, useAction } from '../../components/Forms';
import type { FieldSpec } from '../../components/Forms';
import { Modal, State } from '../../components/Ui';

function ItemEditor({
  restaurantId,
  categories,
  item,
  saved,
  close,
}: {
  restaurantId: string;
  categories: FieldSpec['options'];
  item: Dish | null;
  saved: () => void;
  close: () => void;
}) {
  return (
    <Modal title={item ? 'Modifier cet article' : 'Un nouvel article à la carte'} close={close}>
      <ActionForm
        fields={[
          {
            name: 'category_id',
            label: 'Catégorie',
            value: item ? String(item.category_id) : undefined,
            options: categories,
          },
          { name: 'name', label: 'Nom de l’article', value: item?.name, maxLength: 150 },
          {
            name: 'description',
            label: 'Description et ingrédients',
            value: item?.description ?? '',
            required: false,
          },
          {
            name: 'price',
            label: 'Prix en FC (exemple : 12500.00)',
            value: item?.price,
            maxLength: 12,
          },
        ]}
        submit="Enregistrer l’article"
        action={async (values) => {
          const payload = {
            ...values,
            category_id: Number(values.category_id),
            available: item?.available ?? true,
            is_daily: item?.is_daily ?? false,
            ...(item ? { expected_version: item.version } : {}),
          };
          await request(
            `/restaurants/${restaurantId}/items${item ? `/${item.id}` : ''}`,
            item ? 'PATCH' : 'POST',
            payload,
          );
          saved();
          close();
        }}
      />
    </Modal>
  );
}

export function StaffCatalogue({
  restaurantId,
  manager,
}: {
  restaurantId: string;
  manager: boolean;
}) {
  const catalogue = useResource(`staff-menu:${restaurantId}`, (signal) =>
    menu('api', restaurantId, signal),
  );
  const [editing, setEditing] = useState<Dish | null | undefined>(undefined);
  const task = useAction();
  const categories =
    catalogue.data?.categories.map((row) => ({ value: String(row.id), label: row.name })) ?? [];
  function availability(item: Dish, daily: boolean) {
    void task.run(async () => {
      await request(`/restaurants/${restaurantId}/items/${item.id}/availability`, 'PATCH', {
        available: daily ? item.available : !item.available,
        is_daily: daily ? !item.is_daily : item.is_daily,
        expected_version: item.version,
      });
      catalogue.reload();
    });
  }
  return (
    <div className="panel">
      <div className="section-heading">
        <h3>La carte du restaurant</h3>
        {manager && (
          <button
            className="button"
            disabled={categories.length === 0}
            onClick={() => setEditing(null)}
          >
            Ajouter un article
          </button>
        )}
      </div>
      <State loading={catalogue.loading} error={catalogue.error} retry={catalogue.reload} />
      {task.error && (
        <p className="error" role="alert">
          {task.error}
        </p>
      )}
      {catalogue.data?.items.map((item) => (
        <article className="catalogue-row" key={item.id}>
          <div>
            <strong>{item.name}</strong>
            <p>
              {money(item.price)} · {item.available ? 'Disponible' : 'Épuisé'}
              {item.is_daily ? ' · À l’honneur' : ''}
            </p>
          </div>
          <div className="inline-actions">
            <button disabled={task.pending} onClick={() => availability(item, false)}>
              {item.available ? 'Marquer épuisé' : 'Rendre disponible'}
            </button>
            <button disabled={task.pending} onClick={() => availability(item, true)}>
              Plat du jour
            </button>
            {manager && (
              <>
                <button onClick={() => setEditing(item)}>Modifier</button>
                <button
                  disabled={task.pending}
                  onClick={() => {
                    if (
                      window.confirm(
                        `Archiver « ${item.name} » ? L’historique des commandes est conservé.`,
                      )
                    ) {
                      void task.run(async () => {
                        await request(
                          `/restaurants/${restaurantId}/items/${item.id}/archive`,
                          'POST',
                          { expected_version: item.version },
                        );
                        catalogue.reload();
                      });
                    }
                  }}
                >
                  Archiver
                </button>
              </>
            )}
          </div>
        </article>
      ))}
      {manager && (
        <details className="panel">
          <summary>Ajouter une catégorie</summary>
          <ActionForm
            fields={[{ name: 'name', label: 'Nom de la catégorie', maxLength: 100 }]}
            submit="Créer la catégorie"
            action={async (values) => {
              await request(`/restaurants/${restaurantId}/categories`, 'POST', values);
              catalogue.reload();
            }}
          />
        </details>
      )}
      {editing !== undefined && (
        <ItemEditor
          restaurantId={restaurantId}
          categories={categories}
          item={editing}
          saved={catalogue.reload}
          close={() => setEditing(undefined)}
        />
      )}
    </div>
  );
}
