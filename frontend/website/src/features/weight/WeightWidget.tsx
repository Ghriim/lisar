import { Scale } from 'lucide-react'
import { useState } from 'react'
import { Loader, Modal, SystemPanel } from '../../components'
import { formatWeight, formatWhen } from './weightFormat'
import { useLatestWeight } from './queries'
import { WeightPanel } from './WeightPanel'

/**
 * The last known weight on the line and the day it was recorded a size down beneath — not today's
 * unless today's is the last. A click anywhere on the widget opens the window.
 */
export function WeightWidget() {
    const latest = useLatestWeight()
    const [opened, setOpened] = useState(false)

    const recorded = latest.data?.weightInKilograms ?? null

    return (
        <>
            <SystemPanel
                ariaLabel="Pesée du jour"
                onActivate={latest.isSuccess ? () => setOpened(true) : undefined}
            >
                {latest.isPending && <Loader />}

                {latest.isSuccess && (
                    <div className="tracker">
                        <Scale size={45} strokeWidth={1.6} className="tracker-icon" aria-hidden />
                        <div className="tracker-body">
                            {recorded === null || latest.data.day === null ? (
                                <span className="tracker-note">Aucune pesée</span>
                            ) : (
                                <>
                                    <span className="tracker-value">{formatWeight(recorded)}</span>
                                    <div className="tracker-aux">
                                        {formatWhen(latest.data.day, latest.data.recordedAt, latest.data.isFromToday)}
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                )}
            </SystemPanel>

            {opened && latest.isSuccess && (
                <Modal title="Pesée du jour" onClose={() => setOpened(false)}>
                    <WeightPanel latest={latest.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </>
    )
}
