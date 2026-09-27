import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import type { Equipment, EquipmentPayload } from '../api/types'
import {
    ActiveFilter,
    Button,
    ConfirmButton,
    DataTable,
    FilterSelect,
    FormModal,
    ListToolbar,
    Page,
    Row,
    StatusTag,
    SwitchField,
    TextField,
    useActiveFilter,
    useNotifier,
} from '../components'

/** A flag filter's two choices; no choice at all is the unfiltered list. */
type Presence = 'with' | 'without'

function asFlag(presence: Presence | undefined): boolean | undefined {
    return presence === undefined ? undefined : presence === 'with'
}

/**
 * The equipment movements are done with. What a movement tracks when it is logged — a load, a
 * distance — comes from the flags set here.
 */
export function EquipmentsPage() {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    /** undefined: no window. null: creating. An equipment: editing that one. */
    const [editing, setEditing] = useState<Equipment | null | undefined>(undefined)
    const [search, setSearch] = useState('')
    const status = useActiveFilter()
    const [hasWeight, setHasWeight] = useState<Presence | undefined>(undefined)
    const [hasDistance, setHasDistance] = useState<Presence | undefined>(undefined)

    const filters = { isActive: status.isActive, hasWeight: asFlag(hasWeight), hasDistance: asFlag(hasDistance) }
    const equipments = useQuery({
        queryKey: ['equipments', filters],
        queryFn: () => api.fetchEquipments(filters),
    })

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['equipments'] })

    const save = useMutation({
        mutationFn: ({ id, payload }: { id: number | null; payload: EquipmentPayload }) =>
            id === null ? api.createEquipment(payload) : api.updateEquipment(id, payload),
        onSuccess: async () => {
            setEditing(undefined)
            notify.success('Équipement enregistré.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’enregistrement a échoué.'),
    })

    const setActive = useMutation({
        mutationFn: ({ id, active }: { id: number; active: boolean }) =>
            active ? api.activateEquipment(id) : api.deactivateEquipment(id),
        onSuccess: async () => {
            notify.success('Équipement mis à jour.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’opération a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deleteEquipment(id),
        onSuccess: async () => {
            notify.success('Équipement supprimé.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'La suppression a échoué.'),
    })

    const term = search.trim().toLowerCase()
    const rows = (equipments.data ?? []).filter((equipment) => equipment.name.toLowerCase().includes(term))

    return (
        <Page
            title="Équipements"
            action={
                <Button variant="primary" onClick={() => setEditing(null)}>
                    Créer
                </Button>
            }
        >
            <ListToolbar searchPlaceholder="Rechercher un équipement" onSearch={setSearch} searchAsYouType>
                <ActiveFilter value={status.status} onChange={status.setStatus} />
                <FilterSelect<Presence>
                    placeholder="Charge"
                    value={hasWeight}
                    onChange={setHasWeight}
                    options={[
                        { value: 'with', label: 'Avec charge' },
                        { value: 'without', label: 'Sans charge' },
                    ]}
                />
                <FilterSelect<Presence>
                    placeholder="Distance"
                    value={hasDistance}
                    onChange={setHasDistance}
                    options={[
                        { value: 'with', label: 'Avec distance' },
                        { value: 'without', label: 'Sans distance' },
                    ]}
                />
            </ListToolbar>

            <DataTable<Equipment>
                rows={rows}
                rowKey={(equipment) => equipment.id}
                loading={equipments.isPending}
                emptyText="Aucun équipement"
                columns={[
                    { key: 'name', title: 'Équipement', render: (equipment) => equipment.name },
                    {
                        key: 'weight',
                        title: 'Charge',
                        width: 100,
                        render: (equipment) => (equipment.hasWeight ? 'Oui' : '—'),
                    },
                    {
                        key: 'distance',
                        title: 'Distance',
                        width: 100,
                        render: (equipment) => (equipment.hasDistance ? 'Oui' : '—'),
                    },
                    {
                        key: 'status',
                        title: 'Statut',
                        width: 120,
                        render: (equipment) => <StatusTag isActive={equipment.isActive} />,
                    },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (equipment) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(equipment)}>
                                    Modifier
                                </Button>
                                {equipment.isActive ? (
                                    <Button
                                        size="small"
                                        loading={setActive.isPending && setActive.variables?.id === equipment.id}
                                        onClick={() => setActive.mutate({ id: equipment.id, active: false })}
                                    >
                                        Désactiver
                                    </Button>
                                ) : (
                                    <Button
                                        size="small"
                                        onClick={() => setActive.mutate({ id: equipment.id, active: true })}
                                    >
                                        Réactiver
                                    </Button>
                                )}
                                <ConfirmButton
                                    question="Supprimer cet équipement ?"
                                    loading={remove.isPending && remove.variables === equipment.id}
                                    onConfirm={() => remove.mutate(equipment.id)}
                                >
                                    Supprimer
                                </ConfirmButton>
                            </Row>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <FormModal<EquipmentPayload>
                    title={editing === null ? 'Nouvel équipement' : 'Modifier l’équipement'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={(values) =>
                        save.mutate({
                            id: editing?.id ?? null,
                            payload: {
                                name: values.name,
                                hasWeight: values.hasWeight ?? false,
                                hasDistance: values.hasDistance ?? false,
                            },
                        })
                    }
                    initialValues={{
                        name: editing?.name ?? '',
                        hasWeight: editing?.hasWeight ?? false,
                        hasDistance: editing?.hasDistance ?? false,
                    }}
                >
                    <TextField name="name" label="Nom" required autoFocus />
                    <SwitchField
                        name="hasWeight"
                        label="Avec charge"
                        hint="Un mouvement fait avec cet équipement demandera un poids."
                    />
                    <SwitchField
                        name="hasDistance"
                        label="Avec distance"
                        hint="Un mouvement fait avec cet équipement demandera une distance : rameur, vélo, tapis."
                    />
                </FormModal>
            )}
        </Page>
    )
}
