import { BedDouble } from 'lucide-react'
import { useState } from 'react'
import { Loader, Modal, SystemPanel } from '../../components'
import { SleepPanel } from './SleepPanel'
import { useSleepToday } from './queries'
import { formatDuration, timeOf } from './sleepFormat'
import { moodFor } from './sleepMoods'

/**
 * This morning's night, or nothing. Its length on the line, how one woke and the hours it spanned
 * a size down beneath — a click anywhere on the widget opens the window.
 */
export function SleepWidget() {
    const today = useSleepToday()
    const [opened, setOpened] = useState(false)

    const noted = today.data?.durationInMinutes ?? null
    const rating = today.data?.moodRating ?? null
    const mood = rating === null ? undefined : moodFor(rating)

    return (
        <>
            <SystemPanel
                ariaLabel="Nuit dernière"
                onActivate={today.isSuccess ? () => setOpened(true) : undefined}
            >
                {today.isPending && <Loader />}

                {today.isSuccess && (
                    <div className="tracker">
                        <BedDouble size={45} strokeWidth={1.6} className="tracker-icon" aria-hidden />
                        <div className="tracker-body">
                            {noted === null ? (
                                <span className="tracker-note">Nuit non notée</span>
                            ) : (
                                <>
                                    <span className="tracker-value">{formatDuration(noted)}</span>
                                    <div className="tracker-aux">
                                        {mood !== undefined && (
                                            <span className="tooltip-host" data-tooltip={mood.label}>
                                                <mood.icon
                                                    size={18}
                                                    strokeWidth={1.8}
                                                    className="tracker-mood"
                                                    aria-label={mood.label}
                                                />
                                            </span>
                                        )}
                                        <span>
                                            {timeOf(today.data.bedtimeAt)} → {timeOf(today.data.wakeUpAt)}
                                        </span>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                )}
            </SystemPanel>

            {opened && today.isSuccess && (
                <Modal title="Nuit dernière" onClose={() => setOpened(false)}>
                    <SleepPanel night={today.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </>
    )
}
