import { useState } from 'react'
import { TASK_PRIORITIES, type Task, type TaskPriority } from '../../api/types'
import {
    Button,
    DateField,
    Form,
    FormActions,
    FormGrid,
    SelectField,
    TextAreaField,
    TextField,
    useViolations,
} from '../../components'
import { useCategories } from '../categories/queries'
import { useActiveTodos } from '../todos/queries'
import { useCreateTask, useUpdateTask } from './queries'
import { priorityDisplay } from './taskDisplay'

interface TaskComposerProps {
    /** Set when writing a subtask: the task it hangs from. */
    parent: Task | null
    /** Set when editing rather than creating. */
    editing: Task | null
    /** The TODO a new task starts in: the one the list shows, or « A faire » on the dashboard. */
    defaultTodoId: number | null
    onDone: () => void
}

interface FormState {
    title: string
    description: string
    dueDate: string
    priority: TaskPriority | ''
    categoryId: string
    todoId: string
}

/**
 * What a new task starts as: no category, no rank, and the TODO it is created from — the one
 * exception to « nothing is chosen for the person »: a task always sits in a TODO, and the one on
 * screen is the one meant. The priority's default is the server's — Normale, once the form is
 * submitted without one.
 */
function empty(todoId: number | null): FormState {
    return {
        title: '',
        description: '',
        dueDate: '',
        priority: '',
        categoryId: '',
        todoId: todoId === null ? '' : String(todoId),
    }
}

function fromTask(task: Task): FormState {
    return {
        todoId: String(task.todoId),
        title: task.title,
        description: task.description ?? '',
        dueDate: task.dueDate ?? '',
        priority: task.priority ?? '',
        categoryId: task.category === null ? '' : String(task.category.id),
    }
}

/**
 * Mounted by the modal that holds it, and unmounted when that window closes — so the state below
 * starts from the right task without an effect syncing props into state.
 */
export function TaskComposer({ parent, editing, defaultTodoId, onDone }: TaskComposerProps) {
    const [form, setForm] = useState<FormState>(() => (editing === null ? empty(defaultTodoId) : fromTask(editing)))
    const categories = useCategories()
    const todos = useActiveTodos()
    // A subtask sits in its parent's TODO: nothing to choose.
    const isSubtask = parent !== null || (editing !== null && editing.parentId !== null)
    const create = useCreateTask()
    const update = useUpdateTask()

    const pending = create.isPending || update.isPending
    const violations = useViolations(editing === null ? create.error : update.error)

    const set = (field: keyof FormState) => (value: string) =>
        setForm((current) => ({ ...current, [field]: value }))

    const submit = async () => {
        const payload = {
            title: form.title,
            description: form.description === '' ? null : form.description,
            dueDate: form.dueDate === '' ? null : form.dueDate,
            priority: form.priority === '' ? null : form.priority,
            categoryId: form.categoryId === '' ? null : Number(form.categoryId),
            todoId: isSubtask || form.todoId === '' ? null : Number(form.todoId),
        }

        try {
            if (editing !== null) {
                await update.mutateAsync({ id: editing.id, payload })
            } else {
                await create.mutateAsync({ ...payload, parentId: parent?.id ?? null })
            }

            onDone()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    return (
        <Form onSubmit={submit}>
            <TextField
                label="Intitulé"
                isMandatory
                errors={violations.for('title')}
                value={form.title}
                onChange={set('title')}
                placeholder="Ce qu'il y a à faire"
                autoFocus
            />

            <TextAreaField label="Détail" errors={violations.for('description')} value={form.description} onChange={set('description')} />

            {!isSubtask && (
                <SelectField
                    label="TODO"
                    isMandatory
                    errors={violations.for('todoId')}
                    value={form.todoId}
                    onChange={set('todoId')}
                    placeholder="Choisir une TODO"
                    // « A faire » first, then by title: the API's order.
                    options={(todos.data ?? []).map((todo) => ({ value: String(todo.id), label: todo.title }))}
                />
            )}

            <FormGrid columns={2}>
                <SelectField
                    label="Catégorie"
                    errors={violations.for('categoryId')}
                    value={form.categoryId}
                    onChange={set('categoryId')}
                    placeholder="Aucune"
                    options={(categories.data ?? []).map((category) => ({
                        value: String(category.id),
                        label: category.isPersonal ? `${category.label} ·` : category.label,
                    }))}
                />

                <SelectField
                    label="Priorité"
                    errors={violations.for('priority')}
                    value={form.priority}
                    onChange={set('priority')}
                    placeholder="Par défaut"
                    options={TASK_PRIORITIES.map((priority) => ({
                        value: priority,
                        label: priorityDisplay(priority).label,
                    }))}
                />
            </FormGrid>

            <FormGrid columns={2}>
                <DateField label="Échéance" errors={violations.for('dueDate')} value={form.dueDate} onChange={set('dueDate')} />
            </FormGrid>

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>

                <Button variant="primary" submit disabled={pending}>
                    {editing !== null ? 'Enregistrer' : 'Créer'}
                </Button>
            </FormActions>
        </Form>
    )
}
