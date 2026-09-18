import type { CSSProperties, ReactNode } from 'react'

interface ChipProps {
    children: ReactNode
    /** Overrides the chip's own colour: a priority carries the one the back-office set. */
    colour?: string
    tone?: 'default' | 'danger'
}

/** The small pill the app says everything in: a state, a rank, a tag, a due date. */
export function Chip({ children, colour, tone = 'default' }: ChipProps) {
    return (
        <span
            className={tone === 'danger' ? 'chip chip-overdue' : 'chip'}
            style={colour === undefined ? undefined : ({ color: colour } as CSSProperties)}
        >
            {children}
        </span>
    )
}

/** A chip that carries a dot in its own colour, for anything that has one. */
export function DotChip({ children, colour }: { children: ReactNode; colour: string }) {
    return (
        <Chip colour={colour}>
            <i className="chip-dot" />
            {children}
        </Chip>
    )
}

/**
 * The chip that says where a task stands. It takes its colour from the state rather than from
 * the back-office, which is why it is not a DotChip.
 */
export function StateChip({ children, colour }: { children: ReactNode; colour: string }) {
    return (
        <span className="chip chip-state" style={{ '--state-colour': colour } as CSSProperties}>
            {children}
        </span>
    )
}

interface ToggleChipProps {
    children: ReactNode
    pressed: boolean
    onToggle: () => void
}

/** A chip that is also a choice: the tags already used, offered back. */
export function ToggleChip({ children, pressed, onToggle }: ToggleChipProps) {
    return (
        <button type="button" className="chip" aria-pressed={pressed} onClick={onToggle}>
            {children}
        </button>
    )
}
