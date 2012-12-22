<?php

namespace Zerebral\BusinessBundle\Model\Course;

use Zerebral\BusinessBundle\Model\Course\om\BaseCourse;

use Zerebral\BusinessBundle\Model\User\Student;
use Zerebral\BusinessBundle\Model\User\Teacher;
use Zerebral\BusinessBundle\Model\User\User;

class Course extends BaseCourse
{
    /**
     * {@inheritDoc}
     */
    public function preSave(\PropelPDO $con = null)
    {
        //@todo fix it
        $this->setUpdatedAt(date("Y-m-d H:i:s", time()));
        return parent::preSave();
    }

    public function preInsert(\PropelPDO $con = null)
    {
        //@todo fix it
        $this->setCreatedAt(date("Y-m-d H:i:s", time()));
        $this->setAccessCode($this->generateInvite());
        return parent::preInsert($con);
    }

    public function getTeacher()
    {
        return $this->getTeachers()->getFirst();
    }

    /**
     * Generate random string for use it as course invite code
     * @param int $length
     * @return string
     */
    private function generateInvite($length = 7)
    {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    public function resetAccessCode()
    {
        $this->setAccessCode($this->generateInvite());
    }

    /**
     * @param Student|Teacher $user
     */
    public function addUser($user)
    {
        if (($user instanceof Student) && !$this->getStudents()->contains($user)) {
            $this->addStudent($user);
            return true;
        } elseif (($user instanceof Teacher) && !$this->getTeachers()->contains($user)) {
            $this->addTeacher($user);
            return true;
        }

        return false;
    }
}
