// Prise en main : Michael ; relecture croisée du binôme B.
import { useResource } from '../hooks/useResource';
import { SectionTitle, State } from '../components/Ui';

interface Credit {
  id: string;
  file: string;
  title: string;
  author: string;
  license: string;
  license_url: string;
  source: string;
  changes: string;
}

export function Credits() {
  const credits = useResource('credits', async (signal) => {
    const response = await fetch('/images/credits.json', { signal });
    if (!response.ok) {
      throw new Error('Les crédits ne sont pas disponibles. Consultez docs/CREDITS_PHOTOS.md.');
    }
    return (await response.json()) as Credit[];
  });
  return (
    <section className="section">
      <SectionTitle
        eyebrow="LE GOÛT DES CHOSES BIEN FAITES"
        title="Photos, sources et informations."
      />
      <div className="panel">
        <h3>Une démonstration ancrée en RDC</h3>
        <p>
          MBOKA, Imperius et CHEZ MAMIE présentent trois cartes préparées pour le projet du Groupe
          4, ULC-ICAM. Les prix en FC suivent les fourchettes demandées par le groupe ; leur
          validation commerciale et la disponibilité des ingrédients restent à la charge du
          restaurant.
        </p>
        <p>
          Les photos illustrent des types de plats et de produits. Les différences de garniture,
          d’accompagnement et de conditionnement sont précisées sur les articles. Vital’o utilise
          une photo générique de grenadine, qui ne représente pas sa bouteille. Les dessins de l’eau
          et du soda sont des illustrations originales du projet.
        </p>
        <p>
          La sélection de Kinshasa reprend les fourchettes demandées par le groupe : plats congolais
          de 2 000 à 6 000 FC, fast-food de 6 000 à 16 000 FC, boissons sucrées à 1 500 FC et bières
          ou cidre à 4 000 FC. Ces tarifs pédagogiques ne sont pas une enquête de prix du marché.
          Imperius propose six cuisines et des cocktails avec ou sans alcool. CHEZ MAMIE propose des
          petits-déjeuners et brunchs. Leurs articles sont compris entre 6 000 et 22 000 FC. Les
          premiers repères du second restaurant provenaient de cartes publiques en RDC, sans
          conversion de devise :{' '}
          <a href="https://bisofood.com/" target="_blank" rel="noreferrer">
            BisoFood
          </a>{' '}
          et{' '}
          <a href="https://restaurantfn.com/" target="_blank" rel="noreferrer">
            Restaurant FN
          </a>
          . Les prix réels d’un restaurant connecté sont fournis par son backend.
        </p>
        <h3>Cookies et données</h3>
        <p>
          Les cookies de session et de protection des formulaires servent à la connexion. Seul votre
          choix de notice est conservé dans le stockage local. Aucun mot de passe, rôle ou panier
          privé n’y est enregistré. Aucun outil publicitaire ni service de mesure d’audience n’est
          chargé. Le responsable de traitement, ses contacts et les durées de conservation doivent
          être approuvés avant ouverture au public.
        </p>
      </div>
      <h3>Crédits photographiques</h3>
      <p>
        Les images sont stockées avec le projet. Chaque adaptation d’une image CC BY-SA reste sous
        sa licence d’origine.
      </p>
      <State loading={credits.loading} error={credits.error} retry={credits.reload} />
      <div className="three-columns">
        {credits.data?.map((row) => (
          <article className="panel credit-card" key={row.id}>
            <img src={row.file} alt={row.title} loading="lazy" />
            <h4>{row.title}</h4>
            <p>{row.author}</p>
            <p>
              {row.license} · {row.changes}
            </p>
            <a href={row.source} target="_blank" rel="noreferrer">
              Page de la photographie
            </a>
            {row.license_url && (
              <a href={row.license_url} target="_blank" rel="noreferrer">
                Lire la licence
              </a>
            )}
          </article>
        ))}
      </div>
    </section>
  );
}
