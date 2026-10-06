/**
 * The website's shade for each set-type colour code the API stores. The code is the contract;
 * the shade is each front end's business, and these are lit for the System's dark glass.
 */
const SHADES: Record<string, string> = {
    red: '#ff6b81',
    orange: '#ff9f5a',
    yellow: '#ffd166',
    green: '#38f0a8',
    teal: '#3ee6d2',
    blue: '#6f9bff',
    purple: '#b98cff',
    pink: '#ff7ac8',
    grey: '#8fb0d8',
}

/** Falls back to grey: a code this front end does not know still renders. */
export function setTypeShade(code: string): string {
    return SHADES[code] ?? SHADES.grey
}
