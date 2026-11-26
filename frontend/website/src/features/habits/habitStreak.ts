import type { Habit } from '../../api/types'

/**
 * How many planned days in a row a habit has been kept. No run is counted anywhere yet — « a run
 * counts planned days only » is the gamification's to apply — so this is where it will be read
 * from, and until then the line shows « série — ».
 */
export function habitStreak(_habit: Habit): number | null {
    return null
}
