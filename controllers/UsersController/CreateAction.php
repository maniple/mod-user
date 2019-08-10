<?php

/**
 * @property Zend_Controller_Request_Http $_request
 * @property ManipleUser_Form_User $_form
 * @method void requireAuthentication();
 */
class ManipleUser_UsersController_CreateAction
    extends Maniple_Controller_Action_StandaloneForm
{
    /**
     * @Inject
     * @var Zefram_Db
     */
    protected $_db;

    /**
     * @Inject
     * @var ManipleUser_Form_Factory_User
     */
    protected $_userFormFactory;

    /**
     * @Inject('user.model.userMapper')
     * @var ManipleUser_Model_UserMapperInterface
     */
    protected $_userRepository;

    /**
     * @Inject
     * @var ManipleUser_PasswordService
     */
    protected $_passwordService;

    /**
     * @Inject('user.sessionManager')
     * @var Maniple_Security_ContextInterface
     */
    protected $_securityContext;

    protected function _prepare()
    {
        $this->requireAuthentication();
        if (!$this->_securityContext->isAllowed('manage_users')) {
            throw new Maniple_Controller_Exception_NotAllowed();
        }

        $this->_form = $this->_userFormFactory->createForm();
    }

    protected function _process()
    {
        $password = $this->_passwordService->generatePassword();

        $user = $this->_form->populateUser(new ManipleUser_Entity_User());

        $user->setUsername($user->getEmail());
        $user->setActive(true);
        $user->setPassword($this->_passwordService->temporaryPasswordHash($password));
        $user->setCreatedAt(time());

        $this->_db->beginTransaction();
        try {
            $this->_userRepository->saveUser($user);

            $message = new Zefram_Mail;
            $message->setType(Zend_Mime::MULTIPART_RELATED);
            $message->setSubject(sprintf(
                $this->view->translate('Welcome to %s'),
                preg_replace('%https?://%', '', $this->view->serverUrl($this->view->baseUrl()))
            ));
            $message->addTo($user->getEmail());

            $this->view->assign(array(
                'user'     => $user,
                'message'  => $message,
                'password' => $password,
            ));

            $message->setBodyHtml($this->view->render('maniple-user/users/create-mail.twig'));
            $message->send();

            $this->_db->commit();

        } catch (Exception $e) {
            $this->_db->rollBack();
            throw $e;
        }

        $this->_helper->flashMessenger->addSuccessMessage($this->view->translate('User account has been successfully created'));
        return $this->view->url('maniple-user.users.index');
    }
}