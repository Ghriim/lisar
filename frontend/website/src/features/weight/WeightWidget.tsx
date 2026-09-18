import { Pencil, Plus, Scale } from 'lucide-react'
import { useState } from 'react'
import { IconButton, Loader, Modal, Row, SystemPanel } from '../../components'
import { formatWeight, formatWhen } from './weightFormat'
import { useLatestWeight } from './queries'
import { WeightPanel } from './WeightPanel'

/**
 * The last known weight and the day it was recorded — not today's.
 *
 * Someone who skipped three days still sees where they stand; showing nothing until they step on
 * the scale would tell them the least on the day they most want to know.
 */
export function WeightWidget() {
    const latest = useLatestWeight()
    const [opened, setOpened] = useState(false)

    const recorded = latest.data?.weightInKilograms ?? null
    // Correcting today's weight or recording the first of the day are two different gestures,
    // and the icon says which one this is before it is clicked.
    const action =
        latest.data?.isFromToday === true
            ? { icon: Pencil, label: 'Corriger' }
            : { icon: Plus, label: 'Enregistrer' }

    return (
        <SystemPanel
            title="Poids"
            actions={
                latest.isSuccess && (
                    <IconButton icon={action.icon} label={action.label} onClick={() => setOpened(true)} />
                )
            }
        >
            {latest.isPending && <Loader />}

            {latest.isSuccess && (
                <Row>
                    <Scale size={22} strokeWidth={1.8} aria-hidden />
                    {recorded === null || latest.data.day === null ? (
                        <span className="tracker-note">Aucune pesée</span>
                    ) : (
                        <span className="tracker-value">
                            {formatWeight(recorded)}
                            <span className="tracker-note">
                                {' '}
                                · {formatWhen(latest.data.day, latest.data.recordedAt, latest.data.isFromToday)}
                            </span>
                        </span>
                    )}
                </Row>
            )}

            {opened && latest.isSuccess && (
                <Modal title="Pesée du jour" onClose={() => setOpened(false)}>
                    <WeightPanel latest={latest.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </SystemPanel>
    )
}
