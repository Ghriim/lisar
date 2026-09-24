import { Book, Droplet, Dumbbell, Footprints, Heart, Leaf, Moon, Target, type LucideIcon } from 'lucide-react'

/**
 * The API stores a code, never an image. This is the back-office's reading of it — the website
 * has its own, and neither has to agree beyond the code itself.
 */
const ICONS: Record<string, LucideIcon> = {
    run: Footprints,
    book: Book,
    dumbbell: Dumbbell,
    droplet: Droplet,
    leaf: Leaf,
    moon: Moon,
    heart: Heart,
    target: Target,
}

/** Falls back to a target: a code this front end does not know still renders. */
export function HabitIcon({ code, size = 18 }: { code: string; size?: number }) {
    const Icon = ICONS[code] ?? Target

    return <Icon size={size} strokeWidth={1.8} aria-hidden />
}
