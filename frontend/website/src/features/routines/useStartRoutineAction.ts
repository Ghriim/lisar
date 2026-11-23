import { useNavigate } from 'react-router-dom'
import { useCurrentWorkout, useStartWorkoutFromRoutine } from '../workouts/queries'
import type { WorkoutCopyArrival } from '../workouts/useCopyWorkoutAction'
import { failureOf } from '../workouts/workoutFormat'

/**
 * Starting a workout from a routine: a new one starts, laid out like it, and its page opens, saying
 * once what it left out. Only offered while no workout is in progress, as « Refaire » is.
 */
export function useStartRoutineAction() {
    const current = useCurrentWorkout()
    const start = useStartWorkoutFromRoutine()
    const navigate = useNavigate()

    return {
        isOffered: current.data === null,
        isPending: start.isPending,
        error: failureOf(start.error),
        start: (routineId: number) =>
            start.mutate(routineId, {
                onSuccess: ({ workout, skippedMovements }) =>
                    void navigate(`/workouts/${workout.id}`, {
                        state: { skippedMovements } satisfies WorkoutCopyArrival,
                    }),
            }),
    }
}
