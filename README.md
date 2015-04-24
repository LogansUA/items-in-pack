# Items in pack
Module for PrestaShop CMS which allows to set numbers of items in pack for product

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/LogansUA/items-in-pack/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/LogansUA/items-in-pack/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/LogansUA/items-in-pack/badges/build.png?b=master)](https://scrutinizer-ci.com/g/LogansUA/items-in-pack/build-status/master)
[![Join the chat at https://gitter.im/LogansUA/items-in-pack](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/LogansUA/items-in-pack?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

## Installation
Download latest version of module
```
git clone https://github.com/LogansUA/items-in-pack.git
```
Move module dir (`items-in-pack`) to modules folder of your shop
```
mv items-in-pack/ YourShop/modules/
```
Add data attribute to `tr` tag in `YourShop/themes/default-bootstrap/shopping-cart-product-line.tpl` like that
```
data-items-in-pack="{$product.items_in_pack}"
```
