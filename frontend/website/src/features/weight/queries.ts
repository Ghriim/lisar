import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'

const WEIGHT = ['weight'] as const

export function useLatestWeight() {
    return useQuery({
        queryKey: [...WEIGHT, 'latest'],
        queryFn: api.fetchLatestWeight,
    })
}

/**
 * Saving answers with the weight it wrote, so the cache is filled from the answer rather than
 * invalidated and fetched again. One round trip, and the widget is already right.
 */
export function useSaveWeight() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: (weightInKilograms: number) => api.saveWeight(weightInKilograms),
        onSuccess: (weight) => queryClient.setQueryData([...WEIGHT, 'latest'], weight),
    })
}
