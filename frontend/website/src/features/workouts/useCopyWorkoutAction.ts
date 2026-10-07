import { useNavigate } from 'react-router-dom'
import { useCopyWorkout, useCurrentWorkout } from './queries'
import { failureOf } from './workoutFormat'

/** What the new workout's page receives on arrival: the movements the copy left out. */
export interface WorkoutCopyArrival {
    skippedMovements: string[]
}

/**
 * Doing a finished workout again: a new one starts, laid out like it, and its page opens. Only
 * offered while no workout is in progress — the API would refuse it, and the one in progress is
 * where the person belongs.
 */
export function useCopyWorkoutAction() {
    const current = useCurrentWorkout()
    const copy = useCopyWorkout()
    const navigate = useNavigate()

    return {
        isOffered: current.data === null,
        isPending: copy.isPending,
        error: failureOf(copy.error),
        copy: (workoutId: number) =>
            copy.mutate(workoutId, {
                onSuccess: ({ workout, skippedMovements }) =>
                    void navigate(`/workouts/${workout.id}`, {
                        state: { skippedMovements } satisfies WorkoutCopyArrival,
                    }),
            }),
    }
}
