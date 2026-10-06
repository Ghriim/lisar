import { type FormEvent, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Alert, Button, Field, FormActions, SystemPanel, TextInput, useViolations } from '../../components'
import { useStartWorkout } from './queries'
import { failureOf } from './workoutFormat'

/**
 * Starts a workout now, empty, and goes to its page: that is where it is logged. The name can
 * wait — without one, the workout carries its day. Nothing else is asked: a workout is logged as
 * it happens, not filled in beforehand.
 */
export function WorkoutStarter() {
    const start = useStartWorkout()
    const violations = useViolations(start.error)
    const [name, setName] = useState('')
    const navigate = useNavigate()

    const submit = (event: FormEvent) => {
        event.preventDefault()
        start.mutate(name.trim() === '' ? null : name.trim(), {
            onSuccess: (workout) => void navigate(`/workouts/${workout.id}`),
        })
    }

    return (
        <SystemPanel title="Nouvelle séance">
            <form className="form-grid" onSubmit={submit}>
                <Field label="Nom (facultatif)" errors={violations.for('name')}>
                    <TextInput
                        value={name}
                        maxLength={128}
                        placeholder="Push, jambes, footing…"
                        onChange={(event) => setName(event.target.value)}
                    />
                </Field>

                {violations.for('name').length === 0 && start.error !== null && <Alert>{failureOf(start.error) ?? ''}</Alert>}

                <FormActions>
                    <Button submit disabled={start.isPending}>
                        {start.isPending ? 'Démarrer…' : 'Démarrer'}
                    </Button>
                </FormActions>
            </form>
        </SystemPanel>
    )
}
