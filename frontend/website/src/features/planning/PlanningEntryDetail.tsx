import { Pencil, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Alert, Button, ConfirmDialog, DefinitionList, LinkChip, Row, Stack } from '../../components'
import { useTodos } from '../todos/queries'
import { failureOf } from '../workouts/workoutFormat'
import { formatRule, formatTimeSpan, parseDay } from './planningFormat'
import { type KindedEntry, subjectOf } from './planningKinds'
import { useDeleteHabitPlanningEntry, useDeleteTaskPlanningEntry, useDeleteTrainingPlanningEntry } from './queries'

const DAY = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })

interface PlanningEntryDetailProps {
    kinded: KindedEntry
    /** A habit's only entry: it is changed, never deleted — a kept habit always has a planning. */
    isLastOfHabit?: boolean
    onEdit: () => void
    onDeleted: () => void
}

/** An entry read before it is changed: every field, filled in or not. */
export function PlanningEntryDetail({ kinded, isLastOfHabit = false, onEdit, onDeleted }: PlanningEntryDetailProps) {
    const todoTitles = new Map((useTodos().data ?? []).map((todo) => [todo.id, todo.title]))
    const removeTraining = useDeleteTrainingPlanningEntry()
    const removeTask = useDeleteTaskPlanningEntry()
    const removeHabit = useDeleteHabitPlanningEntry()
    const remove = { training: removeTraining, task: removeTask, habit: removeHabit }[kinded.kind]
    const [confirming, setConfirming] = useState(false)
    const { schedule } = kinded.entry
    const subject = subjectOf(kinded)

    return (
        <Stack>
            <DefinitionList
                items={[
                    ...subjectItems(kinded, todoTitles),
                    { label: 'Quand', placeholder: '—', value: formatRule(schedule) },
                    {
                        label: schedule.rule === 'once' ? 'Date' : 'À partir du',
                        placeholder: '—',
                        width: 'half',
                        value: DAY.format(parseDay(schedule.startDate)),
                    },
                    {
                        label: 'Jusqu’au',
                        placeholder: schedule.rule === 'once' ? '—' : 'Sans fin',
                        width: 'half',
                        value: schedule.endDate === null ? null : DAY.format(parseDay(schedule.endDate)),
                    },
                    { label: 'Horaire', placeholder: 'Dans la journée', value: formatTimeSpan(schedule) },
                ]}
            />

            {remove.error !== null && <Alert>{failureOf(remove.error) ?? ''}</Alert>}

            <Row style={{ justifyContent: 'center' }}>
                {!isLastOfHabit && (
                    <Button variant="danger" icon={Trash2} disabled={remove.isPending} onClick={() => setConfirming(true)}>
                        Supprimer
                    </Button>
                )}
                <Button icon={Pencil} onClick={onEdit}>
                    Modifier
                </Button>
            </Row>

            {confirming && (
                <ConfirmDialog
                    title="Supprimer l’entrée"
                    question={
                        {
                            training: `« ${subject} » ne sera plus planifiée : ses dates à venir disparaissent. Les séances déjà faites restent.`,
                            task: `« ${subject} » ne sera plus planifiée : ses dates à venir disparaissent, avec une tâche déjà créée pour une date à venir et pas encore faite. Les tâches d’aujourd’hui et d’avant restent dans la liste.`,
                            habit: `Ces jours de « ${subject} » ne seront plus planifiés : leurs dates à venir disparaissent. Les jours déjà tenus restent.`,
                        }[kinded.kind]
                    }
                    confirmLabel="Supprimer"
                    onCancel={() => setConfirming(false)}
                    onConfirm={() => {
                        setConfirming(false)
                        remove.mutate(kinded.entry.id, { onSuccess: onDeleted })
                    }}
                />
            )}
        </Stack>
    )
}

/** What the entry ties: its routine, its task model, or its habit. */
function subjectItems(kinded: KindedEntry, todoTitles: Map<number, string>) {
    switch (kinded.kind) {
        case 'training':
            return [{ label: 'Routine', placeholder: '—', value: <LinkChip to={`/routines/${kinded.entry.routineId}`}>{kinded.entry.routineName}</LinkChip> }]
        case 'task':
            return [
                { label: 'Intitulé', placeholder: '—', value: kinded.entry.title },
                { label: 'Détail', placeholder: 'Aucun', value: kinded.entry.description },
                { label: 'TODO', placeholder: '—', value: todoTitles.get(kinded.entry.todoId) ?? null },
                { label: 'Catégorie', placeholder: 'Aucune', value: kinded.entry.category?.label ?? null },
            ]
        case 'habit':
            return [{ label: 'Habitude', placeholder: '—', value: kinded.entry.habitName }]
    }
}
