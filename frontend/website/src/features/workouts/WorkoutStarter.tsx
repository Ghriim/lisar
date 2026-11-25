import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Alert, Button, Form, FormActions, SystemPanel, TextField, useViolations } from '../../components'
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

    const submit = async () => {
        // Awaited rather than passed to mutate(): storing the new workout swaps this form for its
        // overview, and mutate() drops the callbacks of a component that is gone by then.
        try {
            const workout = await start.mutateAsync(name.trim() === '' ? null : name.trim())
            void navigate(`/workouts/${workout.id}`)
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    return (
        <SystemPanel title="Nouvelle séance">
            <Form onSubmit={submit}>
                <TextField
                    label="Nom"
                    errors={violations.for('name')}
                    value={name}
                    maxLength={128}
                    placeholder="Push, jambes, footing…"
                    onChange={setName}
                />

                {violations.for('name').length === 0 && start.error !== null && <Alert>{failureOf(start.error) ?? ''}</Alert>}

                <FormActions>
                    <Button submit disabled={start.isPending}>
                        {start.isPending ? 'Démarrer…' : 'Démarrer'}
                    </Button>
                </FormActions>
            </Form>
        </SystemPanel>
    )
}
