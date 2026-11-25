import { Form as AntForm, Input } from 'antd'
import { useMandatoryRules, type FieldProps } from './fieldProps'

export function TextField({ name, label, isMandatory = false, placeholder, hint, autoFocus, disabled }: FieldProps) {
    const rules = useMandatoryRules(isMandatory)

    return (
        <AntForm.Item label={label} name={name} rules={rules} extra={hint}>
            <Input placeholder={placeholder} autoFocus={autoFocus} disabled={disabled} />
        </AntForm.Item>
    )
}
