/** How a night's length is written, and how the API's instants become times of day. */

const MINUTES_PER_HOUR = 60

/** "7 h 30", and "8 h" on the hour — never "8 h 00", which nobody says. */
export function formatDuration(durationInMinutes: number): string {
    const hours = Math.floor(durationInMinutes / MINUTES_PER_HOUR)
    const minutes = durationInMinutes % MINUTES_PER_HOUR

    return minutes === 0 ? `${hours} h` : `${hours} h ${String(minutes).padStart(2, '0')}`
}

/**
 * The time of day the API reports, which it has already read on the clock the day is counted on.
 * Empty string rather than null: it is what an empty `<input type="time">` holds.
 */
export function timeOf(moment: string | null): string {
    return moment === null ? '' : moment.slice(11, 16)
}
