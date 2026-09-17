import { useState, type FormEvent } from 'react'
import { ApiError } from '../../api/client'
import type { Task } from '../../api/types'
import { Field, TextArea, TextInput } from '../../components/Field'
import { FormActions } from '../../components/FormActions'
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
    const failure = (editing === null ? create.error : update.error) ?? null
    const violations = failure instanceof ApiError ? failure : null

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
            tags: form.tags
                .split(',')
                .map((tag) => tag.trim())
                .filter((tag) => tag !== ''),
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
            <Field label="Intitulé" errors={violations?.violationsFor('title')}>
                <TextInput
                    value={form.title}
                    onChange={(event) => set('title')(event.target.value)}
                    placeholder="Ce qu'il y a à faire"
                    required
                    autoFocus
                />
            </Field>

            <Field label="Détail" errors={violations?.violationsFor('description')}>
                <TextArea
                    value={form.description}
                    onChange={(event) => set('description')(event.target.value)}
                    placeholder="Facultatif"
                />
            </Field>

            <div className="form-grid form-grid-two">
                <Field label="Échéance" errors={violations?.violationsFor('dueDate')}>
                    <TextInput
                        type="date"
                        value={form.dueDate}
                        onChange={(event) => set('dueDate')(event.target.value)}
                    />
                </Field>

                <Field label="Rang" errors={violations?.violationsFor('priorityId')}>
                    <select
                        className="field-input"
                        value={form.priorityId}
                        onChange={(event) => set('priorityId')(event.target.value)}
                    >
                        <option value="">Par défaut</option>
                        {(priorities.data ?? []).map((priority) => (
                            <option key={priority.id} value={priority.id}>
                                {priority.label}
                            </option>
                        ))}
                    </select>
                </Field>
            </div>

            <div className="form-grid form-grid-two">
                <Field label="Catégorie" errors={violations?.violationsFor('categoryId')}>
                    <select
                        className="field-input"
                        value={form.categoryId}
                        onChange={(event) => set('categoryId')(event.target.value)}
                    >
                        <option value="">Aucune</option>
                        {(categories.data ?? []).map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.label}
                                {category.isPersonal ? ' ·' : ''}
                            </option>
                        ))}
                    </select>
                </Field>

                <Field label="Étiquettes" errors={violations?.violationsFor('tags')}>
                    <TextInput
                        value={form.tags}
                        onChange={(event) => set('tags')(event.target.value)}
                        placeholder="séparées par des virgules"
                    />
                </Field>
            </div>

            {(tags.data ?? []).length > 0 && (
                <div className="tag-suggestions">
                    {(tags.data ?? []).map((label) => (
                        <button
                            key={label}
                            type="button"
                            className="chip"
                            aria-pressed={chosenTags.includes(label)}
                            onClick={() => toggleTag(label)}
                        >
                            #{label}
                        </button>
                    ))}
                </div>
            )}

            <FormActions>
            <button type="button" className="button button-quiet" onClick={onDone}>
                Annuler
            </button>

            <button type="submit" className="button" disabled={pending}>
                {editing !== null ? 'Enregistrer' : 'Créer'}
            </button>
            </FormActions>
        </form>
    )
}
