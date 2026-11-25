import { useState } from 'react'
import type { SleepNight } from '../../api/types'
import { Button, Form, FormActions, FormGrid, RatingField, Stack, TimeField, useViolations } from '../../components'
import { useSaveSleepNight } from './queries'
import { timeOf } from './sleepFormat'
import { SLEEP_MOODS } from './sleepMoods'

/**
 * Two times and a face. The fields are native time inputs, which hand back exactly the `HH:MM`
 * the API asks for — no parsing, and the phone offers its own picker.
 *
 * Nothing is prefilled on a night not yet noted: unlike a weight, a night has no previous value
 * of its own to correct.
 */
export function SleepPanel({ night, onClose }: { night: SleepNight; onClose: () => void }) {
    const save = useSaveSleepNight()
    const violations = useViolations(save.error)

    const [bedtime, setBedtime] = useState(timeOf(night.bedtimeAt))
    const [wakeUpTime, setWakeUpTime] = useState(timeOf(night.wakeUpAt))
    const [moodRating, setMoodRating] = useState<number | null>(night.moodRating)

    const submit = async () => {
        try {
            await save.mutateAsync({ bedtime, wakeUpTime, moodRating })
            onClose()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    return (
        <Stack>
            <p className="tracker-note" style={{ margin: 0 }}>
                {night.wakeUpAt === null
                    ? 'La nuit est notée pour ce matin. Un coucher plus tard dans la journée que le lever, c’est la veille au soir.'
                    : 'Nuit déjà notée pour ce matin. L’enregistrer à nouveau la corrige.'}
            </p>

            <Form onSubmit={submit}>
                <FormGrid columns={2}>
                    <TimeField label="Coucher" isMandatory errors={violations.for('bedtime')} value={bedtime} onChange={setBedtime} />

                    <TimeField label="Lever" isMandatory errors={violations.for('wakeUpTime')} value={wakeUpTime} onChange={setWakeUpTime} />
                </FormGrid>

                <RatingField
                    label="Humeur au lever"
                    levels={SLEEP_MOODS}
                    value={moodRating}
                    onChange={setMoodRating}
                    errors={violations.for('moodRating')}
                    disabled={save.isPending}
                />

                <FormActions>
                    <Button variant="quiet" onClick={onClose}>
                        Annuler
                    </Button>
                    <Button variant="primary" submit disabled={save.isPending}>
                        {save.isPending ? 'Enregistrer…' : 'Enregistrer'}
                    </Button>
                </FormActions>
            </Form>
        </Stack>
    )
}
