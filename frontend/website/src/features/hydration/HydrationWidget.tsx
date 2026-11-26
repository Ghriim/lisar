import { GlassWater } from 'lucide-react'
import { Button, SegmentedBar, SystemPanel } from '../../components'
import { useHydrationToday, useLogHydration } from './queries'

/** What one tap adds, and what a segment of the bar stands for against a 1500 mL goal. */
const GLASS_IN_MILLILITRES = 250
const SEGMENTS = 6

/**
 * The water drunk today against the day's goal. Drinking is noted in passing, several times a day:
 * one tap adds a glass.
 */
export function HydrationWidget() {
    const today = useHydrationToday()
    const log = useLogHydration()

    const total = today.data?.totalInMillilitres ?? 0
    const goal = today.data?.goalInMillilitres ?? 0

    return (
        <SystemPanel className="tracker">
            <div className="tracker-head">
                <GlassWater size={36} strokeWidth={1.4} className="tracker-icon" role="img" aria-label="Hydratation" />
                <div className="tracker-body">
                    <span className="tracker-value">{total} mL</span>
                    <span className="tracker-aux">objectif {goal} mL</span>
                </div>
            </div>

            <SegmentedBar value={total} max={goal} segments={SEGMENTS} label={`Hydratation : ${total} / ${goal} mL`} thin />

            <Button variant="secondary" disabled={!today.isSuccess || log.isPending} onClick={() => log.mutate(GLASS_IN_MILLILITRES)}>
                +{GLASS_IN_MILLILITRES} mL
            </Button>
        </SystemPanel>
    )
}
