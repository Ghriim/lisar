import type { ReactNode } from 'react'
import { FieldFrame, type FieldProps } from './Field'

export interface Choice<TValue extends string | number> {
    value: TValue
    label: ReactNode
}

/** Choices shown under a heading of their own — movements under their family. */
export interface ChoiceGroup<TValue extends string | number> {
    label: string
    choices: Choice<TValue>[]
}

interface ChoiceFieldProps<TValue extends string | number> extends FieldProps {
    /** Either a flat set of choices, or several sets, each under its heading. */
    choices: Choice<TValue>[] | ChoiceGroup<TValue>[]
    chosen: TValue[]
    /**
     * Called with the chip clicked, chosen or not. What a click does is the caller's: most add or
     * remove it, a picker capped at one replaces what was chosen.
     */
    onToggle: (value: TValue) => void
    /** Shown instead of the chips while there are none: still loading, or nothing matches. */
    empty?: ReactNode
}

/** Several choices, each a chip toggled on and off: the days of a plan, the movements of a block. */
export function ChoiceField<TValue extends string | number>({
    label,
    isMandatory = false,
    errors,
    disabled,
    choices,
    chosen,
    onToggle,
    empty,
}: ChoiceFieldProps<TValue>) {
    const chips = (list: Choice<TValue>[]) => (
        <div className="chip-choices">
            {list.map((choice) => (
                <button
                    key={choice.value}
                    type="button"
                    className="chip"
                    aria-pressed={chosen.includes(choice.value)}
                    disabled={disabled}
                    onClick={() => onToggle(choice.value)}
                >
                    {choice.label}
                </button>
            ))}
        </div>
    )

    return (
        <FieldFrame label={label} isMandatory={isMandatory} errors={errors} group>
            {choices.length === 0
                ? empty
                : isGrouped(choices)
                ? choices.map((group) => (
                      <div key={group.label} className="choice-group">
                          <span className="choice-group-label">{group.label}</span>
                          {chips(group.choices)}
                      </div>
                  ))
                : chips(choices)}
        </FieldFrame>
    )
}

function isGrouped<TValue extends string | number>(
    choices: Choice<TValue>[] | ChoiceGroup<TValue>[],
): choices is ChoiceGroup<TValue>[] {
    return choices.length > 0 && 'choices' in choices[0]
}
