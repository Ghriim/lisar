import { Angry, Frown, Laugh, Meh, Smile } from 'lucide-react'
import type { RatingLevel } from '../../components'

/**
 * The five faces of how a workout felt, from a bad one to a good one. The same faces as the
 * sleep mood — a face means the same thing everywhere — worded for a workout.
 */
export const WORKOUT_FEELINGS: RatingLevel[] = [
    { value: 1, icon: Angry, label: 'Très mauvaise séance' },
    { value: 2, icon: Frown, label: 'Mauvaise séance' },
    { value: 3, icon: Meh, label: 'Séance moyenne' },
    { value: 4, icon: Smile, label: 'Bonne séance' },
    { value: 5, icon: Laugh, label: 'Très bonne séance' },
]

/** Undefined rather than a guess, if the scale ever gains a level this front end does not know. */
export function feelingFor(feeling: number): RatingLevel | undefined {
    return WORKOUT_FEELINGS.find((level) => level.value === feeling)
}
