import { RotateCcw } from 'lucide-react'
import { useParams } from 'react-router-dom'
import type { Todo } from '../api/types'
import { Alert, Button, EmptyState, Loader, SectionHeading, SystemPanel } from '../components'
import { TaskList } from '../features/tasks/TaskList'
import { TodoListPanel } from '../features/todos/TodoListPanel'
import { useReactivateTodo, useTodos } from '../features/todos/queries'
import { failureOf } from '../features/workouts/workoutFormat'

/**
 * One TODO at a time — « A faire » at `/todos`, any other at `/todos/:id` — and the account's
 * TODOs listed beside it, to go from one to another and to act on the TODOs themselves.
 */
export function TodosPage() {
    const { id } = useParams()
    const todos = useTodos()
    const current = todos.data?.find((todo) => (id === undefined ? todo.isDefault : `${todo.id}` === id))

    return (
        <div className="todos-layout">
            <div className="todos-main">
                {todos.isPending && <Loader />}
                {todos.isError && <Alert>Le System ne répond pas. Réessaie dans un instant.</Alert>}
                {todos.isSuccess && current === undefined && <Alert>Cette TODO n’existe pas.</Alert>}
                {current !== undefined && <TodoView key={current.id} todo={current} />}
            </div>

            <aside className="todos-side">
                <TodoListPanel currentId={current?.id ?? null} />
            </aside>
        </div>
    )
}

/** A TODO's tasks — or, archived, the word that they show nowhere, and the way back. */
function TodoView({ todo }: { todo: Todo }) {
    const reactivate = useReactivateTodo()

    if (todo.status === 'archived') {
        const error = failureOf(reactivate.error)

        return (
            <section className="board-section" aria-labelledby="archived-todo-title">
                <SectionHeading id="archived-todo-title" title={todo.title} />
                <SystemPanel>
                    <EmptyState>Cette TODO est archivée : ses tâches ne s’affichent plus.</EmptyState>
                    {error !== null && <Alert>{error}</Alert>}
                    <Button variant="secondary" icon={RotateCcw} disabled={reactivate.isPending} onClick={() => reactivate.mutate(todo.id)}>
                        Réactiver
                    </Button>
                </SystemPanel>
            </section>
        )
    }

    return <TaskList source={{ kind: 'todo', todo }} title={todo.title} />
}
