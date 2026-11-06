import { readFileSync } from 'node:fs'
import { validatePullRequest } from './pr-policy.mjs'

if (process.env.GITHUB_EVENT_NAME !== 'pull_request') {
  console.log('Convention de PR : contrôle applicable lors de l’ouverture ou modification d’une pull request.')
} else {
  const event = JSON.parse(readFileSync(process.env.GITHUB_EVENT_PATH, 'utf8'))
  const errors = validatePullRequest(event.pull_request)
  if (errors.length) {
    for (const error of errors) console.error(error)
    process.exitCode = 1
  } else {
    console.log('Convention de PR conforme. Ce contrôle ne remplace pas la revue humaine.')
  }
}
