import { BedDouble, Pencil } from 'lucide-react'
import { useState } from 'react'
import { IconButton, MenuButton, Modal, SystemPanel, Tooltip } from '../../components'
import { SleepPanel } from './SleepPanel'
import { useSleepToday } from './queries'
import { formatDuration, nightOf } from './sleepFormat'
import { moodFor } from './sleepMoods'
import { sleepWeekAverage } from './sleepWeekAverage'

/** This morning's night, or « Non notée », and the week's average under it. Noting it is the card's main action. */
export function SleepWidget() {
    const today = useSleepToday()
    const [opened, setOpened] = useState(false)

    const noted = today.data?.durationInMinutes ?? null
    const rating = today.data?.moodRating ?? null
    const mood = rating === null ? undefined : moodFor(rating)
    const average = today.data === undefined ? null : sleepWeekAverage(today.data)

    return (
        <>
            <SystemPanel className="tracker">
                <MenuButton label="Options" subject="sommeil" items={[{ label: 'Historique' }, { label: 'Réglages' }]} />
                <IconButton icon={BedDouble} badge={Pencil} hero label="Noter" subject="ma nuit" disabled={!today.isSuccess} onClick={() => setOpened(true)} />
                <div className="tracker-body">
                    <div className="tracker-reading">
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
                    <span className="tracker-trend">
                        <span>Moy. 7 j</span>
                        <span className="tracker-trend-value">{average === null ? '—' : formatDuration(average)}</span>
                    </span>
                </div>
            </SystemPanel>

            {opened && today.isSuccess && (
                <Modal title="Nuit dernière" onClose={() => setOpened(false)}>
                    <SleepPanel night={today.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </>
    )
}
