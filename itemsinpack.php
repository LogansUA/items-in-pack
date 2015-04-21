<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * ItemsInPack
 *
 * @author Oleg Kachinsky <logansoleg@gmail.com>
 */
class ItemsInPack extends Module
{

    /**
     * @var int $itemsInPack Items in Pack
     */
    private $itemsInPack = 1;

    /**
     * @var boolean error
     */
    protected $_errors = false;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->name          = 'itemsinpack';
        $this->tab           = 'items_in_pack';
        $this->version       = '1.0';
        $this->author        = 'Oleg';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Item in pack');
        $this->description = $this->l('Module for display items in pack\'s.');
    }

    /**
     * Install
     *
     * @return bool
     */
    public function install()
    {
        if (!parent::install() OR
            !$this->alterTable('add') OR
            !$this->registerHook('actionAdminControllerSetMedia') OR
            !$this->registerHook('actionProductUpdate') OR
            !$this->registerHook('displayAdminProductsExtra')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Un install
     *
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall() OR !$this->alterTable('remove')) {
            return false;
        }

        return true;
    }

    /**
     * Alter table
     *
     * @param string $method Method
     *
     * @return bool
     */
    public function alterTable($method)
    {
        switch ($method) {
            case 'add':
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product ADD `items_in_pack` INT(11) NOT NULL DEFAULT 1';
                break;
            case 'remove':
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product DROP COLUMN `items_in_pack`';
                break;
        }

        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }

        return true;
    }

    /**
     * Hook display admin products extra
     *
     * @param mixed $params Params
     *
     * @return mixed
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        if (Validate::isLoadedObject($product = new Product((int) Tools::getValue('id_product')))) {
            $this->prepareNewTab();

            return $this->display(__FILE__, 'itemsinpack.tpl');
        }
    }

    /**
     * Hook action admin controller set media
     *
     * @param mixed $params
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        if ($this->context->controller->controller_name == 'AdminProducts' && Tools::getValue('id_product')) {
            $this->context->controller->addJS($this->_path . '/js/itemsinpack.js');
        }
    }

    /**
     * Hook action product update
     *
     * @param mixed $params
     */
    public function hookActionProductUpdate($params)
    {
        $idProduct = (int) Tools::getValue('id_product');

        if (!Db::getInstance()->update('product', [
            'items_in_pack' => Tools::getValue('items-in-pack')
        ], ' id_product = ' . $idProduct)
        ) {
            $this->context->controller->_errors[] = Tools::displayError('Error: ') . mysql_error();
        }
    }

    /**
     * Get custom field
     *
     * @param integer $idProduct
     *
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function getCustomField($idProduct)
    {
        $result = Db::getInstance()->ExecuteS('SELECT items_in_pack FROM ' . _DB_PREFIX_ . 'product WHERE id_product = ' . (int) $idProduct);
        if (!$result) {
            return [];
        }

        return $result[0]['items_in_pack'];
    }

    /**
     * Prepare new tab
     */
    public function prepareNewTab()
    {
        $idProduct = (int) Tools::getValue('id_product');

        $this->context->smarty->assign('itemsInPack', $this->getCustomField($idProduct));
    }
}

?>
