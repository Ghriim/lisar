import { CanSoda, Coffee, FlaskRound, GlassWater, Milk, type LucideIcon } from 'lucide-react'

/**
 * The API stores a code, never an image. This is the website's reading of it — the back-office
 * has its own, and neither has to agree with the other beyond the code itself.
 */
const ICONS: Record<string, LucideIcon> = {
    glass: GlassWater,
    bottle: Milk,
    mug: Coffee,
    can: CanSoda,
    carafe: FlaskRound,
}

const WORDS: Record<string, string> = {
    glass: 'Verre',
    bottle: 'Bouteille',
    mug: 'Tasse',
    can: 'Canette',
    carafe: 'Carafe',
}

/** Falls back to a glass: a shortcut whose code this front end does not know still works. */
export function iconFor(code: string): LucideIcon {
    return ICONS[code] ?? GlassWater
}

export function wordFor(code: string): string {
    return WORDS[code] ?? 'Boisson'
}
