import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const productListDuration = new Trend('product_list_duration');
const productSearchDuration = new Trend('product_search_duration');
const productDetailDuration = new Trend('product_detail_duration');

// Test configuration
export const options = {
    stages: [
        { duration: '30s', target: 10 },   // Warm up
        { duration: '1m', target: 25 },    // Ramp up
        { duration: '2m', target: 25 },    // Sustained load
        { duration: '30s', target: 50 },   // Spike test
        { duration: '30s', target: 25 },   // Recovery
        { duration: '30s', target: 0 },    // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<1000'],  // 95% of requests under 1s
        http_req_failed: ['rate<0.05'],     // Less than 5% errors
        errors: ['rate<0.05'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

// Sample search terms for realistic testing
const searchTerms = ['shoes', 'dress', 'bag', 'watch', 'phone', 'laptop', 'shirt', 'jacket'];

export default function () {
    group('Product Listing', function () {
        // Test product listing with pagination
        const listRes = http.get(`${BASE_URL}/api/v1/products?per_page=24`);
        productListDuration.add(listRes.timings.duration);

        const listSuccess = check(listRes, {
            'product list status 200': (r) => r.status === 200,
            'product list has data': (r) => {
                try {
                    const body = JSON.parse(r.body);
                    return body.data && Array.isArray(body.data);
                } catch {
                    return false;
                }
            },
        });
        errorRate.add(!listSuccess);
    });

    sleep(0.5);

    group('Product Search', function () {
        // Test search with random term (parameter is 'q')
        const term = searchTerms[Math.floor(Math.random() * searchTerms.length)];
        const searchRes = http.get(`${BASE_URL}/api/v1/products/search?q=${term}`);
        productSearchDuration.add(searchRes.timings.duration);

        const searchSuccess = check(searchRes, {
            'search status 200': (r) => r.status === 200,
        });
        errorRate.add(!searchSuccess);
    });

    sleep(0.5);

    group('Product Detail', function () {
        // First get a product ID from the list
        const listRes = http.get(`${BASE_URL}/api/v1/products?per_page=5`);

        if (listRes.status === 200) {
            try {
                const body = JSON.parse(listRes.body);
                if (body.data && body.data.length > 0) {
                    const randomProduct = body.data[Math.floor(Math.random() * body.data.length)];
                    const productId = randomProduct.id;

                    const detailRes = http.get(`${BASE_URL}/api/v1/products/${productId}`);
                    productDetailDuration.add(detailRes.timings.duration);

                    const detailSuccess = check(detailRes, {
                        'product detail status 200': (r) => r.status === 200,
                        'product detail has data': (r) => {
                            try {
                                const detailBody = JSON.parse(r.body);
                                return detailBody.data && detailBody.data.id;
                            } catch {
                                return false;
                            }
                        },
                    });
                    errorRate.add(!detailSuccess);
                }
            } catch {
                errorRate.add(true);
            }
        }
    });

    sleep(1);
}

// Summary at the end of the test
export function handleSummary(data) {
    const summary = {
        'Total Requests': data.metrics.http_reqs.values.count,
        'Failed Requests': data.metrics.http_req_failed.values.passes,
        'Avg Response Time': `${data.metrics.http_req_duration.values.avg.toFixed(2)}ms`,
        'p95 Response Time': `${data.metrics.http_req_duration.values['p(95)'].toFixed(2)}ms`,
        'p99 Response Time': `${data.metrics.http_req_duration.values['p(99)'].toFixed(2)}ms`,
        'Max Response Time': `${data.metrics.http_req_duration.values.max.toFixed(2)}ms`,
    };

    console.log('\n=== PERFORMANCE SUMMARY ===');
    for (const [key, value] of Object.entries(summary)) {
        console.log(`${key}: ${value}`);
    }
    console.log('===========================\n');

    return {
        'stdout': JSON.stringify(summary, null, 2),
    };
}
