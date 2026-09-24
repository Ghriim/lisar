import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import { habitIconWord, habitTrackerWord } from '../api/habitWords'
import { HABIT_ICONS, HABIT_SOURCES, HABIT_TRACKERS, type Habit, type HabitPayload } from '../api/types'
import {
    Button,
    ConfirmButton,
    DataTable,
    FormModal,
    HabitIcon,
    NumberField,
    Page,
    Paragraph,
    Row,
    SelectField,
    StatusTag,
    TextField,
    useNotifier,
} from '../components'

const SOURCE_LABELS: Record<string, string> = { manual: 'Manuelle', tracker: 'Automatique (tracker)' }

export function HabitsPage() {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    /** undefined: no window. null: creating. A habit: editing that one. */
    const [editing, setEditing] = useState<Habit | null | undefined>(undefined)

    const habits = useQuery({ queryKey: ['habits'], queryFn: api.fetchHabits })

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['habits'] })

    const save = useMutation({
        mutationFn: ({ id, payload }: { id: number | null; payload: HabitPayload }) =>
            id === null ? api.createHabit(payload) : api.updateHabit(id, payload),
        onSuccess: async () => {
            setEditing(undefined)
            notify.success('Habitude enregistrée.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’enregistrement a échoué.'),
    })

    const setActive = useMutation({
        mutationFn: ({ id, active }: { id: number; active: boolean }) =>
            active ? api.activateHabit(id) : api.deactivateHabit(id),
        onSuccess: async () => {
            notify.success('Catalogue mis à jour.')
            await refresh()
        },
        onError: (failure) => notify.failure(failure, 'L’opération a échoué.'),
    })

    return (
        <Page
            title="Habitudes"
            action={
                <Button variant="primary" onClick={() => setEditing(null)}>
                    Créer
                </Button>
            }
        >
            <Paragraph muted>
                Le catalogue que les gens suivent. Retirer une habitude la désactive sans effacer ce
                qui a déjà été tenu : les séries déjà acquises lui survivent.
            </Paragraph>

            <DataTable<Habit>
                rows={habits.data ?? []}
                rowKey={(habit) => habit.id}
                loading={habits.isPending}
                emptyText="Aucune habitude"
                columns={[
                    {
                        key: 'name',
                        title: 'Habitude',
                        render: (habit) => (
                            <Row gap={8}>
                                <HabitIcon code={habit.icon} />
                                {habit.name}
                            </Row>
                        ),
                    },
                    {
                        key: 'source',
                        title: 'Complétion',
                        render: (habit) =>
                            habit.sourceKind === 'tracker'
                                ? `Auto · ${habitTrackerWord(habit.trackerKind ?? '')} ≥ ${habit.trackerThreshold ?? ''}`
                                : 'Manuelle',
                    },
                    {
                        key: 'status',
                        title: 'Statut',
                        width: 120,
                        render: (habit) => <StatusTag isActive={habit.isActive} />,
                    },
                    {
                        key: 'actions',
                        title: '',
                        align: 'right',
                        render: (habit) => (
                            <Row gap={8} justify="end">
                                <Button size="small" onClick={() => setEditing(habit)}>
                                    Modifier
                                </Button>
                                {habit.isActive ? (
                                    <ConfirmButton
                                        question="Retirer cette habitude du catalogue ?"
                                        loading={setActive.isPending && setActive.variables?.id === habit.id}
                                        onConfirm={() => setActive.mutate({ id: habit.id, active: false })}
                                    >
                                        Retirer
                                    </ConfirmButton>
                                ) : (
                                    <Button size="small" onClick={() => setActive.mutate({ id: habit.id, active: true })}>
                                        Réactiver
                                    </Button>
                                )}
                            </Row>
                        ),
                    },
                ]}
            />

            {editing !== undefined && (
                <FormModal<HabitPayload>
                    title={editing === null ? 'Nouvelle habitude' : 'Modifier l’habitude'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={(values) =>
                        save.mutate({
                            id: editing?.id ?? null,
                            payload: {
                                name: values.name,
                                icon: values.icon,
                                sourceKind: values.sourceKind,
                                trackerKind: values.trackerKind ?? null,
                                trackerThreshold: values.trackerThreshold ?? null,
                            },
                        })
                    }
                    initialValues={{
                        name: editing?.name ?? '',
                        icon: editing?.icon ?? HABIT_ICONS[0],
                        sourceKind: editing?.sourceKind ?? HABIT_SOURCES[0],
                        trackerKind: editing?.trackerKind ?? null,
                        trackerThreshold: editing?.trackerThreshold ?? null,
                    }}
                >
                    <TextField name="name" label="Nom" required autoFocus />

                    <SelectField
                        name="icon"
                        label="Icône"
                        required
                        options={HABIT_ICONS.map((code) => ({
                            value: code,
                            label: (
                                <Row gap={8}>
                                    <HabitIcon code={code} size={16} />
                                    {habitIconWord(code)}
                                </Row>
                            ),
                        }))}
                    />

                    <SelectField
                        name="sourceKind"
                        label="Complétion"
                        required
                        options={HABIT_SOURCES.map((code) => ({ value: code, label: SOURCE_LABELS[code] }))}
                    />

                    <SelectField
                        name="trackerKind"
                        label="Tracker (si automatique)"
                        hint="Ignoré pour une habitude manuelle."
                        options={HABIT_TRACKERS.map((code) => ({ value: code, label: habitTrackerWord(code) }))}
                    />

                    <NumberField
                        name="trackerThreshold"
                        label="Seuil à atteindre (si automatique)"
                        min={1}
                        max={1000000}
                        hint="La valeur que le tracker doit atteindre dans la journée."
                    />
                </FormModal>
            )}
        </Page>
    )
}
