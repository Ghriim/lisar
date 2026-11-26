import { ApiError } from '../../api/client'
import type { Task } from '../../api/types'
import { humanise } from '../../api/violations'
import { CheckToggle, Chip, TextButton } from '../../components'
import { formatDayShort, formatDayWithWeekday, isOverdue } from './taskDisplay'
import { formatExperience, taskExperience } from './taskExperience'
import { useCompleteTask, useReopenTask } from './queries'

interface TaskItemProps {
    task: Task
    /** Opens the task in the window that holds everything else one can do to it. */
    onView: (task: Task) => void
    /** Drawn as a line of « Aujourd'hui »: lit, and wearing its category's name. */
    inToday?: boolean
}

/**
 * A task as a line: the box that ticks it off, the title that opens it, a line of chips, and what
 * it earns. Two actions and no more — everything else is in the task's window.
 */
export function TaskItem({ task, onView, inToday = false }: TaskItemProps) {
    const complete = useCompleteTask()
    const reopen = useReopenTask()

    const done = task.state === 'done'
    const overdue = isOverdue(task)
    const busy = complete.isPending || reopen.isPending

    const subtaskCount = task.subtasks.length
    const doneSubtaskCount = task.subtasks.filter((subtask) => subtask.state === 'done').length

    // Refusing to close a task whose subtask is still open is the one error this line can raise,
    // and it belongs next to the line that raised it.
    const failure = [complete.error, reopen.error].find((one) => one !== null)
    const error =
        failure instanceof ApiError
            ? humanise(failure.violationsFor('id')[0] ?? failure.code ?? 'action_failed')
            : null

    const classes = ['task-row']
    if (overdue) {
        classes.push('task-row-late')
    } else if (inToday) {
        classes.push('task-row-today')
    }

    const showCategory = inToday && task.category !== null
    const showHigh = task.priority === 'high'
    const showDue = task.dueDate !== null && !overdue && !inToday

    return (
        <div className={classes.join(' ')}>
            <CheckToggle
                checked={done}
                label={done ? 'Rouvrir' : 'Terminer'}
                subject={task.title}
                danger={overdue}
                disabled={busy}
                onToggle={() => (done ? reopen.mutate(task.id) : complete.mutate(task.id))}
            />

            <div className="task-row-body">
                <div className="task-row-headline">
                    <TextButton muted={done} ariaLabel={`Consulter « ${task.title} »`} onClick={() => onView(task)}>
                        {task.title}
                    </TextButton>
                    {subtaskCount > 0 && (
                        <span className="task-row-subtasks">
                            {doneSubtaskCount} / {subtaskCount} sous-tâches
                        </span>
                    )}
                </div>

                {(showCategory || showHigh || overdue || showDue) && (
                    <div className="task-row-chips">
                        {showCategory && <Chip tone="filled">{task.category?.label}</Chip>}
                        {showHigh && <Chip tone="xp">Priorité haute</Chip>}
                        {overdue && task.dueDate !== null && <Chip tone="danger">En retard · {formatDayShort(task.dueDate)}</Chip>}
                        {showDue && task.dueDate !== null && <Chip>Échéance {formatDayWithWeekday(task.dueDate)}</Chip>}
                    </div>
                )}

                {error !== null && <span className="field-error">{error}</span>}
            </div>

            <span className="task-row-xp">{formatExperience(taskExperience(task))}</span>
        </div>
    )
}
