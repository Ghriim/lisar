import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { CreateTaskPayload, UpdateTaskPayload } from '../../api/types'

/** Every task list in the app answers to this key, so one mutation refreshes them all. */
const TASKS = ['tasks'] as const

export function useTasks(isDone: boolean) {
    return useQuery({
        queryKey: [...TASKS, isDone],
        queryFn: () => api.fetchTasks(isDone),
    })
}

export function usePriorities() {
    return useQuery({
        queryKey: ['priorities'],
        queryFn: api.fetchPriorities,
        // Reference data: it changes in the back-office, not while someone writes a task.
        staleTime: 5 * 60 * 1000,
    })
}

export function useCategories() {
    return useQuery({
        queryKey: ['categories'],
        queryFn: api.fetchCategories,
        staleTime: 5 * 60 * 1000,
    })
}

function useTaskMutation<TVariables, TResult>(mutationFn: (variables: TVariables) => Promise<TResult>) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        // Both lists move together: ticking a task off takes it out of one and into the other.
        onSuccess: () => queryClient.invalidateQueries({ queryKey: TASKS }),
    })
}

export function useCreateTask() {
    return useTaskMutation((payload: CreateTaskPayload) => api.createTask(payload))
}

export function useUpdateTask() {
    return useTaskMutation(({ id, payload }: { id: number; payload: UpdateTaskPayload }) =>
        api.updateTask(id, payload),
    )
}

export function useCompleteTask() {
    return useTaskMutation((id: number) => api.completeTask(id))
}

export function useReopenTask() {
    return useTaskMutation((id: number) => api.reopenTask(id))
}

export function useDeleteTask() {
    return useTaskMutation((id: number) => api.deleteTask(id))
}
