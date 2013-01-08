<?php

namespace Zerebral\BusinessBundle\Model\User;

use Zerebral\BusinessBundle\Model\User\om\BaseTeacher;

class Teacher extends BaseTeacher
{
    public function preSave(\PropelPDO $con = null)
    {
        if ($this->getUser($con) !== null && $this->getUser($con)->isNew()) {
            $this->getUser($con)->setRole(User::ROLE_TEACHER);
        }
        return parent::preSave($con);
    }

    public function getFullName()
    {
        return $this->getUser()->getFullName();
    }

    public function getCourseAssignments(\Zerebral\BusinessBundle\Model\Course\Course $course)
    {
        return $course->getAssignments();
    }

    /**
     * @param \Zerebral\BusinessBundle\Model\Course\Course $course
     * @param null $ongoing if null - get all, false - only with due_at, true - only without due at
     * @return \PropelObjectCollection
     */
    public function getCourseAssignmentsDueDate(\Zerebral\BusinessBundle\Model\Course\Course $course, $ongoing = null)
    {
        $assignments = \Zerebral\BusinessBundle\Model\Assignment\AssignmentQuery::create()->getCourseAssignmentsDueDate($course, $ongoing);
        if ($ongoing == true) {
            $assignments->addAscendingOrderByColumn('LOWER(assignments.name)');
        } else {
            $assignments->addDescendingOrderByColumn('assignments.due_at');
        }

        return $assignments->find();
//        $c = new \Criteria();
//        $c->addDescendingOrderByColumn('due_at');
//        if ($ongoing === false) {
//            $c->addCond('dueAt', 'due_at', null, \Criteria::ISNOTNULL);
//        } else if ($ongoing === true) {
//            $c->addCond('dueAt', 'due_at', null, \Criteria::ISNULL);
//        }
//        $c->addJoin(\Zerebral\BusinessBundle\Model\Assignment\AssignmentPeer::ID, \Zerebral\BusinessBundle\Model\Assignment\StudentAssignmentPeer::ASSIGNMENT_ID, \Criteria::LEFT_JOIN);
//        $c->addCond('students', 'student_assignments.id', null, \Criteria::ISNOTNULL);
//        $c->combine(array('students', 'dueAt'), \Criteria::LOGICAL_AND);
//        $c->addGroupByColumn('assignments.id');
//
//        return $course->getAssignments($c);
    }

    public function getCourseAssignmentsDraft(\Zerebral\BusinessBundle\Model\Course\Course $course)
    {
        $c = new \Criteria();
        $c->addJoin(\Zerebral\BusinessBundle\Model\Assignment\AssignmentPeer::ID, \Zerebral\BusinessBundle\Model\Assignment\StudentAssignmentPeer::ASSIGNMENT_ID, \Criteria::LEFT_JOIN);
        $c->add('student_assignments.id', null, \Criteria::ISNULL);
        $c->addDescendingOrderByColumn('due_at');

        return $course->getAssignments($c);
    }

    public function getAssignmentsDueDate($ongoing = null)
    {
        $assignments = \Zerebral\BusinessBundle\Model\Assignment\AssignmentQuery::create()->getCourseAssignmentsDueDate(null, $ongoing, $this);
        return $assignments->find();
    }

    public function getAssignmentsDraft()
    {
        $c = new \Criteria();
        $c->addJoin(\Zerebral\BusinessBundle\Model\Assignment\AssignmentPeer::ID, \Zerebral\BusinessBundle\Model\Assignment\StudentAssignmentPeer::ASSIGNMENT_ID, \Criteria::LEFT_JOIN);
        $c->add('student_assignments.id', null, \Criteria::ISNULL);
        $c->addDescendingOrderByColumn('due_at');

        return $this->getAssignments($c);
    }

    public function hasCourse(\Zerebral\BusinessBundle\Model\Course\Course $course)
    {
        foreach($this->getCourses() as $c){
            if($course->getId() == $c->getId()){
                return true;
            }
        }
        return false;
    }
}
