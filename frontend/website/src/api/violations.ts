/**
 * The API answers in error codes so that both front ends can word them their own way. This is
 * the website's way.
 */
const MESSAGES: Record<string, string> = {
    username_required: 'Un nom est nécessaire.',
    username_too_short: 'Trois caractères au minimum.',
    username_too_long: 'Trente-deux caractères au maximum.',
    username_invalid_characters: 'Lettres, chiffres, tiret et tiret bas uniquement.',
    username_already_used: 'Ce nom est déjà pris.',
    email_required: 'Une adresse est nécessaire.',
    email_invalid: 'Cette adresse ne ressemble pas à une adresse.',
    email_too_long: 'Cette adresse est trop longue.',
    email_already_used: 'Un compte existe déjà avec cette adresse.',
    password_required: 'Un mot de passe est nécessaire.',
    password_too_short: 'Huit caractères au minimum.',
    password_missing_lowercase: 'Il manque une minuscule.',
    password_missing_uppercase: 'Il manque une majuscule.',
    password_missing_digit: 'Il manque un chiffre.',
    password_missing_special_character: 'Il manque un caractère spécial.',
    title_required: 'Une quête a besoin d’un nom.',
    title_too_long: 'Deux cent cinquante-cinq caractères au maximum.',
    description_too_long: 'Description trop longue.',
    due_date_invalid: 'Date attendue au format AAAA-MM-JJ.',
    tag_too_long: 'Trente-deux caractères au maximum par étiquette.',
    priority_not_found: 'Ce rang n’existe pas.',
    category_not_found: 'Cette catégorie n’existe pas.',
    parent_task_not_found: 'La quête parente est introuvable.',
    parent_task_is_a_subtask: 'Une sous-quête ne peut pas en porter d’autres.',
    task_has_open_subtasks: 'Une sous-quête est encore ouverte.',
}

export function humanise(code: string): string {
    return MESSAGES[code] ?? code
}
