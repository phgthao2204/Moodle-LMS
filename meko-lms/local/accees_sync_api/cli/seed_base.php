<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    local_accees_sync_api
// @copyright  2026 Du an ACCEES (Erasmus+ CBHE-101179860)
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define('CLI_SCRIPT', true);

$configcandidates = [
    __DIR__ . '/../../../config.php',
    __DIR__ . '/../../../../config.php',
    '/var/www/moodle/config.php',
    '/var/www/html/config.php',
];
$configloaded = false;
foreach ($configcandidates as $candidate) {
    if (file_exists($candidate)) {
        require_once($candidate);
        $configloaded = true;
        break;
    }
}
if (!$configloaded) {
    fwrite(STDERR, "[ERROR] Unable to locate Moodle config.php.\n");
    exit(1);
}

require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/enrollib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'reset-credentials' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($unrecognized)) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    cli_writeln("Usage: php seed_base.php [--reset-credentials] [--help]");
    cli_writeln("Initializes T0 ACCEES-LMS shared fixtures (5 core custom fields, sample course/users, integration role, OAuth2 client).");
    exit(0);
}

global $DB, $CFG;

// Prevent Moodle CLI seeding from attempting outbound SMTP to noreply@<ip>.
$CFG->noemailever = true;

\core\session\manager::set_user(get_admin());

cli_heading('local_accees_sync_api - T0 Base Test Data Seeder');
cli_writeln('Target wwwroot: ' . $CFG->wwwroot);

// -----------------------------------------------------------------------------
// 1. Enable Web Services & REST protocol.
// -----------------------------------------------------------------------------
set_config('enablewebservices', 1);
$protocols = array_filter(array_map('trim', explode(',', (string) ($CFG->webserviceprotocols ?? ''))));
if (!in_array('rest', $protocols, true)) {
    $protocols[] = 'rest';
    set_config('webserviceprotocols', implode(',', $protocols));
}
cli_writeln('[1/6] Web services and REST protocol enabled.');

// -----------------------------------------------------------------------------
// 2. Ensure 5 Core ACCEES Course Custom Fields exist.
// -----------------------------------------------------------------------------
$cfcatname = 'ACCEES Course Metadata';
$cfcat = $DB->get_record('customfield_category', [
    'component' => 'core_course',
    'area' => 'course',
    'name' => $cfcatname,
]);
if (!$cfcat) {
    $cathandler = \core_customfield\handler::get_handler('core_course', 'course');
    $catid = $cathandler->create_category($cfcatname);
    $cfcat = $DB->get_record('customfield_category', ['id' => $catid], '*', MUST_EXIST);
    cli_writeln("  [+] Created customfield category: {$cfcatname}");
}

$customfieldsdef = [
    [
        'shortname' => 'accees_course_status',
        'name' => 'Course Status',
        'type' => 'text',
        'configdata' => ['required' => 0, 'uniquevalues' => 0, 'defaultvalue' => '', 'displaysize' => 40, 'maxlength' => 120, 'ispassword' => 0, 'link' => ''],
    ],
    [
        'shortname' => 'accees_delivery_mode',
        'name' => 'Delivery Mode',
        'type' => 'text',
        'configdata' => ['required' => 0, 'uniquevalues' => 0, 'defaultvalue' => '', 'displaysize' => 40, 'maxlength' => 120, 'ispassword' => 0, 'link' => ''],
    ],
    [
        'shortname' => 'accees_duration',
        'name' => 'Duration Hours',
        'type' => 'text',
        'configdata' => ['required' => 0, 'uniquevalues' => 0, 'defaultvalue' => '', 'displaysize' => 40, 'maxlength' => 120, 'ispassword' => 0, 'link' => ''],
    ],
    [
        'shortname' => 'accees_institution',
        'name' => 'Institution',
        'type' => 'text',
        'configdata' => ['required' => 0, 'uniquevalues' => 0, 'defaultvalue' => '', 'displaysize' => 40, 'maxlength' => 255, 'ispassword' => 0, 'link' => ''],
    ],
    [
        'shortname' => 'accees_is_featured',
        'name' => 'Featured Course',
        'type' => 'checkbox',
        'configdata' => ['required' => 0, 'uniquevalues' => 0, 'checkbydefault' => 0],
    ],
];

$sortorder = 1;
foreach ($customfieldsdef as $def) {
    $field = $DB->get_record('customfield_field', ['shortname' => $def['shortname']]);
    if (!$field) {
        $catctrl = \core_customfield\category_controller::create($cfcat->id);
        $fieldctrl = \core_customfield\field_controller::create(0, (object) [
            'shortname' => $def['shortname'],
            'name' => $def['name'],
            'type' => $def['type'],
            'sortorder' => $sortorder,
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'configdata' => json_encode($def['configdata']),
        ], $catctrl);
        $fieldctrl->save();
        cli_writeln("  [+] Created custom field: {$def['shortname']} ({$def['type']})");
    }
    $sortorder++;
}
cli_writeln('[2/6] Verified 5 core ACCEES custom fields.');

