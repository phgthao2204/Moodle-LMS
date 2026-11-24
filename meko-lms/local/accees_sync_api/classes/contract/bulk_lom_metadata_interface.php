<?php
// This file is part of Moodle - http://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later

namespace local_accees_sync_api\contract;

/** Optional batch extension; existing single-course LOM implementations remain compatible. */
interface bulk_lom_metadata_interface extends lom_metadata_interface {
    /**
     * Export the nine LOM groups using already loaded course records.
     *
     * @param \stdClass[] $courses Course records including lang and summary.
     * @param string $apiversion API version.
     * @return array<int, array> LOM payloads keyed by course ID.
     */
    public function export_lom_batch(array $courses, string $apiversion = '1.0'): array;
}
