import { type FormEvent, useState } from 'react'
import type { PlanningSchedule, TrainingPlanningEntry } from '../../api/types'
import {
    Alert,
    Button,
    Field,
    FormActions,
    FormGrid,
    Select,
    TextInput,
    ToggleChip,
    useViolations,
} from '../../components'
import { useRoutines } from '../routines/queries'
import { failureOf } from '../workouts/workoutFormat'
import { MONTH_WEEKS, RULES, WEEKDAYS } from './planningFormat'
import { useCreateTrainingPlanningEntry, useUpdateTrainingPlanningEntry } from './queries'

interface TrainingEntryFormProps {
    /** The entry being changed, or null for a new one. */
    editing: TrainingPlanningEntry | null
    /** "YYYY-MM-DD": what a new entry starts on, shown filled in. */
    today: string
    onDone: () => void
}

const MONTH_DAYS = Array.from({ length: 31 }, (_, index) => index + 1)

/**
 * A routine and when: the rule, then only the fields that rule reads. Nothing is chosen for the
 * person — no routine, no rule, no day — but the start date, which opens on today.
 */
export function TrainingEntryForm({ editing, today, onDone }: TrainingEntryFormProps) {
    const routines = useRoutines()
    const create = useCreateTrainingPlanningEntry()
    const update = useUpdateTrainingPlanningEntry()
    const mutation = editing === null ? create : update
    const violations = useViolations(mutation.error)
    const field = (name: string) => violations.for(`schedule.${name}`)

    const initial = editing?.schedule
    const [routineId, setRoutineId] = useState(editing === null ? '' : `${editing.routineId}`)
    const [rule, setRule] = useState(initial?.rule ?? '')
    const [startDate, setStartDate] = useState(initial?.startDate ?? today)
    const [endDate, setEndDate] = useState(initial?.endDate ?? '')
    const [weekdays, setWeekdays] = useState<number[]>(initial?.weekdays ?? [])
    const [weekInterval, setWeekInterval] = useState(`${initial?.weekInterval ?? 1}`)
    const [monthWeeks, setMonthWeeks] = useState<number[]>(initial?.monthWeeks ?? [])
    const [monthDays, setMonthDays] = useState<number[]>(initial?.monthDays ?? [])
    const [startTime, setStartTime] = useState(initial?.startTime ?? '')
    const [duration, setDuration] = useState(initial?.durationInMinutes?.toString() ?? '')

    const submit = async (event: FormEvent) => {
        event.preventDefault()

        const schedule: PlanningSchedule = {
            rule,
            startDate,
            endDate: rule === 'once' || endDate === '' ? null : endDate,
            weekdays,
            weekInterval: Number(weekInterval) || 1,
            monthWeeks,
            monthDays,
            startTime: startTime === '' ? null : startTime,
            durationInMinutes: startTime === '' || duration === '' ? null : Number(duration),
        }
        const payload = { routineId: Number(routineId), schedule }

        try {
            if (editing === null) {
                await create.mutateAsync(payload)
            } else {
                await update.mutateAsync({ id: editing.id, payload })
            }
            onDone()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    const readsWeekdays = rule === 'weekly' || rule === 'monthly_weekday'

    return (
        <form className="form-grid" onSubmit={(event) => void submit(event)}>
            <Field label="Routine" errors={violations.for('routineId')}>
                <Select
                    value={routineId}
                    onChange={setRoutineId}
                    placeholder={routines.isPending ? 'Chargement…' : 'Choisir une routine'}
                    options={(routines.data ?? []).map((routine) => ({ value: `${routine.id}`, label: routine.name }))}
                />
            </Field>

            <Field label="Quand" errors={field('rule')}>
                <Select value={rule} onChange={setRule} placeholder="Choisir une règle" options={RULES} />
            </Field>

            <FormGrid columns={2}>
                <Field label={rule === 'once' ? 'Le' : 'À partir du'} errors={field('startDate')}>
                    <TextInput type="date" value={startDate} onChange={(event) => setStartDate(event.target.value)} />
                </Field>
                {rule !== 'once' && (
                    <Field label="Jusqu’au (facultatif)" errors={field('endDate')}>
                        <TextInput type="date" value={endDate} onChange={(event) => setEndDate(event.target.value)} />
                    </Field>
                )}
            </FormGrid>

            {readsWeekdays && (
                <Field label="Jours" errors={field('weekdays')}>
                    <div className="chip-choices">
                        {WEEKDAYS.map((day) => (
                            <ToggleChip key={day.value} pressed={weekdays.includes(day.value)} onToggle={() => setWeekdays(toggled(weekdays, day.value))}>
                                {day.long}
                            </ToggleChip>
                        ))}
                    </div>
                </Field>
            )}

            {rule === 'weekly' && (
                <Field label="Une semaine sur" errors={field('weekInterval')}>
                    <TextInput type="number" inputMode="numeric" min={1} max={52} value={weekInterval} onChange={(event) => setWeekInterval(event.target.value)} />
                </Field>
            )}

            {rule === 'monthly_weekday' && (
                <Field label="Rang dans le mois" errors={field('monthWeeks')}>
                    <div className="chip-choices">
                        {MONTH_WEEKS.map((week) => (
                            <ToggleChip key={week.value} pressed={monthWeeks.includes(week.value)} onToggle={() => setMonthWeeks(toggled(monthWeeks, week.value))}>
                                {week.label}
                            </ToggleChip>
                        ))}
                    </div>
                </Field>
            )}

            {rule === 'monthly_day' && (
                <Field label="Jours du mois" errors={field('monthDays')}>
                    <div className="chip-choices">
                        {MONTH_DAYS.map((day) => (
                            <ToggleChip key={day} pressed={monthDays.includes(day)} onToggle={() => setMonthDays(toggled(monthDays, day))}>
                                {day}
                            </ToggleChip>
                        ))}
                    </div>
                </Field>
            )}

            <FormGrid columns={2}>
                <Field label="Heure (facultatif)" errors={field('startTime')}>
                    <TextInput type="time" value={startTime} onChange={(event) => setStartTime(event.target.value)} />
                </Field>
                {startTime !== '' && (
                    <Field label="Durée en minutes (facultatif)" errors={field('durationInMinutes')}>
                        <TextInput type="number" inputMode="numeric" min={1} max={1440} placeholder="75" value={duration} onChange={(event) => setDuration(event.target.value)} />
                    </Field>
                )}
            </FormGrid>

            {violations.isGeneral && <Alert>{failureOf(mutation.error) ?? ''}</Alert>}

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>
                <Button submit disabled={mutation.isPending}>
                    {editing === null
                        ? mutation.isPending ? 'Planifier…' : 'Planifier'
                        : mutation.isPending ? 'Enregistrer…' : 'Enregistrer'}
                </Button>
            </FormActions>
        </form>
    )
}

function toggled(values: number[], value: number): number[] {
    return values.includes(value) ? values.filter((one) => one !== value) : [...values, value]
}
