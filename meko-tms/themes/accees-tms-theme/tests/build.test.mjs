import assert from 'node:assert/strict';
import {readFile, readdir} from 'node:fs/promises';
import path from 'node:path';
import test from 'node:test';
import AdmZip from 'adm-zip';
import {root, routes} from '../scripts/build.mjs';
import {renderIcon} from '../scripts/icons.mjs';

const archive = new AdmZip(path.join(root, 'build/libs/accees-tms-theme.war'));

test('production theme defaults to real widgets and mock icons retain their meaning', () => {
    assert.match(archive.readAsText('WEB-INF/liferay-look-and-feel.xml'), /key="demo-mode"[^>]*value="false"/);
    assert.notEqual(renderIcon('<i class="fa-solid fa-magnifying-glass"></i>'), renderIcon('<i class="fa-solid fa-download"></i>'));
    assert.throws(() => renderIcon('<i class="fa-solid fa-unmapped"></i>'), /Unsupported mockup icon/);
});

test('WAR contains the ACCEES identity, fonts, CTU and EU assets', () => {
    for (const name of ['templates/portal_normal.ftl', 'templates/footer.ftl', 'images/logo_accees.png', 'images/logo_ctu.png', 'images/eu_cofunded_white.png']) {
        assert.ok(archive.getEntry(name), name);
    }
    assert.match(archive.readAsText('WEB-INF/liferay-look-and-feel.xml'), /id="accees-tms"/);
    assert.match(archive.readAsText('templates/footer.ftl'), /101179860/);
    for (const entry of archive.getEntries().filter(entry => entry.entryName.endsWith('.woff2'))) {
        assert.equal(entry.getData().subarray(0, 4).toString(), 'wOF2');
    }
    assert.match(archive.readAsText('css/fonts.css'), /U\+1EA0-1EF9/);
});

test('linked mockup screens are packaged with deployed portal routes', async () => {
    const pages = JSON.parse(await readFile(path.join(root, 'fixtures/pages.json'), 'utf8'));
    assert.equal(Object.keys(pages).length, 9);
    for (const key of Object.keys(pages)) {
        const html = archive.readAsText(`templates/demo/${key}.ftl`);
        assert.ok(html.length > 100);
        assert.doesNotMatch(html, /(?:\d{2}_.*?\.html|http:\/\/meko\.localhost)/);
        for (const image of html.matchAll(/src="\$\{images_folder\}\/([^"]+)"/g)) {
            assert.ok(archive.getEntry('images/' + image[1]), `${key}: missing ${image[1]}`);
        }
    }
    assert.equal(routes['13_liferay_tms_course_plan_form.html'], '/admin/course-plan');
    assert.equal(routes['10_liferay_tms_learner_dashboard.html'], '/my-profile');
    assert.equal(routes['12_email_enrollment_rejected.html'], '/admin/email-rejected');
    assert.match(archive.readAsText('templates/demo/home.ftl'), /href="\$\{registration_url\}"/);
});

test('compiled theme uses solid colors, Be Vietnam Pro and 1320px containers', () => {
    const css = archive.readAsText('css/main.css');
    assert.match(css, /max-width: 1320px/);
    assert.match(css, /Be Vietnam Pro/);
    assert.match(css, /#0000B6/i);
    assert.match(css, /#39B500/i);
    assert.match(css, /#EA6400/i);
    assert.match(css, /#0e1726/i);
});

test('every LMS link leaves Liferay SPA with a full page navigation', () => {
    let checked = 0;
    for (const entry of archive.getEntries().filter(entry => entry.entryName.endsWith('.ftl'))) {
        for (const match of entry.getData().toString().matchAll(/<a\b[^>]*href="\$\{lms_url\}[^"]*"[^>]*>/g)) {
            checked++;
            assert.match(match[0], /\bdata-senna-off="true"/, `${entry.entryName}: Moodle must load its own document and styles`);
        }
    }
    assert.ok(checked >= 5, 'Check header, footer and fixture links to LMS');
});

test('theme source and fixtures contain no emoji, gradients or embedded scripts', async () => {
    async function scan(directory) {
        for (const entry of await readdir(directory, {withFileTypes: true})) {
            const file = path.join(directory, entry.name);
            if (entry.isDirectory()) await scan(file);
            else if (/\.(?:scss|ftl|js|json|svg)$/.test(entry.name)) {
                const source = await readFile(file, 'utf8');
                assert.doesNotMatch(source, /[\u{1F300}-\u{1FAFF}]/u, file);
                assert.doesNotMatch(source, /(?:linear|radial)-gradient|<linearGradient|<radialGradient/i, file);
            }
        }
    }
    await scan(path.join(root, 'src'));
    await scan(path.join(root, 'fixtures'));
});
