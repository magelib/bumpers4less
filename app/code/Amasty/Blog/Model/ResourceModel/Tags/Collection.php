<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Blog
 */

namespace Amasty\Blog\Model\ResourceModel\Tags;

class Collection extends \Magento\Rule\Model\ResourceModel\Rule\Collection\AbstractCollection
{
    const MIN_SIZE = 1;
    const MAX_SIZE = 10;

    protected $_addWheightData = false;
    protected $_postDataJoined = false;
    
    protected $_map = ['fields' => [
        'tag_id' => 'main_table.tag_id'
    ]];

    public function _construct()
    {
        $this->_init('Amasty\Blog\Model\Tags', 'Amasty\Blog\Model\ResourceModel\Tags');
    }

    public function addCount()
    {
        $this->getSelect()
            ->joinLeft(
                ['posttag' => $this->getTable('amasty_blog_posts_tag')],
                'main_table.tag_id = posttag.tag_id',
                ['COUNT(posttag.`tag_id`) as count']
            )
        ;
        $this->getSelect()
            ->group('main_table.tag_id');
        return $this;
    }

    public function addWieghtData($store = null)
    {
        $this->_addWheightData = true;
        $this->_joinPostData();
        $this->getSelect()
            ->columns(array('post_count' => new \Zend_Db_Expr("COUNT(post.post_id)")))
            ->group("main_table.tag_id")
        ;

        if ($store){

            if (!is_array($store)){
                $store = array($store);
            }

            $store = "'".implode("','", $store)."'";
            $postStoreTable = $this->getTable('amasty_blog_posts_store');
            $this->getSelect()
                ->join(array('store'=>$postStoreTable), "post.post_id = store.post_id", array())
                ->where(new \Zend_Db_Expr("store.store_id IN ({$store})"))
            ;
        }
        return $this;
    }

    protected function _joinPostData()
    {
        if ($this->_postDataJoined){
            return $this;
        }

        $this->_postDataJoined = true;

        $postTagTable = $this->getTable('amasty_blog_posts_tag');
        $this->getSelect()
            ->join(array('post'=>$postTagTable), "post.tag_id = main_table.tag_id", array())
        ;

        return $this;
    }

    public function setMinimalPostCountFilter($count)
    {
        if ($this->_addWheightData){
            $this->getSelect()
                ->having("COUNT(post.post_id) >= ?", $count)
            ;
        }
        return $this;
    }

    public function setPostStatusFilter($status)
    {
        if (!is_array($status)){
            $status = array($status);
        }

        $postTable = $this->getTable('amasty_blog_posts');
        $this->getSelect()
            ->join(array('postEntity' => $postTable), "post.post_id = postEntity.post_id", array())
            ->where("postEntity.status IN (?)", $status);

        return $this;
    }

    public function setNameOrder()
    {
        $this->getSelect()->order("main_table.name ASC");
        return $this;
    }

    public function addPostFilter($postIds)
    {
        if (!is_array($postIds)){
            $postIds = array($postIds);
        }

        $this->_joinPostData();

        $this->getSelect()
            ->where("post.post_id IN (?)", $postIds)
        ;

        return $this;
    }
}