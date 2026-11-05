// Prise en main : Assek ; relecture croisée du binôme B.
import { useCallback, useEffect, useRef, useState } from 'react';
import { messageOf } from '../services/http';

export function useResource<T>(key: string, loader: (signal: AbortSignal) => Promise<T>) {
  const currentLoader = useRef(loader);
  const [state, setState] = useState<{
    key: string;
    revision: number;
    data: T | null;
    loading: boolean;
    error: string;
  }>({ key, revision: 0, data: null, loading: true, error: '' });
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    currentLoader.current = loader;
  });
  useEffect(() => {
    // Un changement de restaurant annule la lecture précédente et masque son ancien résultat.
    const controller = new AbortController();
    void currentLoader
      .current(controller.signal)
      .then((data) => {
        if (!controller.signal.aborted) {
          setState({ key, revision, data, loading: false, error: '' });
        }
      })
      .catch((error: unknown) => {
        if (!controller.signal.aborted) {
          setState({ key, revision, data: null, loading: false, error: messageOf(error) });
        }
      });
    return () => controller.abort();
  }, [key, revision]);
  const visible =
    state.key === key && state.revision === revision
      ? state
      : { data: null, loading: true, error: '' };
  const reload = useCallback(() => setRevision((value) => value + 1), []);
  return { ...visible, reload };
}
