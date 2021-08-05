<?php

declare(strict_types=1);

namespace Webburza\SyliusWishlistPlugin\Factory\View;

use Webburza\SyliusWishlistPlugin\Model\WishlistInterface;
use Webburza\SyliusWishlistPlugin\Factory\View\WishlistItemViewFactory;

class WishlistViewFactory
{

  /** @var string */
  private $wishlistItemViewFactory;

  public function __construct(WishlistItemViewFactory $wishlistItemViewFactory)
  {
    $this->wishlistItemViewFactory = $wishlistItemViewFactory;
  }

  public function create(WishlistInterface $wishlist, $channel, string $locale): WishlistView
  {
    /** @var WishlistView $wishlistView */
    $wishlistView = new WishlistView();

    $wishlistView->title = $wishlist->getTitle();
    $wishlistView->id = $wishlist->getId();
    $wishlistView->slug = $wishlist->getSlug();
    $wishlistView->description = $wishlist->getDescription();
    $wishlistView->public = $wishlist->isPublic();

    /** @var ImageInterface $image */
    foreach ($wishlist->getItems() as $item) {
      $wishlistView->items[] = $this->wishlistItemViewFactory->create($item, $channel, $locale);
    }

    return $wishlistView;
  }

}
