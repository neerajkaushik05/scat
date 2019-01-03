<?
include '../scat.php';
include '../lib/item.php';

$vendor= (int)$_REQUEST['vendor'];
if (!$vendor)
  die_jsonp("You need to specify a vendor.");

$sql_criteria= "1=1";
$items= $_REQUEST['items'];
if (!empty($items)) {
  list($sql_criteria, $x)= item_terms_to_sql($db, $items, FIND_OR);
}

$q= "UPDATE item, vendor_item
        SET item.retail_price = vendor_item.retail_price
     WHERE item.id = vendor_item.item
       AND vendor = $vendor
       AND $sql_criteria";

$r= $db->query($q)
  or die_query($db, $q);

echo jsonp(array('message' => "Updated items"));
