import { test, expect } from '@playwright/test';
import { config } from '../src/config';
import { getAuthHeaders } from '../src/auth-helper';

test.describe('UC-LMS-11 — Partial Course & LOM Update @uc11 @deployed', () => {
  test('UC-11.1: Happy Path — Update course fields & verify persistence with GET', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const courseId = config.sampleCourseId;
    const updateTimeTag = Date.now();

    const patchPayload = {
      summary: `<p>Updated via Playwright Automated E2E at ${updateTimeTag}</p>`,
      custom_fields: {
        accees_delivery_mode: 'blended',
        accees_duration: '45',
      },
    };

    const patchResp = await request.patch(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}`,
      { headers, data: patchPayload }
    );
    expect(patchResp.status()).toBe(200);

    const patchData = await patchResp.json();
    expect(patchData.id).toBe(courseId);
    expect(patchData).toHaveProperty('updated_fields');

    // Confirm changes persisted with subsequent GET
    const getResp = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}`,
      { headers }
    );
    expect(getResp.status()).toBe(200);
    const updatedCourse = await getResp.json();
    expect(updatedCourse.custom_fields.accees_delivery_mode).toMatch(/blended/i);
    expect(updatedCourse.custom_fields).toHaveProperty('accees_duration');
  });

  test('UC-11.2: Negative — Invalid custom field value returns 422 INVALID_CUSTOMFIELD_VALUE', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const courseId = config.sampleCourseId;

    const invalidPayload = {
      custom_fields: {
        accees_is_featured: 'yes', // Must be boolean according to spec
      },
    };

    const response = await request.patch(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}`,
      { headers, data: invalidPayload }
    );
    expect([400, 422]).toContain(response.status());
    const body = await response.json();
    expect(['INVALID_CUSTOMFIELD_VALUE', 'INVALID_PAYLOAD']).toContain(body.error?.code);
  });

  test('UC-11.3: Negative — Non-existent course ID returns 404 COURSE_NOT_FOUND', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const response = await request.patch(
      `${config.restApiV2Url}/local_accees_sync_api/courses/999999`,
      { headers, data: { fullname: 'Should Fail' } }
    );
    expect(response.status()).toBe(404);
    const body = await response.json();
    expect(body.error?.code).toBe('COURSE_NOT_FOUND');
  });

  test('UC-11.4: Security — Update course without token returns 401 UNAUTHORIZED', async ({ request }) => {
    const response = await request.patch(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${config.sampleCourseId}`,
      {
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        data: { fullname: 'Unauthenticated update' },
      }
    );
    expect(response.status()).toBe(401);
  });
});
