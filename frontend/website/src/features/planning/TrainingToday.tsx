import { Calendar, Check, Dumbbell, Moon, Play } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import type { TrainingPlanningEntry, TrainingPlanningSlot } from '../../api/types'
import { Alert, Button, IconButton, IconLink, Loader, SectionHeading, SystemPanel, Tooltip } from '../../components'
import { useRoutines } from '../routines/queries'
import { useStartRoutineAction } from '../routines/useStartRoutineAction'
import { addDays, dayKey } from './planningFormat'
import { useTrainingPlanning } from './queries'
import { byStartTime, formatExerciseCount, formatMinutes, weekdayOf } from './trainingFormat'

/** How far ahead « Prochaine séance » looks on a rest day. */
const LOOKAHEAD_IN_DAYS = 14

const FAILURE = 'Le System ne répond pas. Réessaie dans un instant.'

/**
 * Today's sessions, on the dashboard, by time — the ones with none last — each started from its
 * line. Nothing today: a rest day, with the next session, or nothing planned at all.
 */
export function TrainingToday() {
    const now = new Date()
    const today = dayKey(now)
    const planning = useTrainingPlanning(today, today)
    const ahead = useTrainingPlanning(dayKey(addDays(now, 1)), dayKey(addDays(now, LOOKAHEAD_IN_DAYS)))
    const routines = useRoutines()
    const start = useStartRoutineAction()
    const navigate = useNavigate()

    const entries = new Map((planning.data?.entries ?? []).map((entry) => [entry.id, entry]))
    const sessions = (planning.data?.slots ?? [])
        .filter((slot) => slot.day === today)
        .flatMap((slot) => {
            const entry = entries.get(slot.entryId)

            return entry === undefined ? [] : [{ slot, entry }]
        })
        .sort((a, b) => byStartTime(a.entry, b.entry))

    const movementCounts = new Map((routines.data ?? []).map((routine) => [routine.id, routine.movementCount]))

    const next = (ahead.data?.slots ?? []).find((slot) => slot.state === 'to_come')
    const nextEntry = next === undefined ? undefined : ahead.data?.entries.find((entry) => entry.id === next.entryId)

    return (
        <section className="board-section" aria-labelledby="training-title">
            <SectionHeading
                id="training-title"
                title="Entraînement"
                actions={<IconLink icon={Calendar} label="Voir ma journée" ariaLabel="Voir ma journée dans le planning" to="/planning" />}
            />

            <SystemPanel className="training-card">
                {planning.isPending && <Loader />}
                {planning.isError && <Alert>{FAILURE}</Alert>}

                {planning.isSuccess && sessions.length > 0 && (
                    <div className="training-list">
                        {sessions.map(({ slot, entry }) => (
                            <TrainingLine
                                key={`${slot.entryId}-${slot.day}`}
                                slot={slot}
                                entry={entry}
                                movementCount={movementCounts.get(entry.routineId)}
                                canStart={start.isOffered && !start.isPending}
                                onStart={() => start.start(entry.routineId)}
                            />
                        ))}
                    </div>
                )}

                {planning.isSuccess && sessions.length === 0 && entries.size > 0 && (
                    <div className="training-empty">
                        <div className="training-empty-head">
                            <Moon size={36} strokeWidth={1.4} className="tracker-icon" aria-hidden />
                            <div className="tracker-body">
                                <span className="training-empty-title">Jour de repos</span>
                                <span className="tracker-aux">La récupération fait partie du programme.</span>
                            </div>
                        </div>
                        {nextEntry !== undefined && next !== undefined && (
                            <div className="training-next">
                                <span className="training-next-label">Prochaine séance</span>
                                <span>
                                    {weekdayOf(next.day)} · {nextEntry.routineName}
                                </span>
                            </div>
                        )}
                    </div>
                )}

                {planning.isSuccess && entries.size === 0 && (
                    <div className="training-empty">
                        <div className="training-empty-head">
                            <Dumbbell size={36} strokeWidth={1.4} className="training-empty-icon" aria-hidden />
                            <div className="tracker-body">
                                <span className="training-empty-title">Aucune séance prévue</span>
                                <span className="tracker-aux">Rien au programme aujourd’hui.</span>
                            </div>
                        </div>
                        <Button variant="secondary" onClick={() => void navigate('/planning')}>
                            Planifier une séance
                        </Button>
                    </div>
                )}

                {start.error !== null && <Alert>{start.error}</Alert>}
            </SystemPanel>
        </section>
    )
}

interface TrainingLineProps {
    slot: TrainingPlanningSlot
    entry: TrainingPlanningEntry
    movementCount: number | undefined
    canStart: boolean
    onStart: () => void
}

/** A session: its time, its name and what it holds, then the way to start it — or its check, once done. */
function TrainingLine({ slot, entry, movementCount, canStart, onStart }: TrainingLineProps) {
    const done = slot.state === 'kept'
    const time = entry.schedule.startTime
    const duration = entry.schedule.durationInMinutes
    const details = [duration === null ? null : formatMinutes(duration), movementCount === undefined ? null : formatExerciseCount(movementCount)]
        .filter((part) => part !== null)
        .join(' · ')

    return (
        <div className="training-row">
            {time === null ? (
                <Tooltip label="Sans horaire">
                    <span className="training-time training-time-off" tabIndex={0} aria-label="Sans horaire">
                        --:--
                    </span>
                </Tooltip>
            ) : (
                <span className="training-time">{time}</span>
            )}

            <div className="training-row-body">
                <span className={done ? 'training-name training-name-done' : 'training-name'}>{entry.routineName}</span>
                {details !== '' && <span className="tracker-aux">{details}</span>}
            </div>

            {done ? (
                <Tooltip label="Terminée">
                    <span className="training-done" tabIndex={0} aria-label={`Terminée : ${entry.routineName}`}>
                        <span className="training-done-disc">
                            <Check size={14} strokeWidth={3} aria-hidden />
                        </span>
                    </span>
                </Tooltip>
            ) : (
                <IconButton icon={Play} label="Démarrer" subject={entry.routineName} variant="primary" large disabled={!canStart} onClick={onStart} />
            )}
        </div>
    )
}
