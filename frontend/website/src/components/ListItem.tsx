import { ChevronDown, ChevronRight } from 'lucide-react'
import { useState, type CSSProperties, type ReactNode } from 'react'
import { IconButton } from './IconButton'

interface ListItemProps {
    /** Plain text: it is also what the fold control announces. */
    title: string
    /** Right after the title, dimmed. A progress count, a quantity, a duration. */
    note?: string
    description?: string | null
    /** The line under the title: chips, mostly. */
    meta?: ReactNode
    /** The cluster on the right. Icon buttons, usually. */
    actions?: ReactNode
    /** Colours the left edge: a priority, a status, a muscle group. */
    accent?: string
    /** Faded and struck through: something already dealt with. */
    muted?: boolean
    /** Shown under the row, in red: what the last action on it answered. */
    error?: string | null
    /** Nested items. Their presence is what makes the row foldable. */
    children?: ReactNode
    /** Folded is the default: a row should read as one line until someone asks for more. */
    defaultUnfolded?: boolean
}

/**
 * One row of a list, whatever the list is about. It owns its own fold state, because whether a
 * row is open is nobody else's business.
 */
export function ListItem({
    title,
    note,
    description,
    meta,
    actions,
    accent,
    muted = false,
    error = null,
    children,
    defaultUnfolded = false,
}: ListItemProps) {
    const [unfolded, setUnfolded] = useState(defaultUnfolded)
    const foldable = children !== undefined && children !== null && children !== false

    return (
        <>
            <article
                className={muted ? 'list-item list-item-muted' : 'list-item'}
                style={{ '--list-item-accent': accent } as CSSProperties}
            >
                <div className="list-item-body">
                    <div className="list-item-title">
                        {foldable && (
                            <IconButton
                                icon={unfolded ? ChevronDown : ChevronRight}
                                small
                                expanded={unfolded}
                                label={unfolded ? 'Replier' : 'Déplier'}
                                subject={title}
                                onClick={() => setUnfolded((open) => !open)}
                            />
                        )}

                        <span>{title}</span>

                        {note !== undefined && <span className="list-item-note">{note}</span>}
                    </div>

                    {description !== null && description !== undefined && description !== '' && (
                        <p className="list-item-description">{description}</p>
                    )}

                    {meta !== undefined && <div className="list-item-meta">{meta}</div>}

                    {error !== null && <p className="field-error">{error}</p>}
                </div>

                {actions !== undefined && <div className="list-item-actions">{actions}</div>}
            </article>

            {foldable && unfolded && <div className="list-item-children">{children}</div>}
        </>
    )
}
