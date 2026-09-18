import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import type { Category } from '../api/types'
import {
    Button,
    ConfirmButton,
    DataTable,
    FormModal,
    Page,
    Paragraph,
    Row,
    TextField,
    useNotifier,
} from '../components'

/**
 * The common categories, the ones everyone picks from. What people create for themselves never
 * shows up here — the API does not serve it to this surface.
 */
export function CategoriesPage() {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    const [editing, setEditing] = useState<Category | null | undefined>(undefined)

    const categories = useQuery({ queryKey: ['categories'], queryFn: api.fetchCategories })

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['categories'] })

    const save = useMutation({
        mutationFn: ({ id, label }: { id: number | null; label: string }) =>
            id === null ? api.createCategory(label) : api.updateCategory(id, label),
        onSuccess: async () => {
            setEditing(undefined)
            notify.success('Catégorie enregistrée.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’enregistrement a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deleteCategory(id),
        onSuccess: async () => {
            notify.success('Catégorie supprimée.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'La suppression a échoué.'),
    })

    return (
        <Page
            title="Catégories communes"
            action={
                <Button variant="primary" onClick={() => setEditing(null)}>
                    Créer
                </Button>
            }
        >
            <Paragraph muted>
                Visibles par tous les comptes. Les catégories qu’une personne crée pour elle-même
                n’apparaissent pas ici.
            </Paragraph>

            <DataTable<Category>
                rows={categories.data ?? []}
                rowKey={(category) => category.id}
                loading={categories.isPending}
                emptyText="Aucune catégorie commune"
                columns={[
                    { key: 'label', title: 'Libellé', render: (category) => category.label },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (category) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(category)}>
                                    Renommer
                                </Button>
                                <ConfirmButton
                                    question="Supprimer cette catégorie ?"
                                    loading={remove.isPending && remove.variables === category.id}
                                    onConfirm={() => remove.mutate(category.id)}
                                >
                                    Supprimer
                                </ConfirmButton>
                            </Row>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <FormModal<{ label: string }>
                    title={editing === null ? 'Nouvelle catégorie' : 'Renommer la catégorie'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={({ label }) => save.mutate({ id: editing?.id ?? null, label })}
                    initialValues={{ label: editing?.label ?? '' }}
                >
                    <TextField name="label" label="Libellé" required autoFocus />
                </FormModal>
            )}
        </Page>
    )
}
