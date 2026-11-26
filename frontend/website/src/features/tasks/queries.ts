import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { CreateTaskPayload, Task, UpdateTaskPayload } from '../../api/types'

/** Every task list in the app answers to this key, so one mutation refreshes them all. */
const TASKS = ['tasks'] as const

/** The planning's key, spelt here rather than imported: a task made by a planning entry is a date of it. */
const PLANNING = ['planning'] as const

/** The status' key, likewise: ticking a task off is what will earn experience. */
const STATUS = ['status'] as const

export function useTasks(isDone: boolean) {
    return useQuery({
        queryKey: [...TASKS, isDone],
        queryFn: () => api.fetchTasks(isDone),
    })
}

/**
 * One task, kept fresh while its window is open. The key sits under the same prefix as the
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

function useTaskMutation<TVariables, TResult>(mutationFn: (variables: TVariables) => Promise<TResult>) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        onSuccess: async () => {
            // Both lists move together: ticking a task off takes it out of one and into the
            // other. A planned task's date follows it on the planning, and the status bar what
            // it earned.
            await Promise.all([
                queryClient.invalidateQueries({ queryKey: TASKS }),
                queryClient.invalidateQueries({ queryKey: PLANNING }),
                queryClient.invalidateQueries({ queryKey: STATUS }),
            ])
        },
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
