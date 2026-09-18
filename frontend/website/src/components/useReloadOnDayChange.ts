import { useEffect, useRef } from 'react'

/** How often a visible page checks, in milliseconds. */
const CHECK_INTERVAL = 5 * 60 * 1000

/**
 * Reloads the page when the server's day turns under it.
 *
 * A tab left open overnight shows yesterday: the totals, the goal, the entries — all of it
 * belongs to a day that is over. The back end is not confused, it writes to the right day; the
 * page is, and no amount of refetching one widget fixes a screen built around the wrong date.
 *
 * The day is the server's, never one computed here: the timezone days are counted in is a
 * back-end decision, and duplicating it in the browser is how the two start disagreeing.
 *
 * @param day     the day the page is currently showing, as the API reports it
 * @param recheck asks the API again — a cheap request that answers "what day is it now?"
 */
export function useReloadOnDayChange(day: string | undefined, recheck: () => void): void {
    const loadedDay = useRef<string | undefined>(undefined)

    useEffect(() => {
        if (day === undefined) {
            return
        }

        if (loadedDay.current === undefined) {
            loadedDay.current = day

            return
        }

        if (loadedDay.current !== day) {
            window.location.reload()
        }
    }, [day])

    useEffect(() => {
        const check = () => {
            if (document.visibilityState === 'visible') {
                recheck()
            }
        }

        // Coming back to the tab is the common case — someone opens their laptop the next
        // morning — and it is caught at once. The interval is the safety net for a screen left
        // on across midnight.
        document.addEventListener('visibilitychange', check)
        const timer = window.setInterval(check, CHECK_INTERVAL)

        return () => {
            document.removeEventListener('visibilitychange', check)
            window.clearInterval(timer)
        }
    }, [recheck])
}
