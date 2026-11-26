import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import { categoryIconWord } from '../api/categoryWords'
import { CATEGORY_ICONS, type Category, type CategoryPayload } from '../api/types'
import {
    Button,
    CategoryIcon,
    ConfirmButton,
    DataTable,
    FormModal,
    Page,
    Paragraph,
    Row,
    SelectField,
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
        mutationFn: ({ id, payload }: { id: number | null; payload: CategoryPayload }) =>
            id === null ? api.createCategory(payload) : api.updateCategory(id, payload),
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
                    {
                        key: 'label',
                        title: 'Libellé',
                        render: (category) => (
                            <Row gap={8}>
                                {category.icon !== null && <CategoryIcon code={category.icon} />}
                                {category.label}
                            </Row>
                        ),
                    },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (category) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(category)}>
                                    Modifier
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
                <FormModal<CategoryPayload>
                    title={editing === null ? 'Nouvelle catégorie' : 'Modifier la catégorie'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={({ label, icon }) => save.mutate({ id: editing?.id ?? null, payload: { label, icon } })}
                    initialValues={{ label: editing?.label ?? '', icon: editing?.icon ?? undefined }}
                >
                    <TextField name="label" label="Libellé" isMandatory autoFocus />

                    <SelectField
                        name="icon"
                        label="Icône"
                        isMandatory
                        hint="Celle des tâches et des habitudes rangées dans cette catégorie."
                        placeholder="Choisir une icône"
                        options={CATEGORY_ICONS.map((code) => ({
                            value: code,
                            label: (
                                <Row gap={8}>
                                    <CategoryIcon code={code} size={16} />
                                    {categoryIconWord(code)}
                                </Row>
                            ),
                        }))}
                    />
                </FormModal>
            )}
        </Page>
    )
}
