import type { RoutineSet, WorkoutMovement } from '../../api/types'
import { formatLoad } from '../workouts/workoutFormat'

/** The longest name a routine takes: the workout it starts adds the day to it. */
export const ROUTINE_NAME_MAX_LENGTH = 100

/**
 * What a set prescribes: « 5 reps · 80 % », « 10 reps » for a movement without a load. A
 * unilateral movement's reps count per side, as on a workout.
 */
export function formatPrescription(set: RoutineSet, movement: Pick<WorkoutMovement, 'isUnilateral'>): string {
    const parts: string[] = []

    if (set.reps !== null) {
        parts.push(`${set.reps} reps${movement.isUnilateral ? ' / côté' : ''}`)
    }
    if (set.percentageOfOneRepMax !== null) {
        parts.push(`${`${set.percentageOfOneRepMax}`.replace('.', ',')} %`)
    }

    return parts.join(' · ')
}

/**
 * The load the set would give a workout started now, « ≈ 72 kg », or « 1RM inconnu » while the
 * movement has none. Nothing for a movement without a load.
 */
export function formatTodayLoad(set: RoutineSet): string | undefined {
    if (set.percentageOfOneRepMax === null) {
        return undefined
    }

    return set.estimatedLoadInKilograms === null ? '1RM inconnu' : `≈ ${formatLoad(set.estimatedLoadInKilograms)}`
}

/** « 3 mouvements ». */
export function formatMovementCount(count: number): string {
    return count === 0 ? 'Aucun mouvement' : `${count} mouvement${count > 1 ? 's' : ''}`
}
