import type { Progress } from '../../api/types'
import { ProgressBar } from '../../components'

interface ProgressLineProps {
    name: string
    progress: Progress
}

/** A name, its level, and the bar towards the next with what it holds: « 120 / 200 XP ». */
export function ProgressLine({ name, progress }: ProgressLineProps) {
    const { level, experienceIntoLevel, experienceForNextLevel } = progress

    return (
        <div className="status-line">
            <div className="status-line-head">
                <span className="status-line-name">{name}</span>
                <span className="status-line-level">Niv. {level}</span>
            </div>
            <ProgressBar value={experienceIntoLevel} max={experienceForNextLevel} label={`${name} : ${experienceIntoLevel} sur ${experienceForNextLevel} XP vers le niveau ${level + 1}`} />
            <span className="status-line-xp">
                {experienceIntoLevel} / {experienceForNextLevel} XP
            </span>
        </div>
    )
}
