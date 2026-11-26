import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { CreateTaskPayload, Task, UpdateTaskPayload } from '../../api/types'

/** Every task list in the app answers to this key, so one mutation refreshes them all. */
const TASKS = ['tasks'] as const

/** The planning's key, spelt here rather than imported: a task made by a planning entry is a date of it. */
const PLANNING = ['planning'] as const

/** The status' key, likewise: ticking a task off is what will earn experience. */
const STATUS = ['status'] as const

/** One TODO's tasks, open or done. Off until the TODO is known. */
export function useTasks(isDone: boolean, todoId: number | null, enabled = true) {
    return useQuery({
        queryKey: [...TASKS, 'todo', todoId, isDone],
        queryFn: () => api.fetchTasks(isDone, todoId),
        enabled,
    })
}

/** What the dashboard shows, across the TODOs: the API says which tasks those are. */
export function useTaskOverview() {
    return useQuery({
        queryKey: [...TASKS, 'overview'],
        queryFn: api.fetchTaskOverview,
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

export function useFavoriteTask() {
    return useTaskMutation((id: number) => api.favoriteTask(id))
}

export function useUnfavoriteTask() {
    return useTaskMutation((id: number) => api.unfavoriteTask(id))
}
