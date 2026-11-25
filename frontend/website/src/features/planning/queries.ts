import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { HabitPlanningEntryPayload, TaskPlanningEntryPayload, TrainingPlanningEntryPayload } from '../../api/types'
import { dayKey } from './planningFormat'

/**
 * Every planning query answers to this key: a write, a workout started or deleted, a habit kept or
 * dropped, re-reads them all.
 */
export const PLANNING = ['planning'] as const

/** The habits panel's key, spelt here rather than imported: the habits feature imports this file. */
const HABITS = ['habits'] as const

/** The task lists' key, spelt here likewise: a task entry makes tasks. */
const TASKS = ['tasks'] as const

export function useTrainingPlanning(from: string, to: string) {
    return useQuery({
        queryKey: [...PLANNING, 'training', from, to],
        queryFn: () => api.fetchTrainingPlanning(from, to),
        // Turning a week keeps the one being left on screen until the next arrives.
        placeholderData: keepPreviousData,
    })
}

export function useTaskPlanning(from: string, to: string) {
    return useQuery({
        queryKey: [...PLANNING, 'task', from, to],
        queryFn: () => api.fetchTaskPlanning(from, to),
        placeholderData: keepPreviousData,
    })
}

export function useHabitPlanning(from: string, to: string) {
    return useQuery({
        queryKey: [...PLANNING, 'habit', from, to],
        queryFn: () => api.fetchHabitPlanning(from, to),
        placeholderData: keepPreviousData,
    })
}

/**
 * Every habit entry of the person's, whatever days it falls on: the planning answers all of them
 * with any range, so today's is asked — the dates do not matter here, only the entries.
 */
export function useHabitPlanningEntries() {
    const today = dayKey(new Date())

    return useQuery({
        queryKey: [...PLANNING, 'habit', today, today],
        queryFn: () => api.fetchHabitPlanning(today, today),
        select: (planning) => planning.entries,
    })
}

/**
 * An entry's write answers the entry alone: which dates it gives is the planning's to say, re-read.
 * A habit entry also changes which days the habits panel draws dimmed; a task entry makes or
 * deletes tasks.
 */
function usePlanningMutation<TVariables, TResult>(mutationFn: (variables: TVariables) => Promise<TResult>, touches: 'habits' | 'tasks' | null = null) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        onSuccess: () =>
            Promise.all([
                queryClient.invalidateQueries({ queryKey: PLANNING }),
                touches === null ? null : queryClient.invalidateQueries({ queryKey: touches === 'habits' ? HABITS : TASKS }),
            ]),
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

export function useCreateHabitPlanningEntry() {
    return usePlanningMutation((payload: HabitPlanningEntryPayload) => api.createHabitPlanningEntry(payload), 'habits')
}

export function useUpdateHabitPlanningEntry() {
    return usePlanningMutation(
        ({ id, payload }: { id: number; payload: HabitPlanningEntryPayload }) => api.updateHabitPlanningEntry(id, payload),
        'habits',
    )
}

export function useDeleteHabitPlanningEntry() {
    return usePlanningMutation((id: number) => api.deleteHabitPlanningEntry(id), 'habits')
}

export function useCreateTaskPlanningEntry() {
    return usePlanningMutation((payload: TaskPlanningEntryPayload) => api.createTaskPlanningEntry(payload), 'tasks')
}

export function useUpdateTaskPlanningEntry() {
    return usePlanningMutation(
        ({ id, payload }: { id: number; payload: TaskPlanningEntryPayload }) => api.updateTaskPlanningEntry(id, payload),
        'tasks',
    )
}

export function useDeleteTaskPlanningEntry() {
    return usePlanningMutation((id: number) => api.deleteTaskPlanningEntry(id), 'tasks')
}
