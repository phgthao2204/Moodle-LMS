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

namespace local_accees_sync_api\exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Standard API exception carrying HTTP status and stable machine-readable error code.
 */
class api_exception extends \RuntimeException {

    /** @var int HTTP status code. */
    private int $httpstatus;

    /** @var string Stable error code. */
    private string $errorcode;

    /**
     * Constructor.
     *
     * @param string $errorcode Stable API error code.
     * @param string $message Descriptive error message.
     * @param int $httpstatus HTTP status code.
     */
    public function __construct(string $errorcode, string $message, int $httpstatus) {
        $this->httpstatus = $httpstatus;
        $this->errorcode = $errorcode;
        parent::__construct($message);
    }

    /**
     * Get HTTP status code.
     *
     * @return int
     */
    public function get_http_status(): int {
        return $this->httpstatus;
    }

    /**
     * Get stable error code.
     *
     * @return string
     */
    public function get_error_code(): string {
        return $this->errorcode;
    }
}

