import type { Weight } from '../../api/types'

/**
 * How far the weight moved over seven days, in kilograms, signed — « 7 j · −0,3 kg » under the
 * weight tracker. The API does not answer it yet: null, shown « — », until it does.
 */
export function weightWeekTrend(_latest: Weight): number | null {
    return null
}
