/**
 * The back-office's wording of the habit codes the API stores — icons, sources and trackers. The
 * code is the contract; the word is each front end's business.
 */
const ICON_WORDS: Record<string, string> = {
    run: 'Marche',
    book: 'Lecture',
    dumbbell: 'Sport',
    droplet: 'Hydratation',
    leaf: 'Bien-être',
    moon: 'Sommeil',
    heart: 'Santé',
    target: 'Objectif',
}

const TRACKER_WORDS: Record<string, string> = {
    steps: 'Pas',
    hydration: 'Hydratation',
}

export function habitIconWord(code: string): string {
    return ICON_WORDS[code] ?? 'Objectif'
}

export function habitTrackerWord(code: string): string {
    return TRACKER_WORDS[code] ?? code
}
