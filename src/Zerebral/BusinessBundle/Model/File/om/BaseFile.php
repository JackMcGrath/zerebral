<?php

namespace Zerebral\BusinessBundle\Model\File\om;

use \BaseObject;
use \BasePeer;
use \Criteria;
use \DateTime;
use \Exception;
use \PDO;
use \Persistent;
use \Propel;
use \PropelCollection;
use \PropelDateTime;
use \PropelException;
use \PropelObjectCollection;
use \PropelPDO;
use Glorpen\PropelEvent\PropelEventBundle\Dispatcher\EventDispatcherProxy;
use Glorpen\PropelEvent\PropelEventBundle\Events\ModelEvent;
use Zerebral\BusinessBundle\Model\Assignment\Assignment;
use Zerebral\BusinessBundle\Model\Assignment\AssignmentQuery;
use Zerebral\BusinessBundle\Model\Assignment\StudentAssignment;
use Zerebral\BusinessBundle\Model\Assignment\StudentAssignmentQuery;
use Zerebral\BusinessBundle\Model\File\File;
use Zerebral\BusinessBundle\Model\File\FilePeer;
use Zerebral\BusinessBundle\Model\File\FileQuery;
use Zerebral\BusinessBundle\Model\File\FileReferences;
use Zerebral\BusinessBundle\Model\File\FileReferencesQuery;
use Zerebral\BusinessBundle\Model\Material\CourseMaterial;
use Zerebral\BusinessBundle\Model\Material\CourseMaterialQuery;
use Zerebral\BusinessBundle\Model\User\User;
use Zerebral\BusinessBundle\Model\User\UserQuery;

abstract class BaseFile extends BaseObject implements Persistent
{
    /**
     * Peer class name
     */
    const PEER = 'Zerebral\\BusinessBundle\\Model\\File\\FilePeer';

    /**
     * The Peer class.
     * Instance provides a convenient way of calling static methods on a class
     * that calling code may not be able to identify.
     * @var        FilePeer
     */
    protected static $peer;

    /**
     * The flag var to prevent infinit loop in deep copy
     * @var       boolean
     */
    protected $startCopy = false;

    /**
     * The value for the id field.
     * @var        int
     */
    protected $id;

    /**
     * The value for the name field.
     * @var        string
     */
    protected $name;

    /**
     * The value for the description field.
     * @var        string
     */
    protected $description;

    /**
     * The value for the size field.
     * @var        int
     */
    protected $size;

    /**
     * The value for the mime_type field.
     * @var        string
     */
    protected $mime_type;

    /**
     * The value for the storage field.
     * Note: this column has a database default value of: 'local'
     * @var        string
     */
    protected $storage;

    /**
     * The value for the created_at field.
     * @var        string
     */
    protected $created_at;

    /**
     * @var        PropelObjectCollection|FileReferences[] Collection to store aggregation of FileReferences objects.
     */
    protected $collFileReferencess;
    protected $collFileReferencessPartial;

    /**
     * @var        PropelObjectCollection|CourseMaterial[] Collection to store aggregation of CourseMaterial objects.
     */
    protected $collCourseMaterials;
    protected $collCourseMaterialsPartial;

    /**
     * @var        PropelObjectCollection|User[] Collection to store aggregation of User objects.
     */
    protected $collUsers;
    protected $collUsersPartial;

    /**
     * @var        PropelObjectCollection|Assignment[] Collection to store aggregation of Assignment objects.
     */
    protected $collassignmentReferenceIds;

    /**
     * @var        PropelObjectCollection|StudentAssignment[] Collection to store aggregation of StudentAssignment objects.
     */
    protected $collstudentAssignmentReferenceIds;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     * @var        boolean
     */
    protected $alreadyInSave = false;

    /**
     * Flag to prevent endless validation loop, if this object is referenced
     * by another object which falls in this transaction.
     * @var        boolean
     */
    protected $alreadyInValidation = false;

    /**
     * An array of objects scheduled for deletion.
     * @var		PropelObjectCollection
     */
    protected $assignmentReferenceIdsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var		PropelObjectCollection
     */
    protected $studentAssignmentReferenceIdsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var		PropelObjectCollection
     */
    protected $fileReferencessScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var		PropelObjectCollection
     */
    protected $courseMaterialsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var		PropelObjectCollection
     */
    protected $usersScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see        __construct()
     */
    public function applyDefaultValues()
    {
        $this->storage = 'local';
    }

    /**
     * Initializes internal state of BaseFile object.
     * @see        applyDefaults()
     */
    public function __construct()
    {
        parent::__construct();
        $this->applyDefaultValues();
        EventDispatcherProxy::trigger(array('construct','model.construct'), new ModelEvent($this));
}

