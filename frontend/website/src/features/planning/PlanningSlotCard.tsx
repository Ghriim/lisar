import { Check, Eye, Play, RotateCcw, type LucideIcon } from 'lucide-react'
import { IconButton, Row, StateChip } from '../../components'

const STATES: Record<string, { label: string; colour: string }> = {
    to_come: { label: 'À venir', colour: 'var(--accent)' },
    kept: { label: 'Tenu', colour: 'var(--success)' },
    missed: { label: 'Manqué', colour: 'var(--danger)' },
    kept_unplanned: { label: 'Bonus', colour: 'var(--success)' },
    open: { label: 'À faire', colour: 'var(--accent)' },
    overdue: { label: 'En retard', colour: 'var(--danger)' },
    done: { label: 'Faite', colour: 'var(--success)' },
}

interface PlanningSlotCardProps {
    icon: LucideIcon
    title: string
    /** « 18:00 · 1 h 15 », or null within the day. */
    time: string | null
    /** The days it comes back on, in words — shown where there is room for it, in a list. */
    rhythm?: string | null
    /** to_come, kept, missed, kept_unplanned, open, overdue or done. */
    state: string
    /** Offered on today's training slot not kept yet, while no workout is in progress. */
    onStart?: () => void
    startPending?: boolean
    /** Absent on a day kept unplanned: no entry asks for it. */
    onOpen?: () => void
    /**
     * Ticking the date off, and taking the tick back — on a task made for it, or on today's date of
     * a habit kept by hand. Absent where there is nothing to tick: a task still to come, a habit's
     * other days, a habit its tracker keeps.
     */
    completion?: Completion
}

export interface Completion {
    isDone: boolean
    /** The verb that ticks it — « Terminer » for a task, « Valider » for a habit — and the one that takes it back. */
    doLabel: string
    undoLabel: string
    pending: boolean
    onToggle: () => void
}

/** One date on its day, whatever its kind: what, when, and where it stands. */
export function PlanningSlotCard({ icon: Icon, title, time, rhythm = null, state, onStart, startPending = false, onOpen, completion }: PlanningSlotCardProps) {
    const shown = STATES[state] ?? STATES.to_come

    return (
        <article className={`planning-slot planning-slot-${state}`}>
            <span className="planning-slot-title">
                <Icon size={12} strokeWidth={2} aria-hidden />
                <span className="planning-slot-name">{title}</span>
            </span>
            {rhythm !== null && <span className="planning-slot-time">{rhythm}</span>}
            {time !== null && rhythm === null && <span className="planning-slot-time">{time}</span>}
            <Row style={{ gap: 4, justifyContent: 'space-between' }}>
                <StateChip colour={shown.colour}>{shown.label}</StateChip>
                <Row style={{ gap: 2 }}>
                    {onStart !== undefined && <IconButton icon={Play} small label="Démarrer" subject={title} disabled={startPending} onClick={onStart} />}
                    {completion !== undefined && (
                        <IconButton
                            icon={completion.isDone ? RotateCcw : Check}
                            small
                            label={completion.isDone ? completion.undoLabel : completion.doLabel}
                            subject={title}
                            disabled={completion.pending}
                            onClick={completion.onToggle}
                        />
                    )}
                    {onOpen !== undefined && <IconButton icon={Eye} small label="Consulter" subject={title} onClick={onOpen} />}
                </Row>
            </Row>
        </article>
    )
}
