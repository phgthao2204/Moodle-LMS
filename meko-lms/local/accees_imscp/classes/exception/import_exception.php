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

namespace local_accees_imscp\exception;

use Exception;

/**
 * Domain exception for local_accees_imscp import operations.
 */
class import_exception extends Exception {

    /**
     * @param string $message User-facing error message without stack trace.
     * @param string $errorcode Standard error enum code (e.g. INVALID_PACKAGE).
     * @param int $httpstatus HTTP response status code (e.g. 400, 403, 409, 422).
     * @param array<string, mixed> $details Extra diagnostic fields.
     */
    public function __construct(
        string $message,
        public readonly string $errorcode = 'INVALID_PACKAGE',
        public readonly int $httpstatus = 400,
        public readonly array $details = [],
    ) {
        parent::__construct($message, $httpstatus);
    }
}
