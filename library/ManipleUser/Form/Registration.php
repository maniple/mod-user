<?php

class ManipleUser_Form_Registration extends Zefram_Form
{
    const className = __CLASS__;

    public function __construct(ManipleUser_Model_UserMapperInterface $userRepository, array $options = array())
    {
        $elements = array(
            'first_name' => array(
                'type' => 'text',
                'options' => array(
                    'label' => 'First name',
                    'required' => true,
                    'validators' => array(
                        array('StringLength', true, array('max' => 128)),
                    ),
                    'filters' => array(
                        'StringTrim',
                    ),
                ),
            ),
            'last_name' => array(
                'type' => 'text',
                'options' => array(
                    'label' => 'Surname',
                    'required' => true,
                    'validators' => array(
                        array('StringLength', true, array('max' => 128)),
                    ),
                    'filters' => array(
                        'StringTrim',
                    ),
                ),
            ),
            'email' => array(
                'type' => 'text',
                'options' => array(
                    'label' => 'Email address',
                    'required' => true,
                    'validators' => array(
                        array('StringLength', true, array('max' => 128)),
                        array('EmailAddress', true),
                        array(new ManipleUser_Validate_UserNotExists(array(
                            'userRepository' => $userRepository,
                            'matchBy' => ManipleUser_Validate_User::MATCH_EMAIL,
                            'messages' => array(
                                ManipleUser_Validate_User::USER_EXISTS => 'This email address is already in use',
                            ),
                        )), true),
                    ),
                ),
            ),
            'email_verify' => array(
                'type' => 'text',
                'options' => array(
                    'label' => 'Confirm email address',
                    'required' => true,
                    'validators' => array(
                        array('StringLength', true, array('max' => 128)),
                        array('EmailAddress', true),
                        array('Identical',    true, array(
                            'token' => 'email',
                            'messages' => array(
                                Zend_Validate_Identical::NOT_SAME => 'Email address mismatch',
                            ),
                        )),
                    ),
                ),
            ),
            'password' => array(
                'type' => 'password',
                'options' => array(
                    'label' => 'Password',
                    'required' => true,
                    'validators' => array(
                        array(new Zefram_Validate_NotEqual(array(
                            'token' => 'email',
                            'useContext' => true,
                        )), true),
                    ),
                ),
            ),
            '__submit' => array(
                'type' => 'button',
                'options' => array(
                    'label' => 'Sign up',
                    'attribs' => array(
                        'type' => 'submit',
                    ),
                ),
            ),
        );

        $options['elements'] = $elements;
        parent::__construct($options);
    }
}
