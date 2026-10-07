import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { Workout, WorkoutDetailsPayload, WorkoutSetPayload } from '../../api/types'

/** Every workout query answers to this key, so a write can reach all of them at once. */
const WORKOUTS = ['workouts'] as const

const CURRENT = [...WORKOUTS, 'current'] as const

/** Reference data: it changes in the back-office, not while someone lifts. */
const REFERENCE_STALE_TIME = 5 * 60 * 1000

export function useCurrentWorkout() {
    return useQuery({
        queryKey: CURRENT,
        queryFn: api.fetchCurrentWorkout,
    })
}

export function useWorkout(id: number) {
    return useQuery({
        queryKey: [...WORKOUTS, 'one', id],
        queryFn: () => api.fetchWorkout(id),
    })
}

export function useWorkoutHistory(page: number) {
    return useQuery({
        queryKey: [...WORKOUTS, 'history', page],
        queryFn: () => api.fetchWorkouts(page),
        // Turning a page keeps the one being left on screen until the next arrives.
        placeholderData: keepPreviousData,
    })
}

export function usePreviousPerformances(id: number) {
    return useQuery({
        queryKey: [...WORKOUTS, 'previous', id],
        queryFn: () => api.fetchWorkoutPreviousPerformances(id),
    })
}

export function useWorkoutStats(id: number) {
    return useQuery({
        queryKey: [...WORKOUTS, 'stats', id],
        queryFn: () => api.fetchWorkoutStats(id),
    })
}

export function useWorkoutMovements() {
    return useQuery({
        queryKey: ['workout-movements'],
        queryFn: api.fetchWorkoutMovements,
        staleTime: REFERENCE_STALE_TIME,
    })
}

export function useWorkoutSetTypes() {
    return useQuery({
        queryKey: ['workout-set-types'],
        queryFn: api.fetchWorkoutSetTypes,
        staleTime: REFERENCE_STALE_TIME,
    })
}

/**
 * A write inside a workout answers the whole workout: it goes straight into the cache, so the
 * screen redraws from that one response. What it cannot know — the history rows, what each
 * movement gave the last time — is re-read. Only starting, finishing and deleting change which
 * days a workout habit was kept on.
 */
function useWorkoutMutation<TVariables>(
    mutationFn: (variables: TVariables) => Promise<Workout>,
    { touchesHabits = false }: { touchesHabits?: boolean } = {},
) {
    const store = useStoreWorkout()

    return useMutation({
        mutationFn,
        onSuccess: (workout) => store(workout, { touchesHabits }),
    })
}

/** Puts a workout a write answered into the cache, and re-reads what that write may have changed. */
function useStoreWorkout() {
    const queryClient = useQueryClient()

    return async (workout: Workout, { touchesHabits = false }: { touchesHabits?: boolean } = {}) => {
        queryClient.setQueryData([...WORKOUTS, 'one', workout.id], workout)
        queryClient.setQueryData(CURRENT, workout.isInProgress ? workout : null)

        await Promise.all([
            queryClient.invalidateQueries({ queryKey: [...WORKOUTS, 'history'] }),
            queryClient.invalidateQueries({ queryKey: [...WORKOUTS, 'previous', workout.id] }),
            queryClient.invalidateQueries({ queryKey: [...WORKOUTS, 'stats', workout.id] }),
            touchesHabits ? queryClient.invalidateQueries({ queryKey: ['habits'] }) : null,
        ])
    }
}

export function useStartWorkout() {
    return useWorkoutMutation((name: string | null) => api.startWorkout(name))
}

/** Starting a workout from a past one answers the new workout, with what it left out. */
export function useCopyWorkout() {
    const store = useStoreWorkout()

    return useMutation({
        mutationFn: (id: number) => api.copyWorkout(id),
        onSuccess: (copy) => store(copy.workout),
    })
}

export function useUpdateWorkout() {
    return useWorkoutMutation(({ id, ...payload }: { id: number } & WorkoutDetailsPayload) =>
        api.updateWorkout(id, payload),
    )
}

export function useFinishWorkout() {
    return useWorkoutMutation((id: number) => api.finishWorkout(id), { touchesHabits: true })
}

/** Abandoning the one in progress and deleting a finished one are the same call. */
export function useDeleteWorkout() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: (workout: Workout) => api.deleteWorkout(workout.id),
        onSuccess: async (_, workout) => {
            queryClient.removeQueries({ queryKey: [...WORKOUTS, 'one', workout.id] })
            if (workout.isInProgress) {
                queryClient.setQueryData(CURRENT, null)
            }

            await Promise.all([
                queryClient.invalidateQueries({ queryKey: [...WORKOUTS, 'history'] }),
                queryClient.invalidateQueries({ queryKey: ['habits'] }),
            ])
            queryClient.removeQueries({ queryKey: [...WORKOUTS, 'stats', workout.id] })
        },
    })
}

export function useAddWorkoutBlock() {
    return useWorkoutMutation(({ id, movementIds }: { id: number; movementIds: number[] }) =>
        api.addWorkoutBlock(id, movementIds),
    )
}

export function useReorderWorkoutBlocks() {
    return useWorkoutMutation(({ id, blockIds }: { id: number; blockIds: number[] }) =>
        api.reorderWorkoutBlocks(id, blockIds),
    )
}

export function useDeleteWorkoutBlock() {
    return useWorkoutMutation(({ id, blockId }: { id: number; blockId: number }) =>
        api.deleteWorkoutBlock(id, blockId),
    )
}

export function useAddWorkoutExercise() {
    return useWorkoutMutation(({ id, blockId, movementId }: { id: number; blockId: number; movementId: number }) =>
        api.addWorkoutExercise(id, blockId, movementId),
    )
}

export function useUpdateWorkoutExercise() {
    return useWorkoutMutation(({ id, exerciseId, note }: { id: number; exerciseId: number; note: string | null }) =>
        api.updateWorkoutExercise(id, exerciseId, note),
    )
}

export function useDeleteWorkoutExercise() {
    return useWorkoutMutation(({ id, exerciseId }: { id: number; exerciseId: number }) =>
        api.deleteWorkoutExercise(id, exerciseId),
    )
}

export function useAddWorkoutSet() {
    return useWorkoutMutation(
        ({ id, exerciseId, payload }: { id: number; exerciseId: number; payload: WorkoutSetPayload }) =>
            api.addWorkoutSet(id, exerciseId, payload),
    )
}

export function useUpdateWorkoutSet() {
    return useWorkoutMutation(
        ({ id, setId, payload }: { id: number; setId: number; payload: WorkoutSetPayload }) =>
            api.updateWorkoutSet(id, setId, payload),
    )
}

export function useCompleteWorkoutSet() {
    return useWorkoutMutation(({ id, setId }: { id: number; setId: number }) => api.completeWorkoutSet(id, setId))
}

export function useUncompleteWorkoutSet() {
    return useWorkoutMutation(({ id, setId }: { id: number; setId: number }) => api.uncompleteWorkoutSet(id, setId))
}

export function useDeleteWorkoutSet() {
    return useWorkoutMutation(({ id, setId }: { id: number; setId: number }) => api.deleteWorkoutSet(id, setId))
}
