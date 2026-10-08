import { Trophy } from 'lucide-react'
import type { PersonalBestRecord } from '../../api/types'
import { ListItem } from '../../components'
import { formatShortDay } from '../workouts/workoutFormat'
import { formatPersonalBestValue, personalBestAnchor, personalBestLabel } from './personalBestFormat'

/**
 * One record: what it is, where it stands, when it got there — and, folded under it, every time
 * it was beaten before, the latest first.
 */
export function PersonalBestRecordItem({ record }: { record: PersonalBestRecord }) {
    const earlier = record.progression.slice(0, -1).reverse()

    return (
        <ListItem
            id={personalBestAnchor({ ...record, movementId: record.current.movementId })}
            icon={<Trophy size={15} strokeWidth={2} aria-hidden />}
            title={personalBestLabel(record)}
            note={formatPersonalBestValue(record.current)}
            description={`Le ${formatShortDay(record.current.achievedAt)}`}
        >
            {earlier.length > 0 &&
                earlier.map((step) => (
                    <ListItem key={step.id} title={formatPersonalBestValue(step)} note={`le ${formatShortDay(step.achievedAt)}`} />
                ))}
        </ListItem>
    )
}
