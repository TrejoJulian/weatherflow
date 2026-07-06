import http from 'k6/http';
import { check, sleep } from 'k6';
import { buildOptions, MONITOR_BASE_URL, STATION_ID } from './lib/profiles.js';

export const options = buildOptions();

export default function () {
    const response = http.get(`${MONITOR_BASE_URL}/api/reports/avg/day?station_id=${STATION_ID}`);

    check(response, {
        'status is 200': (result) => result.status === 200,
        'body has a numeric averageTemperature': (result) => typeof result.json('averageTemperature') === 'number',
    });

    sleep(1);
}
