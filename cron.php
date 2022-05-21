<?php

include('config/config.inc.php');
include('init.php');

ini_set('display_errors', 'On');

if(isset($_GET["token"]) && $_GET["token"]=="s6df4fjgfc2xf4g1hs31h4vd53f2"){

    //CODE ICI
    $product = Db::getInstance()->ExecuteS('
        SELECT id_product 
        FROM '._DB_PREFIX_.'product
    ');


    foreach($product as $i => $products)
    {
        echo $products['id_product'];
        echo "<br/>";
        $image = Db::getInstance()->getRow('
            SELECT * 
            FROM '._DB_PREFIX_.'image
            WHERE id_product = "'.$products['id_product'].'"
        ');
        
        $stock = Db::getInstance()->ExecuteS('
            SELECT * 
            FROM '._DB_PREFIX_.'stock 
            WHERE id_product = "'.$products['id_product'].'"
        ');
        
        $stocktotal = 0;
        foreach($stock as $list){
            $stocktotal += $list['usable_quantity'];
        }
            

        if (isset($image['id_image']) && $stocktotal > 0){
            echo "activation";
            Db::getInstance()->Execute('
            UPDATE '._DB_PREFIX_.'product
            SET available_for_order = 1, visibility="both", active=1 
            WHERE id_product = "'.$products['id_product'].'" AND id_shop_default = 4
            ');
             Db::getInstance()->Execute('
            UPDATE '._DB_PREFIX_.'product_shop
            SET available_for_order = 1, visibility = "both", active=1 
            WHERE id_product = "'.$products['id_product'].'" AND id_shop = 4 
            ');
        }
        else{
            echo "Désactivation";
            Db::getInstance()->Execute('
            UPDATE '._DB_PREFIX_.'product
            SET available_for_order = 0, visibility="none", active=1 
            WHERE id_product = "'.$products['id_product'].'" AND id_shop_default = 4 AND id_category_default NOT IN (978 , 1046 , 1082 , 1078 , 1081 , 1083 , 1084 , 3640 , 3641 , 3698)
            ');
             Db::getInstance()->Execute('
            UPDATE '._DB_PREFIX_.'product_shop
            SET available_for_order = 0, visibility="none", active=1 
            WHERE id_product = "'.$products['id_product'].'" AND id_shop = 4 AND id_category_default NOT IN (978 , 1046 , 1082 , 1078 , 1081 , 1083 , 1084 , 3640 , 3641 , 3698)
            ');
        }

        
    }

    

}

else{
    echo "Vous n'êtes pas autherntifiés";
}

?>