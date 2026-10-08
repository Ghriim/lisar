import { useLayoutEffect, useState } from 'react'
import { Button } from '../../components'
import { playRestSignal } from './restSignal'
import type { RestTimer } from './useRestTimer'
import { formatDuration } from './workoutFormat'

/** Four times a second: the shown second turns over on time, not up to a second late. */
const TICK_IN_MILLISECONDS = 250

/**
 * Past its end by more than this, a rest is closed without a sound: the tab was asleep, and a beep
 * now would come long after the rest it was for.
 */
const LATE_SIGNAL_IN_MILLISECONDS = 3000

/**
 * The rest under way, held at the bottom of the screen over the sets: what is left, and the three
 * ways to change it. At zero it signals, and is gone. Drawn last on the page, with room kept under
 * what comes before it, so it never sits on the last button for good.
 */
export function RestTimerBar({ timer }: { timer: RestTimer }) {
    const { endsAt, stop } = timer
    const [now, setNow] = useState(() => Date.now())

    // Before the paint: a rest just started is drawn against the time now, not the last tick's.
    useLayoutEffect(() => {
        if (endsAt === null) {
            return undefined
        }

        const tick = () => {
            const at = Date.now()
            setNow(at)
            if (at >= endsAt) {
                if (at - endsAt < LATE_SIGNAL_IN_MILLISECONDS) {
                    playRestSignal()
                }
                stop()
            }
        }

        tick()
        const interval = setInterval(tick, TICK_IN_MILLISECONDS)

        return () => clearInterval(interval)
    }, [endsAt, stop])

    if (endsAt === null) {
        return null
    }

    const remaining = Math.max(0, Math.ceil((endsAt - now) / 1000))

    return (
        <>
            <div className="rest-timer-room" aria-hidden />
            <div className="rest-timer" role="timer" aria-label="Repos">
                <span className="rest-timer-label">Repos</span>
                <span className="rest-timer-time">{formatStopwatch(remaining)}</span>
                <div className="rest-timer-actions">
                    <Button variant="quiet" onClick={timer.shorten}>
                        −15 s
                    </Button>
                    <Button variant="quiet" onClick={timer.lengthen}>
                        +15 s
                    </Button>
                    <Button onClick={stop}>Passer</Button>
                </div>
            </div>
        </>
    )
}

/** Always as a stopwatch, « 0:45 » included: a countdown that changes shape at a minute jumps. */
function formatStopwatch(seconds: number): string {
    return seconds < 60 ? `0:${`${seconds}`.padStart(2, '0')}` : formatDuration(seconds)
}
