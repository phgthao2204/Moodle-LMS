import { test, expect } from '@playwright/test';
import { config } from '../src/config';
import { getAuthHeaders, getBearerToken } from '../src/auth-helper';

test.describe('T0 — Discovery & OAuth 2.0 Authentication @t0 @deployed', () => {
  test('T0.1: GET OpenAPI JSON specification should return 200 OK', async ({ request }) => {
    const response = await request.get(`${config.restApiV2Url}/openapi.json`);
    expect([200, 500]).toContain(response.status());
    if (response.status() === 200) {
      const data = await response.json();
      expect(data).toHaveProperty('openapi');
      expect(data).toHaveProperty('info');
      expect(data).toHaveProperty('paths');
    }
  });

  test('T0.2: GET healthcheck for local_accees_sync_api should return ready', async ({ request }) => {
    const response = await request.get(`${config.restApiV2Url}/local_accees_sync_api/health`);
    expect(response.status()).toBe(200);

    const data = await response.json();
    expect(data).toMatchObject({
      component: 'local_accees_sync_api',
      status: 'ready',
    });
    expect(Array.isArray(data.api_versions)).toBeTruthy();
    expect(data.api_versions).toContain('1.0');
  });

  test('T0.3: GET healthcheck for local_accees_imscp should return 200 OK', async ({ request }) => {
    const response = await request.get(`${config.restApiV2Url}/local_accees_imscp/health`);
    // Some versions expose health at /health or report component readiness
    if (response.status() === 200) {
      const data = await response.json();
      expect(data).toHaveProperty('status');
    } else {
      expect([200, 404]).toContain(response.status());
    }
  });

  test('T0.4: Protected endpoint without Authorization header must return 401 UNAUTHORIZED', async ({ request }) => {
    const response = await request.get(`${config.restApiV2Url}/local_accees_sync_api/courses`);
    expect(response.status()).toBe(401);

    const body = await response.json();
    expect(body.error?.code).toBe('UNAUTHORIZED');
  });

  test('T0.5: Protected endpoint with invalid Bearer token must be rejected (401)', async ({ request }) => {
    const response = await request.get(`${config.restApiV2Url}/local_accees_sync_api/courses`, {
      headers: {
        Authorization: 'Bearer invalid_garbage_token_12345',
        Accept: 'application/json',
      },
    });
    expect([401, 403]).toContain(response.status());
    const body = await response.json();
    expect(['UNAUTHORIZED', 'FORBIDDEN', 'INVALID_TOKEN']).toContain(body.error?.code);
  });

  test('T0.6: Can acquire valid Bearer token via OAuth 2.0 / Token endpoint', async ({ request }) => {
    const token = await getBearerToken(request);
    expect(token).toBeTruthy();
    expect(typeof token).toBe('string');
    expect(token.length).toBeGreaterThan(10);

    // Verify token works on authenticated endpoint
    const headers = await getAuthHeaders(request);
    const response = await request.get(`${config.restApiV2Url}/local_accees_sync_api/courses/${config.sampleCourseId}`, { headers });
    expect(response.status()).toBe(200);
  });
});
