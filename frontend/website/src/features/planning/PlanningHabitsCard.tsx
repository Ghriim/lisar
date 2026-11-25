import { CalendarCheck, Eye } from 'lucide-react'
import { IconButton, Row } from '../../components'

interface PlanningHabitsCardProps {
    /** How many of the day's habits were kept, bonus days included. */
    kept: number
    total: number
    /** Whether one of them was missed: a day gone by with a habit not kept. */
    hasMissed: boolean
    onOpen: () => void
}

/**
 * A day's habits held within the day, as one card: seven every day for each habit would bury the
 * rest of the week. It counts them; the eye lists them.
 */
export function PlanningHabitsCard({ kept, total, hasMissed, onOpen }: PlanningHabitsCardProps) {
    const state = kept === total ? 'kept' : hasMissed ? 'overdue' : 'to_come'
    const title = `${kept}/${total} habitude${total > 1 ? 's' : ''}`

    return (
        <article className={`planning-slot planning-slot-${state}`}>
            <Row spread style={{ gap: 4 }}>
                <span className="planning-slot-title">
                    <CalendarCheck size={12} strokeWidth={2} aria-hidden />
                    <span className="planning-slot-name">{title}</span>
                </span>
                <IconButton icon={Eye} small label="Consulter" subject={title} onClick={onOpen} />
            </Row>
        </article>
    )
}
