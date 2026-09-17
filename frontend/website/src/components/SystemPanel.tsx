import type { ReactNode } from 'react'

interface SystemPanelProps {
    /** Rendered as the System's own label for the window: short, and shouted. */
    title?: string
    actions?: ReactNode
    children: ReactNode
    className?: string
}

/**
 * The one surface of the app. Everything the System says arrives inside one of these.
 */
export function SystemPanel({ title, actions, children, className }: SystemPanelProps) {
    return (
        <section className={className === undefined ? 'panel' : `panel ${className}`}>
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
