import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'
import type { TodoStatus } from '../../api/types'

const TODOS = ['todos'] as const

/** Spelt here rather than imported: archiving or deleting a TODO moves tasks and the planning. */
const TASKS = ['tasks'] as const
const PLANNING = ['planning'] as const

/** The account's TODOs, the default first, then by title. Every one when no status is named. */
export function useTodos(status: TodoStatus | null = null) {
    return useQuery({
        queryKey: [...TODOS, status],
        queryFn: () => api.fetchTodos(status),
    })
}

/** The active ones: what a task, a planning entry, the page's list may be put in. */
export function useActiveTodos() {
    return useTodos('active')
}

function useTodoMutation<TVariables, TResult>(mutationFn: (variables: TVariables) => Promise<TResult>) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        onSuccess: async () => {
            await Promise.all([
                queryClient.invalidateQueries({ queryKey: TODOS }),
                queryClient.invalidateQueries({ queryKey: TASKS }),
                queryClient.invalidateQueries({ queryKey: PLANNING }),
            ])
        },
    })
}

export function useCreateTodo() {
    return useTodoMutation((title: string) => api.createTodo(title))
}

export function useRenameTodo() {
    return useTodoMutation(({ id, title }: { id: number; title: string }) => api.updateTodo(id, title))
}

export function useArchiveTodo() {
    return useTodoMutation((id: number) => api.archiveTodo(id))
}

export function useReactivateTodo() {
    return useTodoMutation((id: number) => api.reactivateTodo(id))
}

export function useDeleteTodo() {
    return useTodoMutation((id: number) => api.deleteTodo(id))
}
