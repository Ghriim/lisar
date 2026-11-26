import type { Status } from '../../api/types'

/**
 * The title the player wears — « L'Éveillé ». The API has none yet: this is where it gets read
 * once it does, and until then the status card shows the slot empty.
 */
export function playerTitle(_status: Status): string | null {
    return null
}
