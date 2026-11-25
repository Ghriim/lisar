import { useState } from 'react'
import type { Workout } from '../../api/types'
import {
    Alert,
    Button,
    Form,
    FormActions,
    RatingField,
    TextAreaField,
    TextField,
    useViolations,
} from '../../components'
import { useUpdateWorkout } from './queries'
import { WORKOUT_FEELINGS } from './workoutFeelings'
import { failureOf, workoutName } from './workoutFormat'

/**
 * What a person says about a workout: a name, a note, how it felt. The whole of it is sent every
 * time, so the form opens on what is there. The two moments are not part of it — they are never
 * rewritten.
 */
export function WorkoutDetailsForm({ workout, onDone }: { workout: Workout; onDone: () => void }) {
    const update = useUpdateWorkout()
    const violations = useViolations(update.error)

    const [name, setName] = useState(workout.name ?? '')
    const [note, setNote] = useState(workout.note ?? '')
    const [feeling, setFeeling] = useState<number | null>(workout.feeling)

    const submit = async () => {
        try {
            await update.mutateAsync({
                id: workout.id,
                name: name.trim() === '' ? null : name.trim(),
                note: note.trim() === '' ? null : note,
                feeling,
            })
            onDone()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    return (
        <Form onSubmit={submit}>
            <TextField
                label="Nom"
                errors={violations.for('name')}
                value={name}
                // The name it carries without one, so leaving it empty is a visible choice.
                placeholder={workoutName({ name: null, startedAt: workout.startedAt })}
                maxLength={128}
                onChange={setName}
            />

            <RatingField
                label="Ressenti"
                levels={WORKOUT_FEELINGS}
                value={feeling}
                onChange={setFeeling}
                errors={violations.for('feeling')}
                disabled={update.isPending}
            />

            <TextAreaField label="Note" errors={violations.for('note')} value={note} rows={4} maxLength={5000} onChange={setNote} />

            {violations.isGeneral && <Alert>{failureOf(update.error) ?? ''}</Alert>}

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>
                <Button submit disabled={update.isPending}>
                    {update.isPending ? 'Enregistrer…' : 'Enregistrer'}
                </Button>
            </FormActions>
        </Form>
    )
}
