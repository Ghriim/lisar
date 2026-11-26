import type { ReactNode } from 'react'

interface SectionHeadingProps {
    /** Shown as `[ TÂCHES ]`. */
    title: string
    /** Given to the heading, so the card under it can be labelled by it (`aria-labelledby`). */
    id: string
    /** The section's actions, aligned on the right. */
    actions?: ReactNode
}

/**
 * A section's title, on its own line above its card rather than inside it, with the section's
 * actions on the right. `SystemPanel` keeps its own title for the screens that want it inside.
 */
export function SectionHeading({ title, id, actions }: SectionHeadingProps) {
    return (
        <header className="section-heading">
            <h2 id={id} className="section-title">
                [ {title} ]
            </h2>
            {actions !== undefined && <div className="section-actions">{actions}</div>}
        </header>
    )
}
