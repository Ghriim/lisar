import { useState, type FormEvent } from 'react'
import { TASK_PRIORITIES, type Task, type TaskPriority } from '../../api/types'
import {
    Button,
    Field,
    FormActions,
    FormGrid,
    Select,
    TextArea,
    TextInput,
    useViolations,
} from '../../components'
import { useCategories, useCreateTask, useUpdateTask } from './queries'
import { priorityDisplay } from './taskDisplay'

interface TaskComposerProps {
    /** Set when writing a subtask: the quest it hangs from. */
    parent: Task | null
    /** Set when editing rather than creating. */
    editing: Task | null
    onDone: () => void
}

interface FormState {
    title: string
    description: string
    dueDate: string
    priority: TaskPriority | ''
    categoryId: string
}

/**
 * What a new quest starts as: no category, no rank. The only default anywhere is the
 * priority, and it is the server that applies it — Normale, once the form is submitted without
 * one.
 */
const EMPTY: FormState = {
    title: '',
    description: '',
    dueDate: '',
    priority: '',
    categoryId: '',
}

function fromTask(task: Task): FormState {
    return {
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
export function TaskComposer({ parent, editing, onDone }: TaskComposerProps) {
    const [form, setForm] = useState<FormState>(() => (editing === null ? EMPTY : fromTask(editing)))
    const categories = useCategories()
    const create = useCreateTask()
    const update = useUpdateTask()

    const pending = create.isPending || update.isPending
    const violations = useViolations(editing === null ? create.error : update.error)

    const set = (field: keyof FormState) => (value: string) =>
        setForm((current) => ({ ...current, [field]: value }))

    const submit = async (event: FormEvent) => {
        event.preventDefault()

        const payload = {
            title: form.title,
            description: form.description === '' ? null : form.description,
            dueDate: form.dueDate === '' ? null : form.dueDate,
            priority: form.priority === '' ? null : form.priority,
            categoryId: form.categoryId === '' ? null : Number(form.categoryId),
        }

        try {
            if (editing !== null) {
                await update.mutateAsync({ id: editing.id, payload })
            } else {
                await create.mutateAsync({ ...payload, parentId: parent?.id ?? null })
            }

            setForm(EMPTY)
            onDone()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    return (
        <form className="form-grid" onSubmit={(event) => void submit(event)}>
            <Field label="Intitulé" errors={violations.for('title')}>
                <TextInput
                    value={form.title}
                    onChange={(event) => set('title')(event.target.value)}
                    placeholder="Ce qu'il y a à faire"
                    required
                    autoFocus
                />
            </Field>

            <Field label="Détail" errors={violations.for('description')}>
                <TextArea
                    value={form.description}
                    onChange={(event) => set('description')(event.target.value)}
                    placeholder="Facultatif"
                />
            </Field>

            <FormGrid columns={2}>
                <Field label="Catégorie" errors={violations.for('categoryId')}>
                    <Select
                        value={form.categoryId}
                        onChange={set('categoryId')}
                        placeholder="Aucune"
                        options={(categories.data ?? []).map((category) => ({
                            value: String(category.id),
                            label: category.isPersonal ? `${category.label} ·` : category.label,
                        }))}
                    />
                </Field>

                <Field label="Priorité" errors={violations.for('priority')}>
                    <Select
                        value={form.priority}
                        onChange={set('priority')}
                        placeholder="Par défaut"
                        options={TASK_PRIORITIES.map((priority) => ({
                            value: priority,
                            label: priorityDisplay(priority).label,
                        }))}
                    />
                </Field>
            </FormGrid>

            <FormGrid columns={2}>
                <Field label="Échéance" errors={violations.for('dueDate')}>
                    <TextInput
                        type="date"
                        value={form.dueDate}
                        onChange={(event) => set('dueDate')(event.target.value)}
                    />
                </Field>
            </FormGrid>

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>

                <Button variant="primary" submit disabled={pending}>
                    {editing !== null ? 'Enregistrer' : 'Créer'}
                </Button>
            </FormActions>
        </form>
    )
}
