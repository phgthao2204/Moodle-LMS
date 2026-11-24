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
use local_accees_imscp\exception\export_exception;
use stdClass;
use Throwable;
use ZipArchive;

/**
 * Manager handling ACCEES-LOM IMS Common Cartridge (.imscc) packaging and course export.
 */
class export_manager {

    /** @var int Default export artifact TTL in seconds (24 hours). */
    public const ARTIFACT_TTL_SECONDS = 86400;

    /**
     * Export a Moodle course into an .imscc package with ACCEES-LOM metadata.
     *
     * @param int $courseid Target Moodle course ID.
     * @param array<string, mixed> $options Export options (include_userdata, lom_override, delivery).
     * @return array<string, mixed> Result payload matching UC-LMS-09 schema.
     * @throws export_exception
     */
    public function export_course(int $courseid, array $options = []): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        if ($courseid <= 0) {
            throw new export_exception(
                message: 'Invalid courseid parameter: must be a positive integer',
                errorcode: 'INVALID_PARAMETER',
                httpstatus: 400,
                details: ['courseid' => $courseid],
            );
        }

        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course || (int) $course->id === (int) SITEID) {
            throw new export_exception(
                message: "Course with id {$courseid} not found",
                errorcode: 'COURSE_NOT_FOUND',
                httpstatus: 404,
                details: ['courseid' => $courseid],
            );
        }

        $lomoverride = $options['lom_override'] ?? [];
        $delivery = $options['delivery'] ?? 'download_url';

        // 1. Extract LOM metadata via LomMetadataInterface.
        $lom = $this->get_course_lom_metadata($courseid);
        if (!empty($lomoverride) && is_array($lomoverride)) {
            $lom = $this->merge_recursive_override($lom, $lomoverride);
        }

        // 2. Prepare temporary directory for packaging.
        $tempdir = make_temp_directory('imscp_export/' . uniqid('exp_', true));
        $webcontentdir = $tempdir . '/webcontent';
        if (!is_dir($webcontentdir)) {
            mkdir($webcontentdir, 0777, true);
        }

        try {
            // 3. Extract course sections and activities.
            $manifeststructure = $this->extract_course_structure($course, $webcontentdir);

            // 4. Generate imsmanifest.xml with embedded ACCEES-LOM.
            $manifestxml = $this->generate_manifest_xml($course, $lom, $manifeststructure);
            file_put_contents($tempdir . '/imsmanifest.xml', $manifestxml);

            // 5. Build .imscc (ZIP) archive.
            $safeshortname = preg_replace('/[^A-Za-z0-9_\-]/', '_', $course->shortname);
            $filename = "{$safeshortname}-v1.0.imscc";

            $exportstoragedir = $this->get_export_storage_dir();
            $token = 'token-' . bin2hex(random_bytes(16));
            $artifactpath = $exportstoragedir . '/' . $token . '.imscc';

            $zip = new ZipArchive();
            if ($zip->open($artifactpath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new export_exception(
                    message: 'Failed to create export archive file',
                    errorcode: 'EXPORT_PACKAGING_FAILED',
                    httpstatus: 500,
                );
            }

            $this->add_directory_to_zip($zip, $tempdir, '');
            $zip->close();

            if (!file_exists($artifactpath) || filesize($artifactpath) === 0) {
                throw new export_exception(
                    message: 'Export packaging failed: resulting archive is missing or empty',
                    errorcode: 'EXPORT_PACKAGING_FAILED',
                    httpstatus: 500,
                );
            }

            $sizebytes = filesize($artifactpath);
            $checksum = hash_file('sha256', $artifactpath);
            $expiresat = time() + self::ARTIFACT_TTL_SECONDS;
            $expiresiso = date('c', $expiresat);

            // 6. Save artifact metadata for subsequent download endpoint.
            $metapath = $exportstoragedir . '/' . $token . '.meta.json';
            $metadata = [
                'token' => $token,
                'course_id' => (int) $course->id,
                'shortname' => (string) $course->shortname,
                'filename' => $filename,
                'filepath' => $artifactpath,
                'size_bytes' => (int) $sizebytes,
                'checksum_sha256' => $checksum,
                'expires_at' => $expiresat,
                'expires_iso' => $expiresiso,
            ];
            file_put_contents($metapath, json_encode($metadata, JSON_PRETTY_PRINT));

            $downloadurl = $CFG->wwwroot . '/api/rest/v2/local_accees_imscp/exports/' . $token;

            if ($delivery === 'binary') {
                return [
                    'delivery' => 'binary',
                    'filepath' => $artifactpath,
                    'filename' => $filename,
                    'size_bytes' => (int) $sizebytes,
                    'checksum_sha256' => $checksum,
                ];
            }

            return [
                'course_id' => (int) $course->id,
                'shortname' => (string) $course->shortname,
                'filename' => $filename,
                'size_bytes' => (int) $sizebytes,
                'checksum_sha256' => $checksum,
                'download_url' => $downloadurl,
                'expires_at' => $expiresiso,
                'lom_included' => true,
            ];
        } catch (Throwable $e) {
            if ($e instanceof export_exception) {
                throw $e;
            }
            throw new export_exception(
                message: 'Failed to package course: ' . $e->getMessage(),
                errorcode: 'EXPORT_PACKAGING_FAILED',
                httpstatus: 500,
            );
        } finally {
            fulldelete($tempdir);
        }
    }

    /**
     * Retrieve metadata for a previously exported artifact by token.
     *
     * @param string $token Export download token.
     * @return array<string, mixed>
     * @throws export_exception
     */
    public function get_export_artifact(string $token): array {
        $exportdir = $this->get_export_storage_dir();
        $safetoken = preg_replace('/[^A-Za-z0-9_\-]/', '', $token);
        $metapath = $exportdir . '/' . $safetoken . '.meta.json';

        if (!file_exists($metapath)) {
            throw new export_exception(
                message: 'Export artifact not found or has expired',
                errorcode: 'COURSE_NOT_FOUND',
                httpstatus: 404,
                details: ['token' => $token],
            );
        }

        $meta = json_decode((string) file_get_contents($metapath), true);
        if (!is_array($meta) || !file_exists($meta['filepath'] ?? '')) {
            throw new export_exception(
                message: 'Export artifact file is no longer available on disk',
                errorcode: 'COURSE_NOT_FOUND',
                httpstatus: 404,
                details: ['token' => $token],
            );
        }

        if (time() > ($meta['expires_at'] ?? 0)) {
            @unlink($metapath);
            @unlink($meta['filepath']);
            throw new export_exception(
                message: 'Export artifact download link has expired',
                errorcode: 'COURSE_NOT_FOUND',
                httpstatus: 404,
                details: ['token' => $token],
            );
        }

        return $meta;
    }

    /**
     * Get or create directory storing export artifacts.
     *
     * @return string
     */
    protected function get_export_storage_dir(): string {
        return make_temp_directory('imscp_exports');
    }

    /**
     * Read LOM metadata for course using lom_metadata_manager or course_handler.
     *
     * @param int $courseid
     * @return array<string, mixed>
     */
    protected function get_course_lom_metadata(int $courseid): array {
        $lomserviceclass = '\local_accees_sync_api\manager\lom_metadata_manager';
        if (class_exists($lomserviceclass)) {
            $lomservice = new $lomserviceclass();
            return $lomservice->export_lom($courseid);
        }

        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $handler = course_handler::create();
        $customs = $handler->export_instance_data_object($courseid, true);

        $status = !empty($customs->accees_course_status) ? (string) $customs->accees_course_status : null;
        $institution = !empty($customs->accees_institution) ? (string) $customs->accees_institution : null;
        $duration = (isset($customs->accees_duration) && is_numeric($customs->accees_duration)) ? (int) $customs->accees_duration : null;
        $deliverymode = !empty($customs->accees_delivery_mode) ? (string) $customs->accees_delivery_mode : null;
        $isfeatured = !empty($customs->accees_is_featured);

        return [
            'schema_version' => 'LOM-ACCEES-1.0',
            'general' => [
                'identifier' => $course->shortname,
                'title' => $course->fullname,
                'language' => ['vi'],
                'description' => strip_tags($course->summary ?? ''),
                'keyword' => [],
            ],
            'lifecycle' => array_filter([
                'version' => '1.0',
                'status' => $status,
                'contribute' => $institution !== null ? [
                    [
                        'role' => 'author',
                        'entity' => $institution,
                        'date' => date('Y-m-d', $course->timemodified ?: time()),
                    ],
                ] : [],
            ], fn($v) => $v !== null),
            'metametadata' => [
                'metadata_schema' => ['LOMv1.0', 'ACCEES-LOMv1.0'],
            ],
            'technical' => array_filter([
                'format' => ['application/zip', 'text/html'],
                'duration_hours' => $duration,
                'delivery_mode' => $deliverymode,
            ], fn($v) => $v !== null),
            'educational' => [
                'learning_resource_type' => ['course'],
                'intended_end_user_role' => ['learner'],
                'typical_learning_time' => $duration !== null ? "PT{$duration}H" : null,
            ],
            'rights' => [
                'cost' => 'no',
                'copyright_and_other_restrictions' => 'yes',
                'license' => 'CC-BY-4.0',
            ],
            'relation' => [],
            'annotation' => [],
            'classification' => array_filter([
                'institution' => $institution,
                'is_featured' => $isfeatured,
                'competency_codes' => [],
            ], fn($v) => $v !== null),
        ];
    }

    /**
     * Recursively merge override array over base LOM array.
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    protected function merge_recursive_override(array $base, array $override): array {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && !array_is_list($value)) {
                $base[$key] = $this->merge_recursive_override($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    /**
     * Extract course sections and activities into manifest items and webcontent files.
     *
     * @param stdClass $course
     * @param string $webcontentdir
     * @return array{items: array<int, array<string, mixed>>, resources: array<string, array<string, string>>}
     */
    protected function extract_course_structure(stdClass $course, string $webcontentdir): array {
        global $DB;
        $modinfo = get_fast_modinfo($course);

        $items = [];
        $resources = [];
        $sectionidx = 1;

        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->section == 0 && empty($section->summary) && empty($modinfo->sections[0])) {
                continue;
            }

            $sectiontitle = $section->name;
            if (empty($sectiontitle)) {
                $sectiontitle = 'Chuong ' . $sectionidx;
            }

            $sectionitems = [];
            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    if (!$cm->uservisible && $cm->visible == 0) {
                        continue;
                    }

                    $itemtitle = $cm->name;
                    $resid = 'RES-' . $cm->id;
                    $filename = 'page_' . $cm->id . '.html';
                    $filepath = $webcontentdir . '/' . $filename;

                    // Extract HTML content based on module type.
                    $content = '';
                    if ($cm->modname === 'page') {
                        $pagerec = $DB->get_record('page', ['id' => $cm->instance]);
                        $content = $pagerec ? ($pagerec->content ?? '') : '';
                    } else {
                        if (!empty($cm->instance) && $DB->record_exists($cm->modname, ['id' => $cm->instance])) {
                            $modrec = $DB->get_record($cm->modname, ['id' => $cm->instance], 'intro');
                            $content = !empty($modrec->intro) ? $modrec->intro : '';
                        }
                        if (empty($content)) {
                            $content = '<p>' . htmlspecialchars($itemtitle) . '</p>';
                        }
                    }

                    file_put_contents(
                        $filepath,
                        "<!DOCTYPE html>\n<html lang=\"vi\">\n<head><meta charset=\"UTF-8\"><title>" .
                        htmlspecialchars($itemtitle) .
                        "</title></head>\n<body>\n<h1>" .
                        htmlspecialchars($itemtitle) .
                        "</h1>\n" .
                        $content .
                        "\n</body>\n</html>"
                    );

                    $resources[$resid] = [
                        'identifier' => $resid,
                        'type' => 'webcontent',
                        'href' => 'webcontent/' . $filename,
                    ];

                    $sectionitems[] = [
                        'identifier' => 'ITEM-' . $cm->id,
                        'identifierref' => $resid,
                        'title' => $itemtitle,
                    ];
                }
            }

            $items[] = [
                'identifier' => 'SEC-' . $section->id,
                'title' => $sectiontitle,
                'items' => $sectionitems,
            ];

            $sectionidx++;
        }

        return [
            'items' => $items,
            'resources' => $resources,
        ];
    }

    /**
     * Generate imsmanifest.xml content with ACCEES-LOM metadata and course resources.
     *
     * @param stdClass $course
     * @param array<string, mixed> $lom
     * @param array{items: array<int, array<string, mixed>>, resources: array<string, array<string, string>>} $structure
     * @return string
     */
    protected function generate_manifest_xml(stdClass $course, array $lom, array $structure): string {
        $shortname = htmlspecialchars($course->shortname);
        $rawtitle = $lom['general']['title'] ?? $course->fullname;
        if (is_array($rawtitle)) {
            $rawtitle = $rawtitle['string'] ?? reset($rawtitle) ?? $course->fullname;
        }
        $title = htmlspecialchars((string) $rawtitle);

        $rawdesc = $lom['general']['description'] ?? strip_tags($course->summary ?? '');
        if (is_array($rawdesc)) {
            $rawdesc = $rawdesc['string'] ?? reset($rawdesc) ?? strip_tags($course->summary ?? '');
        }
        $description = htmlspecialchars((string) $rawdesc);

        $rawversion = $lom['lifecycle']['version'] ?? '1.0';
        if (is_array($rawversion)) {
            $rawversion = $rawversion['string'] ?? reset($rawversion) ?? '1.0';
        }
        $version = htmlspecialchars((string) $rawversion);

        $status = isset($lom['lifecycle']['status']) ? htmlspecialchars((string) $lom['lifecycle']['status']) : '';
        $deliverymode = isset($lom['technical']['delivery_mode']) ? htmlspecialchars((string) $lom['technical']['delivery_mode']) : '';
        $duration = isset($lom['technical']['duration_hours']) ? htmlspecialchars((string) $lom['technical']['duration_hours']) : '';
        $institution = isset($lom['classification']['institution']) ? htmlspecialchars((string) $lom['classification']['institution']) : '';
        $isfeatured = !empty($lom['classification']['is_featured']) ? 'true' : 'false';

        $statusxml = !empty($status) ? "        <status>\n          <value>{$status}</value>\n        </status>\n" : '';
        $contributexml = !empty($institution) ? "        <contribute>\n          <role>\n            <value>author</value>\n          </role>\n          <entity>{$institution}</entity>\n        </contribute>\n" : '';
        $durationxml = !empty($duration) ? "        <duration_hours>{$duration}</duration_hours>\n" : '';
        $deliverymodexml = !empty($deliverymode) ? "        <delivery_mode>{$deliverymode}</delivery_mode>\n" : '';
        $institutionxml = !empty($institution) ? "        <institution>{$institution}</institution>\n" : '';
        $typicallearningtimexml = !empty($duration) ? "        <typicalLearningTime>\n          <duration>PT{$duration}H</duration>\n        </typicalLearningTime>\n" : '';

        $organizationsxml = '';
        foreach ($structure['items'] as $sec) {
            $organizationsxml .= "      <item identifier=\"{$sec['identifier']}\">\n";
            $organizationsxml .= "        <title>" . htmlspecialchars($sec['title']) . "</title>\n";
            foreach ($sec['items'] as $subitem) {
                $organizationsxml .= "        <item identifier=\"{$subitem['identifier']}\" identifierref=\"{$subitem['identifierref']}\">\n";
                $organizationsxml .= "          <title>" . htmlspecialchars($subitem['title']) . "</title>\n";
                $organizationsxml .= "        </item>\n";
            }
            $organizationsxml .= "      </item>\n";
        }

        $resourcesxml = '';
        foreach ($structure['resources'] as $res) {
            $resourcesxml .= "    <resource identifier=\"{$res['identifier']}\" type=\"{$res['type']}\" href=\"{$res['href']}\">\n";
            $resourcesxml .= "      <file href=\"{$res['href']}\" />\n";
            $resourcesxml .= "    </resource>\n";
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="MANIFEST-{$shortname}" xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/LOM">
  <metadata>
    <schema>IMS Common Cartridge</schema>
    <schemaversion>1.1.0</schemaversion>
    <lom>
      <general>
        <identifier>
          <catalog>ACCEES</catalog>
          <entry>{$shortname}</entry>
        </identifier>
        <title>
          <string language="vi">{$title}</string>
        </title>
        <language>vi</language>
        <description>
          <string language="vi">{$description}</string>
        </description>
      </general>
      <lifecycle>
        <version>
          <string>{$version}</string>
        </version>
{$statusxml}{$contributexml}      </lifecycle>
      <metametadata>
        <metadataSchema>LOMv1.0</metadataSchema>
        <metadataSchema>ACCEES-LOMv1.0</metadataSchema>
      </metametadata>
      <technical>
        <format>application/zip</format>
        <format>text/html</format>
{$durationxml}{$deliverymodexml}      </technical>
      <educational>
        <learningResourceType>
          <value>course</value>
        </learningResourceType>
        <intendedEndUserRole>
          <value>learner</value>
        </intendedEndUserRole>
{$typicallearningtimexml}      </educational>
      <rights>
        <cost>
          <value>no</value>
        </cost>
        <copyrightAndOtherRestrictions>
          <value>yes</value>
        </copyrightAndOtherRestrictions>
        <license>CC-BY-4.0</license>
      </rights>
      <classification>
{$institutionxml}        <is_featured>{$isfeatured}</is_featured>
      </classification>
    </lom>
  </metadata>
  <organizations default="ORG-1">
    <organization identifier="ORG-1" structure="rooted-hierarchy">
      <title>{$title}</title>
{$organizationsxml}    </organization>
  </organizations>
  <resources>
{$resourcesxml}  </resources>
</manifest>
XML;
    }

    /**
     * Recursively add a directory into an active ZipArchive.
     *
     * @param ZipArchive $zip
     * @param string $source
     * @param string $prefix
     */
    protected function add_directory_to_zip(ZipArchive $zip, string $source, string $prefix): void {
        $files = scandir($source);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $sourcepath = $source . '/' . $file;
            $zippath = $prefix ? ($prefix . '/' . $file) : $file;

            if (is_dir($sourcepath)) {
                $zip->addEmptyDir($zippath);
                $this->add_directory_to_zip($zip, $sourcepath, $zippath);
            } else {
                $zip->addFile($sourcepath, $zippath);
            }
        }
    }
}
