import { Scale } from 'lucide-react'
import { useState } from 'react'
import { Button, Modal, SystemPanel } from '../../components'
import { formatWeight, formatWhenShort } from './weightFormat'
import { useLatestWeight } from './queries'
import { WeightPanel } from './WeightPanel'

/** The last known weight and the day it was taken — not today's unless today's is the last. */
export function WeightWidget() {
    const latest = useLatestWeight()
    const [opened, setOpened] = useState(false)

    const recorded = latest.data?.weightInKilograms ?? null
    const day = latest.data?.day ?? null

    return (
        <>
            <SystemPanel className="tracker">
                <div className="tracker-head">
                    <Scale size={36} strokeWidth={1.4} className="tracker-icon" role="img" aria-label="Poids" />
                    <div className="tracker-body">
                        {recorded === null || day === null || latest.data === undefined ? (
                            <span className="tracker-word">Aucune pesée</span>
                        ) : (
                            <>
                                <span className="tracker-value">{formatWeight(recorded)}</span>
                                <span className="tracker-aux">{formatWhenShort(day, latest.data.recordedAt, latest.data.isFromToday)}</span>
                            </>
                        )}
                    </div>
                </div>

                <Button variant="secondary" disabled={!latest.isSuccess} onClick={() => setOpened(true)}>
                    Nouvelle pesée
                </Button>
            </SystemPanel>

            {opened && latest.isSuccess && (
                <Modal title="Pesée du jour" onClose={() => setOpened(false)}>
                    <WeightPanel latest={latest.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </>
    )
}
