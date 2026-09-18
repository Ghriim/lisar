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
