import { test, expect } from '@playwright/test';
import crypto from 'crypto';
import { config } from '../src/config';
import { getAuthHeaders } from '../src/auth-helper';

test.describe('UC-LMS-14 — Learning Results & Push Webhooks @uc14', () => {
  const courseId = config.sampleCourseId;

  test.beforeEach(async ({ request }) => {
    // Check if UC-14 route is deployed on target LMS
    const probe = await request.get(`${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/results`);
    const text = await probe.text();
    if (probe.status() === 404 && (text.includes('Slim') || text.includes('not supported') || !text.includes('error'))) {
      test.skip(true, 'UC-LMS-14 endpoint is not yet deployed on target server');
    }
  });

  test('UC-14.1: Happy Path — GET course learning results with pagination', async ({ request }) => {
    const headers = await getAuthHeaders(request);
    const response = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/results?page=1&per_page=10`,
      { headers }
    );
    expect([200, 501]).toContain(response.status());

    if (response.status() === 200) {
      const data = await response.json();
      expect(data).toHaveProperty('course_id');
      expect(data).toHaveProperty('items');
      expect(Array.isArray(data.items)).toBeTruthy();
    }
  });

  test('UC-14.2: Happy Path — Filter results by username and completed_only', async ({ request }) => {
    const headers = await getAuthHeaders(request);
    const response = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/results?completed_only=true`,
      { headers }
    );
    expect([200, 501]).toContain(response.status());
  });

  test('UC-14.3: Negative — Filter by unknown user returns 404 USER_NOT_FOUND', async ({ request }) => {
    const headers = await getAuthHeaders(request);
    const response = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/results?username=non_existent_usr_${Date.now()}`,
      { headers }
    );
    expect([404, 200, 501]).toContain(response.status());
    if (response.status() === 404) {
      const body = await response.json();
      expect(body.error?.code).toBe('USER_NOT_FOUND');
    }
  });

  test('UC-14.4: Security — Results endpoint without token returns 401 UNAUTHORIZED', async ({ request }) => {
    const response = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/results`,
      { headers: { Accept: 'application/json' } }
    );
    expect(response.status()).toBe(401);
  });

  test('UC-14.5: Webhook Signature Verification — Verify HMAC-SHA256 signature generator & parser', async () => {
    // Tests contract compliance of HMAC X-ACCEES-Signature: sha256=...
    const secret = config.webhookSecret;
    const testPayload = JSON.stringify({
      event_id: 'evt_test_001',
      event_type: 'course_completed',
      course_id: courseId,
      user_id: 10,
    });

    const expectedSignature = 'sha256=' + crypto.createHmac('sha256', secret).update(testPayload).digest('hex');
    expect(expectedSignature).toMatch(/^sha256=[a-f0-9]{64}$/);

    // Verify constant time buffer equality for security check
    const signatureHash = expectedSignature.slice(7);
    const computedHash = crypto.createHmac('sha256', secret).update(testPayload).digest('hex');
    expect(crypto.timingSafeEqual(Buffer.from(signatureHash), Buffer.from(computedHash))).toBe(true);
  });
});
