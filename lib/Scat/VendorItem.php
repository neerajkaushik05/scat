<?
namespace Scat;

class VendorItem extends \Model {
  public static
  function findByItemIdForVendor($item_id, $vendor_id, $active= 1) {
    return \Model::factory('VendorItem')
             ->where('vendor', $vendor_id)
             ->where('item', $item_id)
             ->where('active', $active)
             ->find_many();
  }

  public function item() {
    return $this->belongs_to('Item', 'item');
  }

  public function vendor() {
    return $this->belongs_to('Person', 'vendor');
  }
}