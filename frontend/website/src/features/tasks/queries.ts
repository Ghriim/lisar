import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { CreateTaskPayload, Task, UpdateTaskPayload } from '../../api/types'

/** Every task list in the app answers to this key, so one mutation refreshes them all. */
const TASKS = ['tasks'] as const

export function useTasks(isDone: boolean) {
    return useQuery({
        queryKey: [...TASKS, isDone],
        queryFn: () => api.fetchTasks(isDone),
    })
}

/**
 * One quest, kept fresh while its window is open. The key sits under the same prefix as the
 * lists, so every mutation refreshes it too — which is what lets the window show the effect of
 * an action taken inside it.
 */
export function useTask(id: number, fallback: Task) {
    return useQuery({
        queryKey: [...TASKS, 'one', id],
        queryFn: () => api.fetchTask(id),
        initialData: fallback,
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
        onSuccess: async () => {
            // Both lists move together: ticking a task off takes it out of one and into the
            // other. And writing a task can mint a tag the account did not have.
            await queryClient.invalidateQueries({ queryKey: TASKS })
            await queryClient.invalidateQueries({ queryKey: ['tags'] })
        },
    })
}

export function useTags() {
    return useQuery({
        queryKey: ['tags'],
        queryFn: api.fetchTags,
    })
}

/**
 * A category the person owns: renaming one changes how every task reads, so the task lists go
 * with it.
 */
function useCategoryMutation<TVariables, TResult>(
    mutationFn: (variables: TVariables) => Promise<TResult>,
) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        onSuccess: async () => {
            await queryClient.invalidateQueries({ queryKey: ['categories'] })
            await queryClient.invalidateQueries({ queryKey: TASKS })
        },
    })
}

export function useCreateCategory() {
    return useCategoryMutation((label: string) => api.createCategory(label))
}

export function useUpdateCategory() {
    return useCategoryMutation(({ id, label }: { id: number; label: string }) =>
        api.updateCategory(id, label),
    )
}

export function useDeleteCategory() {
    return useCategoryMutation((id: number) => api.deleteCategory(id))
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
