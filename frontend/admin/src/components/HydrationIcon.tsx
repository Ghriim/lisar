import { CanSoda, Coffee, FlaskRound, GlassWater, Milk, type LucideIcon } from 'lucide-react'

/**
 * The API stores a code, never an image. This is the back-office's reading of it — the website
 * has its own, and neither has to agree with the other beyond the code itself.
 */
const ICONS: Record<string, LucideIcon> = {
    glass: GlassWater,
    bottle: Milk,
    mug: Coffee,
    can: CanSoda,
    carafe: FlaskRound,
}

/** Falls back to a glass: a code this front end does not know still renders. */
export function HydrationIcon({ code, size = 18 }: { code: string; size?: number }) {
    const Icon = ICONS[code] ?? GlassWater

    return <Icon size={size} strokeWidth={1.8} aria-hidden />
}
