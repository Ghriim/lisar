import { Pencil, Trash2 } from 'lucide-react'
import { useState } from 'react'
import type { TrainingPlanningEntry } from '../../api/types'
import { Alert, Button, ConfirmDialog, DefinitionList, LinkChip, Row, Stack } from '../../components'
import { failureOf } from '../workouts/workoutFormat'
import { formatRule, formatTimeSpan, parseDay } from './planningFormat'
import { useDeleteTrainingPlanningEntry } from './queries'

const DAY = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })

interface TrainingEntryDetailProps {
    entry: TrainingPlanningEntry
    onEdit: () => void
    onDeleted: () => void
}

/** A training entry read before it is changed: every field, filled in or not. */
export function TrainingEntryDetail({ entry, onEdit, onDeleted }: TrainingEntryDetailProps) {
    const remove = useDeleteTrainingPlanningEntry()
    const [confirming, setConfirming] = useState(false)
    const { schedule } = entry

    return (
        <Stack>
            <DefinitionList
                items={[
                    { label: 'Routine', placeholder: '—', value: <LinkChip to={`/routines/${entry.routineId}`}>{entry.routineName}</LinkChip> },
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
                <Button variant="danger" icon={Trash2} disabled={remove.isPending} onClick={() => setConfirming(true)}>
                    Supprimer
                </Button>
                <Button icon={Pencil} onClick={onEdit}>
                    Modifier
                </Button>
            </Row>

            {confirming && (
                <ConfirmDialog
                    title="Supprimer l’entrée"
                    question={`« ${entry.routineName} » ne sera plus planifiée : ses dates à venir disparaissent. Les séances déjà faites restent.`}
                    confirmLabel="Supprimer"
                    onCancel={() => setConfirming(false)}
                    onConfirm={() => {
                        setConfirming(false)
                        remove.mutate(entry.id, { onSuccess: onDeleted })
                    }}
                />
            )}
        </Stack>
    )
}
