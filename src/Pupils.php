<?php
namespace Courses;

use DBAL\Database;
use DBAL\Modifiers\Modifier;
use Configuration\Config;
use DateTime;

class Pupils
{
    protected $db;
    protected $config;
    
    protected $instructors_table = 'instructors';
    
    public $bulkUserTypes = [1 => "Learner Drivers", 2 => "PDIs", 3 => "Instructors", 4 => "Tutors"];
    public $typeField = [1 => 'learner', 2 => 'pdi', 3 => 'instructor', 4 => 'tutor'];

    /**
     * Pass an instance of the database to the class
     * @param Database $db This should be an instance of the database
     */
    public function __construct(Database $db, Config $config)
    {
        $this->db = $db;
        $this->config = $config;
    }
    
    /**
     * Adds a pupil to a given course
     * @param int $pupilID This should be the users unique ID
     * @param int|boolean $isInstructor If the pupil is a instructor should set to 1/true or is just a standard pupil need to set to 0/false
     * @param int $courseID This need to be the course that you are assigning the pupil to
     * @param datetime The date in which the user will have access until, If set to NULL no expiry is set
     * @return boolean If the information has been successfully added will return true else will return false
     */
    public function addPupilAccess($pupilID, $isInstructor, $courseID, $expiry = null)
    {
        if ($expiry !== null && DateTime::createFromFormat('Y-m-d H:i:s', $expiry) !== false) {
            $expiry = date('Y-m-d H:i:s', strtotime($expiry));
        }
        if (is_numeric($pupilID) && (is_numeric($isInstructor) || is_bool($isInstructor)) && is_numeric($courseID)) {
            return $this->db->insert($this->config->table_course_access, ['user_id' => Modifier::setNullOnEmpty(boolval($isInstructor) === false ? $pupilID : null), 'instructor_id' => Modifier::setNullOnEmpty(boolval($isInstructor) === false ? null : $pupilID), 'course_id' => $courseID, 'expiry_date' => $expiry]);
        }
        return false;
    }
    
    /**
     * Returns an array of users by type (see bulkUserTypes array)
     * @param int $type This should be the type ID
     * @param int|array $limit This should be a limit of the number of results shown
     * @return array|boolean If any users exist for the given type they will be returned as an array else will return false
     */
    protected function getUsersByType($type, $limit = 0)
    {
        if ($type == 1 || $type == 2) {
            return $this->db->query("SELECT `id`, 0 as `is_instructor` FROM `{$this->config->table_users}` WHERE `student` = 1 AND `student_type` = ?;", [$type]);
        } else {
            return $this->db->query("SELECT `id`, 1 as `is_instructor` FROM `{$this->instructors_table}` WHERE `isactive` >= 1".($type == 4 ? " AND `tutor` = 1" : "").";");
        }
        return false;
    }
    
    public function countUsersByType($type)
    {
        return count($this->getUsersByType($type, 0));
    }

