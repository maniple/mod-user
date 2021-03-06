<?php

abstract class ManipleUser_Validate_User extends Zend_Validate_Abstract
{
    const MATCH_ID                = 'id';
    const MATCH_EMAIL             = 'email';
    const MATCH_USERNAME          = 'username';
    const MATCH_USERNAME_OR_EMAIL = 'usernameOrEmail';

    const USER_NOT_EXISTS         = 'userNotExists';
    const USER_EXISTS             = 'userExists';

    /**
     * @var ManipleUser_Model_UserMapperInterface
     */
    protected $_userRepository;

    /**
     * @var ManipleUser_Model_UserInterface
     */
    protected $_user;

    /**
     * Value, that if matched will exclude user from query result
     * @var mixed
     */
    protected $_exclude;

    /**
     * @var string
     */
    protected $_matchBy = self::MATCH_ID;

    protected $_messageTemplates = array(
        self::USER_NOT_EXISTS => 'No matching user was found',
        self::USER_EXISTS     => 'A matching user was found',
    );

    protected $_messageVariables = array(
        'user' => '_user',
    );

    /**
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        if ($options) {
            foreach ($options as $key => $value) {
                $method = 'set' . $key;
                if (method_exists($this, $method)) {
                    $this->{$method}($value);
                }
            }
        }
    }

    /**
     * @param  ManipleUser_Model_UserMapperInterface $userRepository
     * @return $this
     */
    public function setUserRepository(ManipleUser_Model_UserMapperInterface $userRepository)
    {
        $this->_userRepository = $userRepository;
        return $this;
    }

    /**
     * @return ManipleUser_Model_UserMapperInterface
     * @throws Exception
     */
    public function getUserRepository()
    {
        if (empty($this->_userRepository)) {
            throw new Exception('User repository is not configured');
        }
        return $this->_userRepository;
    }

    /**
     * @param  string $matchBy
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setMatchBy($matchBy)
    {
        $matchBy = (string) $matchBy;

        switch ($matchBy) {
            case self::MATCH_ID:
            case self::MATCH_EMAIL:
            case self::MATCH_USERNAME:
            case self::MATCH_USERNAME_OR_EMAIL:
                $this->_matchBy = $matchBy;
                break;

            default:
                throw new InvalidArgumentException(sprintf(
                    "Unsupported matchBy option value: '%s'", $matchBy
                ));
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getMatchBy()
    {
        return $this->_matchBy;
    }

    /**
     * Sets a new exclude value
     *
     * @param mixed $exclude
     * @return $this
     */
    public function setExclude($exclude)
    {
        $this->_exclude = $exclude;
        return $this;
    }

    /**
     * Retrieves user from repository matched by given value interpreted
     * according to current matchBy setting.
     *
     * @param  mixed $value
     * @return ManipleUser_Model_UserInterface
     * @throws RuntimeException
     */
    protected function _getUserByValue($value)
    {
	    $user = null;

        switch ($this->_matchBy) {
            case self::MATCH_ID:
                $user = $this->getUserRepository()->getUser($value);
                break;

            case self::MATCH_EMAIL:
                $user = $this->getUserRepository()->getUserByEmail($value);
                break;

            case self::MATCH_USERNAME:
                $user = $this->getUserRepository()->getUserByUsername($value);
                break;

            case self::MATCH_USERNAME_OR_EMAIL:
                $user = $this->getUserRepository()->getUserByUsernameOrEmail($value);
                break;

            default:
                throw new RuntimeException(sprintf(
                    "Unsupported matchBy option value: '%s'", $this->_matchBy
                ));
        }

        if ($user && $this->_exclude && $this->_matchesExclude($user)) {
            return null;
        }

        return $user;
    }

    /**
     * @param ManipleUser_Model_UserInterface|null $user
     * @return bool
     */
    protected function _matchesExclude(ManipleUser_Model_UserInterface $user)
    {
        switch ($this->_matchBy) {
            case self::MATCH_ID:
                return $this->_exclude === $user->getId();

            case self::MATCH_EMAIL:
                return $this->_exclude === $user->getEmail();
                break;

            case self::MATCH_USERNAME:
                return $this->_exclude === $user->getUsername();
                break;

            case self::MATCH_USERNAME_OR_EMAIL:
                return $this->_exclude === $user->getUsername() || $this->_exclude === $user->getEmail();
                break;
        }

        return false;
    }
}
