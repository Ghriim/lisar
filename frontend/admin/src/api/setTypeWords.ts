/**
 * The back-office's reading of the set-type colour codes the API stores: a word and a shade for
 * each. The code is the contract; the word and the shade are each front end's business.
 */
const COLOURS: Record<string, { word: string; shade: string }> = {
    red: { word: 'Rouge', shade: '#e5484d' },
    orange: { word: 'Orange', shade: '#f76b15' },
    yellow: { word: 'Jaune', shade: '#e2a336' },
    green: { word: 'Vert', shade: '#30a46c' },
    teal: { word: 'Turquoise', shade: '#12a594' },
    blue: { word: 'Bleu', shade: '#3e63dd' },
    purple: { word: 'Violet', shade: '#8e4ec6' },
    pink: { word: 'Rose', shade: '#d6409f' },
    grey: { word: 'Gris', shade: '#8b8d98' },
}

export function setTypeColourWord(code: string): string {
    return COLOURS[code]?.word ?? code
}

/** Falls back to grey: a code this front end does not know still renders. */
export function setTypeColourShade(code: string): string {
    return COLOURS[code]?.shade ?? COLOURS.grey.shade
}
