import {
    Book,
    Brush,
    Droplet,
    Dumbbell,
    Footprints,
    Guitar,
    Heart,
    Leaf,
    MonitorSmartphone,
    Moon,
    NotebookPen,
    Target,
    type LucideIcon,
} from 'lucide-react'

/**
 * How this front end draws a catalogue habit's icon code. The API answers a code, not an image;
 * an unknown code still renders, as a target, so a code added in the back-office before this front
 * is updated does not leave a blank.
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
    guitar: Guitar,
    brush: Brush,
    notebook: NotebookPen,
    monitor: MonitorSmartphone,
}

const WORDS: Record<string, string> = {
    run: 'Marche',
    book: 'Lecture',
    dumbbell: 'Sport',
    droplet: 'Hydratation',
    leaf: 'Bien-être',
    moon: 'Sommeil',
    heart: 'Santé',
    target: 'Objectif',
    guitar: 'Instrument de musique',
    brush: 'Art',
    notebook: 'Étude',
    monitor: 'Travail',
}

export function iconFor(code: string): LucideIcon {
    return ICONS[code] ?? Target
}

export function wordFor(code: string): string {
    return WORDS[code] ?? 'Habitude'
}
