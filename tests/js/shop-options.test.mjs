import assert from 'node:assert/strict';
import test from 'node:test';

import {
    collectCartOptions,
    collectConfiguration,
} from '../../resources/js/shop-options.js';

const optionInput = ({ value, text, price = 0 }) => ({
    value,
    dataset: { price: String(price) },
    closest: () => ({
        querySelector: () => ({ textContent: text }),
        textContent: text,
    }),
});

const scope = (selectors, selectorLists = {}) => ({
    querySelector: (selector) => selectors[selector] || null,
    querySelectorAll: (selector) => selectorLists[selector] || [],
    closest: () => null,
    parentElement: null,
});

test('collectCartOptions returns serializable legacy product choices', () => {
    const root = scope({
        '.side': { value: 'Левое' },
        '.widthType:checked': optionInput({ value: 'Ширина по ткани', text: 'Ширина по ткани' }),
        '.controlColor:checked': optionInput({ value: '#fff', text: 'Белый' }),
    });

    assert.deepEqual(collectCartOptions(root), {
        side: 'left',
        widthType: 'fabric',
        controlColor: 'white',
        configuration: {},
    });
});

test('collectConfiguration includes every roller shutter option and its price', () => {
    const additionalSelector = [
        'input[name="ral-paint"]:checked',
        'input[name="popup-ral-paint"]:checked',
        'input[name="photo-print"]:checked',
        'input[name="popup-photo-print"]:checked',
    ].join(',');
    const root = scope({
        'input[name="installation-type"]:checked,input[name="popup-installation-type"]:checked':
            optionInput({ value: 'built-in', text: 'Встроенный монтаж', price: 1200 }),
        'input[name*="widhType"]:checked':
            optionInput({ value: 'Ширина по габариту', text: 'Короб снаружи' }),
        'input[name="control-type"]:checked,input[name="popup-control-type"]:checked':
            optionInput({ value: 'electric', text: 'Автоматическое управление', price: 7000 }),
        'input[name="lock-type"]:checked,input[name="popup-lock-type"]:checked':
            optionInput({ value: 'lock', text: 'Замок', price: 1600 }),
        'input[name="lock-device"]:checked,input[name="popup-lock-device"]:checked':
            optionInput({ value: 'rigel', text: 'Ригельный замок', price: 900 }),
    }, {
        [additionalSelector]: [
            optionInput({ value: 'ral-paint', text: 'Окраска в цвет RAL', price: 3500 }),
            optionInput({ value: 'photo-print', text: 'Фотопечать', price: 5000 }),
        ],
    });

    assert.deepEqual(collectConfiguration(root), {
        installation_type: {
            label: 'Вид монтажа',
            value: 'Встроенный монтаж',
            code: 'built-in',
            price: 1200,
        },
        box_position: {
            label: 'Тип монтажа',
            value: 'Короб снаружи',
            code: 'Ширина по габариту',
            price: 0,
        },
        control_type: {
            label: 'Тип управления рольставни',
            value: 'Автоматическое управление',
            code: 'electric',
            price: 7000,
        },
        lock_type: {
            label: 'Тип запорного устройства',
            value: 'Замок',
            code: 'lock',
            price: 1600,
        },
        lock_device: {
            label: 'Блокирующее устройство',
            value: 'Ригельный замок',
            code: 'rigel',
            price: 900,
        },
        additional_options: [
            {
                label: 'Дополнительная опция',
                value: 'Окраска в цвет RAL',
                code: 'ral-paint',
                price: 3500,
            },
            {
                label: 'Дополнительная опция',
                value: 'Фотопечать',
                code: 'photo-print',
                price: 5000,
            },
        ],
    });
});
