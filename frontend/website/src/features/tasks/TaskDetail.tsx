import { Check, Pencil, Plus, RotateCcw, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { ApiError } from '../../api/client'
import type { Task } from '../../api/types'
import { humanise } from '../../api/violations'
import {
    Alert,
    Chip,
    ConfirmDialog,
    DataList,
    DefinitionList,
    DotChip,
    IconButton,
    ListItem,
    Row,
    StateChip,
    Stack,
} from '../../components'
import { useCompleteTask, useDeleteTask, useReopenTask, useTask } from './queries'
import { formatDay, isOverdue, stateDisplay } from './taskDisplay'

interface TaskDetailProps {
    task: Task
    /** Turns this same window into the form, for the quest or for one of its sub-quests. */
    onEdit: (task: Task) => void
    onAddSubtask: () => void
    /** Called once the quest itself is gone, so whoever opened this window can close it. */
    onDeleted: () => void
}

/**
 * A quest, read rather than edited. It is where the actions that used to crowd every row now
 * live: the list keeps only what is done constantly — ticking off, and opening this.
 */
export function TaskDetail({ task: opened, onEdit, onAddSubtask, onDeleted }: TaskDetailProps) {
    // The row that opened this window is a snapshot; anything done from here — ticking a
    // sub-quest off, deleting one — has to show at once. So the window re-reads the quest, and
    // starts from the snapshot so nothing flickers.
    const { data: task } = useTask(opened.id, opened)

    const complete = useCompleteTask()
    const reopen = useReopenTask()
    const remove = useDeleteTask()
    /** The quest a confirmation is pending on: this one, or one of its sub-quests. */
    const [confirming, setConfirming] = useState<Task | null>(null)

    const state = stateDisplay(task.state)
    const subtaskCount = task.subtasks.length
    const doneSubtaskCount = task.subtasks.filter((subtask) => subtask.state === 'done').length
    const busy = complete.isPending || reopen.isPending || remove.isPending

    const failure = [complete.error, reopen.error, remove.error].find((one) => one !== null)
    const error =
        failure instanceof ApiError
            ? humanise(failure.violationsFor('id')[0] ?? failure.code ?? 'action_failed')
            : null

    return (
        <Stack>
            {/* The same order and the same grid as the form, so a field is where it was when
                it was filled in. The state joins the title line: it is what a reader looks at
                first, and the chip already says the word. */}
            <DefinitionList
                items={[
                    {
                        label: 'Intitulé',
                        placeholder: '—',
                        width: 'three-quarters',
                        value: task.title,
                    },
                    {
                        label: 'État',
                        placeholder: '—',
                        width: 'quarter',
                        hideLabel: true,
                        value: <StateChip colour={state.colour}>{state.label}</StateChip>,
                    },
                    { label: 'Détail', placeholder: 'Aucune description', value: task.description },
                    {
                        label: 'Catégorie',
                        placeholder: 'Aucune catégorie',
                        width: 'half',
                        value: task.category === null ? null : <Chip>{task.category.label}</Chip>,
                    },
                    {
                        label: 'Priorité',
                        placeholder: 'Aucune priorité',
                        width: 'half',
                        value:
                            task.priority === null ? null : (
                                <DotChip colour={task.priority.colour}>{task.priority.label}</DotChip>
                            ),
                    },
                    {
                        label: 'Étiquettes',
                        placeholder: 'Aucune étiquette',
                        width: 'half',
                        value:
                            task.tags.length === 0 ? null : (
                                <Row wrap>
                                    {task.tags.map((tag) => (
                                        <Chip key={tag}>#{tag}</Chip>
                                    ))}
                                </Row>
                            ),
                    },
                    {
                        label: 'Échéance',
                        placeholder: 'Aucune échéance',
                        width: 'half',
                        value:
                            task.dueDate === null ? null : (
                                <Chip tone={isOverdue(task) ? 'danger' : 'default'}>
                                    {isOverdue(task) ? 'En retard · ' : ''}
                                    {formatDay(task.dueDate)}
                                </Chip>
                            ),
                    },
                ]}
            />

            {error !== null && <Alert>{error}</Alert>}

            <Row style={{ justifyContent: 'center' }}>
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

                <IconButton icon={Pencil} label="Modifier" subject={task.title} onClick={() => onEdit(task)} />

                {task.parentId === null && (
                    <IconButton
                        icon={Plus}
                        label="Ajouter une sous-quête"
                        subject={task.title}
                        onClick={onAddSubtask}
                    />
                )}

                <IconButton
                    icon={Trash2}
                    variant="danger"
                    label="Supprimer"
                    subject={task.title}
                    disabled={busy}
                    onClick={() => setConfirming(task)}
                />
            </Row>

            {subtaskCount > 0 && (
                <DataList<Task>
                    groups={[
                        { label: `Sous-quêtes (${doneSubtaskCount}/${subtaskCount})`, items: task.subtasks },
                    ]}
                    keyOf={(subtask) => subtask.id}
                    emptyText="Aucune sous-quête."
                    renderItem={(subtask) => (
                        <SubtaskRow
                            subtask={subtask}
                            onEdit={() => onEdit(subtask)}
                            onDelete={() => setConfirming(subtask)}
                        />
                    )}
                />
            )}

            {confirming !== null && (
                <ConfirmDialog
                    title="Supprimer la quête"
                    question={questionFor(confirming)}
                    confirmLabel="Supprimer"
                    onCancel={() => setConfirming(null)}
                    onConfirm={() => {
                        const target = confirming
                        setConfirming(null)
                        remove.mutate(target.id, {
                            // Deleting the quest this window is about leaves nothing to show.
                            onSuccess: target.id === task.id ? onDeleted : undefined,
                        })
                    }}
                />
            )}
        </Stack>
    )
}

interface SubtaskRowProps {
    subtask: Task
    onEdit: () => void
    onDelete: () => void
}

/** A sub-quest carries the same actions as its parent, minus the one that would nest further. */
function SubtaskRow({ subtask, onEdit, onDelete }: SubtaskRowProps) {
    const complete = useCompleteTask()
    const reopen = useReopenTask()

    const state = stateDisplay(subtask.state)
    const busy = complete.isPending || reopen.isPending

    return (
        <ListItem
            title={subtask.title}
            muted={subtask.state === 'done'}
            accent={subtask.priority?.colour}
            meta={<StateChip colour={state.colour}>{state.label}</StateChip>}
            actions={
                <>
                    {subtask.state === 'done' ? (
                        <IconButton
                            icon={RotateCcw}
                            label="Rouvrir"
                            subject={subtask.title}
                            disabled={busy}
                            onClick={() => reopen.mutate(subtask.id)}
                        />
                    ) : (
                        <IconButton
                            icon={Check}
                            label="Terminer"
                            subject={subtask.title}
                            disabled={busy}
                            onClick={() => complete.mutate(subtask.id)}
                        />
                    )}

                    <IconButton icon={Pencil} label="Modifier" subject={subtask.title} onClick={onEdit} />

                    <IconButton
                        icon={Trash2}
                        variant="danger"
                        label="Supprimer"
                        subject={subtask.title}
                        onClick={onDelete}
                    />
                </>
            }
        />
    )
}

function questionFor(task: Task): string {
    if (task.subtasks.length > 0) {
        return `« ${task.title} » et ses ${task.subtasks.length} sous-quêtes seront supprimées. C’est définitif.`
    }

    return `« ${task.title} » sera supprimée. C’est définitif.`
}