// -----------------------------------------------------------------------------
// 3. Ensure Sample Category & Sample Course (ACCEES-CS101).
// -----------------------------------------------------------------------------
$category = $DB->get_record('course_categories', ['idnumber' => 'ACCEES_CAT_DEV']);
if (!$category) {
    $category = \core_course_category::create((object) [
        'name' => 'ACCEES Digital Competence Training',
        'idnumber' => 'ACCEES_CAT_DEV',
        'description' => 'Shared T0 development category for ACCEES-LMS REST API testing.',
        'parent' => 0,
        'visible' => 1,
    ]);
    cli_writeln("  [+] Created category ACCEES_CAT_DEV (id={$category->id})");
}

$samplecourse = $DB->get_record('course', ['shortname' => 'ACCEES-CS101']);
if (!$samplecourse) {
    $samplecourse = create_course((object) [
        'shortname' => 'ACCEES-CS101',
        'fullname' => 'Introduction to Digital Competence and Data Literacy',
        'category' => $category->id,
        'summary' => '<p>Sample ACCEES course for REST API v2 and IMSCC integration verification.</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'visible' => 1,
        'startdate' => time(),
        'enddate' => time() + (90 * DAYSECS),
    ]);
    cli_writeln("  [+] Created sample course ACCEES-CS101 (id={$samplecourse->id})");
}

$lommanager = new \local_accees_sync_api\manager\lom_metadata_manager();
$lommanager->apply_lom((int) $samplecourse->id, [
    'custom_fields' => [
        'accees_course_status' => 'published',
        'accees_delivery_mode' => 'blended',
        'accees_duration' => '30',
        'accees_institution' => 'Can Tho University',
        'accees_is_featured' => true,
    ],
]);
cli_writeln("[3/6] Verified sample course ACCEES-CS101 (id={$samplecourse->id}) with LOM custom fields.");

// -----------------------------------------------------------------------------
// 4. Ensure Sample Accounts & Service Account (accees_service).
// -----------------------------------------------------------------------------
$seedpassword = getenv('ACCEES_SEED_PASSWORD') ?: 'AcceesDev2026!@#';
$ensureuser = static function (array $spec) use ($DB, $CFG, $seedpassword): \stdClass {
    $user = $DB->get_record('user', ['username' => $spec['username'], 'mnethostid' => $CFG->mnet_localhost_id]);
    if ($user) {
        return $user;
    }
    $record = (object) [
        'auth' => 'manual',
        'confirmed' => 1,
        'mnethostid' => $CFG->mnet_localhost_id,
        'username' => $spec['username'],
        'password' => $seedpassword,
        'firstname' => $spec['firstname'],
        'lastname' => $spec['lastname'],
        'email' => $spec['email'],
        'idnumber' => $spec['idnumber'],
        'lang' => 'en',
    ];
    $record->id = user_create_user($record, true, false);
    cli_writeln("  [+] Created user: {$spec['username']} (id={$record->id})");
    return $DB->get_record('user', ['id' => $record->id], '*', MUST_EXIST);
};

$teacher = $ensureuser([
    'username' => 'accees_teacher1',
    'firstname' => 'Giang Vien',
    'lastname' => 'ACCEES',
    'email' => 'teacher1@accees.local',
    'idnumber' => 'GV-ACCEES-001',
]);
$student = $ensureuser([
    'username' => 'accees_student1',
    'firstname' => 'Hoc Vien',
    'lastname' => 'ACCEES',
    'email' => 'student1@accees.local',
    'idnumber' => 'SV-ACCEES-001',
]);
$serviceuser = $ensureuser([
    'username' => 'accees_service',
    'firstname' => 'Integration',
    'lastname' => 'Service',
    'email' => 'service@accees.local',
    'idnumber' => 'SVC-ACCEES-001',
]);

// Enrol sample teacher and student into ACCEES-CS101.
$manualenrol = enrol_get_plugin('manual');
$enrolinstance = $DB->get_record('enrol', ['courseid' => $samplecourse->id, 'enrol' => 'manual']);
if (!$enrolinstance && $manualenrol) {
    $instanceid = $manualenrol->add_instance($samplecourse);
    $enrolinstance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
}
if ($manualenrol && $enrolinstance) {
    $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
    $studentrole = $DB->get_record('role', ['shortname' => 'student']);
    if ($teacherrole) {
        $manualenrol->enrol_user($enrolinstance, $teacher->id, $teacherrole->id);
    }
    if ($studentrole) {
        $manualenrol->enrol_user($enrolinstance, $student->id, $studentrole->id);
    }
}
cli_writeln('[4/6] Verified sample accounts and course enrolments.');

// -----------------------------------------------------------------------------
// 5. Ensure Integration Role (accees_integration_service) & Capabilities.
// -----------------------------------------------------------------------------
$role = $DB->get_record('role', ['shortname' => 'accees_integration_service']);
if (!$role) {
    $roleid = create_role(
        'ACCEES Integration Service',
        'accees_integration_service',
        'Service role for TMS/DLRP REST API v2 integration.'
    );
    set_role_contextlevels($roleid, [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE]);
    $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);
    cli_writeln("  [+] Created role: accees_integration_service (id={$role->id})");
} else {
    set_role_contextlevels($role->id, [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE]);
}