    /**
     * Get the [id] column value.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the [name] column value.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the [description] column value.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the [size] column value.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get the [mime_type] column value.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * Get the [storage] column value.
     *
     * @return string
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Get the [optionally formatted] temporal [created_at] column value.
     *
     *
     * @param string $format The date/time format string (either date()-style or strftime()-style).
     *				 If format is null, then the raw DateTime object will be returned.
     * @return mixed Formatted date/time value as string or DateTime object (if format is null), null if column is null, and 0 if column value is 0000-00-00 00:00:00
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getCreatedAt($format = null)
    {
        if ($this->created_at === null) {
            return null;
        }

        if ($this->created_at === '0000-00-00 00:00:00') {
            // while technically this is not a default value of null,
            // this seems to be closest in meaning.
            return null;
        }

        try {
            $dt = new DateTime($this->created_at);
        } catch (Exception $x) {
            throw new PropelException("Internally stored date/time/timestamp value could not be converted to DateTime: " . var_export($this->created_at, true), $x);
        }

        if ($format === null) {
            // Because propel.useDateTimeClass is true, we return a DateTime object.
            return $dt;
        }

        if (strpos($format, '%') !== false) {
            return strftime($format, $dt->format('U'));
        }

        return $dt->format($format);

    }

    /**
     * Set the value of [id] column.
     *
     * @param int $v new value
     * @return File The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[] = FilePeer::ID;
        }


        return $this;
    } // setId()

    /**
     * Set the value of [name] column.
     *
     * @param string $v new value
     * @return File The current object (for fluent API support)
     */
    public function setName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->name !== $v) {
            $this->name = $v;
            $this->modifiedColumns[] = FilePeer::NAME;
        }


        return $this;
    } // setName()

    /**
     * Set the value of [description] column.
     *
     * @param string $v new value
     * @return File The current object (for fluent API support)
     */
    public function setDescription($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->description !== $v) {
            $this->description = $v;
            $this->modifiedColumns[] = FilePeer::DESCRIPTION;
        }


        return $this;
    } // setDescription()

    /**
     * Set the value of [size] column.
     *
     * @param int $v new value
     * @return File The current object (for fluent API support)
     */
    public function setSize($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->size !== $v) {
            $this->size = $v;
            $this->modifiedColumns[] = FilePeer::SIZE;
        }


        return $this;
    } // setSize()

    /**
     * Set the value of [mime_type] column.
     *
     * @param string $v new value
     * @return File The current object (for fluent API support)
     */
    public function setMimeType($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->mime_type !== $v) {
            $this->mime_type = $v;
            $this->modifiedColumns[] = FilePeer::MIME_TYPE;
        }


        return $this;
    } // setMimeType()

    /**
     * Set the value of [storage] column.
     *
     * @param string $v new value
     * @return File The current object (for fluent API support)
     */
    public function setStorage($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->storage !== $v) {
            $this->storage = $v;
            $this->modifiedColumns[] = FilePeer::STORAGE;
        }


        return $this;
    } // setStorage()

    /**
     * Sets the value of [created_at] column to a normalized version of the date/time value specified.
     *
     * @param mixed $v string, integer (timestamp), or DateTime value.
     *               Empty strings are treated as null.
     * @return File The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->created_at !== null || $dt !== null) {
            $currentDateAsString = ($this->created_at !== null && $tmpDt = new DateTime($this->created_at)) ? $tmpDt->format('Y-m-d H:i:s') : null;
            $newDateAsString = $dt ? $dt->format('Y-m-d H:i:s') : null;
            if ($currentDateAsString !== $newDateAsString) {
                $this->created_at = $newDateAsString;
                $this->modifiedColumns[] = FilePeer::CREATED_AT;
            }
        } // if either are not null


        return $this;
    } // setCreatedAt()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
            if ($this->storage !== 'local') {
                return false;
            }

        // otherwise, everything was equal, so return true
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array $row The row returned by PDOStatement->fetch(PDO::FETCH_NUM)
     * @param int $startcol 0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false)
    {
        try {

            $this->id = ($row[$startcol + 0] !== null) ? (int) $row[$startcol + 0] : null;
            $this->name = ($row[$startcol + 1] !== null) ? (string) $row[$startcol + 1] : null;
            $this->description = ($row[$startcol + 2] !== null) ? (string) $row[$startcol + 2] : null;
            $this->size = ($row[$startcol + 3] !== null) ? (int) $row[$startcol + 3] : null;
            $this->mime_type = ($row[$startcol + 4] !== null) ? (string) $row[$startcol + 4] : null;
            $this->storage = ($row[$startcol + 5] !== null) ? (string) $row[$startcol + 5] : null;
            $this->created_at = ($row[$startcol + 6] !== null) ? (string) $row[$startcol + 6] : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }
            $this->postHydrate($row, $startcol, $rehydrate);
            return $startcol + 7; // 7 = FilePeer::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating File object", $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {

    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param boolean $deep (optional) Whether to also de-associated any related objects.
     * @param PropelPDO $con (optional) The PropelPDO connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, PropelPDO $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getConnection(FilePeer::DATABASE_NAME, Propel::CONNECTION_READ);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $stmt = FilePeer::doSelectStmt($this->buildPkeyCriteria(), $con);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $stmt->closeCursor();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collFileReferencess = null;

            $this->collCourseMaterials = null;

            $this->collUsers = null;

            $this->collassignmentReferenceIds = null;
            $this->collstudentAssignmentReferenceIds = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param PropelPDO $con
     * @return void
     * @throws PropelException
     * @throws Exception
     * @see        BaseObject::setDeleted()
     * @see        BaseObject::isDeleted()
     */
    public function delete(PropelPDO $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getConnection(FilePeer::DATABASE_NAME, Propel::CONNECTION_WRITE);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = FileQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            // event behavior
            EventDispatcherProxy::trigger(array('delete.pre','model.delete.pre'), new ModelEvent($this));
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                // event behavior
                EventDispatcherProxy::trigger(array('delete.post', 'model.delete.post'), new ModelEvent($this));
                $con->commit();
                $this->setDeleted(true);
            } else {
                $con->commit();
            }
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param PropelPDO $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @throws Exception
     * @see        doSave()
     */
    public function save(PropelPDO $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getConnection(FilePeer::DATABASE_NAME, Propel::CONNECTION_WRITE);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            // event behavior
            EventDispatcherProxy::trigger('model.save.pre', new ModelEvent($this));
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // event behavior
                EventDispatcherProxy::trigger('model.insert.pre', new ModelEvent($this));
            } else {
                $ret = $ret && $this->preUpdate($con);
                // event behavior
                EventDispatcherProxy::trigger(array('update.pre', 'model.update.pre'), new ModelEvent($this));
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                    // event behavior
                    EventDispatcherProxy::trigger('model.insert.post', new ModelEvent($this));
                } else {
                    $this->postUpdate($con);
                    // event behavior
                    EventDispatcherProxy::trigger(array('update.post', 'model.update.post'), new ModelEvent($this));
                }
                $this->postSave($con);
                // event behavior
                EventDispatcherProxy::trigger('model.save.post', new ModelEvent($this));
                FilePeer::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }
            $con->commit();

            return $affectedRows;
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param PropelPDO $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see        save()
     */
    protected function doSave(PropelPDO $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            if ($this->assignmentReferenceIdsScheduledForDeletion !== null) {
                if (!$this->assignmentReferenceIdsScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    $pk = $this->getPrimaryKey();
                    foreach ($this->assignmentReferenceIdsScheduledForDeletion->getPrimaryKeys(false) as $remotePk) {
                        $pks[] = array($pk, $remotePk);
                    }
                    FileReferencesQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);
                    $this->assignmentReferenceIdsScheduledForDeletion = null;
                }

                foreach ($this->getassignmentReferenceIds() as $assignmentReferenceId) {
                    if ($assignmentReferenceId->isModified()) {
                        $assignmentReferenceId->save($con);
                    }
                }
            }

            if ($this->studentAssignmentReferenceIdsScheduledForDeletion !== null) {
                if (!$this->studentAssignmentReferenceIdsScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    $pk = $this->getPrimaryKey();
                    foreach ($this->studentAssignmentReferenceIdsScheduledForDeletion->getPrimaryKeys(false) as $remotePk) {
                        $pks[] = array($pk, $remotePk);
                    }
                    FileReferencesQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);
                    $this->studentAssignmentReferenceIdsScheduledForDeletion = null;
                }

                foreach ($this->getstudentAssignmentReferenceIds() as $studentAssignmentReferenceId) {
                    if ($studentAssignmentReferenceId->isModified()) {
                        $studentAssignmentReferenceId->save($con);
                    }
                }
            }

            if ($this->fileReferencessScheduledForDeletion !== null) {
                if (!$this->fileReferencessScheduledForDeletion->isEmpty()) {
                    FileReferencesQuery::create()
                        ->filterByPrimaryKeys($this->fileReferencessScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->fileReferencessScheduledForDeletion = null;
                }
            }

            if ($this->collFileReferencess !== null) {
                foreach ($this->collFileReferencess as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->courseMaterialsScheduledForDeletion !== null) {
                if (!$this->courseMaterialsScheduledForDeletion->isEmpty()) {
                    CourseMaterialQuery::create()
                        ->filterByPrimaryKeys($this->courseMaterialsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->courseMaterialsScheduledForDeletion = null;
                }
            }

            if ($this->collCourseMaterials !== null) {
                foreach ($this->collCourseMaterials as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->usersScheduledForDeletion !== null) {
                if (!$this->usersScheduledForDeletion->isEmpty()) {
                    foreach ($this->usersScheduledForDeletion as $user) {
                        // need to save related object because we set the relation to null
                        $user->save($con);
                    }
                    $this->usersScheduledForDeletion = null;
                }
            }

            if ($this->collUsers !== null) {
                foreach ($this->collUsers as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param PropelPDO $con
     *
     * @throws PropelException
     * @see        doSave()
     */
    protected function doInsert(PropelPDO $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[] = FilePeer::ID;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . FilePeer::ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(FilePeer::ID)) {
            $modifiedColumns[':p' . $index++]  = '`id`';
        }
        if ($this->isColumnModified(FilePeer::NAME)) {
            $modifiedColumns[':p' . $index++]  = '`name`';
        }
        if ($this->isColumnModified(FilePeer::DESCRIPTION)) {
            $modifiedColumns[':p' . $index++]  = '`description`';
        }
        if ($this->isColumnModified(FilePeer::SIZE)) {
            $modifiedColumns[':p' . $index++]  = '`size`';
        }
        if ($this->isColumnModified(FilePeer::MIME_TYPE)) {
            $modifiedColumns[':p' . $index++]  = '`mime_type`';
        }
        if ($this->isColumnModified(FilePeer::STORAGE)) {
            $modifiedColumns[':p' . $index++]  = '`storage`';
        }
        if ($this->isColumnModified(FilePeer::CREATED_AT)) {
            $modifiedColumns[':p' . $index++]  = '`created_at`';
        }

        $sql = sprintf(
            'INSERT INTO `files` (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case '`id`':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case '`name`':
                        $stmt->bindValue($identifier, $this->name, PDO::PARAM_STR);
                        break;
                    case '`description`':
                        $stmt->bindValue($identifier, $this->description, PDO::PARAM_STR);
                        break;
                    case '`size`':
                        $stmt->bindValue($identifier, $this->size, PDO::PARAM_INT);
                        break;
                    case '`mime_type`':
                        $stmt->bindValue($identifier, $this->mime_type, PDO::PARAM_STR);
                        break;
                    case '`storage`':
                        $stmt->bindValue($identifier, $this->storage, PDO::PARAM_STR);
                        break;
                    case '`created_at`':
                        $stmt->bindValue($identifier, $this->created_at, PDO::PARAM_STR);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param PropelPDO $con
     *
     * @see        doSave()
     */
    protected function doUpdate(PropelPDO $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();
        BasePeer::doUpdate($selectCriteria, $valuesCriteria, $con);
    }

    /**
     * Array of ValidationFailed objects.
     * @var        array ValidationFailed[]
     */
    protected $validationFailures = array();

    /**
     * Gets any ValidationFailed objects that resulted from last call to validate().
     *
     *
     * @return array ValidationFailed[]
     * @see        validate()
     */
    public function getValidationFailures()
    {
        return $this->validationFailures;
    }

    /**
     * Validates the objects modified field values and all objects related to this table.
     *
     * If $columns is either a column name or an array of column names
     * only those columns are validated.
     *
     * @param mixed $columns Column name or an array of column names.
     * @return boolean Whether all columns pass validation.
     * @see        doValidate()
     * @see        getValidationFailures()
     */
    public function validate($columns = null)
    {
        $res = $this->doValidate($columns);
        if ($res === true) {
            $this->validationFailures = array();

            return true;
        }

        $this->validationFailures = $res;

        return false;
    }

    /**
     * This function performs the validation work for complex object models.
     *
     * In addition to checking the current object, all related objects will
     * also be validated.  If all pass then <code>true</code> is returned; otherwise
     * an aggreagated array of ValidationFailed objects will be returned.
     *
     * @param array $columns Array of column names to validate.
     * @return mixed <code>true</code> if all validations pass; array of <code>ValidationFailed</code> objets otherwise.
     */
    protected function doValidate($columns = null)
    {
        if (!$this->alreadyInValidation) {
            $this->alreadyInValidation = true;
            $retval = null;

            $failureMap = array();


            if (($retval = FilePeer::doValidate($this, $columns)) !== true) {
                $failureMap = array_merge($failureMap, $retval);
            }


                if ($this->collFileReferencess !== null) {
                    foreach ($this->collFileReferencess as $referrerFK) {
                        if (!$referrerFK->validate($columns)) {
                            $failureMap = array_merge($failureMap, $referrerFK->getValidationFailures());
                        }
                    }
                }

                if ($this->collCourseMaterials !== null) {
                    foreach ($this->collCourseMaterials as $referrerFK) {
                        if (!$referrerFK->validate($columns)) {
                            $failureMap = array_merge($failureMap, $referrerFK->getValidationFailures());
                        }
                    }
                }

                if ($this->collUsers !== null) {
                    foreach ($this->collUsers as $referrerFK) {
                        if (!$referrerFK->validate($columns)) {
                            $failureMap = array_merge($failureMap, $referrerFK->getValidationFailures());
                        }
                    }
                }


            $this->alreadyInValidation = false;
        }

        return (!empty($failureMap) ? $failureMap : true);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param string $name name
     * @param string $type The type of fieldname the $name is of:
     *               one of the class type constants BasePeer::TYPE_PHPNAME, BasePeer::TYPE_STUDLYPHPNAME
     *               BasePeer::TYPE_COLNAME, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_NUM.
     *               Defaults to BasePeer::TYPE_PHPNAME
     * @return mixed Value of field.
     */
    public function getByName($name, $type = BasePeer::TYPE_PHPNAME)
    {
        $pos = FilePeer::translateFieldName($name, $type, BasePeer::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getId();
                break;
            case 1:
                return $this->getName();
                break;
            case 2:
                return $this->getDescription();
                break;
            case 3:
                return $this->getSize();
                break;
            case 4:
                return $this->getMimeType();
                break;
            case 5:
                return $this->getStorage();
                break;
            case 6:
                return $this->getCreatedAt();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants BasePeer::TYPE_PHPNAME, BasePeer::TYPE_STUDLYPHPNAME,
     *                    BasePeer::TYPE_COLNAME, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_NUM.
     *                    Defaults to BasePeer::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to true.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = BasePeer::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        if (isset($alreadyDumpedObjects['File'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['File'][$this->getPrimaryKey()] = true;
        $keys = FilePeer::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getName(),
            $keys[2] => $this->getDescription(),
            $keys[3] => $this->getSize(),
            $keys[4] => $this->getMimeType(),
            $keys[5] => $this->getStorage(),
            $keys[6] => $this->getCreatedAt(),
        );
        if ($includeForeignObjects) {
            if (null !== $this->collFileReferencess) {
                $result['FileReferencess'] = $this->collFileReferencess->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collCourseMaterials) {
                $result['CourseMaterials'] = $this->collCourseMaterials->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collUsers) {
                $result['Users'] = $this->collUsers->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param string $name peer name
     * @param mixed $value field value
     * @param string $type The type of fieldname the $name is of:
     *                     one of the class type constants BasePeer::TYPE_PHPNAME, BasePeer::TYPE_STUDLYPHPNAME
     *                     BasePeer::TYPE_COLNAME, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_NUM.
     *                     Defaults to BasePeer::TYPE_PHPNAME
     * @return void
     */
    public function setByName($name, $value, $type = BasePeer::TYPE_PHPNAME)
    {
        $pos = FilePeer::translateFieldName($name, $type, BasePeer::TYPE_NUM);

        $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param int $pos position in xml schema
     * @param mixed $value field value
     * @return void
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setName($value);
                break;
            case 2:
                $this->setDescription($value);
                break;
            case 3:
                $this->setSize($value);
                break;
            case 4:
                $this->setMimeType($value);
                break;
            case 5:
                $this->setStorage($value);
                break;
            case 6:
                $this->setCreatedAt($value);
                break;
        } // switch()
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants BasePeer::TYPE_PHPNAME, BasePeer::TYPE_STUDLYPHPNAME,
     * BasePeer::TYPE_COLNAME, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_NUM.
     * The default key type is the column's BasePeer::TYPE_PHPNAME
     *
     * @param array  $arr     An array to populate the object from.
     * @param string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = BasePeer::TYPE_PHPNAME)
    {
        $keys = FilePeer::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setName($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setDescription($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setSize($arr[$keys[3]]);
        if (array_key_exists($keys[4], $arr)) $this->setMimeType($arr[$keys[4]]);
        if (array_key_exists($keys[5], $arr)) $this->setStorage($arr[$keys[5]]);
        if (array_key_exists($keys[6], $arr)) $this->setCreatedAt($arr[$keys[6]]);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(FilePeer::DATABASE_NAME);

        if ($this->isColumnModified(FilePeer::ID)) $criteria->add(FilePeer::ID, $this->id);
        if ($this->isColumnModified(FilePeer::NAME)) $criteria->add(FilePeer::NAME, $this->name);
        if ($this->isColumnModified(FilePeer::DESCRIPTION)) $criteria->add(FilePeer::DESCRIPTION, $this->description);
        if ($this->isColumnModified(FilePeer::SIZE)) $criteria->add(FilePeer::SIZE, $this->size);
        if ($this->isColumnModified(FilePeer::MIME_TYPE)) $criteria->add(FilePeer::MIME_TYPE, $this->mime_type);
        if ($this->isColumnModified(FilePeer::STORAGE)) $criteria->add(FilePeer::STORAGE, $this->storage);
        if ($this->isColumnModified(FilePeer::CREATED_AT)) $criteria->add(FilePeer::CREATED_AT, $this->created_at);

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = new Criteria(FilePeer::DATABASE_NAME);
        $criteria->add(FilePeer::ID, $this->id);

        return $criteria;
    }

    /**
     * Returns the primary key for this object (row).
     * @return int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param  int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {

        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param object $copyObj An object of File (or compatible) type.
     * @param boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setName($this->getName());
        $copyObj->setDescription($this->getDescription());
        $copyObj->setSize($this->getSize());
        $copyObj->setMimeType($this->getMimeType());
        $copyObj->setStorage($this->getStorage());
        $copyObj->setCreatedAt($this->getCreatedAt());

        if ($deepCopy && !$this->startCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);
            // store object hash to prevent cycle
            $this->startCopy = true;

            foreach ($this->getFileReferencess() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addFileReferences($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getCourseMaterials() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addCourseMaterial($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getUsers() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUser($relObj->copy($deepCopy));
                }
            }

            //unflag object copy
            $this->startCopy = false;
        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return File Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }

    /**
     * Returns a peer instance associated with this om.
     *
     * Since Peer classes are not to have any instance attributes, this method returns the
     * same instance for all member of this class. The method could therefore
     * be static, but this would prevent one from overriding the behavior.
     *
     * @return FilePeer
     */
    public function getPeer()
    {
        if (self::$peer === null) {
            self::$peer = new FilePeer();
        }

        return self::$peer;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('FileReferences' == $relationName) {
            $this->initFileReferencess();
        }
        if ('CourseMaterial' == $relationName) {
            $this->initCourseMaterials();
        }
        if ('User' == $relationName) {
            $this->initUsers();
        }
    }

    /**
     * Clears out the collFileReferencess collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return File The current object (for fluent API support)
     * @see        addFileReferencess()
     */
    public function clearFileReferencess()
    {
        $this->collFileReferencess = null; // important to set this to null since that means it is uninitialized
        $this->collFileReferencessPartial = null;

        return $this;
    }

    /**
     * reset is the collFileReferencess collection loaded partially
     *
     * @return void
     */
    public function resetPartialFileReferencess($v = true)
    {
        $this->collFileReferencessPartial = $v;
    }

    /**
     * Initializes the collFileReferencess collection.
     *
     * By default this just sets the collFileReferencess collection to an empty array (like clearcollFileReferencess());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initFileReferencess($overrideExisting = true)
    {
        if (null !== $this->collFileReferencess && !$overrideExisting) {
            return;
        }
        $this->collFileReferencess = new PropelObjectCollection();
        $this->collFileReferencess->setModel('FileReferences');
    }

    /**
     * Gets an array of FileReferences objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this File is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param Criteria $criteria optional Criteria object to narrow the query
     * @param PropelPDO $con optional connection object
     * @return PropelObjectCollection|FileReferences[] List of FileReferences objects
     * @throws PropelException
     */
    public function getFileReferencess($criteria = null, PropelPDO $con = null)
    {
        $partial = $this->collFileReferencessPartial && !$this->isNew();
        if (null === $this->collFileReferencess || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collFileReferencess) {
                // return empty collection
                $this->initFileReferencess();
            } else {
                $collFileReferencess = FileReferencesQuery::create(null, $criteria)
                    ->filterByFile($this)
                    ->find($con);
                if (null !== $criteria) {
                    if (false !== $this->collFileReferencessPartial && count($collFileReferencess)) {
                      $this->initFileReferencess(false);

                      foreach($collFileReferencess as $obj) {
                        if (false == $this->collFileReferencess->contains($obj)) {
                          $this->collFileReferencess->append($obj);
                        }
                      }

                      $this->collFileReferencessPartial = true;
                    }

                    return $collFileReferencess;
                }

                if($partial && $this->collFileReferencess) {
                    foreach($this->collFileReferencess as $obj) {
                        if($obj->isNew()) {
                            $collFileReferencess[] = $obj;
                        }
                    }
                }

                $this->collFileReferencess = $collFileReferencess;
                $this->collFileReferencessPartial = false;
            }
        }

        return $this->collFileReferencess;
    }

    /**
     * Sets a collection of FileReferences objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param PropelCollection $fileReferencess A Propel collection.
     * @param PropelPDO $con Optional connection object
     * @return File The current object (for fluent API support)
     */
    public function setFileReferencess(PropelCollection $fileReferencess, PropelPDO $con = null)
    {
        $fileReferencessToDelete = $this->getFileReferencess(new Criteria(), $con)->diff($fileReferencess);

        $this->fileReferencessScheduledForDeletion = unserialize(serialize($fileReferencessToDelete));

        foreach ($fileReferencessToDelete as $fileReferencesRemoved) {
            $fileReferencesRemoved->setFile(null);
        }

        $this->collFileReferencess = null;
        foreach ($fileReferencess as $fileReferences) {
            $this->addFileReferences($fileReferences);
        }

        $this->collFileReferencess = $fileReferencess;
        $this->collFileReferencessPartial = false;

        return $this;
    }

    /**
     * Returns the number of related FileReferences objects.
     *
     * @param Criteria $criteria
     * @param boolean $distinct
     * @param PropelPDO $con
     * @return int             Count of related FileReferences objects.
     * @throws PropelException
     */
    public function countFileReferencess(Criteria $criteria = null, $distinct = false, PropelPDO $con = null)
    {
        $partial = $this->collFileReferencessPartial && !$this->isNew();
        if (null === $this->collFileReferencess || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collFileReferencess) {
                return 0;
            }

            if($partial && !$criteria) {
                return count($this->getFileReferencess());
            }
            $query = FileReferencesQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByFile($this)
                ->count($con);
        }

        return count($this->collFileReferencess);
    }

    /**
     * Method called to associate a FileReferences object to this object
     * through the FileReferences foreign key attribute.
     *
     * @param    FileReferences $l FileReferences
     * @return File The current object (for fluent API support)
     */
    public function addFileReferences(FileReferences $l)
    {
        if ($this->collFileReferencess === null) {
            $this->initFileReferencess();
            $this->collFileReferencessPartial = true;
        }
        if (!in_array($l, $this->collFileReferencess->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddFileReferences($l);
        }

        return $this;
    }

    /**
     * @param	FileReferences $fileReferences The fileReferences object to add.
     */
    protected function doAddFileReferences($fileReferences)
    {
        $this->collFileReferencess[]= $fileReferences;
        $fileReferences->setFile($this);
    }

    /**
     * @param	FileReferences $fileReferences The fileReferences object to remove.
     * @return File The current object (for fluent API support)
     */
    public function removeFileReferences($fileReferences)
    {
        if ($this->getFileReferencess()->contains($fileReferences)) {
            $this->collFileReferencess->remove($this->collFileReferencess->search($fileReferences));
            if (null === $this->fileReferencessScheduledForDeletion) {
                $this->fileReferencessScheduledForDeletion = clone $this->collFileReferencess;
                $this->fileReferencessScheduledForDeletion->clear();
            }
            $this->fileReferencessScheduledForDeletion[]= clone $fileReferences;
            $fileReferences->setFile(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this File is new, it will return
     * an empty collection; or if this File has previously
     * been saved, it will retrieve related FileReferencess from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in File.
     *
     * @param Criteria $criteria optional Criteria object to narrow the query
     * @param PropelPDO $con optional connection object
     * @param string $join_behavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return PropelObjectCollection|FileReferences[] List of FileReferences objects
     */
    public function getFileReferencessJoinassignmentReferenceId($criteria = null, $con = null, $join_behavior = Criteria::LEFT_JOIN)
    {
        $query = FileReferencesQuery::create(null, $criteria);
        $query->joinWith('assignmentReferenceId', $join_behavior);

        return $this->getFileReferencess($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this File is new, it will return
     * an empty collection; or if this File has previously
     * been saved, it will retrieve related FileReferencess from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in File.
     *
     * @param Criteria $criteria optional Criteria object to narrow the query
     * @param PropelPDO $con optional connection object
     * @param string $join_behavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return PropelObjectCollection|FileReferences[] List of FileReferences objects
     */
    public function getFileReferencessJoinstudentAssignmentReferenceId($criteria = null, $con = null, $join_behavior = Criteria::LEFT_JOIN)
    {
        $query = FileReferencesQuery::create(null, $criteria);
        $query->joinWith('studentAssignmentReferenceId', $join_behavior);

        return $this->getFileReferencess($query, $con);
    }

    /**
     * Clears out the collCourseMaterials collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return File The current object (for fluent API support)
     * @see        addCourseMaterials()
     */
    public function clearCourseMaterials()
    {
        $this->collCourseMaterials = null; // important to set this to null since that means it is uninitialized
        $this->collCourseMaterialsPartial = null;

        return $this;
    }

    /**
     * reset is the collCourseMaterials collection loaded partially
     *
     * @return void
     */
    public function resetPartialCourseMaterials($v = true)
    {
        $this->collCourseMaterialsPartial = $v;
    }

    /**
     * Initializes the collCourseMaterials collection.
     *
     * By default this just sets the collCourseMaterials collection to an empty array (like clearcollCourseMaterials());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initCourseMaterials($overrideExisting = true)
    {
        if (null !== $this->collCourseMaterials && !$overrideExisting) {
            return;
        }
        $this->collCourseMaterials = new PropelObjectCollection();
        $this->collCourseMaterials->setModel('CourseMaterial');
    }

    /**
     * Gets an array of CourseMaterial objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this File is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param Criteria $criteria optional Criteria object to narrow the query
     * @param PropelPDO $con optional connection object
     * @return PropelObjectCollection|CourseMaterial[] List of CourseMaterial objects
     * @throws PropelException
     */
    public function getCourseMaterials($criteria = null, PropelPDO $con = null)
    {
        $partial = $this->collCourseMaterialsPartial && !$this->isNew();
        if (null === $this->collCourseMaterials || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collCourseMaterials) {
                // return empty collection
                $this->initCourseMaterials();
            } else {
                $collCourseMaterials = CourseMaterialQuery::create(null, $criteria)
                    ->filterByFile($this)
                    ->find($con);
                if (null !== $criteria) {
                    if (false !== $this->collCourseMaterialsPartial && count($collCourseMaterials)) {
                      $this->initCourseMaterials(false);

                      foreach($collCourseMaterials as $obj) {
                        if (false == $this->collCourseMaterials->contains($obj)) {
                          $this->collCourseMaterials->append($obj);
                        }
                      }

                      $this->collCourseMaterialsPartial = true;
                    }

                    return $collCourseMaterials;
                }

                if($partial && $this->collCourseMaterials) {
                    foreach($this->collCourseMaterials as $obj) {
                        if($obj->isNew()) {
                            $collCourseMaterials[] = $obj;
                        }
                    }
                }

                $this->collCourseMaterials = $collCourseMaterials;
                $this->collCourseMaterialsPartial = false;
            }
        }

        return $this->collCourseMaterials;
    }

    /**
     * Sets a collection of CourseMaterial objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param PropelCollection $courseMaterials A Propel collection.
     * @param PropelPDO $con Optional connection object
     * @return File The current object (for fluent API support)
     */
    public function setCourseMaterials(PropelCollection $courseMaterials, PropelPDO $con = null)
    {
        $courseMaterialsToDelete = $this->getCourseMaterials(new Criteria(), $con)->diff($courseMaterials);

        $this->courseMaterialsScheduledForDeletion = unserialize(serialize($courseMaterialsToDelete));

        foreach ($courseMaterialsToDelete as $courseMaterialRemoved) {
            $courseMaterialRemoved->setFile(null);
        }

        $this->collCourseMaterials = null;
        foreach ($courseMaterials as $courseMaterial) {
            $this->addCourseMaterial($courseMaterial);
        }

        $this->collCourseMaterials = $courseMaterials;
        $this->collCourseMaterialsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related CourseMaterial objects.
     *
     * @param Criteria $criteria
     * @param boolean $distinct
     * @param PropelPDO $con
     * @return int             Count of related CourseMaterial objects.
     * @throws PropelException
     */
    public function countCourseMaterials(Criteria $criteria = null, $distinct = false, PropelPDO $con = null)
    {
        $partial = $this->collCourseMaterialsPartial && !$this->isNew();
        if (null === $this->collCourseMaterials || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collCourseMaterials) {
                return 0;
            }

            if($partial && !$criteria) {
                return count($this->getCourseMaterials());
            }
            $query = CourseMaterialQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByFile($this)
                ->count($con);
        }

        return count($this->collCourseMaterials);
    }

    /**
     * Method called to associate a CourseMaterial object to this object
     * through the CourseMaterial foreign key attribute.
     *
     * @param    CourseMaterial $l CourseMaterial
     * @return File The current object (for fluent API support)
     */
    public function addCourseMaterial(CourseMaterial $l)
    {
        if ($this->collCourseMaterials === null) {
            $this->initCourseMaterials();
            $this->collCourseMaterialsPartial = true;
        }
        if (!in_array($l, $this->collCourseMaterials->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddCourseMaterial($l);
        }

        return $this;
    }

    /**
     * @param	CourseMaterial $courseMaterial The courseMaterial object to add.
     */
    protected function doAddCourseMaterial($courseMaterial)
    {
        $this->collCourseMaterials[]= $courseMaterial;
        $courseMaterial->setFile($this);
    }

    /**
     * @param	CourseMaterial $courseMaterial The courseMaterial object to remove.
     * @return File The current object (for fluent API support)
     */
    public function removeCourseMaterial($courseMaterial)
    {
        if ($this->getCourseMaterials()->contains($courseMaterial)) {
            $this->collCourseMaterials->remove($this->collCourseMaterials->search($courseMaterial));
            if (null === $this->courseMaterialsScheduledForDeletion) {
                $this->courseMaterialsScheduledForDeletion = clone $this->collCourseMaterials;
                $this->courseMaterialsScheduledForDeletion->clear();
            }
            $this->courseMaterialsScheduledForDeletion[]= clone $courseMaterial;
            $courseMaterial->setFile(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this File is new, it will return
     * an empty collection; or if this File has previously
     * been saved, it will retrieve related CourseMaterials from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in File.
     *
     * @param Criteria $criteria optional Criteria object to narrow the query
     * @param PropelPDO $con optional connection object
     * @param string $join_behavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return PropelObjectCollection|CourseMaterial[] List of CourseMaterial objects
     */
    public function getCourseMaterialsJoinTeacher($criteria = null, $con = null, $join_behavior = Criteria::LEFT_JOIN)
    {
        $query = CourseMaterialQuery::create(null, $criteria);
        $query->joinWith('Teacher', $join_behavior);

        return $this->getCourseMaterials($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this File is new, it will return
     * an empty collection; or if this File has previously
     * been saved, it will retrieve related CourseMaterials from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in File.
     *
     * @param Criteria $criteria optional Criteria object to narrow the query
     * @param PropelPDO $con optional connection object
     * @param string $join_behavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return PropelObjectCollection|CourseMaterial[] List of CourseMaterial objects
     */
    public function getCourseMaterialsJoinCourse($criteria = null, $con = null, $join_behavior = Criteria::LEFT_JOIN)
    {
        $query = CourseMaterialQuery::create(null, $criteria);
        $query->joinWith('Course', $join_behavior);

        return $this->getCourseMaterials($query, $con);
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this File is new, it will return
     * an empty collection; or if this File has previously
     * been saved, it will retrieve related CourseMaterials from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in File.
     *
     * @param Criteria $criteria optional Criteria object to narrow the query
     * @param PropelPDO $con optional connection object
     * @param string $join_behavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return PropelObjectCollection|CourseMaterial[] List of CourseMaterial objects
     */
    public function getCourseMaterialsJoinCourseFolder($criteria = null, $con = null, $join_behavior = Criteria::LEFT_JOIN)
    {
        $query = CourseMaterialQuery::create(null, $criteria);
        $query->joinWith('CourseFolder', $join_behavior);

        return $this->getCourseMaterials($query, $con);
    }

    /**
     * Clears out the collUsers collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return File The current object (for fluent API support)
     * @see        addUsers()
     */
    public function clearUsers()
    {
        $this->collUsers = null; // important to set this to null since that means it is uninitialized
        $this->collUsersPartial = null;

        return $this;
    }

    /**
     * reset is the collUsers collection loaded partially
     *
     * @return void
     */
    public function resetPartialUsers($v = true)
    {
        $this->collUsersPartial = $v;
    }

    /**
     * Initializes the collUsers collection.
     *
     * By default this just sets the collUsers collection to an empty array (like clearcollUsers());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initUsers($overrideExisting = true)
    {
        if (null !== $this->collUsers && !$overrideExisting) {
            return;
        }
        $this->collUsers = new PropelObjectCollection();
        $this->collUsers->setModel('User');
    }

    /**
     * Gets an array of User objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this File is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param Criteria $criteria optional Criteria object to narrow the query
     * @param PropelPDO $con optional connection object
     * @return PropelObjectCollection|User[] List of User objects
     * @throws PropelException
     */
    public function getUsers($criteria = null, PropelPDO $con = null)
    {
        $partial = $this->collUsersPartial && !$this->isNew();
        if (null === $this->collUsers || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collUsers) {
                // return empty collection
                $this->initUsers();
            } else {
                $collUsers = UserQuery::create(null, $criteria)
                    ->filterByAvatar($this)
                    ->find($con);
                if (null !== $criteria) {
                    if (false !== $this->collUsersPartial && count($collUsers)) {
                      $this->initUsers(false);

                      foreach($collUsers as $obj) {
                        if (false == $this->collUsers->contains($obj)) {
                          $this->collUsers->append($obj);
                        }
                      }

                      $this->collUsersPartial = true;
                    }

                    return $collUsers;
                }

                if($partial && $this->collUsers) {
                    foreach($this->collUsers as $obj) {
                        if($obj->isNew()) {
                            $collUsers[] = $obj;
                        }
                    }
                }

                $this->collUsers = $collUsers;
                $this->collUsersPartial = false;
            }
        }

        return $this->collUsers;
    }

    /**
     * Sets a collection of User objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param PropelCollection $users A Propel collection.
     * @param PropelPDO $con Optional connection object
     * @return File The current object (for fluent API support)
     */
    public function setUsers(PropelCollection $users, PropelPDO $con = null)
    {
        $usersToDelete = $this->getUsers(new Criteria(), $con)->diff($users);

        $this->usersScheduledForDeletion = unserialize(serialize($usersToDelete));

        foreach ($usersToDelete as $userRemoved) {
            $userRemoved->setAvatar(null);
        }

        $this->collUsers = null;
        foreach ($users as $user) {
            $this->addUser($user);
        }

        $this->collUsers = $users;
        $this->collUsersPartial = false;

        return $this;
    }

    /**
     * Returns the number of related User objects.
     *
     * @param Criteria $criteria
     * @param boolean $distinct
     * @param PropelPDO $con
     * @return int             Count of related User objects.
     * @throws PropelException
     */
    public function countUsers(Criteria $criteria = null, $distinct = false, PropelPDO $con = null)
    {
        $partial = $this->collUsersPartial && !$this->isNew();
        if (null === $this->collUsers || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collUsers) {
                return 0;
            }

            if($partial && !$criteria) {
                return count($this->getUsers());
            }
            $query = UserQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByAvatar($this)
                ->count($con);
        }

        return count($this->collUsers);
    }

    /**
     * Method called to associate a User object to this object
     * through the User foreign key attribute.
     *
     * @param    User $l User
     * @return File The current object (for fluent API support)
     */
    public function addUser(User $l)
    {
        if ($this->collUsers === null) {
            $this->initUsers();
            $this->collUsersPartial = true;
        }
        if (!in_array($l, $this->collUsers->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddUser($l);
        }

        return $this;
    }

    /**
     * @param	User $user The user object to add.
     */
    protected function doAddUser($user)
    {
        $this->collUsers[]= $user;
        $user->setAvatar($this);
    }

    /**
     * @param	User $user The user object to remove.
     * @return File The current object (for fluent API support)
     */
    public function removeUser($user)
    {
        if ($this->getUsers()->contains($user)) {
            $this->collUsers->remove($this->collUsers->search($user));
            if (null === $this->usersScheduledForDeletion) {
                $this->usersScheduledForDeletion = clone $this->collUsers;
                $this->usersScheduledForDeletion->clear();
            }
            $this->usersScheduledForDeletion[]= $user;
            $user->setAvatar(null);
        }

        return $this;
    }

    /**
     * Clears out the collassignmentReferenceIds collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return File The current object (for fluent API support)
     * @see        addassignmentReferenceIds()
     */
    public function clearassignmentReferenceIds()
    {
        $this->collassignmentReferenceIds = null; // important to set this to null since that means it is uninitialized
        $this->collassignmentReferenceIdsPartial = null;

        return $this;
    }

    /**
     * Initializes the collassignmentReferenceIds collection.
     *
     * By default this just sets the collassignmentReferenceIds collection to an empty collection (like clearassignmentReferenceIds());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initassignmentReferenceIds()
    {
        $this->collassignmentReferenceIds = new PropelObjectCollection();
        $this->collassignmentReferenceIds->setModel('Assignment');
    }

    /**
     * Gets a collection of Assignment objects related by a many-to-many relationship
     * to the current object by way of the file_references cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this File is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param Criteria $criteria Optional query object to filter the query
     * @param PropelPDO $con Optional connection object
     *
     * @return PropelObjectCollection|Assignment[] List of Assignment objects
     */
    public function getassignmentReferenceIds($criteria = null, PropelPDO $con = null)
    {
        if (null === $this->collassignmentReferenceIds || null !== $criteria) {
            if ($this->isNew() && null === $this->collassignmentReferenceIds) {
                // return empty collection
                $this->initassignmentReferenceIds();
            } else {
                $collassignmentReferenceIds = AssignmentQuery::create(null, $criteria)
                    ->filterByFile($this)
                    ->find($con);
                if (null !== $criteria) {
                    return $collassignmentReferenceIds;
                }
                $this->collassignmentReferenceIds = $collassignmentReferenceIds;
            }
        }

        return $this->collassignmentReferenceIds;
    }

    /**
     * Sets a collection of Assignment objects related by a many-to-many relationship
     * to the current object by way of the file_references cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param PropelCollection $assignmentReferenceIds A Propel collection.
     * @param PropelPDO $con Optional connection object
     * @return File The current object (for fluent API support)
     */
    public function setassignmentReferenceIds(PropelCollection $assignmentReferenceIds, PropelPDO $con = null)
    {
        $this->clearassignmentReferenceIds();
        $currentassignmentReferenceIds = $this->getassignmentReferenceIds();

        $this->assignmentReferenceIdsScheduledForDeletion = $currentassignmentReferenceIds->diff($assignmentReferenceIds);

        foreach ($assignmentReferenceIds as $assignmentReferenceId) {
            if (!$currentassignmentReferenceIds->contains($assignmentReferenceId)) {
                $this->doAddassignmentReferenceId($assignmentReferenceId);
            }
        }

        $this->collassignmentReferenceIds = $assignmentReferenceIds;

        return $this;
    }

    /**
     * Gets the number of Assignment objects related by a many-to-many relationship
     * to the current object by way of the file_references cross-reference table.
     *
     * @param Criteria $criteria Optional query object to filter the query
     * @param boolean $distinct Set to true to force count distinct
     * @param PropelPDO $con Optional connection object
     *
     * @return int the number of related Assignment objects
     */
    public function countassignmentReferenceIds($criteria = null, $distinct = false, PropelPDO $con = null)
    {
        if (null === $this->collassignmentReferenceIds || null !== $criteria) {
            if ($this->isNew() && null === $this->collassignmentReferenceIds) {
                return 0;
            } else {
                $query = AssignmentQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByFile($this)
                    ->count($con);
            }
        } else {
            return count($this->collassignmentReferenceIds);
        }
    }

    /**
     * Associate a Assignment object to this object
     * through the file_references cross reference table.
     *
     * @param  Assignment $assignment The FileReferences object to relate
     * @return File The current object (for fluent API support)
     */
    public function addassignmentReferenceId(Assignment $assignment)
    {
        if ($this->collassignmentReferenceIds === null) {
            $this->initassignmentReferenceIds();
        }
        if (!$this->collassignmentReferenceIds->contains($assignment)) { // only add it if the **same** object is not already associated
            $this->doAddassignmentReferenceId($assignment);

            $this->collassignmentReferenceIds[]= $assignment;
        }

        return $this;
    }

    /**
     * @param	assignmentReferenceId $assignmentReferenceId The assignmentReferenceId object to add.
     */
    protected function doAddassignmentReferenceId($assignmentReferenceId)
    {
        $fileReferences = new FileReferences();
        $fileReferences->setassignmentReferenceId($assignmentReferenceId);
        $this->addFileReferences($fileReferences);
    }

    /**
     * Remove a Assignment object to this object
     * through the file_references cross reference table.
     *
     * @param Assignment $assignment The FileReferences object to relate
     * @return File The current object (for fluent API support)
     */
    public function removeassignmentReferenceId(Assignment $assignment)
    {
        if ($this->getassignmentReferenceIds()->contains($assignment)) {
            $this->collassignmentReferenceIds->remove($this->collassignmentReferenceIds->search($assignment));
            if (null === $this->assignmentReferenceIdsScheduledForDeletion) {
                $this->assignmentReferenceIdsScheduledForDeletion = clone $this->collassignmentReferenceIds;
                $this->assignmentReferenceIdsScheduledForDeletion->clear();
            }
            $this->assignmentReferenceIdsScheduledForDeletion[]= $assignment;
        }

        return $this;
    }

    /**
     * Clears out the collstudentAssignmentReferenceIds collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return File The current object (for fluent API support)
     * @see        addstudentAssignmentReferenceIds()
     */
    public function clearstudentAssignmentReferenceIds()
    {
        $this->collstudentAssignmentReferenceIds = null; // important to set this to null since that means it is uninitialized
        $this->collstudentAssignmentReferenceIdsPartial = null;

        return $this;
    }

    /**
     * Initializes the collstudentAssignmentReferenceIds collection.
     *
     * By default this just sets the collstudentAssignmentReferenceIds collection to an empty collection (like clearstudentAssignmentReferenceIds());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initstudentAssignmentReferenceIds()
    {
        $this->collstudentAssignmentReferenceIds = new PropelObjectCollection();
        $this->collstudentAssignmentReferenceIds->setModel('StudentAssignment');
    }

    /**
     * Gets a collection of StudentAssignment objects related by a many-to-many relationship
     * to the current object by way of the file_references cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this File is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param Criteria $criteria Optional query object to filter the query
     * @param PropelPDO $con Optional connection object
     *
     * @return PropelObjectCollection|StudentAssignment[] List of StudentAssignment objects
     */
    public function getstudentAssignmentReferenceIds($criteria = null, PropelPDO $con = null)
    {
        if (null === $this->collstudentAssignmentReferenceIds || null !== $criteria) {
            if ($this->isNew() && null === $this->collstudentAssignmentReferenceIds) {
                // return empty collection
                $this->initstudentAssignmentReferenceIds();
            } else {
                $collstudentAssignmentReferenceIds = StudentAssignmentQuery::create(null, $criteria)
                    ->filterByFile($this)
                    ->find($con);
                if (null !== $criteria) {
                    return $collstudentAssignmentReferenceIds;
                }
                $this->collstudentAssignmentReferenceIds = $collstudentAssignmentReferenceIds;
            }
        }

        return $this->collstudentAssignmentReferenceIds;
    }

    /**
     * Sets a collection of StudentAssignment objects related by a many-to-many relationship
     * to the current object by way of the file_references cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param PropelCollection $studentAssignmentReferenceIds A Propel collection.
     * @param PropelPDO $con Optional connection object
     * @return File The current object (for fluent API support)
     */
    public function setstudentAssignmentReferenceIds(PropelCollection $studentAssignmentReferenceIds, PropelPDO $con = null)
    {
        $this->clearstudentAssignmentReferenceIds();
        $currentstudentAssignmentReferenceIds = $this->getstudentAssignmentReferenceIds();

        $this->studentAssignmentReferenceIdsScheduledForDeletion = $currentstudentAssignmentReferenceIds->diff($studentAssignmentReferenceIds);

        foreach ($studentAssignmentReferenceIds as $studentAssignmentReferenceId) {
            if (!$currentstudentAssignmentReferenceIds->contains($studentAssignmentReferenceId)) {
                $this->doAddstudentAssignmentReferenceId($studentAssignmentReferenceId);
            }
        }

        $this->collstudentAssignmentReferenceIds = $studentAssignmentReferenceIds;

        return $this;
    }

    /**
     * Gets the number of StudentAssignment objects related by a many-to-many relationship
     * to the current object by way of the file_references cross-reference table.
     *
     * @param Criteria $criteria Optional query object to filter the query
     * @param boolean $distinct Set to true to force count distinct
     * @param PropelPDO $con Optional connection object
     *
     * @return int the number of related StudentAssignment objects
     */
    public function countstudentAssignmentReferenceIds($criteria = null, $distinct = false, PropelPDO $con = null)
    {
        if (null === $this->collstudentAssignmentReferenceIds || null !== $criteria) {
            if ($this->isNew() && null === $this->collstudentAssignmentReferenceIds) {
                return 0;
            } else {
                $query = StudentAssignmentQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByFile($this)
                    ->count($con);
            }
        } else {
            return count($this->collstudentAssignmentReferenceIds);
        }
    }

    /**
     * Associate a StudentAssignment object to this object
     * through the file_references cross reference table.
     *
     * @param  StudentAssignment $studentAssignment The FileReferences object to relate
     * @return File The current object (for fluent API support)
     */
    public function addstudentAssignmentReferenceId(StudentAssignment $studentAssignment)
    {
        if ($this->collstudentAssignmentReferenceIds === null) {
            $this->initstudentAssignmentReferenceIds();
        }
        if (!$this->collstudentAssignmentReferenceIds->contains($studentAssignment)) { // only add it if the **same** object is not already associated
            $this->doAddstudentAssignmentReferenceId($studentAssignment);

            $this->collstudentAssignmentReferenceIds[]= $studentAssignment;
        }

        return $this;
    }

    /**
     * @param	studentAssignmentReferenceId $studentAssignmentReferenceId The studentAssignmentReferenceId object to add.
     */
    protected function doAddstudentAssignmentReferenceId($studentAssignmentReferenceId)
    {
        $fileReferences = new FileReferences();
        $fileReferences->setstudentAssignmentReferenceId($studentAssignmentReferenceId);
        $this->addFileReferences($fileReferences);
    }

    /**
     * Remove a StudentAssignment object to this object
     * through the file_references cross reference table.
     *
     * @param StudentAssignment $studentAssignment The FileReferences object to relate
     * @return File The current object (for fluent API support)
     */
    public function removestudentAssignmentReferenceId(StudentAssignment $studentAssignment)
    {
        if ($this->getstudentAssignmentReferenceIds()->contains($studentAssignment)) {
            $this->collstudentAssignmentReferenceIds->remove($this->collstudentAssignmentReferenceIds->search($studentAssignment));
            if (null === $this->studentAssignmentReferenceIdsScheduledForDeletion) {
                $this->studentAssignmentReferenceIdsScheduledForDeletion = clone $this->collstudentAssignmentReferenceIds;
                $this->studentAssignmentReferenceIdsScheduledForDeletion->clear();
            }
            $this->studentAssignmentReferenceIdsScheduledForDeletion[]= $studentAssignment;
        }

        return $this;
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {
        $this->id = null;
        $this->name = null;
        $this->description = null;
        $this->size = null;
        $this->mime_type = null;
        $this->storage = null;
        $this->created_at = null;
        $this->alreadyInSave = false;
        $this->alreadyInValidation = false;
        $this->clearAllReferences();
        $this->applyDefaultValues();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references to other model objects or collections of model objects.
     *
     * This method is a user-space workaround for PHP's inability to garbage collect
     * objects with circular references (even in PHP 5.3). This is currently necessary
     * when using Propel in certain daemon or large-volumne/high-memory operations.
     *
     * @param boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collFileReferencess) {
                foreach ($this->collFileReferencess as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collCourseMaterials) {
                foreach ($this->collCourseMaterials as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collUsers) {
                foreach ($this->collUsers as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collassignmentReferenceIds) {
                foreach ($this->collassignmentReferenceIds as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collstudentAssignmentReferenceIds) {
                foreach ($this->collstudentAssignmentReferenceIds as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        if ($this->collFileReferencess instanceof PropelCollection) {
            $this->collFileReferencess->clearIterator();
        }
        $this->collFileReferencess = null;
        if ($this->collCourseMaterials instanceof PropelCollection) {
            $this->collCourseMaterials->clearIterator();
        }
        $this->collCourseMaterials = null;
        if ($this->collUsers instanceof PropelCollection) {
            $this->collUsers->clearIterator();
        }
        $this->collUsers = null;
        if ($this->collassignmentReferenceIds instanceof PropelCollection) {
            $this->collassignmentReferenceIds->clearIterator();
        }
        $this->collassignmentReferenceIds = null;
        if ($this->collstudentAssignmentReferenceIds instanceof PropelCollection) {
            $this->collstudentAssignmentReferenceIds->clearIterator();
        }
        $this->collstudentAssignmentReferenceIds = null;
    }

    /**
     * return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(FilePeer::DEFAULT_STRING_FORMAT);
    }

    /**
     * return true is the object is in saving state
     *
     * @return boolean
     */
    public function isAlreadyInSave()
    {
        return $this->alreadyInSave;
    }

}
