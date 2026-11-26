import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import * as api from '../../api/endpoints'

/** The categories the person can choose from: the common ones, then their own. */
const CATEGORIES = ['categories'] as const

/** The task lists' key, spelt here rather than imported: a task reads with its category's label. */
const TASKS = ['tasks'] as const

export function useCategories() {
    return useQuery({
        queryKey: CATEGORIES,
        queryFn: api.fetchCategories,
        staleTime: 5 * 60 * 1000,
    })
}

/**
 * A category the person owns: renaming one changes how every task in it reads, so the task lists
 * go with it. Habits sit in common categories only, which nothing here changes.
 */
function useCategoryMutation<TVariables, TResult>(mutationFn: (variables: TVariables) => Promise<TResult>) {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn,
        onSuccess: async () => {
            await queryClient.invalidateQueries({ queryKey: CATEGORIES })
            await queryClient.invalidateQueries({ queryKey: TASKS })
        },
    })
}

export function useCreateCategory() {
    return useCategoryMutation((label: string) => api.createCategory(label))
}

export function useUpdateCategory() {
    return useCategoryMutation(({ id, label }: { id: number; label: string }) => api.updateCategory(id, label))
}

export function useDeleteCategory() {
    return useCategoryMutation((id: number) => api.deleteCategory(id))
}
