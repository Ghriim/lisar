import { Check, Eye, RotateCcw } from 'lucide-react'
import { ApiError } from '../../api/client'
import type { Task } from '../../api/types'
import { humanise } from '../../api/violations'
import { Chip, DotChip, IconButton, ListItem, StateChip } from '../../components'
import { useCompleteTask, useReopenTask } from './queries'
import { formatDay, isOverdue, stateDisplay } from './taskDisplay'

interface TaskItemProps {
    task: Task
    /** Opens the quest in the window that holds everything else one can do to it. */
    onView: (task: Task) => void
}

/** What a task looks like as a row: which chips, which actions, what the count counts. */
export function TaskItem({ task, onView }: TaskItemProps) {
    const complete = useCompleteTask()
    const reopen = useReopenTask()

    const state = stateDisplay(task.state)
    const overdue = isOverdue(task)
    const busy = complete.isPending || reopen.isPending

    const subtaskCount = task.subtasks.length
    const doneSubtaskCount = task.subtasks.filter((subtask) => subtask.state === 'done').length

    // Refusing to close a task whose subtask is still open is the one error this row can raise,
    // and it belongs next to the row that raised it.
    const failure = [complete.error, reopen.error].find((one) => one !== null)
    const error =
        failure instanceof ApiError
            ? humanise(failure.violationsFor('id')[0] ?? failure.code ?? 'action_failed')
            : null

    return (
        <>
            <ListItem
                title={task.title}
                note={subtaskCount > 0 ? `(${doneSubtaskCount}/${subtaskCount})` : undefined}
                description={task.description}
                accent={task.priority?.colour}
                muted={task.state === 'done'}
                error={error}
                meta={
                    <>
                        <StateChip colour={state.colour}>{state.label}</StateChip>

                        {task.priority !== null && (
                            <DotChip colour={task.priority.colour}>{task.priority.label}</DotChip>
                        )}

                        {task.dueDate !== null && (
                            <Chip tone={overdue ? 'danger' : 'default'}>
                                {overdue ? 'En retard · ' : ''}
                                {formatDay(task.dueDate)}
                            </Chip>
                        )}

                        {task.tags.map((tag) => (
                            <Chip key={tag}>#{tag}</Chip>
                        ))}
                    </>
                }
                actions={
                    <>
                        {task.state === 'done' ? (
                            <IconButton
                                icon={RotateCcw}
                                label="Rouvrir"
                                subject={task.title}
                                disabled={busy}
                                onClick={() => reopen.mutate(task.id)}
                            />
                        ) : (
                            <IconButton
                                icon={Check}
                                label="Terminer"
                                subject={task.title}
                                disabled={busy}
                                onClick={() => complete.mutate(task.id)}
                            />
                        )}

                        <IconButton
                            icon={Eye}
                            label="Consulter"
                            subject={task.title}
                            onClick={() => onView(task)}
                        />
                    </>
                }
            >
                {subtaskCount > 0
                    ? task.subtasks.map((subtask) => (
                          <TaskItem key={subtask.id} task={subtask} onView={onView} />
                      ))
                    : undefined}
            </ListItem>

        </>
    )
}
