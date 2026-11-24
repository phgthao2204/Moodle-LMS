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

namespace local_accees_sync_api\manager;

use local_accees_sync_api\exception\api_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Service for handling course enrolments compliant with UC-LMS-12 spec.
 */
class enrolment_manager {

    /**
     * Enrol a user into a course, auto-creating the account if strict_sso is false.
     *
     * @param int $courseid Target course ID.
     * @param \stdClass $payload Request body.
     * @param string $version ACCEES API version ('1.0' or '1.1').
     * @return array Contains 'status' (int) and 'payload' (array).
     * @throws api_exception
     */
    public function enrol(int $courseid, \stdClass $payload, string $version = '1.0'): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/enrol/locallib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        if (!isset($payload->user) || !is_object($payload->user)) {
            throw new api_exception('MISSING_USER_FIELDS', 'Field "user" object is required in request payload.', 400);
        }

        $userobj = $payload->user;
        $username = isset($userobj->username) ? trim((string)$userobj->username) : '';
        $email = isset($userobj->email) ? trim((string)$userobj->email) : '';
        $firstname = isset($userobj->firstname) ? trim((string)$userobj->firstname) : '';
        $lastname = isset($userobj->lastname) ? trim((string)$userobj->lastname) : '';
        $idnumber = isset($userobj->idnumber) ? trim((string)$userobj->idnumber) : '';

        if ($username === '' && $email === '') {
            throw new api_exception('MISSING_USER_FIELDS', 'Either username or email must be provided to identify the user.', 400);
        }

        // Determine strict_sso mode (explicit in payload, or version 1.1 / config fallback).
        $defaultstrict = (bool) get_config('local_accees_sync_api', 'default_strict_sso');
        $strictsso = isset($payload->strict_sso) ? (bool)$payload->strict_sso : ($version === '1.1' || $defaultstrict);

        // Resolve role.
        $rolename = isset($payload->role) && trim((string)$payload->role) !== '' ? trim((string)$payload->role) : 'student';
        $role = $DB->get_record('role', ['shortname' => $rolename], '*', IGNORE_MISSING);
        if (!$role) {
            throw new api_exception('ROLE_NOT_FOUND', "Role '{$rolename}' was not found.", 404);
        }

        // Resolve timestamps.
        $timestart = isset($payload->timestart) ? (int)$payload->timestart : 0;
        $timeend = isset($payload->timeend) ? (int)$payload->timeend : 0;
        if ($timestart < 0 || $timeend < 0) {
            throw new api_exception('INVALID_PAYLOAD', 'Timestamps timestart and timeend must be non-negative.', 400);
        }
        if ($timeend > 0 && $timestart > 0 && $timeend < $timestart) {
            throw new api_exception('INVALID_PAYLOAD', 'timeend must not precede timestart.', 400);
        }

        // Resolve manual enrolment instance.
        $manualenrol = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'manual',
            'status' => ENROL_INSTANCE_ENABLED,
        ], '*', IGNORE_MISSING);

        if (!$manualenrol || !$instance) {
            throw new api_exception('ENROLMENT_CONFLICT', 'Manual enrolment plugin is not enabled for this course.', 409);
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            // Find existing user.
            $user = null;
            if ($username !== '') {
                $user = $DB->get_record('user', [
                    'username' => clean_param($username, PARAM_USERNAME),
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'deleted' => 0,
                ], '*', IGNORE_MISSING);
            }

            if (!$user && $email !== '') {
                $loweredemail = \core_text::strtolower($email);
                $users = $DB->get_records_sql(
                    'SELECT * FROM {user} WHERE LOWER(email) = :email AND deleted = 0',
                    ['email' => $loweredemail]
                );
                if (count($users) > 1) {
                    throw new api_exception('ENROLMENT_CONFLICT', 'Ambiguous email address matches multiple users.', 409);
                }
                if (count($users) === 1) {
                    $user = reset($users);
                }
            }

            $usercreated = false;
            if (!$user) {
                if ($strictsso) {
                    throw new api_exception('USER_NOT_FOUND', 'User was not found and strict SSO mode is active.', 404);
                }

                // Validate required fields for user creation.
                if ($email === '' || $firstname === '' || $lastname === '') {
                    throw new api_exception('MISSING_USER_FIELDS', 'Fields email, firstname, and lastname are required to auto-create user.', 400);
                }
                if (!validate_email($email)) {
                    throw new api_exception('MISSING_USER_FIELDS', 'A valid email address is required.', 400);
                }

                $usernamecandidate = $username !== '' ? $username : strtok($email, '@');
                $usernamecandidate = clean_param($usernamecandidate, PARAM_USERNAME);
                if ($usernamecandidate === '') {
                    throw new api_exception('MISSING_USER_FIELDS', 'A valid username could not be derived.', 400);
                }

                if ($DB->record_exists('user', [
                    'username' => $usernamecandidate,
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'deleted' => 0,
                ])) {
                    throw new api_exception('ENROLMENT_CONFLICT', "Username '{$usernamecandidate}' is already in use.", 409);
                }

                $userrecord = (object)[
                    'username' => $usernamecandidate,
                    'password' => generate_password(20),
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'auth' => 'manual',
                    'confirmed' => 1,
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'lang' => $CFG->lang ?? 'en',
                    'idnumber' => $idnumber,
                ];

                $userid = user_create_user($userrecord, true, false);
                $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
                $usercreated = true;
            } else {
                if (!empty($user->suspended)) {
                    throw new api_exception('ENROLMENT_CONFLICT', 'User account is suspended.', 409);
                }
            }

            // Check if already enrolled in this course with this role.
            $context = \context_course::instance($courseid);
            $userenrolment = $DB->get_record('user_enrolments', [
                'enrolid' => $instance->id,
                'userid' => $user->id,
            ], '*', IGNORE_MISSING);

            $hasrole = user_has_role_assignment($user->id, $role->id, $context->id);

            if ($userenrolment && $hasrole) {
                $enrolmentstatus = 'already_enrolled';
                $httpstatus = 200;
            } else {
                try {
                    $manualenrol->enrol_user(
                        $instance,
                        $user->id,
                        (int)$role->id,
                        $timestart,
                        $timeend,
                        ENROL_USER_ACTIVE
                    );
                } catch (\Throwable $me) {
                    // If enrolment in DB succeeded but sending notification email failed, proceed.
                    if (!$DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id])) {
                        throw $me;
                    }
                }
                $enrolmentstatus = 'enrolled';
                $httpstatus = $usercreated ? 201 : 200;
            }

            $transaction->allow_commit();

            return [
                'status' => $httpstatus,
                'payload' => [
                    'course_id' => $courseid,
                    'user_id' => (int)$user->id,
                    'username' => (string)$user->username,
                    'role' => (string)$role->shortname,
                    'user_created' => (bool)$usercreated,
                    'enrolment_status' => $enrolmentstatus,
                    'timestart' => $timestart,
                    'timeend' => $timeend,
                ],
            ];
        } catch (\Throwable $e) {
            try {
                $transaction->rollback($e);
            } catch (\Throwable $ignored) {
            }
            throw $e;
        }
    }
}

