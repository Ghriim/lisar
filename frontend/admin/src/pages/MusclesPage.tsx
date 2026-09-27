import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import type { Muscle, MuscleGroup, MusclePayload } from '../api/types'
import {
    ActiveFilter,
    Button,
    ConfirmButton,
    DataTable,
    DetailDrawer,
    FilterSelect,
    FormModal,
    ListToolbar,
    Page,
    Row,
    SelectField,
    StatusTag,
    Tag,
    TextField,
    useActiveFilter,
    useNotifier,
} from '../components'

/**
 * The muscles movements target, and — from the same screen — the groups they sit in. A group is
 * only a name, so it gets a panel on this page rather than a page of its own.
 */
export function MusclesPage() {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    /** undefined: no window. null: creating. A muscle: editing that one. */
    const [editing, setEditing] = useState<Muscle | null | undefined>(undefined)
    const [managingGroups, setManagingGroups] = useState(false)
    const [search, setSearch] = useState('')
    const status = useActiveFilter()
    const [muscleGroupId, setMuscleGroupId] = useState<number | undefined>(undefined)

    const filters = { isActive: status.isActive, muscleGroupId }
    const muscles = useQuery({
        queryKey: ['muscles', filters],
        queryFn: () => api.fetchMuscles(filters),
    })
    const groups = useQuery({ queryKey: ['muscle-groups'], queryFn: api.fetchMuscleGroups })

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['muscles'] })

    const save = useMutation({
        mutationFn: ({ id, payload }: { id: number | null; payload: MusclePayload }) =>
            id === null ? api.createMuscle(payload) : api.updateMuscle(id, payload),
        onSuccess: async () => {
            setEditing(undefined)
            notify.success('Muscle enregistré.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’enregistrement a échoué.'),
    })

    const setActive = useMutation({
        mutationFn: ({ id, active }: { id: number; active: boolean }) =>
            active ? api.activateMuscle(id) : api.deactivateMuscle(id),
        onSuccess: async () => {
            notify.success('Muscle mis à jour.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’opération a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deleteMuscle(id),
        onSuccess: async () => {
            notify.success('Muscle supprimé.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'La suppression a échoué.'),
    })

    const term = search.trim().toLowerCase()
    const rows = (muscles.data ?? []).filter(
        (muscle) => muscle.name.toLowerCase().includes(term) || muscle.muscleGroupName.toLowerCase().includes(term),
    )

    // An inactive group takes no new muscle, but a muscle already in one keeps it on the form.
    const groupOptions = (groups.data ?? [])
        .filter((group) => group.isActive || group.id === editing?.muscleGroupId)
        .map((group) => ({ value: String(group.id), label: group.name }))

    return (
        <Page
            title="Muscles"
            action={
                <Row gap={8}>
                    <Button onClick={() => setManagingGroups(true)}>Gérer les groupes</Button>
                    <Button variant="primary" onClick={() => setEditing(null)}>
                        Créer
                    </Button>
                </Row>
            }
        >
            <ListToolbar searchPlaceholder="Rechercher un muscle ou un groupe" onSearch={setSearch} searchAsYouType>
                <ActiveFilter value={status.status} onChange={status.setStatus} />
                {/* Every group, inactive ones included: this narrows the list, it assigns nothing. */}
                <FilterSelect<number>
                    placeholder="Groupe"
                    value={muscleGroupId}
                    onChange={setMuscleGroupId}
                    options={(groups.data ?? []).map((group) => ({ value: group.id, label: group.name }))}
                />
            </ListToolbar>

            <DataTable<Muscle>
                rows={rows}
                rowKey={(muscle) => muscle.id}
                loading={muscles.isPending}
                emptyText="Aucun muscle"
                columns={[
                    { key: 'name', title: 'Muscle', render: (muscle) => muscle.name },
                    {
                        key: 'group',
                        title: 'Groupe',
                        render: (muscle) => (
                            <Row gap={8}>
                                {muscle.muscleGroupName}
                                {!muscle.muscleGroupIsActive && <Tag colour="red">Groupe désactivé</Tag>}
                            </Row>
                        ),
                    },
                    {
                        key: 'status',
                        title: 'Statut',
                        width: 120,
                        render: (muscle) => <StatusTag isActive={muscle.isActive} />,
                    },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (muscle) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(muscle)}>
                                    Modifier
                                </Button>
                                {muscle.isActive ? (
                                    <Button
                                        size="small"
                                        loading={setActive.isPending && setActive.variables?.id === muscle.id}
                                        onClick={() => setActive.mutate({ id: muscle.id, active: false })}
                                    >
                                        Désactiver
                                    </Button>
                                ) : (
                                    <Button size="small" onClick={() => setActive.mutate({ id: muscle.id, active: true })}>
                                        Réactiver
                                    </Button>
                                )}
                                <ConfirmButton
                                    question="Supprimer ce muscle ?"
                                    loading={remove.isPending && remove.variables === muscle.id}
                                    onConfirm={() => remove.mutate(muscle.id)}
                                >
                                    Supprimer
                                </ConfirmButton>
                            </Row>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <FormModal<{ name: string; muscleGroupId: string | undefined }>
                    title={editing === null ? 'Nouveau muscle' : 'Modifier le muscle'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={(values) =>
                        save.mutate({
                            id: editing?.id ?? null,
                            payload: { name: values.name, muscleGroupId: Number(values.muscleGroupId) },
                        })
                    }
                    initialValues={{
                        name: editing?.name ?? '',
                        // Nothing preselected on a creation: the group is a choice, not a default.
                        muscleGroupId: editing === null ? undefined : String(editing.muscleGroupId),
                    }}
                >
                    <TextField name="name" label="Nom" required autoFocus />
                    <SelectField
                        name="muscleGroupId"
                        label="Groupe"
                        required
                        options={groupOptions}
                        hint="Seuls les groupes actifs reçoivent un muscle."
                    />
                </FormModal>
            )}

            {managingGroups && (
                <DetailDrawer title="Groupes musculaires" onClose={() => setManagingGroups(false)}>
                    <MuscleGroupManager groups={groups.data ?? []} loading={groups.isPending} />
                </DetailDrawer>
            )}
        </Page>
    )
}

/** The groups, in the drawer: create, rename, deactivate, delete once empty. */
function MuscleGroupManager({ groups, loading }: { groups: MuscleGroup[]; loading: boolean }) {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    const [editing, setEditing] = useState<MuscleGroup | null | undefined>(undefined)

    // A group's name and status show on every muscle row, so both lists are refreshed together.
    const refresh = () =>
        Promise.all([
            queryClient.invalidateQueries({ queryKey: ['muscle-groups'] }),
            queryClient.invalidateQueries({ queryKey: ['muscles'] }),
        ])

    const save = useMutation({
        mutationFn: ({ id, name }: { id: number | null; name: string }) =>
            id === null ? api.createMuscleGroup(name) : api.updateMuscleGroup(id, name),
        onSuccess: async () => {
            setEditing(undefined)
            notify.success('Groupe enregistré.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’enregistrement a échoué.'),
    })

    const setActive = useMutation({
        mutationFn: ({ id, active }: { id: number; active: boolean }) =>
            active ? api.activateMuscleGroup(id) : api.deactivateMuscleGroup(id),
        onSuccess: async () => {
            notify.success('Groupe mis à jour.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’opération a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deleteMuscleGroup(id),
        onSuccess: async () => {
            notify.success('Groupe supprimé.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'La suppression a échoué.'),
    })

    return (
        <>
            <Row justify="end" style={{ marginBottom: 16 }}>
                <Button variant="primary" onClick={() => setEditing(null)}>
                    Créer
                </Button>
            </Row>

            <DataTable<MuscleGroup>
                rows={groups}
                rowKey={(group) => group.id}
                loading={loading}
                emptyText="Aucun groupe"
                columns={[
                    { key: 'name', title: 'Groupe', render: (group) => group.name },
                    {
                        key: 'status',
                        title: 'Statut',
                        width: 110,
                        render: (group) => <StatusTag isActive={group.isActive} />,
                    },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (group) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(group)}>
                                    Renommer
                                </Button>
                                {group.isActive ? (
                                    <Button
                                        size="small"
                                        loading={setActive.isPending && setActive.variables?.id === group.id}
                                        onClick={() => setActive.mutate({ id: group.id, active: false })}
                                    >
                                        Désactiver
                                    </Button>
                                ) : (
                                    <Button size="small" onClick={() => setActive.mutate({ id: group.id, active: true })}>
                                        Réactiver
                                    </Button>
                                )}
                                <ConfirmButton
                                    question="Supprimer ce groupe ?"
                                    loading={remove.isPending && remove.variables === group.id}
                                    onConfirm={() => remove.mutate(group.id)}
                                >
                                    Supprimer
                                </ConfirmButton>
                            </Row>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <FormModal<{ name: string }>
                    title={editing === null ? 'Nouveau groupe' : 'Renommer le groupe'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={({ name }) => save.mutate({ id: editing?.id ?? null, name })}
                    initialValues={{ name: editing?.name ?? '' }}
                >
                    <TextField name="name" label="Nom" required autoFocus />
                </FormModal>
            )}
        </>
    )
}
