<?php

class ManipleUser_Signup_SignupManager
{
    const className = __CLASS__;

    /**
     * @Inject
     * @var Zefram_Db
     */
    protected $_db;

    /**
     * @Inject
     * @var ManipleCore_Settings_SettingsManager
     */
    protected $_settingsManager;

    /**
     * @Inject('user.model.userMapper')
     * @var ManipleUser_Model_UserMapper
     */
    protected $_userRepository;

    /**
     * @Inject('ManipleUser.UserSettings')
     * @var ManipleUser_UserSettings_Service
     */
    protected $_userSettingsManager;

    /**
     * @Inject('Config')
     */
    protected $_config;

    /**
     * @var Zend_EventManager_EventManager
     */
    protected $_events;

    public function __construct(Zend_EventManager_SharedEventManager $sharedEventManager)
    {
        $this->_events = new Zend_EventManager_EventManager();
        $this->_events->setIdentifiers(array(
            __CLASS__,
            get_class($this),
            'ManipleUser.SignupManager',
        ));
        $this->_events->setSharedCollections($sharedEventManager);
        $this->_events->trigger('init', $this);
    }

    /**
     * @return Zend_Form
     */
    public function createSignupForm()
    {
        if ($this->_config instanceof Zend_Config) {
            $formClass = isset($this->_config->{'mod_user'}->{'registration'}->{'formClass'})
                ? $this->_config->{'mod_user'}->{'registration'}->{'formClass'}
                : null;
        } else {
            $formClass = isset($this->_config['mod_user']['registration']['formClass'])
                ? $this->_config['mod_user']['registration']['formClass']
                : null;
        }

        if (!$formClass) {
            $formClass = ManipleUser_Form_Registration::className;
        }

        $form = new $formClass($this->_userRepository);
        $this->_events->trigger('createSignupForm', $form);

        return $form;
    }

    /**
     * @param array $data
     * @param string $clientIp OPTIONAL
     * @return Zend_Db_Table_Row_Abstract
     */
    public function createSignupRecord(array $data, $clientIp = null)
    {
        // make sure email is lowercased
        $tolower = new Zend_Filter_StringToLower();
        $email = $tolower->filter($data['email']);

        if (isset($data['username'])) {
            $data['username'] = $tolower->filter($data['username']);
        }

        $data['email'] = $email;
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        $reg = $this->_db->getTable(ManipleUser_Model_DbTable_Registrations::className)->createRow(array(
            'reg_id'     => Zefram_Math_Rand::getString(64, Zefram_Math_Rand::BASE64URL),
            'created_at' => time(),
            'expires_at' => null, // TODO registration.lifetime setting
            'ip_addr'    => $clientIp,
            'email'      => $email,
            'data'       => Zefram_Json::encode($data, array('unescapedSlashes' => true, 'unescapedUnicode' => true)),
            'status'     => 'PENDING',
        ));
        $reg->save();

        return $reg;
    }

    /**
     * Create user account from registration
     *
     * @param string $token
     * @return ManipleUser_Model_UserInterface
     */
    public function createUser($token)
    {
        $reg = $this->_db->getTable(ManipleUser_Model_DbTable_Registrations::className)->fetchRow(array(
            'reg_id = ?' => (string) $token,
            'status = ?' => 'PENDING',
        ));

        if (!$reg) {
            throw new ManipleUser_Signup_Exception_SignupNotFound();
        }

        // TODO check for signup expiration
        $user = $this->_userRepository->getUserByEmail($reg->email);

        if ($user) {
            try {
                $reg->status = 'INVALIDATED';
                $reg->save();
            } catch (Exception $e) {
            }

            throw new ManipleUser_Signup_Exception_UserAlreadyRegistered();
        }

        $data = Zefram_Json::decode($reg->data);

        //$auto_accept_domains = array('fuw.edu.pl');
        //$domain = substr($data['email'], strrpos($data['email'], '@') + 1);
        //$auto_accept = in_array($domain, $auto_accept_domains);

        $reg->confirmed_at = time();

        /*if ($auto_accept) {
            $reg->status = 'ACCEPTED';
        } else {
            $reg->status = 'CONFIRMED';
        }*/

        $reg->status = 'CONFIRMED';
        $reg->save();

        // Invalidate all other PENDING registrations for this user
        $this->_db->getTable(ManipleUser_Model_DbTable_Registrations::className)->update(
            array('status' => 'INVALIDATED'),
            array(
                'email = ?' => (string) $reg->email,
                'status = ?' => 'PENDING',
            )
        );

        $user = $this->_userRepository->createUser();
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();

        if (empty($data['username'])) {
            $data['username'] = $reg->email;
        }

        $data['signup_ip'] = $reg->ip_addr;

        foreach ($data as $key => $value) {
            $method = 'set' . $filter->filter($key);
            if (method_exists($user, $method)) {
                $user->{$method}($value);
                unset($data[$key]);
            }
        }

        $user->setCreatedAt(time());
        $user->setId(null); // enforce auto-generation
        $this->_userRepository->saveUser($user);

        $this->_events->trigger('createUser', $user, array('data' => $data));

        return $user;
    }
}