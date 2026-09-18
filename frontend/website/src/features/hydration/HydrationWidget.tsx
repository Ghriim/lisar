import { Eye, GlassWater } from 'lucide-react'
import { useState } from 'react'
import { IconButton, Loader, Modal, ProgressBar, Row, SystemPanel } from '../../components'
import { HydrationPanel } from './HydrationPanel'
import { useHydrationToday } from './queries'

/**
 * A glass of water and where the day stands, on the quest log. Drinking is noted in passing,
 * several times a day: sending someone to another page for it would mean they stop noting it.
 *
 * Everything but the glance happens in the window behind the eye.
 */
export function HydrationWidget() {
    const today = useHydrationToday()
    const [opened, setOpened] = useState(false)

    return (
        <SystemPanel
            title="Hydratation"
            actions={<IconButton icon={Eye} label="Consulter" onClick={() => setOpened(true)} />}
        >
            {today.isPending && <Loader />}

            {today.isSuccess && (
                <>
                    <Row spread>
                        <Row>
                            <GlassWater size={22} strokeWidth={1.8} aria-hidden />
                            <span className="tracker-value">
                                {today.data.totalInMillilitres}
                                <span className="tracker-note"> / {today.data.goalInMillilitres} mL</span>
                            </span>
                        </Row>
                    </Row>

                    <div style={{ marginTop: 12 }}>
                        <ProgressBar
                            value={today.data.totalInMillilitres}
                            max={today.data.goalInMillilitres}
                            label={`${today.data.totalInMillilitres} sur ${today.data.goalInMillilitres} millilitres`}
                        />
                    </div>
                </>
            )}

            {opened && today.isSuccess && (
                <Modal title="Hydratation du jour" onClose={() => setOpened(false)}>
                    <HydrationPanel day={today.data} />
                </Modal>
            )}
        </SystemPanel>
    )
}
