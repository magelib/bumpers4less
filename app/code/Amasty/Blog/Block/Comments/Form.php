<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Blog
 */

namespace Amasty\Blog\Block\Comments;

class Form extends \Amasty\Blog\Block\Comments
{
    protected $_collection;

    protected $_post;
    protected $_replyTo;
    protected $_formData = array();

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate("Amasty_Blog::comments/form.phtml");
    }

    public function setPost($value)
    {
        $this->_post = $value;
        return $this;
    }

    public function setReplyTo($value)
    {
        $this->_replyTo = $value;
    }

    public function canPostComments()
    {
        return $this->settingsHelper->getCommentsAllowGuests();
    }

    public function getReplyTo()
    {
        return $this->_replyTo ? $this->_replyTo->getId() : 0;
    }

    public function getPost()
    {
        return $this->_post;
    }

    public function getPostId()
    {
        return $this->getPost()->getId();
    }

    public function isReply()
    {
        return !!$this->getReplyTo();
    }

    public function canPost()
    {
        return $this->settingsHelper->getCommentsAllowGuests() || $this->isLoggedId();
    }

    public function setFormData(array $data)
    {
        $this->_formData = $data;
    }

    public function getFormData()
    {
        return new \Magento\Framework\DataObject($this->_formData);
    }

    public function getRegisterUrl()
    {
        return $this->getUrl('customer/account/create');
    }

    public function getLoginUrl()
    {
        $params = array('post_id' => $this->getPostId());
        if ($this->isReply()){
            $params['reply_to'] = $this->getReplyTo();
        }
        return $this->getUrl('customer/account/login', $params);
    }

    public function isLoggedId()
    {
        return $this->session->isLoggedIn();
    }

    public function getCustomerId()
    {
        return $this->session->getCustomerId();
    }

    public function getCustomerName()
    {
        return $this->isLoggedId() ? $this->session->getCustomer()->getName() : $this->dataHelper->loadCommentorName();
    }

    public function getCustomerEmail()
    {
        return $this->isLoggedId() ? $this->session->getCustomer()->getEmail() : $this->dataHelper->loadCommentorEmail();
    }

    public function getSessionId()
    {
        return $this->getData('session_id');
    }

    public function getMessageBlockHtml()
    {
        $block = $this->getMessagesBlock();
        if ($block){
            $block->setMessages($this->session->getMessages(true));
        }
        return $block->toHtml();
    }

    public function getEmailsEnabled()
    {
        return $this->settingsHelper->getCommentNotificationsEnabled();
    }

    public function getReplyToCustomerName()
    {
        $comment = $this->commentsModel->load($this->getReplyTo());
        if ($comment->getId()){
            return $comment->getName();
        }

        return false;
    }

    public function isCustomerSubscribed()
    {
        $storedValue = $this->dataHelper->loadIsSubscribed();
        if (!is_null($storedValue)){
            return $storedValue;
        }

        return true;
    }

    public function htmlEscape($string)
    {
        return $this->context->getEscaper()->escapeHtml($string);
    }
}