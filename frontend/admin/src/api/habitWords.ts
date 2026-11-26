/**
 * The back-office's wording of the habit codes the API stores — its trackers. The
 * code is the contract; the word is each front end's business.
 */
const TRACKER_WORDS: Record<string, string> = {
    steps: 'Pas',
    hydration: 'Hydratation',
    workout: 'Séances terminées',
}

export function habitTrackerWord(code: string): string {
    return TRACKER_WORDS[code] ?? code
}
