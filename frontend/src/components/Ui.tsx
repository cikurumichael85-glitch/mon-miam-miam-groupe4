// Prise en main : Michael ; relecture croisée du binôme B.
import { useId, useRef, useEffect } from 'react';
import type { ReactNode } from 'react';

export function Icon({
  name,
  size = 20,
}: {
  name: 'bag' | 'arrow' | 'pin' | 'leaf' | 'clock' | 'user' | 'close' | 'search' | 'check';
  size?: number;
}) {
  const paths = {
    bag: 'M5 7h14l1 14H4L5 7zm3 0V5a4 4 0 018 0v2',
    arrow: 'M4 12h16m-6-6 6 6-6 6',
    pin: 'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1116 0zm-5 0a3 3 0 11-6 0 3 3 0 016 0',
    leaf: 'M20 3C5 2 2 10 7 16s15 1 13-13zM5 21 17 8',
    clock: 'M12 8v5l4 2m6-3a10 10 0 11-20 0 10 10 0 0120 0',
    user: 'M20 22v-3a8 8 0 00-16 0v3m13-16a5 5 0 11-10 0 5 5 0 0110 0',
    close: 'm6 6 12 12M6 18 18 6',
    search: 'm16 16 6 6m-4-13a8 8 0 11-16 0 8 8 0 0116 0',
    check: 'm4 12 5 5L20 6',
  };
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.6"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d={paths[name]} />
    </svg>
  );
}

export function State({
  loading,
  error,
  empty,
  retry,
}: {
  loading?: boolean;
  error?: string;
  empty?: string;
  retry?: () => void;
}) {
  if (loading) {
    return (
      <output className="state">
        <span className="spinner" />
        Préparation de votre sélection…
      </output>
    );
  }
  if (error) {
    return (
      <div className="state error" role="alert">
        <p>{error}</p>
        {retry && (
          <button className="button secondary" onClick={retry}>
            Réessayer
          </button>
        )}
      </div>
    );
  }
  return empty ? (
    <div className="state">
      <Icon name="leaf" size={32} />
      <p>{empty}</p>
    </div>
  ) : null;
}

export function Modal({
  title,
  close,
  children,
}: {
  title: string;
  close: () => void;
  children: ReactNode;
}) {
  const dialog = useRef<HTMLDialogElement>(null);
  const label = useId();
  useEffect(() => {
    const element = dialog.current;
    const previous = document.activeElement as HTMLElement | null;
    element?.showModal();
    return () => {
      element?.close();
      previous?.focus();
    };
  }, []);
  return (
    <dialog ref={dialog} className="modal" aria-labelledby={label} onCancel={close}>
      <div className="modal-heading">
        <h2 id={label}>{title}</h2>
        <button className="icon-button" onClick={close} aria-label="Fermer">
          <Icon name="close" />
        </button>
      </div>
      {children}
    </dialog>
  );
}

export function SectionTitle({
  eyebrow,
  title,
  children,
}: {
  eyebrow: string;
  title: string;
  children?: ReactNode;
}) {
  return (
    <div className="section-heading">
      <div>
        <p className="eyebrow">{eyebrow}</p>
        <h2>{title}</h2>
      </div>
      {children}
    </div>
  );
}
