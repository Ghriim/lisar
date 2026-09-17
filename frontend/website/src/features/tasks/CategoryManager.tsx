import { useState, type FormEvent } from 'react'
import { ApiError } from '../../api/client'
import { humanise } from '../../api/violations'
import type { Category } from '../../api/types'
import { Field, TextInput } from '../../components/Field'
import { FormActions } from '../../components/FormActions'
import {
    useCategories,
    useCreateCategory,
    useDeleteCategory,
    useUpdateCategory,
} from './queries'

/**
 * The person's own categories: create, rename, drop. The reference ones are shown alongside so
 * that the list they see here is the list they choose from — but they are not theirs to touch.
 */
export function CategoryManager() {
    const categories = useCategories()
    const create = useCreateCategory()
    const update = useUpdateCategory()
    const remove = useDeleteCategory()

    const [newLabel, setNewLabel] = useState('')
    const [editing, setEditing] = useState<{ id: number; label: string } | null>(null)
    // Which row a refused deletion belongs to: the mutation only knows that one failed.
    const [refusedDeletion, setRefusedDeletion] = useState<{ id: number; code: string } | null>(null)

    const all = categories.data ?? []
    const personal = all.filter((category) => category.isPersonal)
    const reference = all.filter((category) => !category.isPersonal)

    const submitNew = async (event: FormEvent) => {
        event.preventDefault()

        try {
            await create.mutateAsync(newLabel)
            setNewLabel('')
        } catch {
            // The violations are on the mutation, and the field below reads them.
        }
    }

    const submitDelete = async (id: number) => {
        setRefusedDeletion(null)

        try {
            await remove.mutateAsync(id)
        } catch (failure) {
            if (failure instanceof ApiError) {
                setRefusedDeletion({ id, code: failure.violationsFor('id')[0] ?? 'action_failed' })
            }
        }
    }

    const submitRename = async (event: FormEvent) => {
        event.preventDefault()

        if (editing === null) {
            return
        }

        try {
            await update.mutateAsync({ id: editing.id, label: editing.label })
            setEditing(null)
        } catch {
            // Same: the field reads them.
        }
    }

    return (
        <div className="stack">
            <div>
                <h3 className="category-heading">À moi</h3>

                {personal.length === 0 && <p className="empty">Aucune catégorie personnelle.</p>}

                {personal.map((category) =>
                    editing?.id === category.id ? (
                        <form key={category.id} className="form-grid" onSubmit={(event) => void submitRename(event)}>
                            <Field
                                label="Nom"
                                errors={[
                                    ...violationsOf(update.error, 'label'),
                                    ...violationsOf(update.error, 'id'),
                                ]}
                            >
                                <TextInput
                                    value={editing.label}
                                    onChange={(event) => setEditing({ id: category.id, label: event.target.value })}
                                    autoFocus
                                />
                            </Field>

                            <FormActions>
                                <button type="button" className="button button-quiet" onClick={() => setEditing(null)}>
                                    Annuler
                                </button>
                                <button type="submit" className="button" disabled={update.isPending}>
                                    Renommer
                                </button>
                            </FormActions>
                        </form>
                    ) : (
                        <CategoryRow
                            key={category.id}
                            category={category}
                            error={
                                refusedDeletion?.id === category.id
                                    ? humanise(refusedDeletion.code)
                                    : null
                            }
                            onRename={() => setEditing({ id: category.id, label: category.label })}
                            onDelete={() => void submitDelete(category.id)}
                            busy={remove.isPending}
                        />
                    ),
                )}
            </div>

            {reference.length > 0 && (
                <div>
                    <h3 className="category-heading">Communes</h3>

                    {reference.map((category) => (
                        <div key={category.id} className="task">
                            <div className="task-body dim">{category.label}</div>
                        </div>
                    ))}
                </div>
            )}

            <form className="form-grid" onSubmit={(event) => void submitNew(event)}>
                <Field label="Nouvelle catégorie" errors={violationsOf(create.error, 'label')}>
                    <TextInput
                        value={newLabel}
                        onChange={(event) => setNewLabel(event.target.value)}
                        placeholder="Sport, Lecture…"
                        required
                    />
                </Field>

                <FormActions>
                    <button type="submit" className="button" disabled={create.isPending}>
                        Créer
                    </button>
                </FormActions>
            </form>
        </div>
    )
}

interface CategoryRowProps {
    category: Category
    error: string | null
    onRename: () => void
    onDelete: () => void
    busy: boolean
}

function CategoryRow({ category, error, onRename, onDelete, busy }: CategoryRowProps) {
    return (
        <article className="task">
            <div className="task-body">
                {category.label}
                {error !== null && <p className="field-error">{error}</p>}
            </div>

            <div className="task-actions">
                <button
                    type="button"
                    className="icon-button"
                    aria-label={`Renommer « ${category.label} »`}
                    title="Renommer"
                    disabled={busy}
                    onClick={onRename}
                >
                    ✎
                </button>
                <button
                    type="button"
                    className="icon-button icon-button-danger"
                    aria-label={`Supprimer « ${category.label} »`}
                    title="Supprimer"
                    disabled={busy}
                    onClick={onDelete}
                >
                    ✕
                </button>
            </div>
        </article>
    )
}

function violationsOf(error: unknown, field: string): string[] {
    return error instanceof ApiError ? error.violationsFor(field) : []
}
