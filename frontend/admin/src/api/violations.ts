/**
 * The API answers in error codes so that both front ends can word them their own way. This is
 * the back-office's way — terser than the website's, because whoever reads it works here.
 */
const MESSAGES: Record<string, string> = {
    label_required: 'Un libellé est requis.',
    label_too_long: '32 caractères maximum.',
    colour_required: 'Une couleur est requise.',
    colour_invalid: 'Couleur attendue au format #RRGGBB.',
    weight_invalid: 'Poids attendu entre 0 et 9999.',
    priority_label_already_used: 'Une priorité porte déjà ce libellé.',
    priority_in_use: 'Des tâches portent cette priorité.',
    priority_is_the_default: 'C’est la priorité par défaut : donnez-la à une autre d’abord.',
    default_priority_required: 'Le défaut ne se retire pas, il se donne à une autre priorité.',
    category_label_already_used: 'Une catégorie commune porte déjà ce libellé.',
    category_in_use: 'Des tâches sont rangées dans cette catégorie.',
    cannot_deactivate_yourself: 'Vous ne pouvez pas désactiver votre propre compte.',
    body_required: 'Une note vide ne sert à rien.',
    body_too_long: '2000 caractères maximum.',
    email_required: 'Une adresse est requise.',
    email_invalid: 'Adresse invalide.',
    password_required: 'Un mot de passe est requis.',
    icon_unknown: 'Cette icône n’existe pas.',
    volume_invalid: 'Volume attendu entre 1 et 5000 mL.',
    name_required: 'Un nom est requis.',
    name_too_long: '128 caractères maximum.',
    equipment_name_already_used: 'Un équipement porte déjà ce nom.',
    muscle_group_name_already_used: 'Un groupe porte déjà ce nom.',
    muscle_name_already_used: 'Un muscle porte déjà ce nom, dans ce groupe ou un autre.',
    muscle_group_not_found: 'Ce groupe n’existe pas.',
    muscle_group_inactive: 'Ce groupe est désactivé : il ne reçoit pas de nouveau muscle.',
    muscle_group_in_use: 'Des muscles sont encore dans ce groupe.',
    muscle_in_use: 'Des mouvements ciblent ce muscle : désactivez-le plutôt.',
    equipment_in_use: 'Des mouvements utilisent cet équipement : désactivez-le plutôt.',
    movement_name_already_used: 'Un mouvement porte déjà ce nom.',
    movement_family_name_already_used: 'Une famille porte déjà ce nom.',
    movement_family_not_found: 'Cette famille n’existe pas.',
    movement_family_inactive: 'Cette famille est désactivée : elle ne reçoit pas de nouveau mouvement.',
    movement_family_in_use: 'Des mouvements sont encore dans cette famille.',
    primary_muscle_not_found: 'Ce muscle principal n’existe pas.',
    primary_muscle_unavailable: 'Ce muscle principal est désactivé, ou son groupe l’est.',
    secondary_muscle_not_found: 'Un muscle secondaire n’existe pas.',
    secondary_muscle_unavailable: 'Un muscle secondaire est désactivé, ou son groupe l’est.',
    primary_muscle_also_secondary: 'Le muscle principal ne peut pas être aussi secondaire.',
    muscle_id_invalid: 'Muscle invalide.',
    equipment_not_found: 'Un équipement n’existe pas.',
    equipment_inactive: 'Un équipement est désactivé.',
    equipment_id_invalid: 'Équipement invalide.',
    measure_required: 'Une série enregistre au moins des répétitions, une durée ou une distance.',
    description_too_long: '5000 caractères maximum.',
    video_url_invalid: 'Adresse de vidéo invalide.',
    video_url_too_long: '512 caractères maximum.',
}

export function humanise(code: string): string {
    return MESSAGES[code] ?? code
}

/** Every violation of a payload, flattened into one readable line. */
export function summarise(violations: Record<string, string[]>): string {
    return Object.values(violations)
        .flat()
        .map(humanise)
        .join(' ')
}
