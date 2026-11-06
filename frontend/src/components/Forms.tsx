// Prise en main : Assek ; relecture croisée du binôme B.
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { messageOf } from '../services/http';

export interface FieldSpec {
  name: string;
  label: string;
  type?: string;
  value?: string;
  required?: boolean;
  maxLength?: number;
  min?: number;
  max?: number;
  autoComplete?: string;
  options?: { value: string; label: string }[];
}

export function Field({
  name,
  label,
  type = 'text',
  value,
  required = true,
  maxLength = 1000,
  min,
  max,
  autoComplete,
  options,
}: FieldSpec) {
  return (
    <label className="field">
      <span>{label}</span>
      {options ? (
        <select name={name} defaultValue={value} required={required}>
          {options.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      ) : (
        <input
          name={name}
          type={type}
          defaultValue={value}
          required={required}
          maxLength={maxLength}
          min={min}
          max={max}
          autoComplete={autoComplete ?? (type === 'password' ? 'off' : undefined)}
        />
      )}
    </label>
  );
}

export function useAction() {
  const [pending, setPending] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  async function run(action: () => Promise<void>) {
    setPending(true);
    setError('');
    setSuccess('');
    try {
      await action();
    } catch (failure) {
      setError(messageOf(failure));
    } finally {
      setPending(false);
    }
  }
  return { pending, error, success, setSuccess, run };
}

export function ActionForm({
  fields,
  submit,
  action,
  children,
  confirmation,
}: {
  fields: FieldSpec[];
  submit: string;
  action: (values: Record<string, string>) => Promise<string | void>;
  children?: ReactNode;
  confirmation?: string;
}) {
  const task = useAction();
  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const values = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<
      string,
      string
    >;
    if (confirmation && !window.confirm(confirmation)) {
      return;
    }
    void task.run(async () => {
      const result = await action(values);
      task.setSuccess(result ?? 'Modification enregistrée par le serveur.');
    });
  }
  return (
    <form className="form-grid" onSubmit={handleSubmit}>
      <fieldset disabled={task.pending}>
        {fields.map((field) => (
          <Field key={field.name} {...field} />
        ))}
        {children}
      </fieldset>
      {task.error && (
        <p className="error form-message" role="alert">
          {task.error}
        </p>
      )}
      {task.success && <output className="success form-message">{task.success}</output>}
      <button className="button" disabled={task.pending} type="submit">
        {task.pending ? 'En cours…' : submit}
      </button>
    </form>
  );
}
