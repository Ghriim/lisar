import { Footprints } from 'lucide-react'
import { useState } from 'react'
import { Button, Modal, SegmentedBar, SystemPanel } from '../../components'
import { useStepsToday } from './queries'
import { StepPanel } from './StepPanel'

/** The day's steps against the day's goal. The count is set in a window. */
export function StepWidget() {
    const today = useStepsToday()
    const [opened, setOpened] = useState(false)

    const count = today.data?.countInSteps ?? 0
    const goal = today.data?.goalInSteps ?? 0

    return (
        <>
            <SystemPanel className="tracker">
                <div className="tracker-head">
                    <Footprints size={36} strokeWidth={1.4} className="tracker-icon" role="img" aria-label="Pas" />
                    <div className="tracker-body">
                        <span className="tracker-value">{count} pas</span>
                        <span className="tracker-aux">objectif {goal}</span>
                    </div>
                </div>

                <SegmentedBar value={count} max={goal} segments={6} label={`Pas : ${count} / ${goal}`} thin />

                <Button variant="secondary" disabled={!today.isSuccess} onClick={() => setOpened(true)}>
                    Saisir mes pas
                </Button>
            </SystemPanel>

            {opened && today.isSuccess && (
                <Modal title="Pas du jour" onClose={() => setOpened(false)}>
                    <StepPanel day={today.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </>
    )
}
