import { ApiError } from '../../api/client'
import type { WorkoutMovement, WorkoutSet } from '../../api/types'
import { humanise } from '../../api/violations'

/**
 * How a workout, its moments and its sets are written. Its own file: a module that exports
 * components exports nothing else.
 */

const SHORT_DAY = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short' })
const LONG_DAY = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
const HOUR = new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' })

/** The name given, or the one the front words when there is none: « Séance du 2 oct. ». */
export function workoutName(workout: { name: string | null; startedAt: string }): string {
    return workout.name ?? `Séance du ${SHORT_DAY.format(new Date(workout.startedAt))}`
}

/** « 2 oct. », for a history row or the last time a movement was done. */
export function formatShortDay(instant: string): string {
    return SHORT_DAY.format(new Date(instant))
}

/** « jeudi 2 octobre 2026 ». */
export function formatLongDay(instant: string): string {
    return LONG_DAY.format(new Date(instant))
}

/** « 18:02 ». */
export function formatHour(instant: string): string {
    return HOUR.format(new Date(instant))
}

/** How long between two instants, to the minute: « 42 min », « 1 h 08 ». */
export function formatElapsed(from: string, to: string | Date): string {
    const end = typeof to === 'string' ? new Date(to) : to
    const minutes = Math.max(0, Math.floor((end.getTime() - new Date(from).getTime()) / 60000))

    if (minutes < 60) {
        return `${minutes} min`
    }

    return `${Math.floor(minutes / 60)} h ${`${minutes % 60}`.padStart(2, '0')}`
}

/** A load as a person reads it: « 60 kg », « 62,5 kg » — never 62.50. */
export function formatLoad(weightInKilograms: number): string {
    return `${`${Math.round(weightInKilograms * 100) / 100}`.replace('.', ',')} kg`
}

/** « 45 s » under a minute, then a stopwatch: « 1:30 », « 1:05:00 ». */
export function formatDuration(durationInSeconds: number): string {
    if (durationInSeconds < 60) {
        return `${durationInSeconds} s`
    }

    const hours = Math.floor(durationInSeconds / 3600)
    const minutes = Math.floor((durationInSeconds % 3600) / 60)
    const seconds = `${durationInSeconds % 60}`.padStart(2, '0')

    return hours === 0 ? `${minutes}:${seconds}` : `${hours}:${`${minutes}`.padStart(2, '0')}:${seconds}`
}

/** Metres under a kilometre, kilometres from there: « 800 m », « 5,2 km ». */
export function formatDistance(distanceInMetres: number): string {
    if (distanceInMetres < 1000) {
        return `${distanceInMetres} m`
    }

    return `${`${Math.round(distanceInMetres / 100) / 10}`.replace('.', ',')} km`
}

/** « RPE 7,5 ». */
export function formatRpe(rpe: number): string {
    return `RPE ${`${rpe}`.replace('.', ',')}`
}

/**
 * The measures of a set, joined: « 10 reps · 60 kg ». A unilateral movement's reps count per
 * side, and the line says so — that is the whole effect of the flag.
 */
export function formatSetMeasures(set: WorkoutSet, movement: Pick<WorkoutMovement, 'isUnilateral'>): string {
    const parts: string[] = []

    if (set.reps !== null) {
        parts.push(`${set.reps} reps${movement.isUnilateral ? ' / côté' : ''}`)
    }
    if (set.weightInKilograms !== null) {
        parts.push(formatLoad(set.weightInKilograms))
    }
    if (set.durationInSeconds !== null) {
        parts.push(formatDuration(set.durationInSeconds))
    }
    if (set.distanceInMetres !== null) {
        parts.push(formatDistance(set.distanceInMetres))
    }

    return parts.join(' · ')
}

/** What someone typed as a decimal. A comma is what a French keyboard puts there. */
export function parseDecimal(typed: string): number | null {
    const trimmed = typed.trim()

    return trimmed === '' ? null : Number(trimmed.replace(',', '.'))
}

/** A duration typed as seconds (« 90 ») or as a stopwatch reads (« 1:30 », « 1:05:00 »). */
export function parseDuration(typed: string): number | null {
    const trimmed = typed.trim()

    if (trimmed === '') {
        return null
    }

    return trimmed.split(':').reduce((total, part) => total * 60 + Number(part), 0)
}

/** The other way round, to put a duration back in its field. */
export function durationField(durationInSeconds: number | null): string {
    return durationInSeconds === null || durationInSeconds < 60 ? `${durationInSeconds ?? ''}` : formatDuration(durationInSeconds)
}

/** A decimal back in its field, with the comma it was typed with. */
export function decimalField(value: number | null): string {
    return value === null ? '' : `${value}`.replace('.', ',')
}

/**
 * The one line to show when an action on a workout was refused. The API names a field, or none;
 * either way there is no input to put the error under, so the first code is what is said.
 */
export function failureOf(failure: unknown): string | null {
    if (failure === null || failure === undefined) {
        return null
    }

    if (!(failure instanceof ApiError)) {
        return humanise('action_failed')
    }

    const codes = Object.values(failure.violations ?? {}).flat()

    return humanise(codes[0] ?? failure.code ?? 'action_failed')
}
