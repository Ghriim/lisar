import { Form as AntForm, Select } from 'antd'
import type { ReactNode } from 'react'
import { useMandatoryRules, type FieldProps } from './fieldProps'

export interface SelectOption {
    value: string
    label: ReactNode
}

/** Options shown under a heading — muscles under their group. */
export interface SelectOptionGroup {
    label: string
    options: SelectOption[]
}

interface SelectFieldProps extends FieldProps {
    options: SelectOption[] | SelectOptionGroup[]
    /** Several values at once; the field then holds a list, empty when nothing is picked. */
    multiple?: boolean
    /** Typing narrows the options by their label, which must then be text. */
    searchable?: boolean
}

export function SelectField({
    name,
    label,
    isMandatory = false,
    hint,
    placeholder,
    options,
    multiple = false,
    searchable = false,
}: SelectFieldProps) {
    const rules = useMandatoryRules(isMandatory)

    return (
        <AntForm.Item label={label} name={name} rules={rules} extra={hint}>
            <Select<string | string[], SelectOption | SelectOptionGroup>
                options={options}
                mode={multiple ? 'multiple' : undefined}
                placeholder={placeholder}
                showSearch={searchable ? { optionFilterProp: 'label' } : false}
                // An optional single choice can be taken back: the cross empties it.
                allowClear={!isMandatory && !multiple}
            />
        </AntForm.Item>
    )
}
