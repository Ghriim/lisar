import type { PersonalBest } from '../../api/types'
import { formatDistance, formatDuration, formatLoad } from '../workouts/workoutFormat'

/**
 * How a personal best is named and read. The API answers a kind code and a raw value; the words
 * and the units live here, and only here.
 */

/**
 * The anchor of a record on the records page: one per movement — or the workouts as a whole — kind
 * and tier, so any of its steps, current or beaten since, leads to it.
 */
export function personalBestAnchor(record: { movementId: number | null; kind: string; tier: number | null }): string {
    return `record-${record.movementId ?? 'seance'}-${record.kind}${record.tier === null ? '' : `-${record.tier}`}`
}

/** The records page, on one record. */
export function personalBestPath(record: { movementId: number | null; kind: string; tier: number | null }): string {
    return `/records#${personalBestAnchor(record)}`
}

/** The distances worth a name of their own rather than a number. */
const NAMED_DISTANCES: Record<number, string> = {
    21097: 'semi',
    42195: 'marathon',
}

/** « Charge max », « 5RM », « Meilleur temps · 5 km », « Distance max · 24 kg ». */
export function personalBestLabel(record: { kind: string; tier: number | null }): string {
    const { kind, tier } = record

    switch (kind) {
        case 'max_weight':
            return 'Charge max'
        case 'max_weight_for_reps':
            return `${tier}RM`
        case 'estimated_one_rep_max':
            return '1RM estimé'
        case 'max_set_volume':
            return 'Volume sur une série'
        case 'max_workout_volume':
            return 'Volume sur une séance'
        case 'max_reps':
            return 'Reps max'
        case 'max_workout_reps':
            return 'Reps sur une séance'
        case 'max_duration':
            return 'Durée max'
        case 'max_workout_duration':
            return 'Durée sur une séance'
        case 'max_distance':
            return 'Distance max'
        case 'max_workout_distance':
            return 'Distance sur une séance'
        case 'best_time_for_distance':
            return `Meilleur temps · ${tier === null ? '' : (NAMED_DISTANCES[tier] ?? formatDistance(tier))}`
        case 'best_pace':
            return 'Meilleure allure'
        case 'max_distance_for_weight':
            return `Distance max · ${tier === null ? '' : formatLoad(tier)}`
        case 'max_session_volume':
            return 'Plus gros volume'
        case 'max_session_sets':
            return 'Plus de séries'
        case 'longest_session':
            return 'Séance la plus longue'
        default:
            // A kind this front end does not know yet still says something.
            return kind
    }
}

/** The value in the kind's unit: « 62,5 kg », « 15 reps », « 25:00 », « 4:52 /km », « 1 h 08 ». */
export function formatPersonalBestValue(record: Pick<PersonalBest, 'kind' | 'value'>): string {
    const { kind, value } = record

    switch (kind) {
        case 'max_reps':
        case 'max_workout_reps':
            return `${value} reps`
        case 'max_duration':
        case 'max_workout_duration':
        case 'best_time_for_distance':
            return formatDuration(Math.round(value))
        case 'best_pace':
            return `${formatDuration(Math.round(value))} /km`
        case 'max_distance':
        case 'max_workout_distance':
        case 'max_distance_for_weight':
            return formatDistance(value)
        case 'max_session_sets':
            return `${value} séries`
        case 'longest_session':
            return formatHoursAndMinutes(value)
        default:
            return formatLoad(value)
    }
}

/** « 52 min », « 1 h 08 ». */
function formatHoursAndMinutes(seconds: number): string {
    const minutes = Math.floor(seconds / 60)
    if (minutes < 60) {
        return `${minutes} min`
    }

    return `${Math.floor(minutes / 60)} h ${`${minutes % 60}`.padStart(2, '0')}`
}
