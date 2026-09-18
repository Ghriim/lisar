/**
 * The back-office's wording of the icon codes the API stores. The website has its own — the
 * code is the contract, the word is each front end's business.
 */
const WORDS: Record<string, string> = {
    glass: 'Verre',
    bottle: 'Bouteille',
    mug: 'Tasse',
    can: 'Canette',
    carafe: 'Carafe',
}

/** Falls back to a neutral word: a code this front end does not know still reads. */
export function hydrationWord(code: string): string {
    return WORDS[code] ?? 'Boisson'
}
