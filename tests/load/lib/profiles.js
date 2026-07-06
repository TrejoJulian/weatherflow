export const DEFAULT_THRESHOLDS = {
    http_req_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.01'],
};

const PROFILES = {
    smoke: {
        executor: 'constant-vus',
        vus: 3,
        duration: '30s',
    },
    load: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '20s', target: 10 },
            { duration: '80s', target: 20 },
            { duration: '20s', target: 0 },
        ],
    },
    stress: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '20s', target: 20 },
            { duration: '30s', target: 50 },
            { duration: '50s', target: 50 },
            { duration: '20s', target: 0 },
        ],
    },
};

export function buildOptions() {
    const profileName = __ENV.PROFILE || 'smoke';
    const profile = PROFILES[profileName];

    if (!profile) {
        throw new Error(`Unknown PROFILE "${profileName}". Valid profiles: ${Object.keys(PROFILES).join(', ')}`);
    }

    return {
        scenarios: { [profileName]: profile },
        thresholds: DEFAULT_THRESHOLDS,
    };
}

export const CORE_BASE_URL = __ENV.BASE_URL_CORE || 'http://core';
export const MONITOR_BASE_URL = __ENV.BASE_URL_MONITOR || 'http://monitor';

// Default stations are seeded with deterministic UUIDs (...0101 UNQ, ...0102 Bariloche, ...0103 Ushuaia).
export const STATION_ID = __ENV.STATION_ID || '00000000-0000-4000-8000-000000000101';