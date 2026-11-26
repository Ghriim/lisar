import type { SleepNight } from '../../api/types'

/**
 * The average night over the last seven, in minutes — « Moy. 7 j · 7 h 10 » under the sleep
 * tracker. The API does not answer it yet: null, shown « — », until it does.
 */
export function sleepWeekAverage(_night: SleepNight): number | null {
    return null
}