$sysctx = \core\context\system::instance();
$desiredcaps = [
    'local/accees_imscp:import',
    'local/accees_imscp:export',
    'local/accees_sync_api:viewcourses',
    'local/accees_sync_api:updatecourse',
    'local/accees_sync_api:enrol',
    'local/accees_sync_api:unenrol',
    'local/accees_sync_api:viewresults',
    'moodle/course:view',
    'moodle/course:viewhiddencourses',
];

$assignedcaps = 0;
foreach ($desiredcaps as $cap) {
    if ($DB->record_exists('capabilities', ['name' => $cap])) {
        assign_capability($cap, CAP_ALLOW, $role->id, $sysctx->id, true);
        $assignedcaps++;
    }
}
role_assign($role->id, $serviceuser->id, $sysctx->id);
cli_writeln("[5/6] Assigned {$assignedcaps} active capabilities to accees_integration_service and bound accees_service.");

// -----------------------------------------------------------------------------
// 6. Ensure OAuth 2.0 Clients (dev, tms, dlrp) & Optional Debug PAT.
// -----------------------------------------------------------------------------
$canonicalscopes = [
    'local_accees_imscp:imscp:import',
    'local_accees_imscp:imscp:export',
    'local_accees_sync_api:course:read',
    'local_accees_sync_api:course:write',
    'local_accees_sync_api:enrol:manage',
    'local_accees_sync_api:result:read',
];

$clientdefinitions = [
    'accees-dev-client' => 'ACCEES-LMS REST API v2 OAuth 2.0 Client Credentials dev client',
    'accees-tms-client' => 'ACCEES-TMS Liferay integration OAuth 2.0 Client Credentials client',
    'accees-dlrp-client' => 'ACCEES-DLRP InvenioRDM integration OAuth 2.0 Client Credentials client',
];

$clientmanager = \core\di::get(\core\oauth2\server\client_manager::class);
cli_writeln('[6/6] Provisioning OAuth 2.0 Client Credentials clients...');

foreach ($clientdefinitions as $clientname => $clientdesc) {
    $existingclient = $DB->get_record('oauth2_server_clients', ['name' => $clientname]);
    if (!$existingclient) {
        $cliententity = $clientmanager->create_client(
            name: $clientname,
            ownercontext: $sysctx,
            granttypes: [\core\oauth2\server\entity\client_entity::GRANT_TYPE_CLIENT_CREDENTIALS],
            redirecturis: [],
            description: $clientdesc,
            isconfidential: true,
            ispkcerequired: false,
            scopes: $canonicalscopes,
        );
        $clientid = $cliententity->get_id();
        $clientidentifier = $cliententity->getIdentifier();
        $clientsecret = $clientmanager->create_secret($clientid);
        cli_writeln("  [+] Created OAuth 2.0 client: {$clientname}");
        cli_writeln("      client_id     = {$clientidentifier}");
        cli_writeln("      client_secret = {$clientsecret}");
    } else {
        $clientmanager->update_client((int) $existingclient->id, ['scopes' => $canonicalscopes]);
        $clientidentifier = $existingclient->clientidentifier;
        cli_writeln("  [=] Updated scopes on existing OAuth 2.0 client: {$clientname} (client_id={$clientidentifier})");
        $activesecrets = $clientmanager->get_secrets((int) $existingclient->id);
        if (empty($activesecrets) || $options['reset-credentials']) {
            if ($options['reset-credentials']) {
                foreach ($activesecrets as $sec) {
                    $clientmanager->revoke_secret((int) $existingclient->id, (int) $sec->id);
                }
            }
            $newsecret = $clientmanager->create_secret((int) $existingclient->id);
            cli_writeln("      client_secret = {$newsecret}");
        }
    }
}

// Also ensure a debug PAT for accees_service when missing or reset requested.
$patname = 'accees-service-dev-pat';
$existingpat = $DB->get_record('rest_api_tokens', ['name' => $patname, 'userid' => $serviceuser->id]);
if (!$existingpat || $options['reset-credentials']) {
    if ($existingpat) {
        $DB->delete_records('rest_api_tokens', ['id' => $existingpat->id]);
    }
    $tokenrepo = new \core\api\repository\api_token_repository();
    $rawsecret = bin2hex(random_bytes(32));
    $entity = $tokenrepo->create_token(
        $patname,
        $rawsecret,
        (int) $serviceuser->id,
        $canonicalscopes,
        'Dev debug PAT for accees_service (prefer OAuth2 client_credentials for QA).',
        time() + (30 * DAYSECS),
    );
    $pat = 'pat_' . rtrim(base64_encode($entity->get_id() . '/' . $rawsecret), '=');
    cli_writeln('      debug PAT (accees_service) = ' . $pat);
} else {
    cli_writeln('      debug PAT already exists for accees_service (pass --reset-credentials to rotate).');
}

cli_writeln('Token endpoint: ' . $CFG->wwwroot . '/oauth2/token');
cli_writeln('Done.');

