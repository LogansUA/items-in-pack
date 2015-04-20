<?php

if (!defined('_PS_VERSION_'))
    exit;

/**
 * ItemsInPack
 *
 * @author Oleg Kachinsky <logansoleg@gmail.com>
 */
class ItemsInPack extends Module
{
    /* @var boolean error */
    protected $_errors = false;

    public function __construct()
    {
        $this->name = 'itemsinpack';
        $this->tab = 'items_in_pack';
        $this->version = '1.0';
        $this->author = 'Oleg';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Item in pack');
        $this->description = $this->l('Module for display items in pack\'s.');
    }
}
?>
