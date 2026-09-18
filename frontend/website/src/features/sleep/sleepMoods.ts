import { Angry, Frown, Laugh, Meh, Smile } from 'lucide-react'
import type { RatingLevel } from '../../components'

/**
 * The five faces, from a bad morning to a good one.
 *
 * The API answers a number and nothing else — the scale is fixed in its code, and the drawing
 * and the wording are this front end's business, as with the hydration icons.
 */
export const SLEEP_MOODS: RatingLevel[] = [
    { value: 1, icon: Angry, label: 'Très mauvais réveil' },
    { value: 2, icon: Frown, label: 'Mauvais réveil' },
    { value: 3, icon: Meh, label: 'Réveil moyen' },
    { value: 4, icon: Smile, label: 'Bon réveil' },
    { value: 5, icon: Laugh, label: 'Très bon réveil' },
]

/** Undefined rather than a guess, if the scale ever gains a level this front end does not know. */
export function moodFor(rating: number): RatingLevel | undefined {
    return SLEEP_MOODS.find((mood) => mood.value === rating)
}
