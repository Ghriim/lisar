import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import type { Priority, PriorityPayload } from '../api/types'
import {
    Button,
    ColourDot,
    ConfirmButton,
    DataTable,
    FormModal,
    NumberField,
    Page,
    Row,
    SwitchField,
    Tag,
    TextField,
    useNotifier,
} from '../components'

export function PrioritiesPage() {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    /** undefined: no window. null: creating. A priority: editing that one. */
    const [editing, setEditing] = useState<Priority | null | undefined>(undefined)

    const priorities = useQuery({ queryKey: ['priorities'], queryFn: api.fetchPriorities })

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['priorities'] })

    const save = useMutation({
        mutationFn: ({ id, payload }: { id: number | null; payload: PriorityPayload }) =>
            id === null ? api.createPriority(payload) : api.updatePriority(id, payload),
        onSuccess: async () => {
            setEditing(undefined)
            notify.success('Priorité enregistrée.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’enregistrement a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deletePriority(id),
        onSuccess: async () => {
            notify.success('Priorité supprimée.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'La suppression a échoué.'),
    })

    return (
        <Page
            title="Priorités"
            action={
                <Button variant="primary" onClick={() => setEditing(null)}>
                    Créer
                </Button>
            }
        >
            <DataTable<Priority>
                rows={priorities.data ?? []}
                rowKey={(priority) => priority.id}
                loading={priorities.isPending}
                emptyText="Aucune priorité"
                columns={[
                    {
                        key: 'label',
                        title: 'Libellé',
                        render: (priority) => (
                            <Row gap={8}>
                                <ColourDot colour={priority.colour} />
                                {priority.label}
                                {priority.isDefault && <Tag colour="blue">défaut</Tag>}
                            </Row>
                        ),
                    },
                    { key: 'weight', title: 'Poids', width: 100, render: (priority) => priority.weight },
                    { key: 'colour', title: 'Couleur', width: 140, render: (priority) => priority.colour },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (priority) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(priority)}>
                                    Modifier
                                </Button>
                                <ConfirmButton
                                    question="Supprimer cette priorité ?"
                                    loading={remove.isPending && remove.variables === priority.id}
                                    onConfirm={() => remove.mutate(priority.id)}
                                >
                                    Supprimer
                                </ConfirmButton>
                            </Row>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <FormModal<PriorityPayload>
                    title={editing === null ? 'Nouvelle priorité' : 'Modifier la priorité'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={(payload) => save.mutate({ id: editing?.id ?? null, payload })}
                    initialValues={{
                        label: editing?.label ?? '',
                        weight: editing?.weight ?? 0,
                        colour: editing?.colour ?? '#3e63dd',
                        isDefault: editing?.isDefault ?? false,
                    }}
                >
                    <TextField name="label" label="Libellé" required autoFocus />
                    <NumberField
                        name="weight"
                        label="Poids"
                        min={0}
                        max={9999}
                        hint="Plus le poids est faible, plus la priorité remonte."
                    />
                    <TextField name="colour" label="Couleur" required placeholder="#RRGGBB" />
                    <SwitchField
                        name="isDefault"
                        label="Priorité par défaut"
                        disabled={editing?.isDefault === true}
                        hint="La donner à celle-ci la retire à celle qui l’avait. Elle ne se retire jamais seule."
                    />
                </FormModal>
            )}
        </Page>
    )
}
