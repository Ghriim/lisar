interface SegmentedBarProps {
    value: number
    max: number
    /** How many segments the whole is cut into: 25 for experience, 6 for a tracker. */
    segments: number
    /** Read out to a screen reader, which has no bar to look at: « Expérience : 340 / 500 XP ». */
    label: string
    /** A tracker's bar: 6px instead of 8, no glow. */
    thin?: boolean
}

/**
 * How far along something is, in segments: one lights up only once it is whole. Full is full —
 * nothing past the last segment. `ProgressBar` is the same in one continuous line.
 */
export function SegmentedBar({ value, max, segments, label, thin = false }: SegmentedBarProps) {
    const ratio = max <= 0 ? 0 : Math.min(Math.max(value / max, 0), 1)
    const lit = Math.floor(ratio * segments)

    return (
        <div className={thin ? 'segmented-bar segmented-bar-thin' : 'segmented-bar'} role="img" aria-label={label}>
            {Array.from({ length: segments }, (_, index) => (
                <span key={index} className={index < lit ? 'segment segment-on' : 'segment'} aria-hidden />
            ))}
        </div>
    )
}
