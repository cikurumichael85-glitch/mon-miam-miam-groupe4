// Prise en main : Assek ; relecture croisée du binôme B.
import { useState } from 'react';
import type { FormEvent } from 'react';
import type { OpeningSlot } from '../../domain/types';
import { useAction } from '../../components/Forms';
import { request } from '../../services/http';

export const weekdays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
type EditableSlot = OpeningSlot & { key: string };

export function HoursEditor({ restaurantId, version, initial, saved }: {
  restaurantId: string;
  version: number;
  initial: OpeningSlot[];
  saved: () => void;
}) {
  const [slots, setSlots] = useState<EditableSlot[]>(() => initial.map((slot) => ({ ...slot, key: crypto.randomUUID() })));
  const task = useAction();
  function change(key: string, patch: Partial<OpeningSlot>) {
    setSlots((previous) => previous.map((slot) => slot.key === key ? { ...slot, ...patch } : slot));
  }
  function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!window.confirm('Enregistrer ces horaires ? Les jours sans créneau seront fermés aux commandes.')) {
      return;
    }
    void task.run(async () => {
      await request(`/restaurants/${restaurantId}/hours`, 'PATCH', {
        expected_version: version,
        hours: slots.map(({ weekday, opens_at, closes_at }) => ({ weekday, opens_at, closes_at })),
      });
      saved();
    });
  }
  return (
    <form className="form-grid" onSubmit={save}>
      <h3>Horaires d’ouverture aux commandes</h3>
      <p>Horaires locaux du restaurant. Un jour sans créneau est fermé. Pour passer minuit, créez deux créneaux sur deux jours.</p>
      <fieldset disabled={task.pending}>
        {slots.map((slot, index) => (
          <div className="hours-row" key={slot.key}>
            <label className="field">
              <span>Jour du créneau {index + 1}</span>
              <select value={slot.weekday} onChange={(event) => change(slot.key, { weekday: Number(event.target.value) })}>
                {weekdays.map((day, dayIndex) => <option value={dayIndex + 1} key={day}>{day}</option>)}
              </select>
            </label>
            <label className="field">
              <span>Ouverture {index + 1}</span>
              <input required type="time" value={slot.opens_at} onChange={(event) => change(slot.key, { opens_at: event.target.value })} />
            </label>
            <label className="field">
              <span>Fermeture {index + 1}</span>
              <input required type="time" value={slot.closes_at} onChange={(event) => change(slot.key, { closes_at: event.target.value })} />
            </label>
            <button type="button" onClick={() => setSlots((previous) => previous.filter((row) => row.key !== slot.key))}>Retirer le créneau {index + 1}</button>
          </div>
        ))}
        <button type="button" className="button secondary" disabled={slots.length >= 28} onClick={() => setSlots((previous) => [...previous, { key: crypto.randomUUID(), weekday: 1, opens_at: '08:00', closes_at: '18:00' }])}>Ajouter un créneau</button>
      </fieldset>
      {slots.length === 0 && <p className="info-box">Aucun horaire enregistré : les clients ne peuvent pas encore confirmer leurs commandes.</p>}
      {task.error && <p className="error" role="alert">{task.error}</p>}
      <button className="button" type="submit" disabled={task.pending}>Enregistrer les horaires</button>
    </form>
  );
}
