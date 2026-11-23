import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { Routine, RoutineSetPayload, WorkoutBlockExercisePayload } from '../../api/types'

/** Every routine query answers to this key, so a write can reach all of them at once. */
const ROUTINES = ['routines'] as const

const LIST = [...ROUTINES, 'list'] as const

export function useRoutines() {
    return useQuery({
        queryKey: LIST,
        queryFn: api.fetchRoutines,
    })
}

export function useRoutine(id: number) {
    return useQuery({
        queryKey: [...ROUTINES, 'one', id],
        queryFn: () => api.fetchRoutine(id),
    })
}

/**
 * A write on a routine answers the whole routine: it goes straight into the cache, so the page
 * redraws from that one response. The list counts its movements and sorts by name: it is re-read.
 */
function useRoutineMutation<TVariables>(mutationFn: (variables: TVariables) => Promise<Routine>) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        onSuccess: async (routine) => {
            queryClient.setQueryData([...ROUTINES, 'one', routine.id], routine)
            await queryClient.invalidateQueries({ queryKey: LIST })
        },
    })
}

export function useCreateRoutine() {
    return useRoutineMutation((name: string) => api.createRoutine(name))
}

export function useRenameRoutine() {
    return useRoutineMutation(({ id, name }: { id: number; name: string }) => api.updateRoutine(id, name))
}

export function useCopyRoutine() {
    return useRoutineMutation((id: number) => api.copyRoutine(id))
}

/** The workouts it started lose their link to it: they are re-read too. */
export function useDeleteRoutine() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: (id: number) => api.deleteRoutine(id),
        onSuccess: async (_, id) => {
            queryClient.removeQueries({ queryKey: [...ROUTINES, 'one', id] })
            await Promise.all([
                queryClient.invalidateQueries({ queryKey: LIST }),
                queryClient.invalidateQueries({ queryKey: ['workouts'] }),
            ])
        },
    })
}

export function useAddRoutineBlock() {
    return useRoutineMutation(({ id, exercises }: { id: number; exercises: WorkoutBlockExercisePayload[] }) =>
        api.addRoutineBlock(id, exercises),
    )
}

export function useReorderRoutineBlocks() {
    return useRoutineMutation(({ id, blockIds }: { id: number; blockIds: number[] }) =>
        api.reorderRoutineBlocks(id, blockIds),
    )
}

export function useDeleteRoutineBlock() {
    return useRoutineMutation(({ id, blockId }: { id: number; blockId: number }) => api.deleteRoutineBlock(id, blockId))
}

export function useAddRoutineExercise() {
    return useRoutineMutation(
        ({ id, blockId, exercise }: { id: number; blockId: number; exercise: WorkoutBlockExercisePayload }) =>
            api.addRoutineExercise(id, blockId, exercise),
    )
}

export function useUpdateRoutineExercise() {
    return useRoutineMutation(
        ({ id, exerciseId, ...payload }: { id: number; exerciseId: number; note: string | null; restInSeconds: number | null }) =>
            api.updateRoutineExercise(id, exerciseId, payload),
    )
}

export function useDeleteRoutineExercise() {
    return useRoutineMutation(({ id, exerciseId }: { id: number; exerciseId: number }) =>
        api.deleteRoutineExercise(id, exerciseId),
    )
}

export function useAddRoutineSet() {
    return useRoutineMutation(
        ({ id, exerciseId, payload }: { id: number; exerciseId: number; payload: RoutineSetPayload }) =>
            api.addRoutineSet(id, exerciseId, payload),
    )
}

export function useUpdateRoutineSet() {
    return useRoutineMutation(({ id, setId, payload }: { id: number; setId: number; payload: RoutineSetPayload }) =>
        api.updateRoutineSet(id, setId, payload),
    )
}

export function useDeleteRoutineSet() {
    return useRoutineMutation(({ id, setId }: { id: number; setId: number }) => api.deleteRoutineSet(id, setId))
}
