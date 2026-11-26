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

/** The six stats as the status card lines them up, each with the three letters it shows. */
export const STAT_ORDER: { stat: string; abbreviation: string }[] = [
    { stat: 'strength', abbreviation: 'FOR' },
    { stat: 'agility', abbreviation: 'AGI' },
    { stat: 'endurance', abbreviation: 'END' },
    { stat: 'intelligence', abbreviation: 'INT' },
    { stat: 'spirit', abbreviation: 'ESP' },
    { stat: 'willpower', abbreviation: 'VOL' },
]

export function statLabel(stat: string): string {
    return STAT_LABELS[stat] ?? stat
}
