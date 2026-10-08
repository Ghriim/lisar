import { type FormEvent, useState } from 'react'
import type { WorkoutExercise } from '../../api/types'
import { Alert, Button, Field, FormActions, TextArea, TextInput, useViolations } from '../../components'
import { useUpdateWorkoutExercise } from './queries'
import { durationField, failureOf, parseDuration } from './workoutFormat'

interface ExerciseDetailsFormProps {
    workoutId: number
    exercise: WorkoutExercise
    onDone: () => void
}

/**
 * What a movement carries in this workout: the rest the timer counts after each of its sets, and a
 * note — a seat height, a grip, a pain to watch. Either left empty is cleared.
 */
export function ExerciseDetailsForm({ workoutId, exercise, onDone }: ExerciseDetailsFormProps) {
    const update = useUpdateWorkoutExercise()
    const violations = useViolations(update.error)

    const [rest, setRest] = useState(durationField(exercise.restInSeconds))
    const [note, setNote] = useState(exercise.note ?? '')

    const submit = async (event: FormEvent) => {
        event.preventDefault()

        try {
            await update.mutateAsync({
                id: workoutId,
                exerciseId: exercise.id,
                note: note.trim() === '' ? null : note,
                restInSeconds: parseDuration(rest),
            })
            onDone()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    return (
        <form className="form-grid" onSubmit={(event) => void submit(event)}>
            <Field label="Repos (s ou m:ss, facultatif)" errors={violations.for('restInSeconds')}>
                <TextInput
                    inputMode="numeric"
                    placeholder="1:30"
                    value={rest}
                    autoFocus
                    onChange={(event) => setRest(event.target.value)}
                />
            </Field>

            <Field label="Note (facultatif)" errors={violations.for('note')}>
                <TextArea value={note} rows={4} maxLength={5000} onChange={(event) => setNote(event.target.value)} />
            </Field>

            {violations.isGeneral && <Alert>{failureOf(update.error) ?? ''}</Alert>}

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>
                <Button submit disabled={update.isPending}>
                    {update.isPending ? 'Enregistrer…' : 'Enregistrer'}
                </Button>
            </FormActions>
        </form>
    )
}
