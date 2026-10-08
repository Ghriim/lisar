import { useCallback, useState } from 'react'
import type { WorkoutExercise, WorkoutSet } from '../../api/types'
import { unlockRestSignal } from './restSignal'

/** What −15 s and +15 s move the end by. */
const STEP_IN_SECONDS = 15

/** A rest under way: when it ends, and which set started it. */
interface Rest {
    endsAt: number
    setId: number
}

export interface RestTimer {
    /** When the rest under way ends, in epoch milliseconds. Null when none is. */
    endsAt: number | null
    /** A set was ticked: its movement's rest starts over, or none runs if it has none. */
    start: (exercise: WorkoutExercise, set: WorkoutSet) => void
    /** A set was unticked, or its tick refused: the rest it started stops with it. */
    cancel: (set: WorkoutSet) => void
    shorten: () => void
    lengthen: () => void
    /** Passed, or over: either way there is no rest any more. */
    stop: () => void
}

/**
 * The rest between two sets, kept in this tab only: nothing of it is stored, so it ends with the
 * page. It is an instant to count down to rather than seconds to count, so it stays right however
 * long a phone kept the tab asleep. Counting it down is the bar's job: the page holding this does
 * not redraw every second.
 */
export function useRestTimer(): RestTimer {
    const [rest, setRest] = useState<Rest | null>(null)

    const start = useCallback((exercise: WorkoutExercise, set: WorkoutSet) => {
        if (exercise.restInSeconds === null) {
            // The next set is done: whatever rest ran before it is behind.
            setRest(null)

            return
        }

        unlockRestSignal()
        setRest({ endsAt: Date.now() + exercise.restInSeconds * 1000, setId: set.id })
    }, [])

    const cancel = useCallback((set: WorkoutSet) => {
        setRest((current) => (current?.setId === set.id ? null : current))
    }, [])

    // −15 s goes no lower than now: a rest ends at zero, it never runs below it.
    const shift = (seconds: number) =>
        setRest((current) =>
            current === null ? null : { ...current, endsAt: Math.max(Date.now(), current.endsAt + seconds * 1000) },
        )

    return {
        endsAt: rest?.endsAt ?? null,
        start,
        cancel,
        shorten: () => shift(-STEP_IN_SECONDS),
        lengthen: () => shift(STEP_IN_SECONDS),
        stop: useCallback(() => setRest(null), []),
    }
}
