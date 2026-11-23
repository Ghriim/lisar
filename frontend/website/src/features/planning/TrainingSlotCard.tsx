import { Dumbbell, Eye, Play } from 'lucide-react'
import type { TrainingPlanningEntry, TrainingPlanningSlot } from '../../api/types'
import { IconButton, Row, StateChip } from '../../components'
import { formatTimeSpan } from './planningFormat'

const STATES: Record<string, { label: string; colour: string }> = {
    to_come: { label: 'À venir', colour: 'var(--accent)' },
    kept: { label: 'Tenu', colour: 'var(--success)' },
    missed: { label: 'Manqué', colour: 'var(--danger)' },
}

interface TrainingSlotCardProps {
    slot: TrainingPlanningSlot
    entry: TrainingPlanningEntry
    /** Offered on today's slot not kept yet, while no workout is in progress. */
    onStart?: () => void
    startPending: boolean
    onOpen: () => void
}

/** One planned routine on its day: what, when, and where it stands. */
export function TrainingSlotCard({ slot, entry, onStart, startPending, onOpen }: TrainingSlotCardProps) {
    const state = STATES[slot.state] ?? STATES.to_come
    const time = formatTimeSpan(entry.schedule)

    return (
        <article className={`planning-slot planning-slot-${slot.state}`}>
            <span className="planning-slot-title">
                <Dumbbell size={12} strokeWidth={2} aria-hidden />
                {entry.routineName}
            </span>
            {time !== null && <span className="planning-slot-time">{time}</span>}
            <Row style={{ gap: 4, justifyContent: 'space-between' }}>
                <StateChip colour={state.colour}>{state.label}</StateChip>
                <Row style={{ gap: 2 }}>
                    {onStart !== undefined && (
                        <IconButton icon={Play} small label="Démarrer" subject={entry.routineName} disabled={startPending} onClick={onStart} />
                    )}
                    <IconButton icon={Eye} small label="Consulter" subject={entry.routineName} onClick={onOpen} />
                </Row>
            </Row>
        </article>
    )
}
