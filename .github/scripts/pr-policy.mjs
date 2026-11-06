export function validatePullRequest(pr) {
  const errors = []
  const match = /^(MMM-\d+) (feat|fix|docs|test|chore|refactor|ci): .+/.exec(pr.title ?? '')
  if (!match) {
    errors.push('Le titre doit contenir une cle Jira et un type de changement.')
  }
  const body = pr.body ?? ''
  if (match && !body.includes(`https://legroupea.atlassian.net/browse/${match[1]}`)) {
    errors.push('Le lien Jira doit correspondre au titre.')
  }
  for (const heading of ['## Problème et résultat', '## Vérifications réalisées', '## Relecture', '## Limites']) {
    if (!body.includes(heading)) {
      errors.push(`Section manquante : ${heading}`)
    }
  }
  const base = pr.base?.ref
  const head = pr.head?.ref ?? ''
  if (!['main', 'integration'].includes(base)) {
    errors.push('La cible doit etre integration ou main.')
  }
  if (base === 'main' && head !== 'integration') {
    errors.push('main recoit uniquement une livraison integration.')
  }
  const members = ["contrib/moise-balaga", "contrib/lucas-kandende", "contrib/michael-cikuru", "contrib/assek-arloni", "contrib/kabeya-ryan", "contrib/bacirhamya-yedidia", "contrib/prudent-kakesa"]
  if (base === 'integration' && !members.includes(head)) {
    errors.push('Utiliser une branche nominative du groupe.')
  }
  return errors
}
