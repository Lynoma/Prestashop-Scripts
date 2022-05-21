<?php

include('/config/config.inc.php');


ini_set('display_errors', 'On');


$marge = 10; 

function createMultiLangField($field)
{
    $res = array();
    foreach (Language::getIDs(false) as $id_lang) {
        $res[$id_lang] = $field;
    }

    return $res;
}

function eanExistsInDatabase($id_entity,$table){
    $row = Db::getInstance()->getRow('
		SELECT `id_'.bqSQL($table).'` as id
		FROM `'._DB_PREFIX_.bqSQL($table).'` e
		WHERE e.`ean13` = '.(int)$id_entity, false
    );

    return array(isset($row['id']), $row['id']);
}

function getCategoryByName($cat){
    $row = Db::getInstance()->getRow('
        SELECT `id_category` as id
        FROM `'._DB_PREFIX_.'category_lang` c
        WHERE c.`name` = "'.$cat.'" AND id_shop = 4
    ');
    
    return $row['id'];
}

function getStockIfExists($id_prod){
    $row = Db::getInstance()->getRow('
        SELECT `id_stock` as id
        FROM `'._DB_PREFIX_.'stock` s
        WHERE s.`id_product` = "'.$id_prod.'" AND s.`id_warehouse` = 12
    ');
    if(!isset($row['id'])){
        return array(0, 0);
    }
    return array(isset($row['id']), $row['id']);
}

function getOtherStocks($id_prod){
    $row = Db::getInstance()->getRow('
        SELECT `id_stock` as id
        FROM `'._DB_PREFIX_.'stock` s
        WHERE s.`id_product` = "'.$id_prod.'" AND s.`id_warehouse` != 12
    ');
    
    if(isset($row['id']))
        return "true";
    return "false";
}

function getStockApo($id_prod){
    $row = Db::getInstance()->getRow('
        SELECT `physical_quantity` as pq
        FROM `'._DB_PREFIX_.'stock` s
        WHERE s.`id_product` = "'.$id_prod.'" AND s.`id_warehouse` = 3
    ');
    if($row['pq'] > 0){
        return "true";
    }
    return "false";
}

function getOtherStocksQuantity($id_prod){
    $row = Db::getInstance()->ExecuteS('
        SELECT `physical_quantity` as pq
        FROM `'._DB_PREFIX_.'stock` s
        WHERE s.`id_product` = "'.$id_prod.'" AND s.`id_warehouse` != 12
    ');
    
    $var_other_stocks_quantity = 0;
    foreach($row as $quantity){
        $var_other_stocks_quantity += (int)$quantity['pq'];
    }
    return $var_other_stocks_quantity;
}

function getWarehouseProductLocationIfExists($id){
    $row = Db::getInstance()->getRow('
        SELECT `id_product` as id
        FROM `'._DB_PREFIX_.'warehouse_product_location` wpl
        WHERE wpl.`id_product` = "'.$id.'"
    ');
    if(isset($row['id'])){
        return "true"; 
    }else{
        return "false";
    }
}

function categoryExists($cat){
    $row = Db::getInstance()->getRow('
        SELECT `id_category` as id
        FROM `'._DB_PREFIX_.'category_lang` c
        WHERE c.`name` = "'.$cat.'" AND c.`id_shop` = 4
    ');
    
    if(isset($row['id'])){
        return 'true';
    }else{
        return 'false';
    }
}

function selectBaseProdPrice($id){
    $row = Db::getInstance()->getRow('
        SELECT `wholesale_price` as w
        FROM `'._DB_PREFIX_.'product` p
        WHERE p.`id_product` = "'.$id.'"
    ');
    
    return $row['w'];
}

function margePrice($price){
    $price = (double)$price;
    if ($price < 5.00){
        return (double)($price * 1.40);
    }
	elseif($price < 9.99){
        return (double)($price * 1.25);
    }
    elseif($price < 19.99){
        return (double)($price * 1.20);
    }
    elseif($price < 29.99){
        return (double)($price * 1.19);
    }
    elseif($price < 99.99){
        return (double)($price * 1.18);
    }
    elseif($price < 199.99){
        return (double)($price * 1.17);
    }
    elseif($price < 299.99){
        return (double)($price * 1.16);
    }
    elseif($price < 399.99){
        return (double)($price * 1.15);
    }
    elseif($price < 499.99){
        return (double)($price * 1.14);
    }
    elseif($price < 899.99){
        return (double)($price * 1.13);
    }
    elseif($price < 1499.99){
        return (double)($price * 1.12);
    }
    elseif($price > 1499.99){
        return (double)($price * 1.11);
    }
}

function getParentTree($nomCat){
    $idCat = getCategoryByName($nomCat);
    $cat = new Category($idCat);
    $arrayParent = [$idCat];
    while($cat->id_parent != 32){
        array_push($arrayParent, $cat->id_parent);
        $cat = new Category($cat->id_parent);
    }
    array_push($arrayParent, 32);
    return $arrayParent;
}

//------------------------------------------------------------------------------------------------------------

//Set time limit a infini
set_time_limit(6000);
//Set memory limit
ini_set("memory_limit", "1024M");
//Set le shop actuel
Shop::setContext(Shop::CONTEXT_SHOP, 4);
//Set la création de masse
if (!defined('PS_MASS_PRODUCT_CREATION')) {
    define('PS_MASS_PRODUCT_CREATION', true);
}
$prod = new Product();

$val = 0;
$handle = fopen ("/home/papi8268/allofiestaloc.com/17130080STOCK.csv","r");
$linecount=0;
while (fgets($handle) !== false) $linecount++;
fclose($handle);
$handle = fopen ("/home/papi8268/allofiestaloc.com/17130080STOCK.csv","r");
for($b = 0; $b < $linecount; $b++) {
    $data = fgetcsv ($handle, 1000, "|");
    $data = array_map("utf8_encode", $data); //added
    $num = count ($data);
    //Création produit
    $prod = new Product();
    $prod->id_supplier = 29;
    $prod->supplier_name = "Westcoast";
    if($val == 0){
        $val += 1;
        continue;
    }
    
    //Ligne produit
    for ($c=0; $c < $num; $c++) {
        if($val != 0){

            switch($c){
                case 3:
                    $prod->ean13 = (int)$data[$c]; //EAN
                    break;
                case 7:
                    $prod->name = createMultiLangField(str_replace("|","",$data[$c]));; //Nom
                    break;
                case 11:
                    $var_manufacturer = str_replace("|","",$data[$c]);  //Fabricant
                    break;
                case 15:
                    $prod->category = str_replace("|","",$data[$c]);    //Category
                    break;
                case 17:
                    $prod->reference = str_replace("|","",$data[$c]);   //code constructeur
                    break;
                case 19:
                    $prod->wholesale_price = str_replace(",",".",$data[$c]); //Prix d'achat
                    $prod->price = margePrice($prod->wholesale_price);      //Prix HT
                    break;
                case 21:
                    $var_stock_quantity = str_replace("|","",$data[$c]); //stock
                    break;
                default:
                    break;
            }
        }
    }
    
    //Manufacturer
    if(Manufacturer::getIdByName($var_manufacturer)){
        $prod->id_manufacturer = Manufacturer::getIdByName($var_manufacturer);
    }
    
    //Default values
    $prod->id_shop_default = 4; //Shop default
    $prod->id_shop_lists = array(4); //Same
    $prod->advanced_stock_management = "1"; //Stock avancé
    $prod->link_rewrite = createMultiLangField(Tools::str2url($prod->name[1]));  //link rewrite fiche produit
    $prod->active = 0; //Désactivé de base
    $prod->supplier_name = "Westcoast";
    $prod->advanced_stock_management = 1; //Stock avancé
    $prod->warehouse = 12; //Entrepôt Westcoast
    
    if(!isset($prod->id_category)){
        $prod->id_category = array((int)Configuration::get('PS_HOME_CATEGORY')); //Catégorie de base
    }
    $prod->id_category_default = null;
    $prod->visibility = 'both';
    $prod->minimal_quantity = 1;
    $prod->id_tax_rules_group = 1; //Tax rules group a voir
    $catid = getCategoryByName($prod->category);
    if($catid != 32){
        $prod->id_category = array($catid, 32);   
    }else{
        $prod->id_category = array(32);
    }
    $prod->id_category_default = $prod->id_category[0];
    $prod->visibility = 'both';
    $prod->available_now = createMultiLangField(" ");
    $prod->available_later = createMultiLangField("Stock épuisé");
    
    //Si le produit existe, on retourne true et l'id du produit
    $exists = eanExistsInDatabase($prod->ean13, 'product');

    //Si le produit existe
    if($exists[0]){
        
        //Si le produit n'a pas de stock chez apo
        if(getStockApo($exists[1]) == "false"){
            
            //Si le produit existe dans un autre stock avec plus que 0 de quantité
            if(getOtherStocks($exists[1]) == "true" and getOtherStocksQuantity($exists[1] != 0)){
                
                //Récupère le prix de base
                $iniprod = selectBaseProdPrice($exists[1]);
                
                //Si le produit a un fabricant
                if(isset($prod->id_manufacturer) && $prod->wholesale_price < $iniprod && (int)$var_stock_quantity != 0){
                    
                    $requestprod =  '
                    UPDATE `'._DB_PREFIX_.'product` SET price = "'.$prod->price.'", wholesale_price = "'.$prod->wholesale_price.'", id_manufacturer = "'.$prod->id_manufacturer.'" 
                    WHERE id_product = "'.$exists[1].'"
                    ';
                    
                    $requestshop = '
                    UPDATE `'._DB_PREFIX_.'product_shop` SET price = "'.$prod->price.'", wholesale_price = "'.$prod->wholesale_price.'", id_manufacturer = "'.$prod->id_manufacturer.'"
                    WHERE id_product = "'.$exists[1].'"
                    ';
                }elseif($prod->wholesale_price < $iniprod && (int)$var_stock_quantity != 0){
                    $requestprod = '
                        UPDATE `'._DB_PREFIX_.'product` SET price = "'.$prod->price.'", wholesale_price = "'.$prod->wholesale_price.'"
                        WHERE id_product = "'.$exists[1].'"
                    ';
                    
                    $requestshop = '
                    UPDATE `'._DB_PREFIX_.'product_shop` SET price = "'.$prod->price.'", wholesale_price = "'.$prod->wholesale_price.'"
                    WHERE id_product = "'.$exists[1].'"
                    ';
                } 
            }else {
                //Sinon on remplace le prix du produit
                if(isset($prod->id_manufacturer) && (int)$var_stock_quantity != 0){
                    
                    $requestprod =  '
                    UPDATE `'._DB_PREFIX_.'product` SET price = "'.$prod->price.'", wholesale_price = "'.$prod->wholesale_price.'", id_manufacturer = "'.$prod->id_manufacturer.'" 
                    WHERE id_product = "'.$exists[1].'"
                    ';
                    
                    $requestshop = '
                    UPDATE `'._DB_PREFIX_.'product_shop` SET price = "'.$prod->price.'", wholesale_price = "'.$prod->wholesale_price.'", id_manufacturer = "'.$prod->id_manufacturer.'"
                    WHERE id_product = "'.$exists[1].'"
                    ';
                }elseif((int)$var_stock_quantity != 0){
                    $requestprod = '
                        UPDATE `'._DB_PREFIX_.'product` SET price = "'.$prod->price.'", wholesale_price = "'.$prod->wholesale_price.'"
                        WHERE id_product = "'.$exists[1].'"
                    ';
                    
                    $requestshop = '
                    UPDATE `'._DB_PREFIX_.'product_shop` SET price = "'.$prod->price.'", wholesale_price = "'.$prod->wholesale_price.'"
                    WHERE id_product = "'.$exists[1].'"
                    ';
                } 
            }
            
            
            if(isset($requestprod) && isset($requestshop)){
                $updateprod = Db::getInstance()->Execute($requestprod);
                $updateshop = Db::getInstance()->Execute($requestshop);
            }
        }
        $prod->id = $exists[1];
    }else{
        try{
            //Add le produit
            $prod->add();
            //Remet l'id du produit
            $id_product = eanExistsInDataBase($prod->ean13, 'product');
            $prod->id = $id_product[1];
        }
        catch(Exception $e){
            $val = $val + 1;
            continue;
        }
    }
    
    
    

    //--------------------------------------------------------------------------------

    //Add categories if not exist
    if(categoryExists($prod->category) == 'false'){
        //Set les values de la catégorie
        $cat = new Category();
        $cat->name = createMultiLangField($prod->category);
        
        //Default Values
        $cat->id_shop_default = 4;
        $cat->active = 1;
        $cat->parent = Configuration::get('PS_HOME_CATEGORY');
        $cat->id_parent = Configuration::get('PS_HOME_CATEGORY');
        $cat->link_rewrite = createMultiLangField(Tools::str2url($prod->category));
        $cat->add();
    }

    //Update la catégorie du produit
    echo "id : ";
    print_r($prod->id);
    $prod->addToCategories($prod->id_category); //Update la category du produit 

    //--------------------------------------------------------------------------------

    //Ajoute le fournisseur du produit
    $product_supplier = new ProductSupplier($prod->id_supplier);
    $product_supplier->id_product = (int)$prod->id;
    $product_supplier->product_supplier_reference;
    $product_supplier->id_product_attribute = 0;
    $product_supplier->id_supplier = (int)$prod->id_supplier;
    $product_supplier->save();

    //--------------------------------------------------------------------------------

    //Set le Stock produit du fournisseur
    $stockExists = getStockIfExists($prod->id);
    if($stockExists[0] == 1){
        //Reprend le stock
        $stock = new Stock($stockExists[1]);
    }else{
        //Créé un stock
        $stock = new Stock();
        $stock->price_te = 0;
        $stock->id_warehouse = 12;
        $stock->id_product = $prod->id;
        $stock->id_product_attribute = 0;
    }

    //Si le stock est négatif, le set à 0
    if($var_stock_quantity < 0){
        $var_stock_quantity = 0;
    }
    
    //Set les quantités
    $stock->physical_quantity = (int)$var_stock_quantity;
    $stock->usable_quantity = $stock->physical_quantity;
    
    //set stock
    if($stockExists[0] == 1){
        $stock->update();
    }else{
        $stock->add();
    }

    //--------------------------------------------------------------------------------

    //Insert un nouvel entrepôt

            if(getWarehouseProductLocationIfExists($prod->id, $prod->warehouse) == "false"){
                Db::getInstance()->Execute('
                    INSERT INTO `'._DB_PREFIX_.'warehouse_product_location` (`id_product`, `id_product_attribute`, `id_warehouse`) 
                    VALUES ('.$prod->id.', 0, '.$prod->warehouse.')
                ');
            }

    //--------------------------------------------------------------------------------

    //Set qte dépend du stock
    StockAvailable::synchronize($prod->id);
    StockAvailable::setProductDependsOnStock($prod->id, 1, 4);
    
    $val = $val + 1;
    set_time_limit(600);
    }
    

?>