import {Notyf} from 'notyf';
import 'notyf/notyf.min.css';

const notyf = new Notyf({
    duration: 3000,
    position: {
        x: 'right',
        y: 'top'
    },
    types: [
        // SUCCESS
        {
            type: 'file_imported',
            background: '#178F96',
            icon: false,
            position: {
                x: 'right',
                y: 'bottom'
            },
            duration: 5000
        },
        {
            type: 'short_success',
            background: '#178F96',
            icon: false,
            position: {
                x: 'right',
                y: 'bottom'
            },
            duration: 2500
        },

        // ERROR
        {
            type: 'error',
            background: 'red',
            icon: false,
            position: {
                x: 'right',
                y: 'bottom'
            },
            duration: 5000
        },
        {
            type: 'short_error',
            background: 'red',
            icon: false,
            position: {
                x: 'right',
                y: 'bottom'
            },
            duration: 2500
        }
    ]
});

export function displayNotif(txt, type) {
    notyf.open({
        type: type,
        message: txt
    })
}