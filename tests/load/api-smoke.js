import http from 'k6/http';
import { check } from 'k6';

// Quick smoke test - runs in ~30 seconds
export const options = {
    vus: 5,           // 5 virtual users
    duration: '30s',  // Run for 30 seconds
    thresholds: {
        http_req_duration: ['p(95)<2000'],  // 95% under 2s
        http_req_failed: ['rate<0.1'],      // Less than 10% errors
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
    // Product listing
    const listRes = http.get(`${BASE_URL}/api/v1/products?per_page=24`);
    check(listRes, {
        'list status 200': (r) => r.status === 200,
    });

    // Product search (parameter is 'q')
    const searchRes = http.get(`${BASE_URL}/api/v1/products/search?q=test`);
    check(searchRes, {
        'search status 200': (r) => r.status === 200,
    });
}
