# Items in pack
Module for PrestaShop CMS which allows to set numbers of items in pack for product

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/LogansUA/items-in-pack/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/LogansUA/items-in-pack/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/LogansUA/items-in-pack/badges/build.png?b=master)](https://scrutinizer-ci.com/g/LogansUA/items-in-pack/build-status/master)
[![Join the chat at https://gitter.im/LogansUA/items-in-pack](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/LogansUA/items-in-pack?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0bcb0708-5e9d-4e9d-9cd9-a0ef3577ca59/mini.png)](https://insight.sensiolabs.com/projects/0bcb0708-5e9d-4e9d-9cd9-a0ef3577ca59)

## Installation
* Download latest version of module
```
git clone https://github.com/LogansUA/ps_itemsinpack.git
```
* Move module dir (`ps_itemsinpack`) to modules folder of your shop
```
mv items-in-pack/ YourShop/modules/
```
* Add data attribute to `tr` tag in `YourShop/themes/default-bootstrap/shopping-cart-product-line.tpl` like that
```
{if isset($product.items_in_pack)}data-items-in-pack="{$product.items_in_pack}"{/if}
```
