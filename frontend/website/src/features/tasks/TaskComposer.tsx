import { useState, type FormEvent } from 'react'
import type { Task } from '../../api/types'
import {
    Button,
    Field,
    FormActions,
    FormGrid,
    Select,
    TextArea,
    TextInput,
    ToggleChip,
    useViolations,
} from '../../components'
import { useCategories, useCreateTask, usePriorities, useTags, useUpdateTask } from './queries'

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
    priorityId: string
    categoryId: string
    tags: string
}

/**
 * What a new quest starts as: no category, no tag, no rank. The only default anywhere is the
 * priority, and it is the back-office that decides it — server side, once the form is submitted
 * without one.
 */
const EMPTY: FormState = {
    title: '',
    description: '',
    dueDate: '',
    priorityId: '',
    categoryId: '',
    tags: '',
}

function fromTask(task: Task): FormState {
    return {
        title: task.title,
        description: task.description ?? '',
        dueDate: task.dueDate ?? '',
        priorityId: task.priority === null ? '' : String(task.priority.id),
        categoryId: task.category === null ? '' : String(task.category.id),
        tags: task.tags.join(', '),
    }
}

/**
 * Mounted by the modal that holds it, and unmounted when that window closes — so the state below
 * starts from the right task without an effect syncing props into state.
 */
export function TaskComposer({ parent, editing, onDone }: TaskComposerProps) {
    const [form, setForm] = useState<FormState>(() => (editing === null ? EMPTY : fromTask(editing)))
    const priorities = usePriorities()
    const categories = useCategories()
    const tags = useTags()
    const create = useCreateTask()
    const update = useUpdateTask()

    const pending = create.isPending || update.isPending
    const violations = useViolations(editing === null ? create.error : update.error)

    const set = (field: keyof FormState) => (value: string) =>
        setForm((current) => ({ ...current, [field]: value }))

    const chosenTags = form.tags
        .split(',')
        .map((tag) => tag.trim())
        .filter((tag) => tag !== '')

    const toggleTag = (label: string) => {
        const next = chosenTags.includes(label)
            ? chosenTags.filter((tag) => tag !== label)
            : [...chosenTags, label]

        set('tags')(next.join(', '))
    }

    const submit = async (event: FormEvent) => {
        event.preventDefault()

        const payload = {
            title: form.title,
            description: form.description === '' ? null : form.description,
            dueDate: form.dueDate === '' ? null : form.dueDate,
            priorityId: form.priorityId === '' ? null : Number(form.priorityId),
            categoryId: form.categoryId === '' ? null : Number(form.categoryId),
            tags: chosenTags,
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

                <Field label="Priorité" errors={violations.for('priorityId')}>
                    <Select
                        value={form.priorityId}
                        onChange={set('priorityId')}
                        placeholder="Par défaut"
                        options={(priorities.data ?? []).map((priority) => ({
                            value: String(priority.id),
                            label: priority.label,
                        }))}
                    />
                </Field>
            </FormGrid>

            <FormGrid columns={2}>
                <Field label="Étiquettes" errors={violations.for('tags')}>
                    <TextInput
                        value={form.tags}
                        onChange={(event) => set('tags')(event.target.value)}
                        placeholder="séparées par des virgules"
                    />
                </Field>

                <Field label="Échéance" errors={violations.for('dueDate')}>
                    <TextInput
                        type="date"
                        value={form.dueDate}
                        onChange={(event) => set('dueDate')(event.target.value)}
                    />
                </Field>
            </FormGrid>

            {(tags.data ?? []).length > 0 && (
                <div className="tag-suggestions">
                    {(tags.data ?? []).map((label) => (
                        <ToggleChip
                            key={label}
                            pressed={chosenTags.includes(label)}
                            onToggle={() => toggleTag(label)}
                        >
                            #{label}
                        </ToggleChip>
                    ))}
                </div>
            )}

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
