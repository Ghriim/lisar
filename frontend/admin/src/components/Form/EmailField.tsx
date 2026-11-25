import { Form as AntForm, Input } from 'antd'
import { useMandatoryRules, type FieldProps } from './fieldProps'

export function EmailField({ name, label, isMandatory = false, autoFocus }: FieldProps) {
    const rules = useMandatoryRules(isMandatory)

    return (
        <AntForm.Item label={label} name={name} rules={rules}>
            <Input type="email" autoComplete="email" autoFocus={autoFocus} />
        </AntForm.Item>
    )
}
