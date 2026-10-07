import type { CSSProperties, ReactNode } from 'react'

interface IndexBadgeProps {
    /** The rank, short: a number, mostly. */
    children: ReactNode
    /** The frame and the figure take it: the kind of thing the rank belongs to. */
    colour: string
    /**
     * What the colour stands for, in words. The tooltip shows it on hover, and a screen reader
     * hears it: a colour alone tells neither of them anything.
     */
    label: string
    /**
     * What the rank is of. Folded into the spoken label only — a screen reader hears
     * "Série 2 · Dropset", the tooltip stays "Dropset".
     */
    subject?: string
}

/** A rank in a small frame, in a colour — where a row sits in its list, and what kind it is. */
export function IndexBadge({ children, colour, label, subject }: IndexBadgeProps) {
    return (
        <span className="tooltip-host" data-tooltip={label}>
            <span className="index-badge" style={{ '--index-badge-colour': colour } as CSSProperties} role="img" aria-label={subject === undefined ? label : `${subject} · ${label}`}>
                {children}
            </span>
        </span>
    )
}
