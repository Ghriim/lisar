import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import { setTypeColourShade, setTypeColourWord } from '../api/setTypeWords'
import { SET_TYPE_COLOURS, type SetType, type SetTypePayload } from '../api/types'
import {
    ActiveFilter,
    Button,
    ColourDot,
    ConfirmButton,
    DataTable,
    FormModal,
    ListToolbar,
    Page,
    Row,
    SelectField,
    StatusTag,
    SwitchField,
    Tag,
    TextField,
    useActiveFilter,
    useNotifier,
} from '../components'

/**
 * The kinds of set a workout can mark — warm-up, dropset… One of them is the default, the ordinary
 * working set a set takes when it is logged without a type. It moves by being given to another
 * type, and it is neither retired nor deleted: neither action is offered on it.
 */
export function SetTypesPage() {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    /** undefined: no window. null: creating. A set type: editing that one. */
    const [editing, setEditing] = useState<SetType | null | undefined>(undefined)
    const [search, setSearch] = useState('')
    const status = useActiveFilter()

    const filters = { isActive: status.isActive }
    const setTypes = useQuery({
        queryKey: ['set-types', filters],
        queryFn: () => api.fetchSetTypes(filters),
    })

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['set-types'] })

    const save = useMutation({
        mutationFn: ({ id, payload }: { id: number | null; payload: SetTypePayload }) =>
            id === null ? api.createSetType(payload) : api.updateSetType(id, payload),
        onSuccess: async () => {
            setEditing(undefined)
            notify.success('Type de série enregistré.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’enregistrement a échoué.'),
    })

    const setActive = useMutation({
        mutationFn: ({ id, active }: { id: number; active: boolean }) =>
            active ? api.activateSetType(id) : api.deactivateSetType(id),
        onSuccess: async () => {
            notify.success('Type de série mis à jour.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’opération a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deleteSetType(id),
        onSuccess: async () => {
            notify.success('Type de série supprimé.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'La suppression a échoué.'),
    })

    const term = search.trim().toLowerCase()
    const rows = (setTypes.data ?? []).filter((setType) => setType.name.toLowerCase().includes(term))

    return (
        <Page
            title="Types de série"
            action={
                <Button variant="primary" onClick={() => setEditing(null)}>
                    Créer
                </Button>
            }
        >
            <ListToolbar searchPlaceholder="Rechercher un type de série" onSearch={setSearch} searchAsYouType>
                <ActiveFilter value={status.status} onChange={status.setStatus} />
            </ListToolbar>

            <DataTable<SetType>
                rows={rows}
                rowKey={(setType) => setType.id}
                loading={setTypes.isPending}
                emptyText="Aucun type de série"
                columns={[
                    {
                        key: 'name',
                        title: 'Type de série',
                        render: (setType) => (
                            <Row gap={8}>
                                {setType.name}
                                {setType.isDefaultType && <Tag colour="blue">défaut</Tag>}
                                {!setType.countsForPersonalBests && <Tag colour="default">hors records</Tag>}
                            </Row>
                        ),
                    },
                    {
                        key: 'colour',
                        title: 'Couleur',
                        width: 160,
                        render: (setType) => (
                            <Row gap={8}>
                                <ColourDot colour={setTypeColourShade(setType.colour)} />
                                {setTypeColourWord(setType.colour)}
                            </Row>
                        ),
                    },
                    {
                        key: 'status',
                        title: 'Statut',
                        width: 120,
                        render: (setType) => <StatusTag isActive={setType.isActive} />,
                    },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (setType) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(setType)}>
                                    Modifier
                                </Button>
                                {setType.isDefaultType ? null : setType.isActive ? (
                                    <Button
                                        size="small"
                                        loading={setActive.isPending && setActive.variables?.id === setType.id}
                                        onClick={() => setActive.mutate({ id: setType.id, active: false })}
                                    >
                                        Désactiver
                                    </Button>
                                ) : (
                                    <Button
                                        size="small"
                                        onClick={() => setActive.mutate({ id: setType.id, active: true })}
                                    >
                                        Réactiver
                                    </Button>
                                )}
                                {!setType.isDefaultType && (
                                    <ConfirmButton
                                        question="Supprimer ce type de série ?"
                                        loading={remove.isPending && remove.variables === setType.id}
                                        onConfirm={() => remove.mutate(setType.id)}
                                    >
                                        Supprimer
                                    </ConfirmButton>
                                )}
                            </Row>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <FormModal<SetTypePayload>
                    title={editing === null ? 'Nouveau type de série' : 'Modifier le type de série'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={(values) =>
                        save.mutate({
                            id: editing?.id ?? null,
                            payload: {
                                name: values.name,
                                colour: values.colour,
                                isDefaultType: values.isDefaultType,
                                countsForPersonalBests: values.countsForPersonalBests,
                            },
                        })
                    }
                    // Nothing preselected on a creation: the administrator picks the colour.
                    initialValues={{
                        name: editing?.name ?? '',
                        colour: editing?.colour,
                        isDefaultType: editing?.isDefaultType ?? false,
                        countsForPersonalBests: editing?.countsForPersonalBests ?? true,
                    }}
                >
                    <TextField name="name" label="Nom" required autoFocus />
                    <SelectField
                        name="colour"
                        label="Couleur"
                        required
                        placeholder="Choisir une couleur"
                        options={SET_TYPE_COLOURS.map((code) => ({
                            value: code,
                            label: (
                                <Row gap={8}>
                                    <ColourDot colour={setTypeColourShade(code)} />
                                    {setTypeColourWord(code)}
                                </Row>
                            ),
                        }))}
                    />
                    <SwitchField
                        name="isDefaultType"
                        label="Type par défaut"
                        disabled={editing?.isDefaultType === true}
                        hint="Le donner à celui-ci le retire à celui qui l’avait. Il ne se retire jamais seul."
                    />
                    <SwitchField
                        name="countsForPersonalBests"
                        label="Compte pour les records"
                        hint="Éteint, ses séries ne battent aucun record — un échauffement, par exemple. Le changer recalcule les records des séries qui le portent."
                    />
                </FormModal>
            )}
        </Page>
    )
}
