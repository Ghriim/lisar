import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { Habit } from '../../api/types'
import { PLANNING } from '../planning/queries'

const HABITS = ['habits'] as const

export function useHabits() {
    return useQuery({
        queryKey: [...HABITS, 'list'],
        queryFn: api.fetchHabits,
    })
}

/**
 * Ticking and un-ticking both answer with the habit they changed, so its line is refreshed from
 * the answer rather than the whole list refetched. Today's date on the planning follows, re-read.
 */
function useCompletionMutation(mutationFn: (habitId: number) => Promise<Habit>) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        onSuccess: async (habit) => {
            queryClient.setQueryData<Habit[]>([...HABITS, 'list'], (habits) =>
                habits?.map((current) => (current.habitId === habit.habitId ? habit : current)),
            )
            await queryClient.invalidateQueries({ queryKey: PLANNING })
        },
    })
}

export function useCompleteHabit() {
    return useCompletionMutation(api.completeHabit)
}

export function useUncompleteHabit() {
    return useCompletionMutation(api.uncompleteHabit)
}

export function useHabitCatalog() {
    return useQuery({
        queryKey: [...HABITS, 'catalog'],
        queryFn: api.fetchHabitCatalog,
    })
}

/**
 * Subscribing or dropping changes both the catalogue (the flag) and the kept list (a row appears
 * or leaves), so both are refetched — and the planning, which a dropped habit leaves with its
 * entries.
 */
function useSubscriptionMutation(mutationFn: (habitId: number) => Promise<unknown>) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        onSuccess: async () => {
            await Promise.all([
                queryClient.invalidateQueries({ queryKey: [...HABITS, 'catalog'] }),
                queryClient.invalidateQueries({ queryKey: [...HABITS, 'list'] }),
                queryClient.invalidateQueries({ queryKey: PLANNING }),
            ])
        },
    })
}

export function useSubscribeHabit() {
    return useSubscriptionMutation(api.subscribeHabit)
}

export function useUnsubscribeHabit() {
    return useSubscriptionMutation(api.unsubscribeHabit)
}
