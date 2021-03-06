<?php
namespace Scat;

class Product extends \Model implements \JsonSerializable {
  private $old_slug;

  public function fields() {
    return [ 'id', 'department_id', 'brand_id', 'name',
             'description', 'slug', 'variation_style',
             'active' ];
  }

  public function brand() {
    return $this->belongs_to('Brand');
  }

  public function brand_name() {
    return $this->brand()->find_one()->name;
  }

  public function dept() {
    return $this->belongs_to('Department');
  }

  public function items($only_active= true) {
    return $this->has_many('Item')
                ->select('item.*')
                ->where_gte('item.active', (int)$only_active);
  }

  public function full_slug() {
    return
      $this->dept()->find_one()->parent()->find_one()->slug . '/' .
      $this->dept()->find_one()->slug . '/' . $this->slug;
  }

  public function stocked() {
    return $this->has_many('Item')
                ->where_gte('item.active', 1)
                ->where_gte('minimum_quantity', 1)
                ->count();
  }

  public function jsonSerialize() {
    return array_merge($this->asArray(), [
      'full_slug' => $this->full_slug()
    ]);
  }

  public function media() {
    $media= $this->has_many_through('Image')->find_many();
    if (!$media[0]->id) {
      return $this->image ?
        [
          [
            'src' => $this->image,
            'thumbnail' => ORDURE_STATIC . $this->image,
            'alt_text' => $this->name
          ]
        ] : null;
    }
    return $media;
  }

  public function addImage($image) {
    $rel= \Model::factory('ImageProduct')->create();
    $rel->image_id= $image->id;
    $rel->product_id= $this->id;
    $rel->save();
  }

  public static function getById($id) {
    $product= \Model::factory('Product')
                ->select('product.*')
                ->select('brand.name', 'brand_name')
                ->select_expr('(SELECT COUNT(DISTINCT variation)
                                  FROM item
                                  WHERE item.product_id = product.id)',
                              'variations')
                ->select_expr('JSON_ARRAYAGG(JSON_OBJECT("id", image.id,
                                                         "uuid", image.uuid,
                                                         "name", image.name,
                                                         "alt_text", image.alt_text,
                                                         "width", image.width,
                                                         "height", image.height,
                                                         "ext", image.ext))',
                              'media')
                ->join('brand', array('product.brand_id', '=', 'brand.id'))
                ->left_outer_join('product_to_image',
                                  array('product.id', '=',
                                        'product_to_image.product_id'))
                ->left_outer_join('image',
                                  array('product_to_image.image_id', '=',
                                        'image.id'))
                ->find_one($id);

    if (!$product) {
      throw new \Exception("No such product.");
    }

    /* Turn media back into an array of information (or fake it) */
    $product->media= json_decode($product->media);
    if (!$product->media[0]->id) {
      $product->media=
        [
          [
            'src' => $product->image,
            'thumbnail' => $product->image,
            'alt_text' => $product->name
          ]
        ];
    }

    return $product;
  }

  // XXX A gross hack to find when slug changes.
  function set_orm($orm) {
    parent::set_orm($orm);
    if ($this->id) {
      $this->old_slug= $this->full_slug();
    }
  }

  function save() {
    if ($this->id &&
        ($this->is_dirty('slug') || $this->is_dirty('department_id'))) {
      $new_slug= $this->full_slug();
      if ($new_slug != $this->old_slug) {
        error_log("Redirecting {$this->old_slug} to $new_slug");
        $redir= \Model::factory('Redirect')->create();
        $redir->source= $this->old_slug;
        $redir->dest= $new_slug;
        $redir->save();
      }
    }
    parent::save();
  }
}
