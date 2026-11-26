import { Archive, Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import type { Todo, TodoStatus } from '../../api/types'
import { Alert, ConfirmDialog, EmptyState, IconButton, Loader, Modal, SectionHeading, SystemPanel, Tabs } from '../../components'
import { failureOf } from '../workouts/workoutFormat'
import { useArchiveTodo, useCreateTodo, useDeleteTodo, useReactivateTodo, useRenameTodo, useTodos } from './queries'
import { TodoTitleForm } from './TodoTitleForm'

interface TodoListPanelProps {
    /** The TODO the page shows, marked in the list. */
    currentId: number | null
}

type Panel = { kind: 'create' } | { kind: 'rename' | 'archive' | 'delete'; todo: Todo } | null

/**
 * The account's TODOs, beside the one shown, and everything done to a TODO itself. The active
 * ones — « A faire » first, then by title — each open by their title and carry their own actions:
 * renamed always, archived or deleted unless it is the default. Behind the filter, the archived
 * ones, each to make active again. A new TODO is created from the heading.
 */
export function TodoListPanel({ currentId }: TodoListPanelProps) {
    const [status, setStatus] = useState<TodoStatus>('active')
    const [panel, setPanel] = useState<Panel>(null)
    const todos = useTodos(status)
    const create = useCreateTodo()
    const rename = useRenameTodo()
    const archive = useArchiveTodo()
    const reactivate = useReactivateTodo()
    const remove = useDeleteTodo()
    const navigate = useNavigate()
    const close = () => setPanel(null)
    const busy = archive.isPending || remove.isPending || reactivate.isPending
    const error = failureOf(archive.error ?? remove.error ?? reactivate.error)

    // The TODO shown goes away: the page falls back on « A faire ».
    const leave = (todo: Todo) => {
        if (todo.id === currentId) {
            void navigate('/todos')
        }
    }

    return (
        <section className="board-section" aria-labelledby="todo-list-title">
            <SectionHeading
                id="todo-list-title"
                title="TODOs"
                actions={<IconButton icon={Plus} label="Créer une TODO" variant="primary" large onClick={() => setPanel({ kind: 'create' })} />}
            />

            <SystemPanel className="todo-list-panel">
                <Tabs<TodoStatus>
                    current={status}
                    onChange={setStatus}
                    tabs={[
                        { value: 'active', label: 'Actives' },
                        { value: 'archived', label: 'Archivées' },
                    ]}
                />

                {todos.isPending && <Loader />}
                {todos.isError && <Alert>Le System ne répond pas. Réessaie dans un instant.</Alert>}
                {error !== null && <Alert>{error}</Alert>}
                {todos.isSuccess && todos.data.length === 0 && (
                    <EmptyState>{status === 'active' ? 'Aucune TODO.' : 'Aucune TODO archivée.'}</EmptyState>
                )}

                {todos.isSuccess && todos.data.length > 0 && (
                    <ul className="todo-list">
                        {todos.data.map((todo) =>
                            status === 'active' ? (
                                <li key={todo.id} className={todo.id === currentId ? 'todo-row todo-row-current' : 'todo-row'}>
                                    <Link
                                        to={todo.isDefault ? '/todos' : `/todos/${todo.id}`}
                                        className="todo-link"
                                        aria-current={todo.id === currentId ? 'page' : undefined}
                                    >
                                        {todo.title}
                                    </Link>
                                    <IconButton icon={Pencil} label="Renommer" subject={todo.title} small onClick={() => setPanel({ kind: 'rename', todo })} />
                                    {!todo.isDefault && (
                                        <>
                                            <IconButton icon={Archive} label="Archiver" subject={todo.title} small disabled={busy} onClick={() => setPanel({ kind: 'archive', todo })} />
                                            <IconButton icon={Trash2} variant="danger" label="Supprimer" subject={todo.title} small disabled={busy} onClick={() => setPanel({ kind: 'delete', todo })} />
                                        </>
                                    )}
                                </li>
                            ) : (
                                <li key={todo.id} className="todo-row">
                                    <span className="todo-archived-title">{todo.title}</span>
                                    <IconButton icon={RotateCcw} label="Réactiver" subject={todo.title} small disabled={busy} onClick={() => reactivate.mutate(todo.id)} />
                                </li>
                            ),
                        )}
                    </ul>
                )}
            </SystemPanel>

            {panel?.kind === 'create' && (
                <Modal title="Nouvelle TODO" onClose={close}>
                    <TodoTitleForm submitLabel="Créer" save={(title) => create.mutateAsync(title)} pending={create.isPending} failure={create.error} onDone={close} />
                </Modal>
            )}

            {panel?.kind === 'rename' && (
                <Modal title="Renommer la TODO" onClose={close}>
                    <TodoTitleForm
                        initial={panel.todo.title}
                        submitLabel="Renommer"
                        save={(title) => rename.mutateAsync({ id: panel.todo.id, title })}
                        pending={rename.isPending}
                        failure={rename.error}
                        onDone={close}
                    />
                </Modal>
            )}

            {panel?.kind === 'archive' && (
                <ConfirmDialog
                    title="Archiver la TODO"
                    question={`« ${panel.todo.title} » sera archivée : ses tâches ne s’afficheront plus nulle part, et sa planification sera supprimée — les entrées du planning qui créent ses tâches. Tu pourras la réactiver, mais pas retrouver sa planification.`}
                    confirmLabel="Archiver"
                    onCancel={close}
                    onConfirm={() => {
                        const { todo } = panel
                        close()
                        archive.mutate(todo.id, { onSuccess: () => leave(todo) })
                    }}
                />
            )}

            {panel?.kind === 'delete' && (
                <ConfirmDialog
                    title="Supprimer la TODO"
                    question={`« ${panel.todo.title} » sera supprimée, avec toutes ses tâches et leurs sous-tâches, terminées ou non, et sa planification. C’est définitif.`}
                    confirmLabel="Supprimer"
                    onCancel={close}
                    onConfirm={() => {
                        const { todo } = panel
                        close()
                        remove.mutate(todo.id, { onSuccess: () => leave(todo) })
                    }}
                />
            )}
        </section>
    )
}
