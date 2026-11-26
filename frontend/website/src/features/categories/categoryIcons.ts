import type { Category } from '../../api/types'
import {
    Book,
    Brush,
    Droplet,
    Dumbbell,
    Footprints,
    Guitar,
    Heart,
    House,
    Leaf,
    MonitorSmartphone,
    Moon,
    NotebookPen,
    Target,
    type LucideIcon,
} from 'lucide-react'

/**
 * How this front end draws a common category's icon code — the icon of every task and habit filed
 * in it, which have none of their own. The API answers a code, not an image; an unknown code still
 * renders, as a target, so a code added in the back-office before this front is updated does not
 * leave a blank.
 */
const ICONS: Record<string, LucideIcon> = {
    home: House,
    run: Footprints,
    book: Book,
    dumbbell: Dumbbell,
    droplet: Droplet,
    leaf: Leaf,
    moon: Moon,
    heart: Heart,
    target: Target,
    guitar: Guitar,
    brush: Brush,
    notebook: NotebookPen,
    monitor: MonitorSmartphone,
}

/** A category's icon; null when there is nothing to draw — no category, or a personal one. */
export function categoryIconOf(category: Category | null): LucideIcon | null {
    if (category === null || category.icon === null) {
        return null
    }

    return ICONS[category.icon] ?? Target
}

/** A bare code, as a planning entry hands it over; null when the habit sits in no category. */
export function iconForCode(code: string | null): LucideIcon | null {
    return code === null ? null : (ICONS[code] ?? Target)
}
