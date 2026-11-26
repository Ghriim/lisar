/**
 * The back-office's wording of the category icon codes the API stores. The code is the contract;
 * the word is each front end's business.
 */
const ICON_WORDS: Record<string, string> = {
    home: 'Maison',
    run: 'Marche',
    book: 'Lecture',
    dumbbell: 'Sport',
    droplet: 'Hydratation',
    leaf: 'Bien-être',
    moon: 'Sommeil',
    heart: 'Santé',
    target: 'Objectif',
    guitar: 'Instrument de musique',
    brush: 'Art',
    notebook: 'Étude',
    monitor: 'Travail',
}

export function categoryIconWord(code: string): string {
    return ICON_WORDS[code] ?? 'Objectif'
}
