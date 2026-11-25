import { Form as AntForm, InputNumber } from 'antd'
import { useMandatoryRules, type FieldProps } from './fieldProps'

interface NumberFieldProps extends FieldProps {
    min?: number
    max?: number
}

export function NumberField({ name, label, isMandatory = false, hint, min, max }: NumberFieldProps) {
    const rules = useMandatoryRules(isMandatory)

    return (
        <AntForm.Item label={label} name={name} rules={rules} extra={hint}>
            <InputNumber min={min} max={max} style={{ width: '100%' }} />
        </AntForm.Item>
    )
}