    /**
     * Checks to see if the user has access to the given course
     * @param int $pupilID This should be the users unique ID
     * @param int|boolean $type If the pupil is a instructor should set to 1/true or is just a standard pupil need to set to 0/false
     * @param int $courseID This need to be the course that you are checking access for
     * @return boolean If the user has access to the course will return true else will return false
     */
    public function getPupilAccess($pupilID, $type, $courseID)
    {
        return boolval(count($this->db->query("SELECT * FROM (
            SELECT * FROM `{$this->config->table_courses}` WHERE `enrol_{$this->typeField[$type]}s` = 1 AND `id` = ?
            UNION
            SELECT `{$this->config->table_courses}`.* FROM `{$this->config->table_courses}`, `{$this->config->table_course_access}` WHERE `` `{$this->config->table_courses}`.`enrol_individuals` = 1 AND `{$this->config->table_courses}`.`id` = `{$this->config->table_course_access}`.`course_id` AND `{$this->config->table_course_access}`.`course_id` = ? AND `{$this->config->table_course_access}`.`user_id` = ?
) as A;", [$courseID, $courseID, $pupilID])));
    }
    
    /**
     * Get a pupils information if the user exists
     * @param int $pupilID This should be the users unique ID
     * @param int|boolean $isInstructor If the pupil is a instructor should set to 1/true or is just a standard pupil need to set to 0/false
     * @return array|false
     */
    public function getPupilInfo($pupilID, $isInstructor)
    {
        return $this->db->select(($isInstructor ? $this->instructors_table : $this->config->table_users), ['id' => $pupilID]);
    }
    
    /**
     * Remove a given pupil from the given course
     * @param int $pupilID This should be the users unique ID
     * @param int|boolean $isInstructor If the pupil is a instructor should set to 1/true or is just a standard pupil need to set to 0/false
     * @param int $courseID This need to be the course that you are removing the pupil from
     * @return boolean If the record has been removed will return true else will return false
     */
    public function removePupilAccess($pupilID, $isInstructor, $courseID)
    {
//        if (is_numeric($pupilID) && is_numeric($courseID)) {
//            return $this->db->delete($this->config->table_course_access, array_merge($this->getUserWhere($pupilID, $isInstructor), ['course_id' => $courseID]));
//        }
//        return false;
    }
    
    /**
     * Checks to see if the user has access to the given course
     * @param int $pupilID This should be the users unique ID
     * @param int $type If the pupil is a instructor should set to 1/true or is just a standard pupil need to set to 0/false
     * @param int $courseID This need to be the course that you are checking access for
     * @return boolean If the user has access to the course will return true else will return false
     */
    public function checkPupilAccess($pupilID, $type, $courseID)
    {
        return $this->getPupilAccess($pupilID, $type, $courseID);
    }
    
    /**
     * Counts the number of pupils on the course
     * @param int $courseID This should be the course ID you want to count the pupils for
     * @param string $search This should be either the name or email you are searching for
     * @return int The number of pupils on the course
     */
    public function countPupilsOnCourse($courseID, $search = '')
    {
        return count($this->getPupilsOnCourse($courseID, 0, 0, $search));
    }
    
    /**
     * Returns all of the pupils on a given course
     * @param int $courseID This should be the course ID you want to list all of the pupils for
     * @param int $start If limiting the number of returned results this is the start number of where you want the results from
     * @param int $limit The maximum number of results to return
     * @param string $search This should be either the name or email you are searching for
     * @return array|boolean If any pupils exist the information will be returned as an array else will return false
     */
    public function getPupilsOnCourse($courseID, $start = 0, $limit = 50, $search = '')
    {
        /*return $this->db->query("SELECT * FROM ((SELECT CONCAT(`{$this->config->table_users}`.`firstname`, ' ', `{$this->config->table_users}`.`lastname`) as `name`, `{$this->config->table_users}`.`email`, `{$this->config->table_users}`.`id`, 0 as `is_instructor` FROM `{$this->config->table_users}`, `{$this->config->table_course_access}` WHERE `{$this->config->table_course_access}`.`course_id` = :courseid AND `{$this->config->table_course_access}`.`user_id` = `{$this->config->table_users}`.`id`".(!empty($search) ? " AND (`{$this->config->table_users}`.`firstname` LIKE :search OR `{$this->config->table_users}`.`lastname` LIKE :search OR `{$this->config->table_users}`.`email` LIKE :search)" : "").") UNION (SELECT `{$this->instructors_table}`.`name`, `{$this->instructors_table}`.`email`, `{$this->instructors_table}`.`id`, 1 as `is_instructor` FROM `{$this->instructors_table}`, `{$this->config->table_course_access}` WHERE `{$this->config->table_course_access}`.`course_id` = :courseid AND `{$this->config->table_course_access}`.`instructor_id` = `{$this->instructors_table}`.`id`".(!empty($search) ? " AND (`{$this->config->instructors_table}`.`name` LIKE :search OR `{$this->instructors_table}`.`email` LIKE :search)" : "").")) as `a` ORDER BY `name` ASC".($limit > 0 ? ' LIMIT '.intval($start).','.$limit.';' : ';'), array_merge([':courseid' => intval($courseID)], (!empty($search) ? [':search' => '%'.$search.'%'] : [])));*/
    }
    
    /**
     * Lists all of the course IDs that the pupil given is enrolled onto
     * @param int $pupilID This should be the pupils ID
     * @param int $type The type of user that the current pupil is
     * @return array|false If the pupil is list on any courses will return an array of the enrolled course ID else will return false
     */
    public function getPupilsCoursesList($pupilID, $type)
    {
        return $this->db->query("SELECT * FROM (
            SELECT * FROM `{$this->config->table_courses}` WHERE `enrol_{$this->typeField[$type]}s` = 1
            UNION
            SELECT `{$this->config->table_courses}`.* FROM `{$this->config->table_courses}`, `{$this->config->table_course_access}` WHERE `{$this->config->table_courses}`.`enrol_individuals` = 1 AND `{$this->config->table_courses}`.`id` = `{$this->config->table_course_access}`.`course_id` AND `{$this->config->table_course_access}`.`user_id` = ?
) as A;", [$pupilID]);
    }
    
    /**
     * Returns the correct field and value to check user access from the database
     * @param int $pupilID This should be the pupils ID
     * @param int|boolean $isInstructor If the pupil is an instructor set this as true/1 else set to false/0 is just a standard learner
     * @return array
     */
    protected function getUserWhere($pupilID, $isInstructor)
    {
        if (boolval($isInstructor) !== true) {
            return ['user_id' => $pupilID];
        }
        return ['instructor_id' => $pupilID];
    }
}
