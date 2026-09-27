import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import type { Equipment, Movement, MovementFamily, MovementPayload, Muscle } from '../api/types'
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
    Stack,
    StatusTag,
    SwitchField,
    Tag,
    Text,
    TextAreaField,
    TextField,
    useActiveFilter,
    useNotifier,
    type SelectOptionGroup,
    type ValuesChangeHandler,
} from '../components'

interface MovementValues {
    name: string
    description: string
    videoUrl: string
    movementFamilyId: string | undefined
    primaryMuscleId: string | undefined
    secondaryMuscleIds: string[]
    equipmentIds: string[]
    tracksReps: boolean
    tracksWeight: boolean
    tracksDuration: boolean
    tracksDistance: boolean
    isUnilateral: boolean
}

/** A muscle is offered to a new movement only when it and its group are both active. */
function isAvailable(muscle: Muscle): boolean {
    return muscle.isActive && muscle.muscleGroupIsActive
}

/**
 * The common movements, and — from the same screen — the families they sit in. A family is only a
 * name, so it gets a panel on this page rather than a page of its own, like muscle groups.
 */
export function MovementsPage() {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    /** undefined: no window. null: creating. A movement: editing that one. */
    const [editing, setEditing] = useState<Movement | null | undefined>(undefined)
    const [managingFamilies, setManagingFamilies] = useState(false)
    const [search, setSearch] = useState('')
    const status = useActiveFilter()
    const [movementFamilyId, setMovementFamilyId] = useState<number | undefined>(undefined)
    const [muscleGroupId, setMuscleGroupId] = useState<number | undefined>(undefined)
    const [muscleId, setMuscleId] = useState<number | undefined>(undefined)
    const [equipmentId, setEquipmentId] = useState<number | undefined>(undefined)

    const filters = { isActive: status.isActive, movementFamilyId, muscleGroupId, muscleId, equipmentId }
    const movements = useQuery({
        queryKey: ['movements', filters],
        queryFn: () => api.fetchMovements(filters),
    })
    const families = useQuery({ queryKey: ['movement-families'], queryFn: api.fetchMovementFamilies })
    const groups = useQuery({ queryKey: ['muscle-groups'], queryFn: api.fetchMuscleGroups })
    const muscles = useQuery({ queryKey: ['muscles', {}], queryFn: () => api.fetchMuscles({}) })
    const equipments = useQuery({ queryKey: ['equipments', {}], queryFn: () => api.fetchEquipments({}) })

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['movements'] })

    const save = useMutation({
        mutationFn: ({ id, payload }: { id: number | null; payload: MovementPayload }) =>
            id === null ? api.createMovement(payload) : api.updateMovement(id, payload),
        onSuccess: async () => {
            setEditing(undefined)
            notify.success('Mouvement enregistré.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’enregistrement a échoué.'),
    })

    const setActive = useMutation({
        mutationFn: ({ id, active }: { id: number; active: boolean }) =>
            active ? api.activateMovement(id) : api.deactivateMovement(id),
        onSuccess: async () => {
            notify.success('Mouvement mis à jour.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’opération a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deleteMovement(id),
        onSuccess: async () => {
            notify.success('Mouvement supprimé.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'La suppression a échoué.'),
    })

    const term = search.trim().toLowerCase()
    const rows = (movements.data ?? []).filter((movement) => movement.name.toLowerCase().includes(term))

    const allMuscles = muscles.data ?? []
    const allEquipments = equipments.data ?? []

    // What a movement already holds stays on its form even once retired; nothing retired is offered anew.
    const heldMuscleIds = new Set(
        editing ? [editing.primaryMuscle.id, ...editing.secondaryMuscles.map((muscle) => muscle.id)] : [],
    )
    const heldEquipmentIds = new Set(editing ? editing.equipments.map((equipment) => equipment.id) : [])

    const familyOptions = (families.data ?? [])
        .filter((family) => family.isActive || family.id === editing?.movementFamilyId)
        .map((family) => ({ value: String(family.id), label: family.name }))

    const muscleOptions = groupByMuscleGroup(
        allMuscles.filter((muscle) => isAvailable(muscle) || heldMuscleIds.has(muscle.id)),
    )

    const equipmentOptions = allEquipments
        .filter((equipment) => equipment.isActive || heldEquipmentIds.has(equipment.id))
        .map((equipment) => ({ value: String(equipment.id), label: equipment.name }))

    const onValuesChange: ValuesChangeHandler<MovementValues> = (changed, values) => {
        const patch: Partial<MovementValues> = {}

        // The primary muscle is never a secondary one too: picking it takes it out of the secondaries.
        if (changed.primaryMuscleId !== undefined) {
            patch.secondaryMuscleIds = values.secondaryMuscleIds.filter((id) => id !== changed.primaryMuscleId)
        }

        // On a creation, the equipments suggest what a set records; the admin adjusts after. An edit
        // is left alone: what the movement records was decided already.
        if (editing === null && changed.equipmentIds !== undefined) {
            const chosen = allEquipments.filter((equipment) => values.equipmentIds.includes(String(equipment.id)))
            if (chosen.some((equipment) => equipment.hasWeight)) {
                patch.tracksWeight = true
            }
            if (chosen.some((equipment) => equipment.hasDistance)) {
                patch.tracksDistance = true
            }
        }

        return patch
    }

    return (
        <Page
            title="Mouvements"
            action={
                <Row gap={8}>
                    <Button onClick={() => setManagingFamilies(true)}>Gérer les familles</Button>
                    <Button variant="primary" onClick={() => setEditing(null)}>
                        Créer
                    </Button>
                </Row>
            }
        >
            <ListToolbar searchPlaceholder="Rechercher un mouvement" onSearch={setSearch} searchAsYouType>
                <ActiveFilter value={status.status} onChange={status.setStatus} />
                {/* Inactive ones included, every time: these narrow the list, they assign nothing. */}
                <FilterSelect<number>
                    placeholder="Famille"
                    value={movementFamilyId}
                    onChange={setMovementFamilyId}
                    options={(families.data ?? []).map((family) => ({ value: family.id, label: family.name }))}
                />
                <FilterSelect<number>
                    placeholder="Groupe"
                    value={muscleGroupId}
                    onChange={(next) => {
                        setMuscleGroupId(next)
                        // A muscle outside the new group would narrow the list to nothing.
                        if (next !== undefined && allMuscles.find((muscle) => muscle.id === muscleId)?.muscleGroupId !== next) {
                            setMuscleId(undefined)
                        }
                    }}
                    options={(groups.data ?? []).map((group) => ({ value: group.id, label: group.name }))}
                />
                <FilterSelect<number>
                    placeholder="Muscle"
                    value={muscleId}
                    onChange={setMuscleId}
                    options={allMuscles
                        .filter((muscle) => muscleGroupId === undefined || muscle.muscleGroupId === muscleGroupId)
                        .map((muscle) => ({ value: muscle.id, label: muscle.name }))}
                />
                <FilterSelect<number>
                    placeholder="Équipement"
                    value={equipmentId}
                    onChange={setEquipmentId}
                    options={allEquipments.map((equipment) => ({ value: equipment.id, label: equipment.name }))}
                />
            </ListToolbar>

            <DataTable<Movement>
                rows={rows}
                rowKey={(movement) => movement.id}
                loading={movements.isPending}
                emptyText="Aucun mouvement"
                columns={[
                    {
                        key: 'name',
                        title: 'Mouvement',
                        render: (movement) => (
                            <Stack gap={0}>
                                {movement.name}
                                <Row gap={8}>
                                    <Text muted size="small">
                                        {movement.movementFamilyName}
                                    </Text>
                                    {!movement.movementFamilyIsActive && <Tag colour="red">Famille désactivée</Tag>}
                                </Row>
                            </Stack>
                        ),
                    },
                    {
                        key: 'primary',
                        title: 'Muscle principal',
                        render: (movement) => <MuscleTag muscle={movement.primaryMuscle} />,
                    },
                    {
                        key: 'secondary',
                        title: 'Muscles secondaires',
                        render: (movement) =>
                            movement.secondaryMuscles.length === 0 ? (
                                <Text muted>Aucun</Text>
                            ) : (
                                <Row gap={4} wrap>
                                    {movement.secondaryMuscles.map((muscle) => (
                                        <MuscleTag key={muscle.id} muscle={muscle} />
                                    ))}
                                </Row>
                            ),
                    },
                    {
                        key: 'equipments',
                        title: 'Équipements',
                        render: (movement) =>
                            movement.equipments.length === 0 ? (
                                <Text muted>Poids du corps</Text>
                            ) : (
                                <Row gap={4} wrap>
                                    {movement.equipments.map((equipment) => (
                                        <EquipmentTag key={equipment.id} equipment={equipment} />
                                    ))}
                                </Row>
                            ),
                    },
                    {
                        key: 'status',
                        title: 'Statut',
                        width: 120,
                        render: (movement) => <StatusTag isActive={movement.isActive} />,
                    },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (movement) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(movement)}>
                                    Modifier
                                </Button>
                                {movement.isActive ? (
                                    <Button
                                        size="small"
                                        loading={setActive.isPending && setActive.variables?.id === movement.id}
                                        onClick={() => setActive.mutate({ id: movement.id, active: false })}
                                    >
                                        Désactiver
                                    </Button>
                                ) : (
                                    <Button size="small" onClick={() => setActive.mutate({ id: movement.id, active: true })}>
                                        Réactiver
                                    </Button>
                                )}
                                <ConfirmButton
                                    question="Supprimer ce mouvement ?"
                                    loading={remove.isPending && remove.variables === movement.id}
                                    onConfirm={() => remove.mutate(movement.id)}
                                >
                                    Supprimer
                                </ConfirmButton>
                            </Row>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <FormModal<MovementValues>
                    title={editing === null ? 'Nouveau mouvement' : 'Modifier le mouvement'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onValuesChange={onValuesChange}
                    onSubmit={(values) =>
                        save.mutate({
                            id: editing?.id ?? null,
                            payload: {
                                name: values.name,
                                description: blankToNull(values.description),
                                videoUrl: blankToNull(values.videoUrl),
                                movementFamilyId: Number(values.movementFamilyId),
                                primaryMuscleId: Number(values.primaryMuscleId),
                                secondaryMuscleIds: values.secondaryMuscleIds.map(Number),
                                equipmentIds: values.equipmentIds.map(Number),
                                tracksReps: values.tracksReps,
                                tracksWeight: values.tracksWeight,
                                tracksDuration: values.tracksDuration,
                                tracksDistance: values.tracksDistance,
                                isUnilateral: values.isUnilateral,
                            },
                        })
                    }
                    initialValues={initialValuesFor(editing)}
                >
                    <TextField
                        name="name"
                        label="Nom"
                        required
                        autoFocus
                        hint="Une variante d’équipement est un mouvement à part : « Développé couché (barre) »."
                    />
                    <SelectField
                        name="movementFamilyId"
                        label="Famille"
                        required
                        searchable
                        options={familyOptions}
                        hint="Seules les familles actives reçoivent un mouvement."
                    />
                    <SelectField name="primaryMuscleId" label="Muscle principal" required searchable options={muscleOptions} />
                    <SelectField
                        name="secondaryMuscleIds"
                        label="Muscles secondaires"
                        multiple
                        searchable
                        options={muscleOptions}
                    />
                    <SelectField
                        name="equipmentIds"
                        label="Équipements"
                        multiple
                        searchable
                        options={equipmentOptions}
                        hint="Aucun : le mouvement se fait au poids du corps."
                    />
                    <Text muted size="small">
                        Une série enregistre au moins des répétitions, une durée ou une distance ; la charge s’y ajoute.
                    </Text>
                    <Row gap={24} wrap>
                        <SwitchField name="tracksReps" label="Répétitions" />
                        <SwitchField name="tracksWeight" label="Charge" />
                        <SwitchField name="tracksDuration" label="Durée" />
                        <SwitchField name="tracksDistance" label="Distance" />
                        <SwitchField name="isUnilateral" label="Unilatéral" />
                    </Row>
                    <TextAreaField name="description" label="Description" />
                    <TextField name="videoUrl" label="Vidéo" placeholder="https://" />
                </FormModal>
            )}

            {managingFamilies && (
                <DetailDrawer title="Familles de mouvements" onClose={() => setManagingFamilies(false)}>
                    <MovementFamilyManager families={families.data ?? []} loading={families.isPending} />
                </DetailDrawer>
            )}
        </Page>
    )
}

function initialValuesFor(movement: Movement | null): MovementValues {
    if (movement === null) {
        // Nothing preselected on a creation: family and muscles are choices, not defaults.
        return {
            name: '',
            description: '',
            videoUrl: '',
            movementFamilyId: undefined,
            primaryMuscleId: undefined,
            secondaryMuscleIds: [],
            equipmentIds: [],
            tracksReps: false,
            tracksWeight: false,
            tracksDuration: false,
            tracksDistance: false,
            isUnilateral: false,
        }
    }

    return {
        name: movement.name,
        description: movement.description ?? '',
        videoUrl: movement.videoUrl ?? '',
        movementFamilyId: String(movement.movementFamilyId),
        primaryMuscleId: String(movement.primaryMuscle.id),
        secondaryMuscleIds: movement.secondaryMuscles.map((muscle) => String(muscle.id)),
        equipmentIds: movement.equipments.map((equipment) => String(equipment.id)),
        tracksReps: movement.tracksReps,
        tracksWeight: movement.tracksWeight,
        tracksDuration: movement.tracksDuration,
        tracksDistance: movement.tracksDistance,
        isUnilateral: movement.isUnilateral,
    }
}

function blankToNull(value: string | undefined): string | null {
    const trimmed = (value ?? '').trim()

    return trimmed === '' ? null : trimmed
}

/** Muscles under their group's name, groups in the order the API lists the muscles. */
function groupByMuscleGroup(muscles: Muscle[]): SelectOptionGroup[] {
    const byGroup = new Map<string, SelectOptionGroup>()
    for (const muscle of muscles) {
        const group = byGroup.get(muscle.muscleGroupName) ?? { label: muscle.muscleGroupName, options: [] }
        group.options.push({ value: String(muscle.id), label: muscle.name })
        byGroup.set(muscle.muscleGroupName, group)
    }

    return [...byGroup.values()]
}

/** A muscle on a row, red when it is no longer offered to new movements. */
function MuscleTag({ muscle }: { muscle: Muscle }) {
    return <Tag colour={isAvailable(muscle) ? 'default' : 'red'}>{muscle.name}</Tag>
}

function EquipmentTag({ equipment }: { equipment: Equipment }) {
    return <Tag colour={equipment.isActive ? 'default' : 'red'}>{equipment.name}</Tag>
}

/** The families, in the drawer: create, rename, deactivate, delete once empty. */
function MovementFamilyManager({ families, loading }: { families: MovementFamily[]; loading: boolean }) {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    const [editing, setEditing] = useState<MovementFamily | null | undefined>(undefined)

    // A family's name and status show on every movement row, so both lists are refreshed together.
    const refresh = () =>
        Promise.all([
            queryClient.invalidateQueries({ queryKey: ['movement-families'] }),
            queryClient.invalidateQueries({ queryKey: ['movements'] }),
        ])

    const save = useMutation({
        mutationFn: ({ id, name }: { id: number | null; name: string }) =>
            id === null ? api.createMovementFamily(name) : api.updateMovementFamily(id, name),
        onSuccess: async () => {
            setEditing(undefined)
            notify.success('Famille enregistrée.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’enregistrement a échoué.'),
    })

    const setActive = useMutation({
        mutationFn: ({ id, active }: { id: number; active: boolean }) =>
            active ? api.activateMovementFamily(id) : api.deactivateMovementFamily(id),
        onSuccess: async () => {
            notify.success('Famille mise à jour.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’opération a échoué.'),
    })

    const remove = useMutation({
        mutationFn: (id: number) => api.deleteMovementFamily(id),
        onSuccess: async () => {
            notify.success('Famille supprimée.')
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

            <DataTable<MovementFamily>
                rows={families}
                rowKey={(family) => family.id}
                loading={loading}
                emptyText="Aucune famille"
                columns={[
                    { key: 'name', title: 'Famille', render: (family) => family.name },
                    {
                        key: 'status',
                        title: 'Statut',
                        width: 110,
                        render: (family) => <StatusTag isActive={family.isActive} />,
                    },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (family) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(family)}>
                                    Renommer
                                </Button>
                                {family.isActive ? (
                                    <Button
                                        size="small"
                                        loading={setActive.isPending && setActive.variables?.id === family.id}
                                        onClick={() => setActive.mutate({ id: family.id, active: false })}
                                    >
                                        Désactiver
                                    </Button>
                                ) : (
                                    <Button size="small" onClick={() => setActive.mutate({ id: family.id, active: true })}>
                                        Réactiver
                                    </Button>
                                )}
                                <ConfirmButton
                                    question="Supprimer cette famille ?"
                                    loading={remove.isPending && remove.variables === family.id}
                                    onConfirm={() => remove.mutate(family.id)}
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
                    title={editing === null ? 'Nouvelle famille' : 'Renommer la famille'}
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
