<?php

class ManipleUser_Form_PasswordReset extends Zefram_Form
{
    protected $_user;

    public function __construct(ManipleUser_Model_UserInterface $user = null)
    {
        $elements = array(
            'password' => array(
                'type' => 'password',
                'options' => array(
                    'required' => true,
                    'label' => 'Password',
                    'validators' => array(
                        array(new Zefram_Validate_NotEqual(array(
                            'useContext' => false,
                            'messages' => array(
                                Zefram_Validate_NotEqual::IS_EQUAL => 'Password must be different from username / email',
                                // 'Hasło musi być różne od nazwy użytkownika / adresu e-mail', // 'Password must be different from username / email',
                            ),
                        )), true),
                        new ManipleUser_Validate_Password(),
                    ),
                ),
            ),
            'password_confirm' => array(
                'type' => 'password',
                'options' => array(
                    'required' => true,
                    'label' => 'Confirm password',
                    'validators' => array(
                        array('Identical', true, array(
                            'token' => 'password',
                            'messages' => array(
                                Zend_Validate_Identical::NOT_SAME => 'Passwords do not match',
                            ),
                        )),
                    ),
                ),
            ),
            '__submit' => array(
                'type' => 'submit',
                'options' => array(
                    'label' => 'Change password',
                ),
            ),
        );

        parent::__construct(compact('elements'));

        if ($user) {
            $this->setUser($user);
        }
    }

    public function setUser(ManipleUser_Model_UserInterface $user)
    {
        $this->_user = $user;
        $this->getElement('password')->getValidator('NotEqual')->setToken($user->getUsername());
        return $this;
    }

    /**
     * @return ManipleUser_Model_UserInterface
     */
    public function getUser()
    {
        return $this->_user;
    }
}
