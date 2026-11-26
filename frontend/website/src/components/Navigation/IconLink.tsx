import type { LucideIcon } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Tooltip } from '../Tooltip'

interface IconLinkProps {
    icon: LucideIcon
    /** What the tooltip shows: where it leads — « Voir ma journée ». */
    label: string
    /** Read out instead, when the tooltip alone does not say where: « Voir ma journée dans le planning ». */
    ariaLabel?: string
    to: string
}

/**
 * An icon that goes somewhere rather than doing something: a link, drawn like a section's
 * `IconButton` (44px, framed) and named on hover like every icon.
 */
export function IconLink({ icon: Icon, label, ariaLabel, to }: IconLinkProps) {
    return (
        <Tooltip label={label}>
            <Link to={to} className="icon-button icon-button-large" aria-label={ariaLabel ?? label}>
                <Icon size={18} strokeWidth={1.6} aria-hidden />
            </Link>
        </Tooltip>
    )
}
