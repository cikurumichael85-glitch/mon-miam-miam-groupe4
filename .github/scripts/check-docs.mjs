import { readFileSync, existsSync, statSync } from 'node:fs'
import { execFileSync } from 'node:child_process'
import path from 'node:path'

const root = process.cwd()
const files = execFileSync('git', ['ls-files', '-z'], { encoding: 'utf8' }).split('\0').filter(Boolean)
const errors = []
for (const required of ['README.md', 'CONTRIBUTING.md', 'docs/PRESENTATION.md', 'docs/BACKLOG.md', 'docs/RESPONSABILITES.md', 'docs/GOUVERNANCE_GIT.md', 'docs/TESTS.md', 'docs/cahier-des-charges/README.md', 'docs/architecture/README.md', '.github/CODEOWNERS']) {
  if (!files.includes(required)) errors.push(`Document non versionné : ${required}`)
}
for (const file of files) {
  const name = path.basename(file)
  if ((/^\.env(?:\.|$)/.test(name) && name !== '.env.example') || name === 'auth.json' || /\.(pem|key)$/.test(name)) {
    errors.push(`Configuration privée potentiellement suivie : ${file}`)
  }
  if (!file.endsWith('.md')) continue
  const contents = readFileSync(file, 'utf8')
  for (const match of contents.matchAll(/!?\[[^\]]*\]\(([^)]+)\)/g)) {
    let target = match[1].replace(/^<|>$/g, '').split('#')[0]
    if (!target || /^[a-z][a-z\d+.-]*:/i.test(target) || target.startsWith('//')) continue
    target = decodeURIComponent(target.split('?')[0])
    const resolved = path.resolve(path.dirname(file), target)
    if (resolved !== root && !resolved.startsWith(root + path.sep)) errors.push(`Lien hors du dépôt : ${file} → ${target}`)
    else if (!existsSync(resolved)) errors.push(`Lien local cassé : ${file} → ${target}`)
  }
}
const tasks = JSON.parse(readFileSync('docs/backlog/taches.json', 'utf8'))
if (tasks.length !== 51 || new Set(tasks.map(t => t.key)).size !== 51) errors.push('Le backlog attendu contient 51 clés Jira distinctes.')
for (const task of tasks) {
  if (!/^MMM-\d+$/.test(task.key) || !['A', 'B', 'C', 'D'].includes(task.group) || !['P0', 'P1', 'P2'].includes(task.priority_label)) errors.push(`Métadonnées invalides : ${task.key}`)
  if (!task.url?.startsWith('https://github.com/cikurumichael85-glitch/mon-miam-miam/issues/')) errors.push(`Issue GitHub manquante : ${task.key}`)
}
const original = 'docs/cahier-des-charges/CDC_Original_Mon_Miam_Miam_V1_0_2.docx'
if (!existsSync(original) || statSync(original).size === 0) errors.push('Cahier des charges original absent ou vide.')
const api = JSON.parse(readFileSync('docs/conception/Contrat_API_P0_OpenAPI.json', 'utf8'))
function walk(value) {
  if (!value || typeof value !== 'object') return
  if (typeof value.$ref === 'string' && value.$ref.startsWith('#/')) {
    const resolved = value.$ref.slice(2).split('/').reduce((node, key) => node?.[key.replaceAll('~1', '/').replaceAll('~0', '~')], api)
    if (resolved === undefined) errors.push(`Référence OpenAPI interne cassée : ${value.$ref}`)
  }
  Object.values(value).forEach(walk)
}
walk(api)
if (errors.length) {
  errors.forEach(error => console.error(error))
  process.exitCode = 1
} else {
  console.log('Documentation : liens locaux, 51 tâches, références OpenAPI internes et noms des fichiers suivis vérifiés. Pas de validation OpenAPI complète ni de scan exhaustif de secrets.')
}
