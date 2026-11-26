import { Pencil, X } from 'lucide-react'
import { useState } from 'react'
import { ApiError } from '../../api/client'
import type { Category } from '../../api/types'
import { humanise } from '../../api/violations'
import {
    Button,
    ConfirmDialog,
    DataList,
    Form,
    FormActions,
    IconButton,
    ListItem,
    Stack,
    TextField,
    useViolations,
} from '../../components'
import { CategoryGlyph } from './CategoryGlyph'
import { useCategories, useCreateCategory, useDeleteCategory, useUpdateCategory } from './queries'

/**
 * The person's own categories: create, rename, drop. The common ones are shown alongside so that
 * the list they see here is the list they choose from — but they are not theirs to touch.
 */
export function CategoryManager() {
    const categories = useCategories()
    const create = useCreateCategory()
    const update = useUpdateCategory()
    const remove = useDeleteCategory()

    const [newLabel, setNewLabel] = useState('')
    const [editing, setEditing] = useState<{ id: number; label: string } | null>(null)
    const [confirming, setConfirming] = useState<Category | null>(null)
    // Which row a refused deletion belongs to: the mutation only knows that one failed.
    const [refusedDeletion, setRefusedDeletion] = useState<{ id: number; code: string } | null>(null)

    const creation = useViolations(create.error)
    const rename = useViolations(update.error)

    const all = categories.data ?? []
    const personal = all.filter((category) => category.isPersonal)
    const reference = all.filter((category) => !category.isPersonal)

    const submitNew = async () => {
        try {
            await create.mutateAsync(newLabel)
            setNewLabel('')
        } catch {
            // The violations are on the mutation, and the field below reads them.
        }
    }

    const submitRename = async () => {
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

    const submitDelete = async (category: Category) => {
        setConfirming(null)
        setRefusedDeletion(null)

        try {
            await remove.mutateAsync(category.id)
        } catch (failure) {
            if (failure instanceof ApiError) {
                setRefusedDeletion({
                    id: category.id,
                    code: failure.violationsFor('id')[0] ?? 'action_failed',
                })
            }
        }
    }

    return (
        <Stack>
            <DataList<Category>
                groups={[{ label: 'À moi', items: personal }]}
                keyOf={(category) => category.id}
                loading={categories.isPending}
                emptyText="Aucune catégorie personnelle."
                renderItem={(category) =>
                    editing?.id === category.id ? (
                        <Form onSubmit={submitRename}>
                            <TextField
                                label="Nom"
                                isMandatory
                                errors={[...rename.for('label'), ...rename.for('id')]}
                                value={editing.label}
                                onChange={(label) => setEditing({ id: category.id, label })}
                                autoFocus
                            />

                            <FormActions>
                                <Button variant="quiet" onClick={() => setEditing(null)}>
                                    Annuler
                                </Button>
                                <Button variant="primary" submit disabled={update.isPending}>
                                    Renommer
                                </Button>
                            </FormActions>
                        </Form>
                    ) : (
                        <ListItem
                            title={category.label}
                            error={
                                refusedDeletion?.id === category.id
                                    ? humanise(refusedDeletion.code)
                                    : null
                            }
                            actions={
                                <>
                                    <IconButton
                                        icon={Pencil}
                                        label="Renommer"
                                        subject={category.label}
                                        disabled={remove.isPending}
                                        onClick={() => setEditing({ id: category.id, label: category.label })}
                                    />
                                    <IconButton
                                        icon={X}
                                        variant="danger"
                                        label="Supprimer"
                                        subject={category.label}
                                        disabled={remove.isPending}
                                        onClick={() => setConfirming(category)}
                                    />
                                </>
                            }
                        />
                    )
                }
            />

            {reference.length > 0 && (
                <DataList<Category>
                    groups={[{ label: 'Communes', items: reference }]}
                    keyOf={(category) => category.id}
                    emptyText="Aucune catégorie commune."
                    renderItem={(category) => <ListItem title={category.label} icon={<CategoryGlyph category={category} size={22} />} />}
                />
            )}

            <Form onSubmit={submitNew}>
                <TextField
                    label="Nouvelle catégorie"
                    isMandatory
                    errors={creation.for('label')}
                    value={newLabel}
                    onChange={setNewLabel}
                    placeholder="Sport, Lecture…"
                />

                <FormActions>
                    <Button variant="primary" submit disabled={create.isPending}>
                        Créer
                    </Button>
                </FormActions>
            </Form>

            {confirming !== null && (
                <ConfirmDialog
                    title="Supprimer la catégorie"
                    question={`« ${confirming.label} » sera supprimée. C’est définitif.`}
                    confirmLabel="Supprimer"
                    onCancel={() => setConfirming(null)}
                    onConfirm={() => void submitDelete(confirming)}
                />
            )}
        </Stack>
    )
}
