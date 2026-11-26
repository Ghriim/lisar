import { Pencil, Scale } from 'lucide-react'
import { useState } from 'react'
import { IconButton, MenuButton, Modal, SystemPanel } from '../../components'
import { formatWeight, formatWhenShort } from './weightFormat'
import { useLatestWeight } from './queries'
import { WeightPanel } from './WeightPanel'
import { weightWeekTrend } from './weightWeekTrend'

/**
 * The last known weight and the day it was taken — not today's unless today's is the last — with
 * how far it moved in a week under it.
 */
export function WeightWidget() {
    const latest = useLatestWeight()
    const [opened, setOpened] = useState(false)

    const recorded = latest.data?.weightInKilograms ?? null
    const day = latest.data?.day ?? null
    const trend = latest.data === undefined ? null : weightWeekTrend(latest.data)

    return (
        <>
            <SystemPanel className="tracker">
                <MenuButton label="Options" subject="poids" items={[{ label: 'Historique' }, { label: 'Réglages' }]} />
                <IconButton icon={Scale} badge={Pencil} hero label="Noter" subject="une pesée" disabled={!latest.isSuccess} onClick={() => setOpened(true)} />
                <div className="tracker-body">
                    <div className="tracker-reading">
                        {recorded === null || day === null || latest.data === undefined ? (
                            <span className="tracker-word">Aucune pesée</span>
                        ) : (
                            <>
                                <span className="tracker-value">{formatWeight(recorded)}</span>
                                <span className="tracker-aux">{formatWhenShort(day, latest.data.recordedAt, latest.data.isFromToday)}</span>
                            </>
                        )}
                    </div>
                    <span className="tracker-trend">
                        <span>7 j</span>
                        <span className="tracker-trend-value tracker-trend-accent">{trend === null ? '—' : formatTrend(trend)}</span>
                    </span>
                </div>
            </SystemPanel>

            {opened && latest.isSuccess && (
                <Modal title="Pesée du jour" onClose={() => setOpened(false)}>
                    <WeightPanel latest={latest.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </>
    )
}

/** « −0,3 kg », « +1,2 kg »: signed, since the direction is the point. */
function formatTrend(kilograms: number): string {
    const sign = kilograms < 0 ? '−' : '+'

    return `${sign}${Math.abs(kilograms).toFixed(1).replace('.', ',')} kg`
}
