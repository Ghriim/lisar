import { GlassWater, Plus } from 'lucide-react'
import { useState } from 'react'
import { IconButton, MenuButton, Modal, SegmentedBar, SystemPanel } from '../../components'
import { HydrationPanel } from './HydrationPanel'
import { useHydrationToday, useLogHydration } from './queries'

/** What one tap adds, and what a segment of the bar stands for against a 1500 mL goal. */
const GLASS_IN_MILLILITRES = 250
const SEGMENTS = 6

/**
 * The water drunk today against the day's goal. Drinking is noted in passing, several times a day:
 * one tap on the glass adds one. The « ⋯ » holds the rest — « Réglages » opens the day's drinks
 * meanwhile, to correct or delete one.
 */
export function HydrationWidget() {
    const today = useHydrationToday()
    const log = useLogHydration()
    const [managing, setManaging] = useState(false)

    const total = today.data?.totalInMillilitres ?? 0
    const goal = today.data?.goalInMillilitres ?? 0

    return (
        <>
            <SystemPanel className="tracker">
                <MenuButton
                    label="Options"
                    subject="hydratation"
                    items={[{ label: 'Historique' }, { label: 'Réglages', onSelect: today.isSuccess ? () => setManaging(true) : undefined }]}
                />
                <IconButton
                    icon={GlassWater}
                    badge={Plus}
                    hero
                    label={`Ajouter ${GLASS_IN_MILLILITRES} mL`}
                    disabled={!today.isSuccess || log.isPending}
                    onClick={() => log.mutate(GLASS_IN_MILLILITRES)}
                />
                <div className="tracker-body">
                    <div className="tracker-reading">
                        <span className="tracker-value">{total} mL</span>
                        <span className="tracker-aux">objectif {goal} mL</span>
                    </div>
                    <SegmentedBar value={total} max={goal} segments={SEGMENTS} label={`Hydratation : ${total} / ${goal} mL`} thin />
                </div>
            </SystemPanel>

            {managing && today.isSuccess && (
                <Modal title="Hydratation du jour" onClose={() => setManaging(false)}>
                    <HydrationPanel day={today.data} />
                </Modal>
            )}
        </>
    )
}
