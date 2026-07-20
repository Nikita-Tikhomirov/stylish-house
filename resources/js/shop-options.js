const cleanOptionLabel = (input) => {
    const label = input.closest?.('label');
    const namedLabel = label?.querySelector?.('.option-name');
    const text = namedLabel?.textContent || label?.textContent || input.value;

    return String(text)
        .replace(/\s*[+(]?\s*\d[\d\s]*\s*(?:₽|р\.?|руб\.?)[)]?/giu, '')
        .trim();
};

const optionFromInput = (input, label) => ({
    label,
    value: cleanOptionLabel(input),
    code: String(input.value || ''),
    price: Math.max(0, Number.parseInt(input.dataset?.price || '0', 10) || 0),
});

const checked = (root, selectors) => root.querySelector(
    selectors.map((selector) => `${selector}:checked`).join(',')
);

const firstInScopes = (scopes, selector) => {
    for (const scope of scopes) {
        const input = scope?.querySelector?.(selector);
        if (input) {
            return input;
        }
    }

    return null;
};

const relatedScopes = (root, trigger) => {
    const scopes = [root];
    let ancestor = trigger || root;

    for (let depth = 0; ancestor && depth < 4; depth += 1) {
        if (!scopes.includes(ancestor)) {
            scopes.push(ancestor);
        }
        ancestor = ancestor.parentElement;
    }

    return scopes;
};

const normalizedValue = (value, values) => values[String(value).trim().toLowerCase()] || String(value);

export const collectConfiguration = (root) => {
    if (!root) {
        return {};
    }

    const configuration = {};
    const fields = [
        [
            'installation_type',
            'Вид монтажа',
            ['input[name="installation-type"]', 'input[name="popup-installation-type"]'],
        ],
        ['box_position', 'Тип монтажа', ['input[name*="widhType"]']],
        ['control_type', 'Тип управления рольставни', ['input[name="control-type"]', 'input[name="popup-control-type"]']],
        ['lock_type', 'Тип запорного устройства', ['input[name="lock-type"]', 'input[name="popup-lock-type"]']],
        ['lock_device', 'Блокирующее устройство', ['input[name="lock-device"]', 'input[name="popup-lock-device"]']],
    ];

    fields.forEach(([key, label, selectors]) => {
        const input = checked(root, selectors);
        if (!input) {
            return;
        }

        const option = optionFromInput(input, label);
        if (key === 'box_position' && !/короб/iu.test(option.value)) {
            return;
        }

        configuration[key] = option;
    });

    const additionalSelector = [
        'input[name="ral-paint"]:checked',
        'input[name="popup-ral-paint"]:checked',
        'input[name="photo-print"]:checked',
        'input[name="popup-photo-print"]:checked',
    ].join(',');
    const additionalInputs = root.querySelectorAll(additionalSelector);

    if (additionalInputs.length) {
        configuration.additional_options = [...additionalInputs].map((input) =>
            optionFromInput(input, 'Дополнительная опция')
        );
    }

    return configuration;
};

export const collectCartOptions = (root, trigger = null) => {
    if (!root) {
        return { configuration: {} };
    }

    const scopes = relatedScopes(root, trigger);
    const side = firstInScopes(scopes, '.side');
    const widthType = firstInScopes(scopes, '.widthType:checked');
    const controlColor = firstInScopes(scopes, '.controlColor:checked');
    const result = {
        configuration: collectConfiguration(root),
    };

    if (side?.value) {
        result.side = normalizedValue(side.value, {
            'левое': 'left',
            'правое': 'right',
            'слева': 'left',
            'справа': 'right',
        });
    }

    if (widthType?.value) {
        result.widthType = normalizedValue(widthType.value, {
            'ширина по ткани': 'fabric',
            'ширина по габариту': 'overall',
        });
    }

    if (controlColor?.value) {
        result.controlColor = normalizedValue(controlColor.value, {
            '#fff': 'white',
            '#ffffff': 'white',
            '#000': 'black',
            '#000000': 'black',
            '#eee': 'grey',
            '#eeeeee': 'grey',
        });
    }

    return result;
};
