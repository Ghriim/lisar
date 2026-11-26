import type { TrainingPlanningEntry } from '../../api/types'

/** « 45 min », « 1 h 30 », « 1 h ». */
export function formatMinutes(minutes: number): string {
    if (minutes < 60) {
        return `${minutes} min`
    }
    const rest = minutes % 60

    return rest === 0 ? `${Math.floor(minutes / 60)} h` : `${Math.floor(minutes / 60)} h ${String(rest).padStart(2, '0')}`
}

/** « 1 exercice », « 5 exercices ». */
export function formatExerciseCount(count: number): string {
    return count <= 1 ? `${count} exercice` : `${count} exercices`
}

/** Timed sessions by their time, then the ones with none, last. */
export function byStartTime(a: TrainingPlanningEntry, b: TrainingPlanningEntry): number {
    const left = a.schedule.startTime
    const right = b.schedule.startTime
    if (left === right) {
        return 0
    }
    if (left === null) {
        return 1
    }
    if (right === null) {
        return -1
    }

    return left < right ? -1 : 1
}

const WEEKDAYS = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']

/** « Vendredi », from "YYYY-MM-DD". */
export function weekdayOf(day: string): string {
    const [year, month, date] = day.split('-').map(Number)

    return WEEKDAYS[new Date(year, month - 1, date).getDay()]
}
