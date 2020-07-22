<?php
namespace Courses;

use DBAL\Database;
use DBAL\Modifiers\Modifier;
use DateTime;

class Pupils{
    protected $db;
    
    protected $users_table = 'products_customers';
    protected $instructors_table = 'instructors';
    
    protected $pupils_table = 'course_access';
    
    public $bulkUserTypes = [1 => "Learner Drivers", 2 => "PDIs", 3 => "Instructors", 4 => "Tutors"];

    /**
     * Pass an instance of the database to the class
     * @param Database $db This should be an instance of the database
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    /**
     * Adds a pupil to a given course
     * @param int $pupilID This should be the users unique ID
     * @param int|boolean $isInstructor If the pupil is a instructor should set to 1/true or is just a standard pupil need to set to 0/false
     * @param int $courseID This need to be the course that you are assigning the pupil to
     * @param datetime The date in which the user will have access until, If set to NULL no expiry is set
     * @return boolean If the information has been successfully added will return true else will return false
     */
    public function addPupilAccess($pupilID, $isInstructor, $courseID, $expiry = NULL) {
        if($expiry !== NULL && DateTime::createFromFormat('Y-m-d H:i:s', $expiry) !== false) {
            $expiry = date('Y-m-d H:i:s', strtotime($expiry));
        }
        if(is_numeric($pupilID) && (is_numeric($isInstructor) || is_bool($isInstructor)) && is_numeric($courseID)) {
            return $this->db->insert($this->pupils_table, ['user_id' => Modifier::setNullOnEmpty(boolval($isInstructor) === false ? $pupilID : NULL), 'instructor_id' => Modifier::setNullOnEmpty(boolval($isInstructor) === false ? NULL : $pupilID), 'course_id' => $courseID, 'expiry_date' => $expiry]);
        }
        return false;
    }
    
    /**
     * Bulk enrol users onto a course
     * @param int $type This should be the user types that you wish to enrol
     * @param int $courseID This should be the ID of the course that you want to enrol the users onto
     */
    public function bulkEnrol($type, $courseID) {
        $users = $this->getUsersByType($type);
        if(is_array($users) && is_numeric($courseID)){
            foreach($users as $user){
                $this->addPupilAccess($user['id'], $user['is_instructor'], $courseID);
            }
        }
    }
    
    /**
     * Returns an array of users by type (see bulkUserTypes array)
     * @param int $type This should be the type ID
     * @return array|boolean If any users exist for the given type they will be returned as an array else will return false 
     */
    protected function getUsersByType($type){
        if($type == 1){return $this->db->query("SELECT `id`, 0 as `is_instructor` FROM `{$this->users_table}` WHERE `student` = 1 AND `student_type` => 1;");}
        elseif($type == 2){return $this->db->query("SELECT `id`, 0 as `is_instructor` FROM `{$this->users_table}` WHERE `student` = 1 AND `student_type` => 2;");}
        elseif($type == 3){return $this->db->query("SELECT `id`, 1 as `is_instructor` FROM `{$this->instructors_table}` WHERE `disabled` = 0;");}
        elseif($type == 4){return $this->db->query("SELECT `id`, 1 as `is_instructor` FROM `{$this->instructors_table}` WHERE `disabled` = 0 AND `tutor` = 1;");}
        return false;
    }
    
    /**
     * Checks to see if the user has access to the given course
     * @param type $pupilID This should be the users unique ID
     * @param int|boolean $isInstructor
     * @param int $courseID This need to be the course that you are checking access for
     * @return boolean If the user has access to the course will return true else will return false
     */
    public function getPupilAccess($pupilID, $isInstructor, $courseID){
        return boolval($this->db->count($this->pupils_table, ['user_id' => $pupilID, 'is_instructor' => $isInstructor, 'course_id' => $courseID]));
    }
    
    /**
     * Remove a given pupil from the given course
     * @param int $pupilID This should be the users unique ID
     * @param int|boolean $isInstructor If the pupil is a instructor should set to 1/true or is just a standard pupil need to set to 0/false
     * @param int $courseID This need to be the course that you are removing the pupil from
     * @return boolean If the record has been removed will return true else will return false
     */
    public function removePupilAccess($pupilID, $isInstructor, $courseID){
        if(is_numeric($pupilID) && (is_numeric($isInstructor) || is_bool($isInstructor)) && is_numeric($courseID)){
            return $this->db->delete($this->pupils_table, ['user_id' => $pupilID, 'is_instructor' => $isInstructor, 'course_id' => $courseID]);
        }
        return false;
    }
    
    /**
     * Checks to see if the user has access to the given course
     * @param type $pupilID This should be the users unique ID
     * @param int|boolean $isInstructor If the pupil is a instructor should set to 1/true or is just a standard pupil need to set to 0/false
     * @param int $courseID This need to be the course that you are checking access for
     * @return boolean If the user has access to the course will return true else will return false
     */
    public function checkPupilAccess($pupilID, $isInstructor, $courseID){
        return $this->getPupilAccess($pupilID, $isInstructor, $courseID);
    }
    
    /**
     * Returns all of the pupils on a given course
     * @param int $courseID This should be the course ID you want to list all of the pupils for
     * @return array|boolean If any pupils exist the information will be returned as an array else will return false
     */
    public function getPupilsOnCourse($courseID){
        if(is_numeric($courseID)){
            return $this->db->query("SELECT * FROM ((SELECT CONCAT(`{$this->users_table}`.`firstname`, ' ', `{$this->users_table}`.`lastname`) as `name`, `{$this->users_table}`.`email`, `{$this->users_table}`.`cust_id` as `user_id`, 0 as `is_instructor` FROM `{$this->users_table}` WHERE `{$this->pupils_table}`.`course_id` = :courseid AND `{$this->pupils_table}`.`is_instructor` = 0 AND `{$this->pupils_table}`.`user_id` = `{$this->users_table}`.`user_id`) UNION (SELECT `{$this->instructors_table}`.`ldiname` as `name`, CONCAT('info@', `{$this->instructors_table}`.`website`) as `email`, `{$this->instructors_table}`.`fino` as `user_id`, 1 as `is_instructor` FROM `{$this->users_table}` WHERE `{$this->pupils_table}`.`course_id` = :courseid AND `{$this->pupils_table}`.`is_instructor` = 1 AND `{$this->pupils_table}`.`user_id` = `{$this->instructors_table}`.`fino`)) ORDER BY `name` ASC;", array(':courseid' => intval($courseID)));
        }
        return false;
    }
    
    /**
     * Lists all of the course IDs that the pupil given is enrolled onto
     * @param int $pupilID This should be the pupils ID
     * @param int|boolean $isPupilInstructor If the pupil is an instructor set this as true/1 else set to false/0 is just a standard learner 
     * @return array|false If the pupil is list on any courses will return an array of the enrolled course ID else will return false
     */
    public function getPupilsCoursesList($pupilID, $isPupilInstructor){
        return $this->db->selectAll($this->pupils_table, array('user_id' => $pupilID, 'is_instructor' => intval($isPupilInstructor)), array('course_id'));
    }
}
