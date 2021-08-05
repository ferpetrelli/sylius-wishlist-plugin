<?php

declare(strict_types=1);

namespace Webburza\SyliusWishlistPlugin\Factory\View;

use Sylius\ShopApiPlugin\Factory\Product\ProductViewFactoryInterface;
use Webburza\SyliusWishlistPlugin\Model\WishlistItemInterface;

class WishlistItemViewFactory
{

  /** @var string */
  private $wishlistViewClass;

  public function __construct(ProductViewFactoryInterface $productViewFactory)
  {
    $this->productViewFactory = $productViewFactory;
  }

  public function create(WishlistItemInterface $wishlistItem, $channel, string $locale): WishlistItemView
  {
    /** @var WishlistItemView $wishlistView */
    $wishlistItemView = new WishlistItemView();

    $wishlistItemView->id = $wishlistItem->getId();
    $wishlistItemView->product = $this->productViewFactory->create($wishlistItem->getProductVariant()->getProduct(), $channel, $locale);
    $wishlistItemView->product_variant_code = $wishlistItem->getProductVariant()->getCode();

    return $wishlistItemView;
  }

}
