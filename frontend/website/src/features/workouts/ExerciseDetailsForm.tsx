import { useState } from 'react'
import { Alert, Button, DurationField, Form, FormActions, TextAreaField, useViolations } from '../../components'
import { durationField, failureOf, parseDuration } from './workoutFormat'

export interface ExerciseDetails {
    note: string | null
    restInSeconds: number | null
}

interface ExerciseDetailsFormProps {
    /** What the movement carries now: the form opens on it. */
    exercise: ExerciseDetails
    /** Sends the whole of it; resolves once saved, rejects with what the API refused. */
    save: (details: ExerciseDetails) => Promise<unknown>
    pending: boolean
    failure: unknown
    onDone: () => void
}

/**
 * What a movement carries in a workout or a routine: the rest the timer counts after each of its
 * sets, and a note — a seat height, a grip, a pain to watch. Either left empty is cleared.
 */
export function ExerciseDetailsForm({ exercise, save, pending, failure, onDone }: ExerciseDetailsFormProps) {
    const violations = useViolations(failure)

    const [rest, setRest] = useState(durationField(exercise.restInSeconds))
    const [note, setNote] = useState(exercise.note ?? '')

    const submit = async () => {
        try {
            await save({ note: note.trim() === '' ? null : note, restInSeconds: parseDuration(rest) })
            onDone()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    return (
        <Form onSubmit={submit}>
            <DurationField
                label="Repos (s ou m:ss)"
                errors={violations.for('restInSeconds')}
                placeholder="1:30"
                value={rest}
                autoFocus
                onChange={setRest}
            />

            <TextAreaField label="Note" errors={violations.for('note')} value={note} rows={4} maxLength={5000} onChange={setNote} />

            {violations.isGeneral && <Alert>{failureOf(failure) ?? ''}</Alert>}

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>
                <Button submit disabled={pending}>
                    {pending ? 'Enregistrer…' : 'Enregistrer'}
                </Button>
            </FormActions>
        </Form>
    )
}
