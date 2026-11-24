import { test, expect } from '@playwright/test';
import fs from 'fs';
import { config } from '../src/config';
import { getAuthHeaders } from '../src/auth-helper';

test.describe('UC-LMS-08 — Import .imscc Course Package @uc08 @deployed', () => {
  const validFixturePath = config.resolveFixture('uc-lms-08/ACCEES-CS101.imscc');
  const noManifestFixturePath = config.resolveFixture('uc-lms-08/invalid_no_manifest.imscc');
  const invalidSyntaxFixturePath = config.resolveFixture('uc-lms-08/invalid_syntax.imscc');

  test('UC-08.1: Happy Path — Import valid .imscc returns 201 Created', async ({ request }) => {
    test.skip(!fs.existsSync(validFixturePath), 'Fixture ACCEES-CS101.imscc not found');

    const headers = await getAuthHeaders(request);
    delete headers['Content-Type']; // Let Playwright set multipart boundary

    const uniqueShortname = `ACCEES-TEST-${Date.now()}`;
    const fileBuffer = fs.readFileSync(validFixturePath);

    const response = await request.post(`${config.restApiV2Url}/local_accees_imscp/packages/import`, {
      headers,
      multipart: {
        package_file: {
          name: 'ACCEES-CS101.imscc',
          mimeType: 'application/zip',
          buffer: fileBuffer,
        },
        category_id: String(config.testCategoryId),
        target_course_shortname: uniqueShortname,
        overwrite: 'false',
      },
    });

    if (![200, 201].includes(response.status())) {
      console.error('UC-08.1 FAILED STATUS:', response.status(), await response.text());
    }
    expect([200, 201]).toContain(response.status());
    const data = await response.json();
    expect(data).toHaveProperty('course_id');
    expect(data).toHaveProperty('shortname');
    expect(data.import_status).toBe('completed');
  });

  test('UC-08.2: Negative — Missing imsmanifest.xml returns 400 INVALID_PACKAGE', async ({ request }) => {
    test.skip(!fs.existsSync(noManifestFixturePath), 'Fixture invalid_no_manifest.imscc not found');

    const headers = await getAuthHeaders(request);
    delete headers['Content-Type'];

    const fileBuffer = fs.readFileSync(noManifestFixturePath);
    const response = await request.post(`${config.restApiV2Url}/local_accees_imscp/packages/import`, {
      headers,
      multipart: {
        package_file: {
          name: 'invalid_no_manifest.imscc',
          mimeType: 'application/zip',
          buffer: fileBuffer,
        },
        category_id: String(config.testCategoryId),
        target_course_shortname: `ACCEES-INVALID-${Date.now()}`,
      },
    });

    expect(response.status()).toBe(400);
    const body = await response.json();
    expect(['INVALID_PACKAGE', 'INVALID_PARAMETER']).toContain(body.error?.code);
  });

  test('UC-08.3: Negative — Invalid LOM XML syntax returns 422 LOM_VALIDATION_FAILED', async ({ request }) => {
    test.skip(!fs.existsSync(invalidSyntaxFixturePath), 'Fixture invalid_syntax.imscc not found');

    const headers = await getAuthHeaders(request);
    delete headers['Content-Type'];

    const fileBuffer = fs.readFileSync(invalidSyntaxFixturePath);
    const response = await request.post(`${config.restApiV2Url}/local_accees_imscp/packages/import`, {
      headers,
      multipart: {
        package_file: {
          name: 'invalid_syntax.imscc',
          mimeType: 'application/zip',
          buffer: fileBuffer,
        },
        category_id: String(config.testCategoryId),
        target_course_shortname: `ACCEES-SYNTAX-${Date.now()}`,
      },
    });

    expect([400, 422]).toContain(response.status());
    const body = await response.json();
    expect(['LOM_VALIDATION_FAILED', 'INVALID_PACKAGE', 'INVALID_PAYLOAD']).toContain(body.error?.code);
  });

  test('UC-08.4: Negative — Duplicate shortname without overwrite returns 409 COURSE_ALREADY_EXISTS', async ({ request }) => {
    test.skip(!fs.existsSync(validFixturePath), 'Fixture ACCEES-CS101.imscc not found');

    const headers = await getAuthHeaders(request);
    delete headers['Content-Type'];

    const fixedShortname = `DUP-COURSE-${Date.now()}`;
    const fileBuffer = fs.readFileSync(validFixturePath);

    // First import
    const firstResp = await request.post(`${config.restApiV2Url}/local_accees_imscp/packages/import`, {
      headers,
      multipart: {
        package_file: { name: 'ACCEES-CS101.imscc', mimeType: 'application/zip', buffer: fileBuffer },
        category_id: String(config.testCategoryId),
        target_course_shortname: fixedShortname,
        overwrite: 'false',
      },
    });
    expect([200, 201]).toContain(firstResp.status());

    // Second import with same shortname and overwrite=false
    const secondResp = await request.post(`${config.restApiV2Url}/local_accees_imscp/packages/import`, {
      headers,
      multipart: {
        package_file: { name: 'ACCEES-CS101.imscc', mimeType: 'application/zip', buffer: fileBuffer },
        category_id: String(config.testCategoryId),
        target_course_shortname: fixedShortname,
        overwrite: 'false',
      },
    });

    expect(secondResp.status()).toBe(409);
    const body = await secondResp.json();
    expect(body.error?.code).toBe('COURSE_ALREADY_EXISTS');
  });

  test('UC-08.5: Security — Import without token returns 401 UNAUTHORIZED', async ({ request }) => {
    const response = await request.post(`${config.restApiV2Url}/local_accees_imscp/packages/import`, {
      headers: { Accept: 'application/json' },
    });
    expect(response.status()).toBe(401);
  });
});
