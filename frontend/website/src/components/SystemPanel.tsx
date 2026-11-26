import { useId, type ReactNode } from 'react'
import { SectionHeading } from './SectionHeading'

interface SystemPanelProps {
    /** Rendered as the System's own label for the window: short, and shouted. */
    title?: string
    actions?: ReactNode
    children: ReactNode
    className?: string
    /**
     * Keeps the title inside the card, for a box that stands alone — a window, the sign-in card.
     * Otherwise a titled card is a section of its screen: its title sits above it, its actions on
     * the right, as on the dashboard.
     */
    titleInside?: boolean
}

/** The one surface of the app. Everything the System says arrives inside one of these. */
export function SystemPanel({ title, actions, children, className, titleInside = false }: SystemPanelProps) {
    const id = useId()
    const classes = className === undefined ? 'panel' : `panel ${className}`

    if (title !== undefined && !titleInside) {
        return (
            <section className="board-section" aria-labelledby={id}>
                <SectionHeading id={id} title={title} actions={actions} />
                <div className={classes}>{children}</div>
            </section>
        )
    }

    return (
        <section className={classes}>
            {(title !== undefined || actions !== undefined) && (
                <header className="row spread panel-title">
                    <span>{title !== undefined ? `[ ${title} ]` : null}</span>
                    {actions}
                </header>
            )}
            {children}
        </section>
    )
}
