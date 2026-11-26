import { BedDouble } from 'lucide-react'
import { useState } from 'react'
import { Button, Modal, SystemPanel, Tooltip } from '../../components'
import { SleepPanel } from './SleepPanel'
import { useSleepToday } from './queries'
import { formatDuration, nightOf } from './sleepFormat'
import { moodFor } from './sleepMoods'

/** This morning's night, or « Non notée ». Rating it is the card's main action. */
export function SleepWidget() {
    const today = useSleepToday()
    const [opened, setOpened] = useState(false)

    const noted = today.data?.durationInMinutes ?? null
    const rating = today.data?.moodRating ?? null
    const mood = rating === null ? undefined : moodFor(rating)

    return (
        <>
            <SystemPanel className="tracker">
                <div className="tracker-head">
                    <BedDouble size={36} strokeWidth={1.4} className="tracker-icon" role="img" aria-label="Sommeil" />
                    <div className="tracker-body">
                        {noted === null ? (
                            <span className="tracker-word">Non notée</span>
                        ) : (
                            <span className="tracker-value">
                                {formatDuration(noted)}
                                {mood !== undefined && (
                                    <Tooltip label={mood.label}>
                                        <mood.icon size={20} strokeWidth={1.6} aria-label={mood.label} />
                                    </Tooltip>
                                )}
                            </span>
                        )}
                        {today.data !== undefined && <span className="tracker-aux">{nightOf(today.data.day)}</span>}
                    </div>
                </div>

                <Button disabled={!today.isSuccess} onClick={() => setOpened(true)}>
                    Noter ma nuit
                </Button>
            </SystemPanel>

            {opened && today.isSuccess && (
                <Modal title="Nuit dernière" onClose={() => setOpened(false)}>
                    <SleepPanel night={today.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </>
    )
}
