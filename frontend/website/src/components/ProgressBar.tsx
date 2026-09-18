interface ProgressBarProps {
    value: number
    max: number
    /** Read out to a screen reader, which has no bar to look at: "750 sur 1500 millilitres". */
    label: string
}

/** How far along something is. Full is full: it never draws past its own end. */
export function ProgressBar({ value, max, label }: ProgressBarProps) {
    const ratio = max <= 0 ? 0 : Math.min(value / max, 1)

    return (
        <div
            className="progress"
            role="progressbar"
            aria-valuenow={value}
            aria-valuemin={0}
            aria-valuemax={max}
            aria-label={label}
        >
            <div className="progress-fill" style={{ width: `${ratio * 100}%` }} />
        </div>
    )
}
