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

namespace local_accees_sync_api\contract;

/**
 * Contract for mapping ACCEES-LOM (9 categories / D1.2a) to Moodle storage.
 *
 * Version 1.0 persists the 5 core Moodle course custom fields while keeping
 * the full 9-category hierarchical payload ready for Version 1.1 extensions.
 */
interface lom_metadata_interface {

    /** @var string Core v1.0 custom field shortname: lifecycle.status. */
    public const FIELD_COURSE_STATUS = 'accees_course_status';

    /** @var string Core v1.0 custom field shortname: technical.delivery_mode. */
    public const FIELD_DELIVERY_MODE = 'accees_delivery_mode';

    /** @var string Core v1.0 custom field shortname: technical.duration_hours. */
    public const FIELD_DURATION = 'accees_duration';

    /** @var string Core v1.0 custom field shortname: classification.institution. */
    public const FIELD_INSTITUTION = 'accees_institution';

    /** @var string Core v1.0 custom field shortname: classification.is_featured. */
    public const FIELD_IS_FEATURED = 'accees_is_featured';

    /** @var string[] Supported 9 top-level LOM-ACCEES categories. */
    public const LOM_CATEGORIES = [
        'general',
        'lifecycle',
        'metametadata',
        'technical',
        'educational',
        'rights',
        'relation',
        'annotation',
        'classification',
    ];

    /**
     * Read course metadata as a 9-category LOM-ACCEES associative structure.
     *
     * @param int $courseid Moodle course ID.
     * @param string $apiversion Target API version ('1.0' or '1.1').
     * @return array<string, mixed>
     */
    public function export_lom(int $courseid, string $apiversion = '1.0'): array;

    /**
     * Persist supported LOM-ACCEES fields into Moodle course and custom fields.
     *
     * @param int $courseid Moodle course ID.
     * @param array<string, mixed> $lom Normalized LOM-ACCEES payload.
     * @param string $apiversion Target API version ('1.0' or '1.1').
     * @return array<string, mixed> Effective stored custom field values.
     */
    public function apply_lom(int $courseid, array $lom, string $apiversion = '1.0'): array;
}
