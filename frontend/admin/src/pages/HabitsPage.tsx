import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import * as api from '../api/endpoints'
import { habitTrackerWord } from '../api/habitWords'
import { HABIT_SOURCES, HABIT_TRACKERS, type Habit, type HabitPayload } from '../api/types'
import {
    ActiveFilter,
    Button,
    ConfirmButton,
    DataTable,
    FormModal,
    CategoryIcon,
    ListToolbar,
    NumberField,
    Page,
    Row,
    SelectField,
    StatusTag,
    TextField,
    useActiveFilter,
    useNotifier,
} from '../components'

/** The form holds the category as a select value: its id written out, or nothing. */
type HabitFormValues = Omit<HabitPayload, 'categoryId'> & { categoryId: string | null }

const SOURCE_LABELS: Record<string, string> = { manual: 'Manuelle', tracker: 'Automatique (tracker)' }

export function HabitsPage() {
    const notify = useNotifier()
    const queryClient = useQueryClient()
    /** undefined: no window. null: creating. A habit: editing that one. */
    const [editing, setEditing] = useState<Habit | null | undefined>(undefined)
    const status = useActiveFilter()

    const habits = useQuery({
        queryKey: ['habits', status.status],
        queryFn: () => api.fetchHabits(status.isActive),
    })

    // Only the common ones: the catalogue is everyone's, a personal label has no place on it.
    const categories = useQuery({ queryKey: ['categories'], queryFn: api.fetchCategories })

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
            <ListToolbar>
                <ActiveFilter value={status.status} onChange={status.setStatus} gender="feminine" />
            </ListToolbar>

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
                                {habit.category?.icon != null && <CategoryIcon code={habit.category.icon} />}
                                {habit.name}
                            </Row>
                        ),
                    },
                    {
                        key: 'category',
                        title: 'Catégorie',
                        render: (habit) => habit.category?.label ?? '—',
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
                <FormModal<HabitFormValues>
                    title={editing === null ? 'Nouvelle habitude' : 'Modifier l’habitude'}
                    submitLabel="Enregistrer"
                    pending={save.isPending}
                    onCancel={() => setEditing(undefined)}
                    onSubmit={(values) =>
                        save.mutate({
                            id: editing?.id ?? null,
                            payload: {
                                name: values.name,
                                sourceKind: values.sourceKind,
                                trackerKind: values.trackerKind ?? null,
                                trackerThreshold: values.trackerThreshold ?? null,
                                categoryId: values.categoryId ? Number(values.categoryId) : null,
                            },
                        })
                    }
                    initialValues={{
                        name: editing?.name ?? '',
                        sourceKind: editing?.sourceKind ?? HABIT_SOURCES[0],
                        trackerKind: editing?.trackerKind ?? null,
                        trackerThreshold: editing?.trackerThreshold ?? null,
                        categoryId: editing?.category ? String(editing.category.id) : null,
                    }}
                >
                    <TextField name="name" label="Nom" isMandatory autoFocus />

                    <SelectField
                        name="categoryId"
                        label="Catégorie"
                        hint="Son icône est celle de l’habitude."
                        placeholder="Aucune"
                        options={(categories.data ?? []).map((category) => ({
                            value: String(category.id),
                            label: category.label,
                        }))}
                    />

                    <SelectField
                        name="sourceKind"
                        label="Complétion"
                        isMandatory
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
