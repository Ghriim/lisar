import { BedDouble, Pencil, Plus } from 'lucide-react'
import { useState } from 'react'
import { IconButton, Loader, Modal, Row, SystemPanel } from '../../components'
import { SleepPanel } from './SleepPanel'
import { useSleepToday } from './queries'
import { formatDuration } from './sleepFormat'
import { moodFor } from './sleepMoods'

/**
 * This morning's night, or nothing.
 *
 * Deliberately not the last night noted, which is what the weight widget does with the last
 * weight: a weight persists, a night does not. Yesterday's seven hours say nothing about how
 * someone is today.
 */
export function SleepWidget() {
    const today = useSleepToday()
    const [opened, setOpened] = useState(false)

    const noted = today.data?.durationInMinutes ?? null
    const rating = today.data?.moodRating ?? null
    const mood = rating === null ? undefined : moodFor(rating)
    const action = noted === null ? { icon: Plus, label: 'Noter' } : { icon: Pencil, label: 'Corriger' }

    return (
        <SystemPanel
            title="Sommeil"
            actions={
                today.isSuccess && (
                    <IconButton icon={action.icon} label={action.label} onClick={() => setOpened(true)} />
                )
            }
        >
            {today.isPending && <Loader />}

            {today.isSuccess && (
                <Row>
                    <BedDouble size={22} strokeWidth={1.8} aria-hidden />
                    {noted === null ? (
                        <span className="tracker-note">Nuit non notée</span>
                    ) : (
                        <span className="tracker-value">
                            {formatDuration(noted)}
                            {mood !== undefined && (
                                <span className="tooltip-host" data-tooltip={mood.label}>
                                    <mood.icon
                                        size={20}
                                        strokeWidth={1.8}
                                        className="tracker-mood"
                                        aria-label={mood.label}
                                    />
                                </span>
                            )}
                        </span>
                    )}
                </Row>
            )}

            {opened && today.isSuccess && (
                <Modal title="Nuit dernière" onClose={() => setOpened(false)}>
                    <SleepPanel night={today.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </SystemPanel>
    )
}
