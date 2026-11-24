import {createHash} from 'node:crypto';
import {mkdir, readFile, writeFile, readdir} from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import AdmZip from 'adm-zip';
import * as sass from 'sass';
import {renderIcon} from './icons.mjs';

export const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export const routes = {
    '07_liferay_tms_portal_home.html': '/home',
    '08_liferay_tms_course_management.html': '/admin',
    '09_liferay_tms_enrollment_records.html': '/admin/enrollments',
    '10_liferay_tms_learner_dashboard.html': '/my-profile',
    '11_email_enrollment_notification.html': '/admin/email-templates',
    '12_email_enrollment_rejected.html': '/admin/email-rejected',
    '13_liferay_tms_course_plan_form.html': '/admin/course-plan',
    '14_liferay_tms_import_reject_logs.html': '/admin/import-logs',
    '15_tms_certificate_qr_verify.html': '/verify'
};

export function renderFixture(page) {
    let html = page.content.replace(/\$\{/g, '&#36;{');
    for (const [file, route] of Object.entries(routes)) {
        html = html.replaceAll(file, '${portal_url}' + route);
    }
    html = html.replace(/(?:01_moodle_lms_homepage|03_moodle_lms_my_courses|05_moodle_lms_course_view_tiles)\.html/g, '${lms_url}/my/');
    html = html.replace(/<a\b[^>]*href="\$\{lms_url\}[^"]*"[^>]*>/g, tag =>
        tag.replace('<a ', '<a data-senna-off="true" '));
    html = html.replaceAll('06_moodle_lms_login_sso.html', '${login_url}');
    html = html.replaceAll('assets/courses/', '${images_folder}/courses/');
    html = html.replaceAll('assets/logo_meko.svg', '${images_folder}/logo_accees.png');
    if (page.source.endsWith('07_liferay_tms_portal_home.html')) {
        html = html.replaceAll('${portal_url}/my-profile', '${registration_url}');
    }
    html = html.replace(/<i\b[^>]*>[\s\S]*?<\/i>/g, renderIcon);
    html = html.replace(/\s+on\w+="[^"]*"/g, '');
    return html;
}

export async function build() {
    const buildDir = path.join(root, 'build');
    await mkdir(path.join(buildDir, 'libs'), {recursive: true});
    const cache = path.join(root, '.cache');
    await mkdir(cache, {recursive: true});
    const baseFile = path.join(cache, 'classic-theme-5.0.105.war');
    let base;
    try {
        base = await readFile(baseFile);
    } catch (error) {
        if (error.code !== 'ENOENT') throw error;
        const response = await fetch('https://repo.maven.apache.org/maven2/com/liferay/plugins/classic-theme/5.0.105/classic-theme-5.0.105.war');
        if (!response.ok) throw new Error(`Classic theme download failed: ${response.status}`);
        base = Buffer.from(await response.arrayBuffer());
        await writeFile(baseFile, base);
    }
    const parent = new AdmZip(base);
    const archive = new AdmZip();
    for (const entry of parent.getEntries()) {
        if (!entry.entryName.startsWith('META-INF/')) archive.addFile(entry.entryName, entry.getData());
    }
    const pages = JSON.parse(await readFile(path.join(root, 'fixtures/pages.json'), 'utf8'));
    let demoStyles = '';
    for (const [key, page] of Object.entries(pages)) {
        archive.addFile(`templates/demo/${key}.ftl`, Buffer.from(renderFixture(page)));
        demoStyles += `.tms-demo.demo-${key} { ${page.styles} }\n`;
    }
    const customStyles = sass.compile(path.join(root, 'src/css/main.scss'), {style: 'expanded'}).css;
    const scopedStyles = sass.compileString(demoStyles, {style: 'expanded', silenceDeprecations: ['legacy-js-api']}).css;
    archive.addFile('css/main.css', Buffer.from(archive.readAsText('css/main.css') + '\n' + scopedStyles + '\n' + customStyles));
    archive.addFile('css/fonts.css', await readFile(path.join(root, 'src/css/_fonts.scss')));
    async function addDirectory(directory, prefix = '') {
        for (const file of await readdir(directory, {withFileTypes: true})) {
            if (file.name === 'css') continue;
            const name = prefix + file.name;
            if (file.isDirectory()) await addDirectory(path.join(directory, file.name), name + '/');
            else archive.addFile(name, await readFile(path.join(directory, file.name)));
        }
    }
    await addDirectory(path.join(root, 'src'));
    const buildHash = createHash('sha256');
    for (const entry of archive.getEntries().sort((a, b) => a.entryName.localeCompare(b.entryName))) {
        buildHash.update(entry.entryName).update(entry.getData());
    }
    const buildId = buildHash.digest('hex').slice(0, 20);
    archive.addFile('templates/portal_normal.ftl', Buffer.from(archive.readAsText('templates/portal_normal.ftl').replaceAll('@ACCEES_BUILD_ID@', buildId)));
    archive.addFile('build-id.txt', Buffer.from(buildId));
    await writeFile(path.join(buildDir, 'theme-build-id.txt'), buildId);
    const cssHash = createHash('sha256').update(customStyles + scopedStyles).digest('hex').slice(0, 16);
    const output = path.join(buildDir, 'libs/accees-tms-theme.war');
    await archive.writeZipPromise(output);
    console.log(`Built ${path.relative(root, output)} (styles ${cssHash}, ${Object.keys(pages).length} demo screens)`);
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) await build();
