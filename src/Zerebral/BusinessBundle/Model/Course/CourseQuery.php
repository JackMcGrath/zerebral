<?php

namespace Zerebral\BusinessBundle\Model\Course;

use Zerebral\BusinessBundle\Model\Course\om\BaseCourseQuery;
use Zerebral\BusinessBundle\Model\Feed\FeedItemPeer;
use Zerebral\BusinessBundle\Model\Feed\FeedCommentPeer;

class CourseQuery extends BaseCourseQuery
{

    /**
     * Find courses by Teacher id
     *
     * @param integer $id
     * @return Course|Course[]|mixed the result, formatted by the current formatter
     */
    public function findByTeacher($id){
       return $this->findByCreatedBy($id);
    }

    /**
     * @param \Zerebral\BusinessBundle\Model\User\Student $roleUser
     */
    public function filterByRoleUser($roleUser)
    {
        $this->joinWith('Assignment', \Criteria::LEFT_JOIN);
        $this->joinWith('CourseTeacher', \Criteria::LEFT_JOIN);
        $this->leftJoinCourseStudent();



        if ($roleUser->getUser()->isTeacher()) {
            $this->filterByTeacher($roleUser);
            $this->addJoinCondition('Assignment', 'Assignment.teacher_id='.$roleUser->getId());
            $this->addGroupByColumn(CourseTeacherPeer::COURSE_ID);
        } else {
            $this->filterByStudent($roleUser);
            $this->leftJoin('Assignment.StudentAssignment StudentAssignments');
            $this->addJoinCondition('StudentAssignments', '`StudentAssignments`.student_id='.$roleUser->getId());
            $this->withColumn('COUNT(DISTINCT `StudentAssignments`.id)', 'studentAssignmentsCount');
            $this->addGroupByColumn(CourseStudentPeer::COURSE_ID);
        }

        $this->withColumn('COUNT(DISTINCT assignments.id)', 'assignmentsCount');
        $this->withColumn('GROUP_CONCAT(distinct IF(assignments.due_at is not null,CONCAT_WS("_",assignments.id, DATE(assignments.due_at)), null) SEPARATOR ",")', 'dueDates');


        return $this;

    }
}
