/** Each stat's name, as the System words it. */
export const STAT_LABELS: Record<string, string> = {
    strength: 'Force',
    endurance: 'Endurance',
    agility: 'Agilité',
    intelligence: 'Intelligence',
    spirit: 'Esprit',
    willpower: 'Volonté',
}

export const GROUP_LABELS: Record<string, string> = {
    physical: 'Physique',
    mental: 'Mental',
}

export function statLabel(stat: string): string {
    return STAT_LABELS[stat] ?? stat
}
