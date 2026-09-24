import { GlassWater } from 'lucide-react'
import { useState } from 'react'
import { Loader, Modal, ProgressBar, SystemPanel } from '../../components'
import { HydrationPanel } from './HydrationPanel'
import { useHydrationToday } from './queries'

/**
 * A glass of water and where the day stands, on the quest log. Drinking is noted in passing,
 * several times a day: a click anywhere on the widget opens the window where everything happens.
 */
export function HydrationWidget() {
    const today = useHydrationToday()
    const [opened, setOpened] = useState(false)

    return (
        <>
            <SystemPanel
                ariaLabel="Hydratation du jour"
                onActivate={today.isSuccess ? () => setOpened(true) : undefined}
            >
                {today.isPending && <Loader />}

                {today.isSuccess && (
                    <div className="tracker">
                        <GlassWater size={45} strokeWidth={1.6} className="tracker-icon" aria-hidden />
                        <div className="tracker-body">
                            <span className="tracker-value">
                                {today.data.totalInMillilitres}
                                <span className="tracker-note"> / {today.data.goalInMillilitres} mL</span>
                            </span>

                            <ProgressBar
                                value={today.data.totalInMillilitres}
                                max={today.data.goalInMillilitres}
                                label={`${today.data.totalInMillilitres} sur ${today.data.goalInMillilitres} millilitres`}
                            />
                        </div>
                    </div>
                )}
            </SystemPanel>

            {opened && today.isSuccess && (
                <Modal title="Hydratation du jour" onClose={() => setOpened(false)}>
                    <HydrationPanel day={today.data} />
                </Modal>
            )}
        </>
    )
}
