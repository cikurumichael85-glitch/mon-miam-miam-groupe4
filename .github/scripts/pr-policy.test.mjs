import test from 'node:test'
import assert from 'node:assert/strict'
import { validatePullRequest } from './pr-policy.mjs'

function request(head = 'contrib/prudent-kakesa', base = 'integration') {
  return {
    title: 'MMM-39 chore: importer le socle',
    body: 'https://legroupea.atlassian.net/browse/MMM-39\n## Problème et résultat\nImport documente.\n## Vérifications réalisées\nA renseigner.\n## Relecture\nA effectuer.\n## Limites\nReconstruction.',
    head: { ref: head },
    base: { ref: base },
  }
}

test('une branche membre peut cibler integration', () => {
  assert.deepEqual(validatePullRequest(request()), [])
})

test('integration peut livrer main', () => {
  assert.deepEqual(validatePullRequest(request('integration', 'main')), [])
})

test('une branche membre ne livre pas directement main', () => {
  assert.notEqual(validatePullRequest(request('contrib/prudent-kakesa', 'main')).length, 0)
})

test('une branche inconnue est refusee', () => {
  assert.notEqual(validatePullRequest(request('contrib/inconnu')).length, 0)
})

test('une ancienne cible develop est refusee', () => {
  assert.notEqual(validatePullRequest(request('contrib/prudent-kakesa', 'develop')).length, 0)
})

test('un titre et des preuves incomplets sont refuses', () => {
  const pr = request()
  pr.title = 'import'
  pr.body = ''
  assert.ok(validatePullRequest(pr).length >= 5)
})
