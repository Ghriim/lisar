/**
 * How a weight is written and how the day it belongs to is said. Its own file: a module that
 * exports components exports nothing else.
 */

/** Two decimals, always, and a comma: 72,40 kg — never 72.4. */
export function formatWeight(weightInKilograms: number): string {
    return `${weightInKilograms.toFixed(2).replace('.', ',')} kg`
}

/**
 * What someone typed, as a number. A comma is what a French keyboard puts there, and refusing
 * it would be refusing the obvious.
 */
export function parseWeight(typed: string): number {
    return Number(typed.replace(',', '.'))
}

/**
 * Today's weight is said by its hour, an older one by its day: on the day itself the hour is
 * what tells you something (morning or evening), and afterwards only the date does.
 */
export function formatWhen(day: string, recordedAt: string | null, isFromToday: boolean): string {
    if (isFromToday) {
        return recordedAt === null ? 'aujourd’hui' : `aujourd’hui à ${recordedAt.slice(11, 16)}`
    }

    const [year, month, dayOfMonth] = day.split('-')

    return `le ${dayOfMonth}/${month}/${year}`
}
