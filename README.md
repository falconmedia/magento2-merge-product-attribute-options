# Falcon Media - Merge Attribute Options
![Supported Magento Versions](https://img.shields.io/badge/magento-%202.4-brightgreen.svg?logo=magento&longCache=true&style=for-the-badge)

### `FalconMedia_MergeAttributeOptions`

This module is created out of frustration, because after importing products from multiple vendors, there were multiple attribute options
for one and the same attribute option that already exists. 

## Module Features
the possibility to show the following by using CLI Commands: 

- List all Product Attributes and option to filter on code, name, and input type 
- List Attribute Options with Product Count
- List Attribute Options with labels per store
- Merge Attribute Options into one option

## Installation
```
composer require falconmedia/magento2-merge-attribute-options
bin/magento module:enable FalconMedia/MergeAttributeOptions
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## Usage


### List all Attributes
`bin/magento falconmedia:attribute:list` will show all Product Attributes

You have also the possibility to filter on the attributes with the options 
- `--code=` or `-C` to filter on an attribute code
- `--name=` or `-N` to filter on an Attribute Name/Label
- `--type=` or `-T` to filter on an Attribute input type

#### Examples
Filter on an attribute code with `--code=`
```
❯ bin/magento falconmedia:attribute:list --code=sku
+--------------+---------------------+----------------------+---------+
| Attribute ID | Attribute Code      | Admin Name           | Type    |
+--------------+---------------------+----------------------+---------+
| 74           | sku                 | SKU                  | text    |
| 122          | sku_type            | Dynamic SKU          | boolean |
| 267          | supplier_sku        | Supplier SKU         | text    |
| 222          | xcore_suppliers_sku | Supplier - Item Code | text    |
+--------------+---------------------+----------------------+---------+
```
or with the short flag of `-C`

```
❯  bin/magento falconmedia:attribute:list -C sku
+--------------+---------------------+----------------------+---------+
| Attribute ID | Attribute Code      | Admin Name           | Type    |
+--------------+---------------------+----------------------+---------+
| 74           | sku                 | SKU                  | text    |
| 122          | sku_type            | Dynamic SKU          | boolean |
| 267          | supplier_sku        | Supplier SKU         | text    |
| 222          | xcore_suppliers_sku | Supplier - Item Code | text    |
+--------------+---------------------+----------------------+---------+
```
Get attribute list with multiple filters 
```
❯  bin/magento falconmedia:attribute:list -C sku -T boolean
+--------------+----------------+-------------+---------+
| Attribute ID | Attribute Code | Admin Name  | Type    |
+--------------+----------------+-------------+---------+
| 122          | sku_type       | Dynamic SKU | boolean |
+--------------+----------------+-------------+---------+
```

### List Attribute Options with Product Count
```
❯ bin/magento falconmedia:attribute:list-options size
Fetching attribute options for 'size'

+-----------+----------+---------------+
| Option ID | Label    | Product Count |
+-----------+----------+---------------+
| 839       | 18 Oz    | 75            |
| 840       | 2 Oz     | 9             |
| 841       | 20 Oz    | 2             |
| 842       | 4 Oz     | 80            |
| 843       | 6 Oz     | 141           |
| 844       | 8 Oz     | 292           |
| 4200      | 12 Oz    | 655           |
| 4201      | 14 Oz    | 697           |
| 4202      | 16 Oz    | 661           |
| 6372      | 8 Oz xl  | 8             |
| 6373      | 10 Oz xl | 8             |
| 6562      | 10 oz    | 701           |
| 6563      | 12 oz    | 69            |
| 6564      | 14 oz    | 70            |
| 6565      | 16 oz    | 67            |
| 6575      | 12oz     | 14            |
| 6576      | 14oz     | 14            |
| 6577      | 16oz     | 13            |
| 6578      | 18oz     | 1             |
| 6580      | 12       | 11            |
| 6581      | 14       | 11            |
| 6582      | 16       | 11            |
| 6583      | 18       | 0             |
| 6589      | 8oz      | 4             |
| 6590      | 4oz      | 1             |
| 6591      | 6oz      | 1             |
| 6596      | 8 oz     | 9             |
| 6599      | 4 oz     | 1             |
| 6600      | 6 oz     | 1             |
+-----------+----------+---------------+
```

As you see there are multiple options for one option, but different spelled

for example 12 Oz,  12 oz, 12oz and 12 are the same option.

### List Attribute Options with labels per store
```
❯ bin/magento falconmedia:attribute:list-options-store color
Attribute: color
+-----------+----------+------------+-------------------------------+
| Option ID | Store ID | Store Name | Label                         |
+-----------+----------+------------+-------------------------------+
| 31        | 0        | Admin      | White                         |
| 31        | 3        | Store NL   | Wit                           |
| 32        | 0        | Admin      | Black                         |
| 32        | 3        | Store NL   | Zwart                         |
| 58        | 0        | Admin      | Blue                          |
| 58        | 3        | Store NL   | Blauw                         |
| 60        | 0        | Admin      | Brown                         |
| 60        | 3        | Store NL   | Bruin                         |
| 69        | 0        | Admin      | Red                           |
| 69        | 3        | Store NL   | Rood                          |
| 94        | 0        | Admin      | Yellow                        |
| 94        | 3        | Store NL   | Geel                          |
| 98        | 0        | Admin      | Green                         |
| 98        | 3        | Store NL   | Groen                         |
| 99        | 0        | Admin      | Gray                          |
| 99        | 3        | Store NL   | Grijs                         |
| 119       | 0        | Admin      | Purple                        |
| 119       | 3        | Store NL   | Paars                         | 
| 151       | 0        | Admin      | Orange                        |
| 151       | 3        | Store NL   | Oranje                        |
| 161       | 0        | Admin      | Pink                          |
| 161       | 3        | Store NL   | Roze                          |
```

### Merge Attribute Options into one option

In the first output you can see the product count per Option Id.
The 12 Oz is used at 655 products, so merging the other attribute options into this option is the best choice.

```
❯ bin/magento falconmedia:attribute:merge-options size 6563,6575,6580 4200
Merging attribute options for 'size'
+------------------+------------------+----------------------+---------+
| Source Option ID | Target Option ID | Merged Product Count | Status  |
+------------------+------------------+----------------------+---------+
| 6563             | 4200             | 69                   | Deleted |
| 6575             | 4200             | 14                   | Deleted |
| 6580             | 4200             | 11                   | Deleted |
+------------------+------------------+----------------------+---------+
```

