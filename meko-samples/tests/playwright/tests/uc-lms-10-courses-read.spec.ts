import { test, expect } from '@playwright/test';
import { config } from '../src/config';
import { getAuthHeaders } from '../src/auth-helper';

test.describe('UC-LMS-10 — Read Courses & LOM Metadata @uc10 @deployed', () => {
  test('UC-10.1: Happy Path — List courses with pagination and default filters', async ({ request }) => {
    const headers = await getAuthHeaders(request);
    const response = await request.get(`${config.restApiV2Url}/local_accees_sync_api/courses?page=1&per_page=10`, { headers });
    expect(response.status()).toBe(200);

    const data = await response.json();
    expect(data).toHaveProperty('items');
    expect(Array.isArray(data.items)).toBeTruthy();
    expect(data).toHaveProperty('pagination');
    expect(data.pagination).toMatchObject({
      page: 1,
      per_page: 10,
    });
    expect(typeof data.pagination.total).toBe('number');
  });

  test('UC-10.2: Happy Path — Course list filtering (category, search, status, delivery_mode)', async ({ request }) => {
    const headers = await getAuthHeaders(request);
    const response = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses?category_id=${config.testCategoryId}&per_page=5`,
      { headers }
    );
    expect(response.status()).toBe(200);
    const data = await response.json();
    expect(Array.isArray(data.items)).toBeTruthy();
  });

  test('UC-10.3: Happy Path — Read single course details with custom_fields and LOM', async ({ request }) => {
    const headers = await getAuthHeaders(request);
    const response = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${config.sampleCourseId}`,
      { headers }
    );
    expect(response.status()).toBe(200);

    const course = await response.json();
    expect(course.id).toBe(config.sampleCourseId);
    expect(course).toHaveProperty('shortname');
    expect(course).toHaveProperty('fullname');
    expect(course).toHaveProperty('custom_fields');
    expect(course).toHaveProperty('lom');
  });

  test('UC-10.4: Negative — per_page > 100 returns 400 INVALID_QUERY_PARAM', async ({ request }) => {
    const headers = await getAuthHeaders(request);
    const response = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses?per_page=101`,
      { headers }
    );
    expect(response.status()).toBe(400);

    const body = await response.json();
    expect(body.error?.code).toBe('INVALID_QUERY_PARAM');
  });

  test('UC-10.5: Negative — Non-existent course ID returns 404 COURSE_NOT_FOUND', async ({ request }) => {
    const headers = await getAuthHeaders(request);
    const response = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses/999999`,
      { headers }
    );
    expect(response.status()).toBe(404);

    const body = await response.json();
    expect(body.error?.code).toBe('COURSE_NOT_FOUND');
  });

  test('UC-10.6: Security — Read courses without token returns 401 UNAUTHORIZED', async ({ request }) => {
    const response = await request.get(`${config.restApiV2Url}/local_accees_sync_api/courses`);
    expect(response.status()).toBe(401);
  });

  for (const version of ['1.0', '1.1']) {
    test(`UC-10.7: API ${version} preserves all nine LOM groups in list and detail`, async ({ request }) => {
      const headers = await getAuthHeaders(request, { 'X-ACCEES-API-Version': version });
      const groups = ['general', 'lifecycle', 'metametadata', 'technical', 'educational',
        'rights', 'relation', 'annotation', 'classification'];
      for (const path of ['/courses?per_page=100', `/courses/${config.sampleCourseId}`]) {
        const response = await request.get(`${config.restApiV2Url}/local_accees_sync_api${path}`, { headers });
        expect(response.status()).toBe(200);
        const data = await response.json();
        const courses = Array.isArray(data.items) ? data.items : [data];
        expect(courses.length).toBeGreaterThan(0);
        for (const course of courses) {
          for (const group of groups) expect(course.lom).toHaveProperty(group);
        }
      }
    });
  }

  test('UC-10.8: Unsupported API version returns 400 for list and detail', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'X-ACCEES-API-Version': '2.0' });
    for (const path of ['/courses', `/courses/${config.sampleCourseId}`]) {
      const response = await request.get(`${config.restApiV2Url}/local_accees_sync_api${path}`, { headers });
      expect(response.status()).toBe(400);
      expect((await response.json()).error.code).toBe('INVALID_QUERY_PARAM');
    }
  });
});

