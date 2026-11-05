// Prise en main : Michael ; accueil personnel du parcours client.
import { useApp } from '../state/AppContext';

export function ClientWelcome() {
  const { user } = useApp();
  if (!user) {
    return null;
  }
  return (
    <section className="client-welcome section" aria-label="Espace client">
      <div><p className="eyebrow">MON ESPACE CLIENT</p><h1>Bonjour, {user.name}. À quoi avez-vous envie de goûter ?</h1><p>Choisissez un restaurant, composez votre panier et retrouvez chaque étape de votre commande.</p></div>
      <div className="inline-actions"><a className="button" href="#carte">Choisir mes plats</a><a className="button secondary" href="#commandes">Suivre mes commandes</a></div>
    </section>
  );
}
