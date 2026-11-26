import { Footprints, Plus } from 'lucide-react'
import { useState } from 'react'
import { IconButton, MenuButton, Modal, SegmentedBar, SystemPanel } from '../../components'
import { useStepsToday } from './queries'
import { StepPanel } from './StepPanel'

/** The day's steps against the day's goal. The count is set in a window; the « ⋯ » holds the rest. */
export function StepWidget() {
    const today = useStepsToday()
    const [opened, setOpened] = useState(false)

    const count = today.data?.countInSteps ?? 0
    const goal = today.data?.goalInSteps ?? 0

    return (
        <>
            <SystemPanel className="tracker">
                <MenuButton label="Options" subject="pas" items={[{ label: 'Historique' }, { label: 'Réglages' }]} />
                <IconButton icon={Footprints} badge={Plus} hero label="Ajouter" subject="des pas" disabled={!today.isSuccess} onClick={() => setOpened(true)} />
                <div className="tracker-body">
                    <div className="tracker-reading">
                        <span className="tracker-value">{count} pas</span>
                        <span className="tracker-aux">objectif {goal}</span>
                    </div>
                    <SegmentedBar value={count} max={goal} segments={6} label={`Pas : ${count} / ${goal}`} thin />
                </div>
            </SystemPanel>

            {opened && today.isSuccess && (
                <Modal title="Pas du jour" onClose={() => setOpened(false)}>
                    <StepPanel day={today.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </>
    )
}
