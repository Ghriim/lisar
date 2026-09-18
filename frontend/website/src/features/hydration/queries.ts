import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { HydrationDay } from '../../api/types'

const HYDRATION = ['hydration'] as const

export function useHydrationToday() {
    return useQuery({
        queryKey: [...HYDRATION, 'today'],
        queryFn: api.fetchHydrationToday,
    })
}

export function useHydrationPresets() {
    return useQuery({
        queryKey: [...HYDRATION, 'presets'],
        queryFn: api.fetchHydrationPresets,
        // Reference data: it changes in the back-office, not while someone drinks.
        staleTime: 5 * 60 * 1000,
    })
}

/**
 * Every write answers with the whole day, so the cache is filled from the answer rather than
 * invalidated and fetched again. One round trip, and the widget is already right.
 */
function useDayMutation<TVariables>(mutationFn: (variables: TVariables) => Promise<HydrationDay>) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        onSuccess: (day) => queryClient.setQueryData([...HYDRATION, 'today'], day),
    })
}

export function useLogHydration() {
    return useDayMutation((volumeInMillilitres: number) => api.createHydrationEntry(volumeInMillilitres))
}

export function useCorrectHydrationEntry() {
    return useDayMutation(({ id, volumeInMillilitres }: { id: number; volumeInMillilitres: number }) =>
        api.updateHydrationEntry(id, volumeInMillilitres),
    )
}

export function useRemoveHydrationEntry() {
    return useDayMutation((id: number) => api.deleteHydrationEntry(id))
}
