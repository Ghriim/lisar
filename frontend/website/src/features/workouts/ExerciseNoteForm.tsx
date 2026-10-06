import { type FormEvent, useState } from 'react'
import type { WorkoutExercise } from '../../api/types'
import { Alert, Button, Field, FormActions, TextArea, useViolations } from '../../components'
import { useUpdateWorkoutExercise } from './queries'
import { failureOf } from './workoutFormat'

interface ExerciseNoteFormProps {
    workoutId: number
    exercise: WorkoutExercise
    onDone: () => void
}

/** The note a movement carries in this workout: a seat height, a grip, a pain to watch. */
export function ExerciseNoteForm({ workoutId, exercise, onDone }: ExerciseNoteFormProps) {
    const update = useUpdateWorkoutExercise()
    const violations = useViolations(update.error)

    const [note, setNote] = useState(exercise.note ?? '')

    const submit = async (event: FormEvent) => {
        event.preventDefault()

        try {
            await update.mutateAsync({
                id: workoutId,
                exerciseId: exercise.id,
                note: note.trim() === '' ? null : note,
            })
            onDone()
        } catch {
            // The violations are on the mutation, and the field below reads them.
        }
    }

    return (
        <form className="form-grid" onSubmit={(event) => void submit(event)}>
            <Field label="Note" errors={violations.for('note')}>
                <TextArea
                    value={note}
                    rows={4}
                    maxLength={5000}
                    autoFocus
                    onChange={(event) => setNote(event.target.value)}
                />
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
