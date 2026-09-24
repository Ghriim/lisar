import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'

const STEPS = ['steps'] as const

export function useStepsToday() {
    return useQuery({
        queryKey: [...STEPS, 'today'],
        queryFn: api.fetchStepsToday,
    })
}

/**
 * Saving answers with the day it wrote, so the cache is filled from the answer rather than
 * invalidated and fetched again. One round trip, and the widget is already right.
 */
export function useSaveSteps() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: (countInSteps: number) => api.saveSteps(countInSteps),
        onSuccess: (day) => queryClient.setQueryData([...STEPS, 'today'], day),
    })
}
