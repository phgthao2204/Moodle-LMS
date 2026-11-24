<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    local_accees_imscp
// @copyright  2026 Du an ACCEES (Erasmus+ CBHE-101179860)
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace local_accees_imscp\manager;

use context_course;
use core_course\customfield\course_handler;
use local_accees_imscp\exception\import_exception;
use SimpleXMLElement;
use stdClass;
use ZipArchive;

/**
 * Manager handling ACCEES-LOM IMS Common Cartridge (.imscc) package unpacking and course import.
 */
class import_manager {

    /**
     * Import an IMS Common Cartridge (.imscc/.zip) package from a filesystem path.
     *
     * @param string $filepath Path to the uploaded or downloaded package archive.
     * @param int $categoryid Target category ID (default 1).
     * @param string|null $targetshortname Optional course shortname override.
     * @param bool $overwrite Whether to overwrite existing course with the same shortname.
     * @return array<string, mixed> Result payload matching UC-LMS-08 schema.
     * @throws import_exception
     */
    public function import_package_from_file(
        string $filepath,
        int $categoryid = 1,
        ?string $targetshortname = null,
        bool $overwrite = false,
    ): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');

        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new import_exception(
                message: 'Package file does not exist or is empty',
                errorcode: 'INVALID_PACKAGE',
                httpstatus: 400,
            );
        }

        // 1. Unpack archive to temporary directory.
        $tempdir = make_temp_directory('imscp_import/' . uniqid('pkg_', true));
        $zip = new ZipArchive();
        $res = $zip->open($filepath);
        if ($res !== true) {
            throw new import_exception(
                message: 'Failed to open package: file is not a valid zip/imscc archive',
                errorcode: 'INVALID_PACKAGE',
                httpstatus: 400,
                details: ['zip_error_code' => $res],
            );
        }

        $zip->extractTo($tempdir);
        $zip->close();

        try {
            // 2. Locate imsmanifest.xml.
            $manifestpath = $tempdir . '/imsmanifest.xml';
            if (!file_exists($manifestpath)) {
                // Check if wrapped in a single root subfolder.
                $subdirs = glob($tempdir . '/*', GLOB_ONLYDIR);
                if (count($subdirs) === 1 && file_exists($subdirs[0] . '/imsmanifest.xml')) {
                    $manifestpath = $subdirs[0] . '/imsmanifest.xml';
                    $tempdir = $subdirs[0];
                } else {
                    throw new import_exception(
                        message: 'Missing imsmanifest.xml in package archive root',
                        errorcode: 'INVALID_PACKAGE',
                        httpstatus: 400,
                    );
                }
            }

            // 3. Parse manifest XML.
            libxml_use_internal_errors(true);
            $xmlcontent = file_get_contents($manifestpath);
            if ($xmlcontent === false || trim($xmlcontent) === '') {
                throw new import_exception(
                    message: 'imsmanifest.xml is empty or unreadable',
                    errorcode: 'INVALID_PACKAGE',
                    httpstatus: 400,
                );
            }

            $xml = simplexml_load_string($xmlcontent, SimpleXMLElement::class, LIBXML_NOCDATA);
            if ($xml === false) {
                $xmlerrors = [];
                foreach (libxml_get_errors() as $err) {
                    $xmlerrors[] = trim($err->message);
                }
                libxml_clear_errors();
                throw new import_exception(
                    message: 'imsmanifest.xml contains invalid XML structure',
                    errorcode: 'LOM_VALIDATION_FAILED',
                    httpstatus: 422,
                    details: ['xml_errors' => $xmlerrors],
                );
            }

            // 4. Extract course metadata and LOM.
            $metadata = $this->extract_metadata_and_lom($xml, $targetshortname);
            $shortname = $metadata['shortname'];
            $fullname = $metadata['fullname'];
            $summary = $metadata['summary'];
            $lom = $metadata['lom'];

            if (empty($shortname)) {
                throw new import_exception(
                    message: 'Course shortname could not be determined from package manifest',
                    errorcode: 'LOM_VALIDATION_FAILED',
                    httpstatus: 422,
                );
            }

            // Validate category.
            if (!$DB->record_exists('course_categories', ['id' => $categoryid])) {
                $defaultcat = $DB->get_field('course_categories', 'MIN(id)', []);
                $categoryid = $defaultcat ? (int) $defaultcat : 1;
            }

            // 5. Check course existence / overwrite rule.
            $existingcourse = $DB->get_record('course', ['shortname' => $shortname]);
            if ($existingcourse && !$overwrite) {
                throw new import_exception(
                    message: "Course with shortname '{$shortname}' already exists",
                    errorcode: 'COURSE_ALREADY_EXISTS',
                    httpstatus: 409,
                    details: [
                        'shortname' => $shortname,
                        'existing_course_id' => (int) $existingcourse->id,
                    ],
                );
            }

            // 6. Create or update course in Moodle.
            $course = null;
            if ($existingcourse && $overwrite) {
                $coursedata = new stdClass();
                $coursedata->id = $existingcourse->id;
                $coursedata->fullname = $fullname;
                $coursedata->category = $categoryid;
                $coursedata->summary = $summary;
                $coursedata->summaryformat = FORMAT_HTML;
                $coursedata->timemodified = time();
                update_course($coursedata);
                $course = $DB->get_record('course', ['id' => $existingcourse->id], '*', MUST_EXIST);
            } else {
                $coursedata = new stdClass();
                $coursedata->fullname = $fullname;
                $coursedata->shortname = $shortname;
                $coursedata->category = $categoryid;
                $coursedata->summary = $summary;
                $coursedata->summaryformat = FORMAT_HTML;
                $coursedata->format = 'topics';
                $coursedata->numsections = 5;
                $coursedata->visible = 1;
                $coursedata->startdate = time();
                $coursedata->timecreated = time();
                $coursedata->timemodified = time();
                $course = create_course($coursedata);
            }

            // 7. Parse organizations & resources, create sections and modules.
            $modulescreated = $this->create_course_modules_from_manifest($xml, $course, $tempdir);

            // 8. Persist LOM metadata (5 core custom fields via LomMetadataInterface).
            $lomsummary = $this->apply_lom_metadata((int) $course->id, $lom);

            return [
                'course_id' => (int) $course->id,
                'shortname' => (string) $course->shortname,
                'fullname' => (string) $course->fullname,
                'category_id' => (int) $categoryid,
                'import_status' => 'completed',
                'modules_created' => (int) $modulescreated,
                'lom_summary' => $lomsummary,
                'warnings' => [],
            ];
        } finally {
            // Cleanup temp directory.
            fulldelete($tempdir);
        }
    }

    /**
     * Download a package from URL and import it.
     *
     * @param string $packageurl Internal or external URL to the .imscc package.
     * @param int $categoryid Target category ID.
     * @param string|null $targetshortname Optional shortname override.
     * @param bool $overwrite Overwrite flag.
     * @return array<string, mixed>
     * @throws import_exception
     */
    public function import_package_from_url(
        string $packageurl,
        int $categoryid = 1,
        ?string $targetshortname = null,
        bool $overwrite = false,
    ): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        if (!filter_var($packageurl, FILTER_VALIDATE_URL)) {
            throw new import_exception(
                message: 'Invalid package_url provided',
                errorcode: 'INVALID_PACKAGE',
                httpstatus: 400,
                details: ['package_url' => $packageurl],
            );
        }

        $temppath = make_temp_directory('imscp_download') . '/' . uniqid('pkg_', true) . '.imscc';
        $fp = fopen($temppath, 'w+');
        if (!$fp) {
            throw new import_exception('Failed to create local temp file for download', 'INVALID_PACKAGE', 400);
        }

        $curl = new \curl();
        $options = [
            'CURLOPT_FILE' => $fp,
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_TIMEOUT' => 60,
        ];
        $curl->get($packageurl, [], $options);
        fclose($fp);

        $httpcode = $curl->get_info()['http_code'] ?? 0;
        if ($httpcode < 200 || $httpcode >= 300 || filesize($temppath) === 0) {
            @unlink($temppath);
            throw new import_exception(
                message: "Failed to download package from package_url (HTTP {$httpcode})",
                errorcode: 'INVALID_PACKAGE',
                httpstatus: 400,
                details: ['http_code' => $httpcode, 'package_url' => $packageurl],
            );
        }

        try {
            return $this->import_package_from_file($temppath, $categoryid, $targetshortname, $overwrite);
        } finally {
            @unlink($temppath);
        }
    }

    /**
     * Extract title, shortname, summary, and LOM metadata structure from SimpleXMLElement.
     *
     * @param SimpleXMLElement $xml
     * @param string|null $overrideShortname
     * @return array{shortname: string, fullname: string, summary: string, lom: array<string, mixed>}
     */
    protected function extract_metadata_and_lom(SimpleXMLElement $xml, ?string $overrideShortname = null): array {
        // Register default and common namespaces.
        $namespaces = $xml->getNamespaces(true);
        $lomxml = null;

        // Try to locate <metadata><lom> with or without namespace.
        if (isset($xml->metadata)) {
            if (isset($xml->metadata->lom)) {
                $lomxml = $xml->metadata->lom;
            } else {
                foreach ($namespaces as $prefix => $ns) {
                    if (str_contains($ns, 'lom') || str_contains($ns, 'imsmd')) {
                        $children = $xml->metadata->children($ns);
                        if (isset($children->lom)) {
                            $lomxml = $children->lom;
                            break;
                        }
                    }
                }
            }
        }

        // Direct search if not found in metadata.
        if (!$lomxml && isset($xml->lom)) {
            $lomxml = $xml->lom;
        }

        // Extract identifier / shortname.
        $shortname = $overrideShortname;
        if (empty($shortname)) {
            if ($lomxml && isset($lomxml->general->identifier->entry)) {
                $shortname = (string) $lomxml->general->identifier->entry;
            } elseif ($lomxml && isset($lomxml->general->identifier)) {
                $shortname = (string) $lomxml->general->identifier;
            } elseif (isset($xml['identifier'])) {
                $shortname = (string) $xml['identifier'];
            }
        }

        // Extract title / fullname.
        $fullname = '';
        if ($lomxml && isset($lomxml->general->title)) {
            $fullname = (string) ($lomxml->general->title->string ?? $lomxml->general->title);
        } elseif (isset($xml->organizations->organization->title)) {
            $fullname = (string) $xml->organizations->organization->title;
        } elseif (isset($xml->metadata->schema)) {
            $fullname = (string) $xml->metadata->schema;
        }
        if (empty($fullname)) {
            $fullname = $shortname ?: 'Imported ACCEES Course';
        }

        // Extract description.
        $summary = '';
        if ($lomxml && isset($lomxml->general->description)) {
            $summary = (string) ($lomxml->general->description->string ?? $lomxml->general->description);
        }

        // Extract 5 core fields without hardcoded fallbacks.
        $status = null;
        if ($lomxml && isset($lomxml->lifecycle->status)) {
            $status = (string) ($lomxml->lifecycle->status->value ?? $lomxml->lifecycle->status);
        }

        $deliverymode = null;
        if ($lomxml && isset($lomxml->technical->delivery_mode)) {
            $deliverymode = (string) $lomxml->technical->delivery_mode;
        } elseif ($lomxml && isset($lomxml->technical->otherPlatformRequirements)) {
            $deliverymode = (string) $lomxml->technical->otherPlatformRequirements;
        }

        $duration = null;
        if ($lomxml && isset($lomxml->technical->duration_hours)) {
            $duration = (string) $lomxml->technical->duration_hours;
        } elseif ($lomxml && isset($lomxml->technical->duration)) {
            $duration = (string) ($lomxml->technical->duration->description->string ?? $lomxml->technical->duration);
        }

        $institution = null;
        if ($lomxml && isset($lomxml->classification->institution)) {
            $institution = (string) $lomxml->classification->institution;
        } elseif ($lomxml && isset($lomxml->lifecycle->contribute->entity)) {
            $institution = (string) $lomxml->lifecycle->contribute->entity;
        }

        $isfeatured = null;
        if ($lomxml && isset($lomxml->classification->is_featured)) {
            $val = strtolower((string) $lomxml->classification->is_featured);
            $isfeatured = ($val === 'true' || $val === '1' || $val === 'yes');
        }

        $lifecycle = [];
        if ($status !== null && trim($status) !== '') {
            $lifecycle['status'] = trim($status);
        }

        $technical = [];
        if ($deliverymode !== null && trim($deliverymode) !== '') {
            $technical['delivery_mode'] = trim($deliverymode);
        }
        if ($duration !== null && is_numeric($duration)) {
            $technical['duration_hours'] = (string) $duration;
        }

        $classification = [];
        if ($institution !== null && trim($institution) !== '') {
            $classification['institution'] = trim($institution);
        }
        if ($isfeatured !== null) {
            $classification['is_featured'] = (bool) $isfeatured;
        }

        $lom = [
            'schema_version' => 'LOM-ACCEES-1.0',
            'general' => [
                'identifier' => $shortname,
                'title' => $fullname,
                'description' => $summary,
            ],
            'lifecycle' => $lifecycle,
            'technical' => $technical,
            'classification' => $classification,
        ];

        return [
            'shortname' => (string) $shortname,
            'fullname' => (string) $fullname,
            'summary' => (string) $summary,
            'lom' => $lom,
        ];
    }

    /**
     * Create sections and course modules (pages/resources) from manifest items.
     *
     * @param SimpleXMLElement $xml
     * @param stdClass $course
     * @param string $tempdir
     * @return int Count of modules created.
     */
    protected function create_course_modules_from_manifest(
        SimpleXMLElement $xml,
        stdClass $course,
        string $tempdir,
    ): int {
        global $DB;
        $modulescreated = 0;

        // Build resource lookup by identifier.
        $resources = [];
        if (isset($xml->resources->resource)) {
            foreach ($xml->resources->resource as $res) {
                $id = (string) $res['identifier'];
                $href = (string) ($res['href'] ?? '');
                $type = (string) ($res['type'] ?? 'webcontent');
                $resources[$id] = [
                    'href' => $href,
                    'type' => $type,
                ];
            }
        }

        // Iterate organization items.
        $sectionnum = 1;
        if (isset($xml->organizations->organization->item)) {
            foreach ($xml->organizations->organization->item as $item) {
                $itemtitle = (string) ($item->title ?? 'Module ' . $sectionnum);
                $identifierref = (string) ($item['identifierref'] ?? '');

                // Ensure section exists.
                course_create_section($course->id, $sectionnum);

                // Sub-items or main item.
                $itemstoprocess = [];
                if (isset($item->item) && count($item->item) > 0) {
                    foreach ($item->item as $subitem) {
                        $itemstoprocess[] = [
                            'title' => (string) ($subitem->title ?? 'Lesson'),
                            'ref' => (string) ($subitem['identifierref'] ?? ''),
                        ];
                    }
                } else {
                    $itemstoprocess[] = [
                        'title' => $itemtitle,
                        'ref' => $identifierref,
                    ];
                }

                foreach ($itemstoprocess as $procitem) {
                    $content = '<p>' . htmlspecialchars($procitem['title']) . '</p>';
                    $ref = $procitem['ref'];
                    if (!empty($ref) && isset($resources[$ref])) {
                        $href = $resources[$ref]['href'];
                        $filepath = $tempdir . '/' . ltrim($href, '/');
                        if (file_exists($filepath)) {
                            $filecontent = @file_get_contents($filepath);
                            if ($filecontent !== false) {
                                $content = $filecontent;
                            }
                        }
                    }

                    // Create page module in this section.
                    $this->create_page_module($course, $sectionnum, $procitem['title'], $content);
                    $modulescreated++;
                }

                $sectionnum++;
            }
        }

        // If manifest had no organizations items but has resources, create modules for resources directly.
        if ($modulescreated === 0 && count($resources) > 0) {
            course_create_section($course->id, 1);
            foreach ($resources as $resid => $resdata) {
                $href = $resdata['href'];
                $title = basename($href);
                $filepath = $tempdir . '/' . ltrim($href, '/');
                $content = file_exists($filepath) ? (string) file_get_contents($filepath) : '<p>' . htmlspecialchars($title) . '</p>';
                $this->create_page_module($course, 1, $title ?: $resid, $content);
                $modulescreated++;
            }
        }

        return $modulescreated;
    }

    /**
     * Create a page module activity instance inside a course section.
     *
     * @param stdClass $course
     * @param int $sectionnum
     * @param string $name
     * @param string $content
     * @return int CMID
     */
    protected function create_page_module(stdClass $course, int $sectionnum, string $name, string $content): int {
        global $DB, $PAGE;

        $pagemodule = $DB->get_record('modules', ['name' => 'page']);
        if (!$pagemodule) {
            return 0;
        }

        $coursecontext = context_course::instance($course->id);
        if ($PAGE) {
            $PAGE->set_context($coursecontext);
        }

        $moduleinfo = new stdClass();
        $moduleinfo->modulename = 'page';
        $moduleinfo->module = $pagemodule->id;
        $moduleinfo->course = $course->id;
        $moduleinfo->section = $sectionnum;
        $moduleinfo->name = $name;
        $moduleinfo->intro = '<p>' . htmlspecialchars($name) . '</p>';
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->page = [
            'text' => $content,
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ];
        $moduleinfo->printintro = 0;
        $moduleinfo->printlastmodified = 1;
        $moduleinfo->display = 5; // Open in page.
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;

        $cm = add_moduleinfo($moduleinfo, $course);
        return $cm ? (int) $cm->coursemodule : 0;
    }

    /**
     * Apply LOM metadata to course custom fields using LomMetadataInterface or course_handler.
     *
     * @param int $courseid
     * @param array<string, mixed> $lom
     * @return array<string, mixed>
     */
    protected function apply_lom_metadata(int $courseid, array $lom): array {
        $lomserviceclass = '\local_accees_sync_api\manager\lom_metadata_manager';
        if (class_exists($lomserviceclass)) {
            $lomservice = new $lomserviceclass();
            try {
                return $lomservice->apply_lom($courseid, $lom);
            } catch (\DomainException $de) {
                throw new import_exception(
                    'Invalid LOM metadata: ' . $de->getMessage(),
                    'LOM_VALIDATION_FAILED',
                    422,
                    ['course_id' => $courseid, 'reason' => $de->getMessage()]
                );
            }
        }

        // Fallback directly to course custom fields handler without hardcoded values.
        $handler = course_handler::create();
        $instance = new stdClass();
        $instance->id = $courseid;
        $saved = [];

        if (!empty($lom['lifecycle']['status'])) {
            $instance->customfield_accees_course_status = (string) $lom['lifecycle']['status'];
            $saved['accees_course_status'] = (string) $lom['lifecycle']['status'];
        }
        if (!empty($lom['technical']['delivery_mode'])) {
            $instance->customfield_accees_delivery_mode = (string) $lom['technical']['delivery_mode'];
            $saved['accees_delivery_mode'] = (string) $lom['technical']['delivery_mode'];
        }
        if (isset($lom['technical']['duration_hours']) && $lom['technical']['duration_hours'] !== null && $lom['technical']['duration_hours'] !== '') {
            $instance->customfield_accees_duration = (string) $lom['technical']['duration_hours'];
            $saved['accees_duration'] = (string) $lom['technical']['duration_hours'];
        }
        if (!empty($lom['classification']['institution'])) {
            $instance->customfield_accees_institution = (string) $lom['classification']['institution'];
            $saved['accees_institution'] = (string) $lom['classification']['institution'];
        }
        if (isset($lom['classification']['is_featured']) && $lom['classification']['is_featured'] !== null) {
            $instance->customfield_accees_is_featured = !empty($lom['classification']['is_featured']) ? 1 : 0;
            $saved['accees_is_featured'] = !empty($lom['classification']['is_featured']);
        }

        if (!empty($saved)) {
            $handler->instance_form_save($instance, false);
        }

        return $saved;
    }
}
