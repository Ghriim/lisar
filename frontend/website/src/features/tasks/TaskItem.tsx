import { ApiError } from '../../api/client'
import type { Task } from '../../api/types'
import { humanise } from '../../api/violations'
import { useCompleteTask, useDeleteTask, useReopenTask } from './queries'
import { formatDay, isOverdue, stateDisplay } from './taskDisplay'

interface TaskItemProps {
    task: Task
    onAddSubtask?: (parent: Task) => void
    onEdit?: (task: Task) => void
}

export function TaskItem({ task, onAddSubtask, onEdit }: TaskItemProps) {
    const complete = useCompleteTask()
    const reopen = useReopenTask()
    const remove = useDeleteTask()

    const state = stateDisplay(task.state)
    const overdue = isOverdue(task)
    const busy = complete.isPending || reopen.isPending || remove.isPending

    // Refusing to close a task whose subtask is still open is the one error this row can raise,
    // and it belongs next to the row that raised it.
    const failure = [complete.error, reopen.error, remove.error].find((one) => one !== null)
    const error =
        failure instanceof ApiError
            ? humanise(failure.violationsFor('id')[0] ?? failure.code ?? 'action_failed')
            : null

    return (
        <>
            <article
                className={task.state === 'done' ? 'task task-done' : 'task'}
                style={{ '--task-accent': task.priority?.colour } as React.CSSProperties}
            >
                <div className="task-body">
                    <div className="task-title">{task.title}</div>

                    {task.description !== null && task.description !== '' && (
                        <p className="task-description">{task.description}</p>
                    )}

                    <div className="task-meta">
                        <span className="chip chip-state" style={{ '--state-colour': state.colour } as React.CSSProperties}>
                            {state.label}
                        </span>

                        {task.priority !== null && (
                            <span className="chip" style={{ color: task.priority.colour }}>
                                <i className="chip-dot" />
                                {task.priority.label}
                            </span>
                        )}

                        {task.dueDate !== null && (
                            <span className={overdue ? 'chip chip-overdue' : 'chip'}>
                                {overdue ? 'En retard · ' : ''}
                                {formatDay(task.dueDate)}
                            </span>
                        )}

                        {task.tags.map((tag) => (
                            <span key={tag} className="chip">
                                #{tag}
                            </span>
                        ))}
                    </div>

                    {error !== null && <p className="field-error">{error}</p>}
                </div>

                <div className="task-actions">
                    {task.state === 'done' ? (
                        <button
                            type="button"
                            className="icon-button"
                            title="Rouvrir"
                            aria-label={`Rouvrir « ${task.title} »`}
                            disabled={busy}
                            onClick={() => reopen.mutate(task.id)}
                        >
                            ↺
                        </button>
                    ) : (
                        <button
                            type="button"
                            className="icon-button"
                            title="Terminer"
                            aria-label={`Terminer « ${task.title} »`}
                            disabled={busy}
                            onClick={() => complete.mutate(task.id)}
                        >
                            ✓
                        </button>
                    )}

                    {onEdit !== undefined && (
                        <button
                            type="button"
                            className="icon-button"
                            title="Modifier"
                            aria-label={`Modifier « ${task.title} »`}
                            disabled={busy}
                            onClick={() => onEdit(task)}
                        >
                            ✎
                        </button>
                    )}

                    {onAddSubtask !== undefined && task.parentId === null && (
                        <button
                            type="button"
                            className="icon-button"
                            title="Ajouter une sous-quête"
                            aria-label={`Ajouter une sous-quête à « ${task.title} »`}
                            disabled={busy}
                            onClick={() => onAddSubtask(task)}
                        >
                            +
                        </button>
                    )}

                    <button
                        type="button"
                        className="icon-button icon-button-danger"
                        title="Supprimer"
                        aria-label={`Supprimer « ${task.title} »`}
                        disabled={busy}
                        onClick={() => remove.mutate(task.id)}
                    >
                        ✕
                    </button>
                </div>
            </article>

            {task.subtasks.length > 0 && (
                <div className="subtasks">
                    {task.subtasks.map((subtask) => (
                        <TaskItem key={subtask.id} task={subtask} onEdit={onEdit} />
                    ))}
                </div>
            )}
        </>
    )
}
