import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { TrainingPlanningEntryPayload } from '../../api/types'

/** Every planning query answers to this key: a write, or a workout started or deleted, re-reads them all. */
export const PLANNING = ['planning'] as const

export function useTrainingPlanning(from: string, to: string) {
    return useQuery({
        queryKey: [...PLANNING, 'training', from, to],
        queryFn: () => api.fetchTrainingPlanning(from, to),
        // Turning a week keeps the one being left on screen until the next arrives.
        placeholderData: keepPreviousData,
    })
}

/** An entry's write answers the entry alone: which dates it gives is the planning's to say, re-read. */
function usePlanningMutation<TVariables, TResult>(mutationFn: (variables: TVariables) => Promise<TResult>) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        onSuccess: () => queryClient.invalidateQueries({ queryKey: PLANNING }),
    })
}

export function useCreateTrainingPlanningEntry() {
    return usePlanningMutation((payload: TrainingPlanningEntryPayload) => api.createTrainingPlanningEntry(payload))
}

export function useUpdateTrainingPlanningEntry() {
    return usePlanningMutation(({ id, payload }: { id: number; payload: TrainingPlanningEntryPayload }) =>
        api.updateTrainingPlanningEntry(id, payload),
    )
}

export function useDeleteTrainingPlanningEntry() {
    return usePlanningMutation((id: number) => api.deleteTrainingPlanningEntry(id))
}
