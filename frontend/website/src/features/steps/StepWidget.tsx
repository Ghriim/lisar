import { Footprints } from 'lucide-react'
import { useState } from 'react'
import { Loader, Modal, ProgressBar, SystemPanel } from '../../components'
import { useStepsToday } from './queries'
import { StepPanel } from './StepPanel'

/**
 * The day's steps against the day's goal, on the quest log. A running total, glanced at: a click
 * anywhere on the widget opens the window where the number is set.
 */
export function StepWidget() {
    const today = useStepsToday()
    const [opened, setOpened] = useState(false)

    const recorded = today.data?.countInSteps ?? null

    return (
        <>
            <SystemPanel
                ariaLabel="Pas du jour"
                onActivate={today.isSuccess ? () => setOpened(true) : undefined}
            >
                {today.isPending && <Loader />}

                {today.isSuccess && (
                    <div className="tracker">
                        <Footprints size={45} strokeWidth={1.6} className="tracker-icon" aria-hidden />
                        <div className="tracker-body">
                            <span className="tracker-value">
                                {recorded ?? 0}
                                <span className="tracker-note"> / {today.data.goalInSteps} pas</span>
                            </span>

                            <ProgressBar
                                value={recorded ?? 0}
                                max={today.data.goalInSteps}
                                label={`${recorded ?? 0} sur ${today.data.goalInSteps} pas`}
                            />
                        </div>
                    </div>
                )}
            </SystemPanel>

            {opened && today.isSuccess && (
                <Modal title="Pas du jour" onClose={() => setOpened(false)}>
                    <StepPanel day={today.data} onClose={() => setOpened(false)} />
                </Modal>
            )}
        </>
    )
}
