import http from 'k6/http';
import { check, sleep } from 'k6';
import { buildOptions, CORE_BASE_URL, STATION_ID } from './lib/profiles.js';

export const options = buildOptions();

export default function () {
    const response = http.get(`${CORE_BASE_URL}/api/reports/current-temp/${STATION_ID}`);

    check(response, {
        'status is 200': (result) => result.status === 200,
        'body has a numeric temperature': (result) => typeof result.json('temperature') === 'number',
    });

    sleep(1);
}