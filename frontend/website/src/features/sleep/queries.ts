import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'

const SLEEP = ['sleep'] as const

export function useSleepToday() {
    return useQuery({
        queryKey: [...SLEEP, 'today'],
        queryFn: api.fetchSleepToday,
    })
}

interface SleepNightPayload {
    bedtime: string
    wakeUpTime: string
    moodRating: number | null
}

/**
 * Saving answers with the night it wrote, so the cache is filled from the answer rather than
 * invalidated and fetched again. One round trip, and the widget is already right.
 */
export function useSaveSleepNight() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: ({ bedtime, wakeUpTime, moodRating }: SleepNightPayload) =>
            api.saveSleepNight(bedtime, wakeUpTime, moodRating),
        onSuccess: (night) => queryClient.setQueryData([...SLEEP, 'today'], night),
    })
}
