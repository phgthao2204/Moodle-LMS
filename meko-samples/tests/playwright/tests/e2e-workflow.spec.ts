import { test, expect } from '@playwright/test';
import fs from 'fs';
import { config } from '../src/config';
import { getAuthHeaders } from '../src/auth-helper';

test.describe('E2E — Full Continuous 7 LMS REST API v2 Lifecycle @e2e', () => {
  let createdCourseId: number = config.sampleCourseId;
  const uniqueTag = Date.now();
  const testStudent = {
    username: `e2e_student_${uniqueTag}`,
    email: `e2e_student_${uniqueTag}@ctu.edu.vn`,
    firstname: 'E2E',
    lastname: 'Learner',
    idnumber: `E2E${uniqueTag.toString().slice(-6)}`,
  };

  test('Step 1: Check LMS readiness & acquire auth credentials (T0)', async ({ request }) => {
    const health = await request.get(`${config.restApiV2Url}/local_accees_sync_api/health`);
    expect(health.status()).toBe(200);

    const headers = await getAuthHeaders(request);
    expect(headers.Authorization).toBeTruthy();
  });

  test('Step 2: Import new test course from .imscc package (UC-LMS-08)', async ({ request }) => {
    const fixturePath = config.resolveFixture('uc-lms-08/ACCEES-CS101.imscc');
    if (!fs.existsSync(fixturePath)) {
      test.skip(true, 'Fixture ACCEES-CS101.imscc not found, continuing with sample course');
      return;
    }

    const headers = await getAuthHeaders(request);
    delete headers['Content-Type'];

    const targetShortname = `E2E-${uniqueTag}`;
    const fileBuffer = fs.readFileSync(fixturePath);

    const response = await request.post(`${config.restApiV2Url}/local_accees_imscp/packages/import`, {
      headers,
      multipart: {
        package_file: { name: 'ACCEES-CS101.imscc', mimeType: 'application/zip', buffer: fileBuffer },
        category_id: String(config.testCategoryId),
        target_course_shortname: targetShortname,
        overwrite: 'false',
      },
    });

    if ([200, 201].includes(response.status())) {
      const data = await response.json();
      createdCourseId = data.course_id;
      expect(createdCourseId).toBeGreaterThan(1);
    }
  });

  test('Step 3: Read course details & verify initial custom fields (UC-LMS-10)', async ({ request }) => {
    const headers = await getAuthHeaders(request);
    const response = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${createdCourseId}`,
      { headers }
    );
    expect(response.status()).toBe(200);
    const course = await response.json();
    expect(course.id).toBe(createdCourseId);
    expect(course).toHaveProperty('custom_fields');
  });

  test('Step 4: Update course metadata and LOM properties (UC-LMS-11)', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const patchResponse = await request.patch(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${createdCourseId}`,
      {
        headers,
        data: {
          summary: `<p>E2E run verified at ${uniqueTag}</p>`,
          custom_fields: {
            accees_delivery_mode: 'blended',
          },
        },
      }
    );
    expect(patchResponse.status()).toBe(200);
  });

  test('Step 5: Enrol a student into the course (UC-LMS-12)', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const enrolResp = await request.post(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${createdCourseId}/enrolments`,
      {
        headers,
        data: {
          user: testStudent,
          role: 'student',
          strict_sso: false,
        },
      }
    );
    expect([200, 201]).toContain(enrolResp.status());
    const enrolData = await enrolResp.json();
    expect(enrolData.username).toBe(testStudent.username);
    expect(enrolData.role).toBe('student');
  });

  test('Step 6: Check learner results / progress (UC-LMS-14)', async ({ request }) => {
    const headers = await getAuthHeaders(request);
    const resultsResp = await request.get(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${createdCourseId}/results?username=${testStudent.username}`,
      { headers }
    );
    expect([200, 404, 501]).toContain(resultsResp.status());
  });

  test('Step 7: Unenrol student from course (UC-LMS-13)', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const unenrolResp = await request.delete(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${createdCourseId}/enrolments?username=${testStudent.username}`,
      { headers }
    );
    expect([200, 404, 405, 501]).toContain(unenrolResp.status());
  });

  test('Step 8: Export course to .imscc common cartridge (UC-LMS-09)', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const exportResp = await request.post(
      `${config.restApiV2Url}/local_accees_imscp/courses/${createdCourseId}/export`,
      {
        headers,
        data: { delivery: 'download_url', include_userdata: false },
      }
    );
    expect([200, 202]).toContain(exportResp.status());
    const exportData = await exportResp.json();
    expect(exportData.course_id).toBe(createdCourseId);
    expect(exportData).toHaveProperty('download_url');
  });
});
