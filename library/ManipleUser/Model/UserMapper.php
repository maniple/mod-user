<?php

/**
 * User repository
 */
class ManipleUser_Model_UserMapper implements ManipleUser_Model_UserMapperInterface
{
    /**
     * @var string
     */
    protected $_userClass = ManipleUser_Entity_User::className;

    /**
     * @Inject
     * @var Zefram_Db
     */
    protected $_db;

    /**
     * @param  string $userClass
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setUserClass($userClass)
    {
        $userClass = (string) $userClass;

        // can't use is_subclass_of as prior to PHP 5.3.7 it does not
        // check interfaces
        if (!in_array('ManipleUser_Model_UserInterface', class_implements($userClass))) {
            throw new InvalidArgumentException('User class must implement ManipleUser_Model_UserInterface interface');
        }

        $this->_userClass = $userClass;

        return $this;
    }

    /**
     * @param  int $userId
     * @return ManipleUser_Model_UserInterface|null
     */
    public function getUser($userId)
    {
        $userId = (int) $userId;
        $row = $this->_getUsersTable()->findRow($userId);
        if ($row) {
            $user = $this->createUser($row->toArray());
            return $user;
        }
        return null;
    }

    /**
     * @param  string $username
     * @return ManipleUser_Model_UserInterface|null
     */
    public function getUserByUsername($username)
    {
        $username = (string) $username;
        return $this->_getUserBy(array('username = LOWER(?)' => $username));
    }

    /**
     * @param  string $email
     * @return ManipleUser_Model_UserInterface|null
     */
    public function getUserByEmail($email)
    {
        $email = (string) $email;
        return $this->_getUserBy(array('email = LOWER(?)' => $email));
    }

    /**
     * @param  string $usernameOrEmail
     * @return ManipleUser_Model_UserInterface|null
     */
    public function getUserByUsernameOrEmail($usernameOrEmail)
    {
        $usernameOrEmail = (string) $usernameOrEmail;

        // usernames and emails are required to be stored lowercase only
        return $this->_getUserBy(array(
            'username = LOWER(?) OR email = LOWER(?)' => $usernameOrEmail,
        ));
    }

    /**
     * @param  int[] $userIds
     * @return ManipleUser_Model_UserInterface[]
     */
    public function getUsers(array $userIds = null)
    {
        $users = array();

        if ($userIds) {
            $userIds = array_map('intval', $userIds);
            $where = array('user_id IN (?)' => $userIds);
        } else {
            $where = null;
        }

        $rows = $this->_getUsersTable()->fetchAll($where);
        foreach ($rows as $row) {
            $user = $this->createUser($row->toArray());
            $users[$user->getId()] = $user;
        }

        return $users;
    }

    /**
     * Saves user entity to the storage.
     *
     * @param  ManipleUser_Model_UserInterface $user
     * @return ManipleUser_Model_UserInterface
     * @throws Exception
     */
    public function saveUser(ManipleUser_Model_UserInterface $user)
    {
        $userId = $user->getId();

        if ($userId) {
            $row = $this->_getUsersTable()->findRow((int) $userId);
        }

        if (empty($row)) {
            $row = $this->_getUsersTable()->createRow();
            $isCreate = true;
        } else {
            $isCreate = false;
        }

        $data = $this->getAsArray($user);

        if ($isCreate) {
            // disallow explicitly setting value on auto increment column, as
            // in some DBMS write may fail if sequence reaches value that is
            // already present in the table
            $sequence = $row->getTable()->info(Zend_Db_Table_Abstract::SEQUENCE);
            foreach ($row->getPrimaryKey() as $column => $value) {
                if ($sequence === true || $sequence === $column) {
                    unset($data[$column]);
                }
            }
        }

        $row->setFromArray($data);
        $row->save();

        $this->setFromArray($user, $row->toArray());
        return $user;
    }

    /**
     * Creates a new instance of user entity.
     *
     * @param  array $data OPTIONAL
     * @return ManipleUser_Model_UserInterface
     */
    public function createUser(array $data = null)
    {
        $userClass = $this->_userClass;
        $user = new $userClass();
        if ($data) {
            $this->setFromArray($user, $data);
        }
        return $user;
    }

    /**
     * @param string|array|Zend_Db_Expr $where
     * @return ManipleUser_Model_UserInterface|null
     */
    protected function _getUserBy($where)
    {
        $row = $this->_getUsersTable()->fetchRow($where);
        if ($row) {
            $user = $this->createUser($row->toArray());
            return $user;
        }
        return null;
    }

    /**
     * @return ManipleUser_Model_DbTable_Users
     * @internal
     */
    protected function _getUsersTable()
    {
        /** @var ManipleUser_Model_DbTable_Users $usersTable */
        $usersTable = $this->_db->getTable(ManipleUser_Model_DbTable_Users::className);
        return $usersTable;
    }

    /**
     * Set user properties from array.
     *
     * @param  ManipleUser_Model_UserInterface $user
     * @param  array $data
     * @return ManipleUser_Model_UserInterface
     */
    public function setFromArray(ManipleUser_Model_UserInterface $user, array $data)
    {
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();

        foreach ($data as $key => $value) {
            $method = 'set' . $filter->filter($key);
            if (method_exists($user, $method)) {
                $user->{$method}($value);
            }
        }

        if (isset($data['user_id']) || array_key_exists('user_id', $data)) {
            $user->setId($data['user_id']);
        }

        return $user;
    }

    /**
     * Extract user properties to an array indexed by corresponding row
     * column names.
     *
     * @param  ManipleUser_Model_UserInterface $user
     * @return array
     */
    public function getAsArray(ManipleUser_Model_UserInterface $user)
    {
        $filter = new Zend_Filter_Word_CamelCaseToUnderscore();
        $data = array('user_id' => null);

        foreach (get_class_methods($user) as $method) {
            if (!strncasecmp($method, 'get', 3)) {
                $key = strtolower($filter->filter(substr($method, 3)));
                $data[$key] = $user->{$method}();
            }
            if (!strncasecmp($method, 'is', 2)) {
                $key = strtolower($filter->filter(substr($method, 2)));
                $data[$key] = $user->{$method}();
            }
        }

        $data['user_id'] = $user->getId();

        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $data[$key] = $value ? 1 : 0;
            }
        }

        return $data;
    }
}
