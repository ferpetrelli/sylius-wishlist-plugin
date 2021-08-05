<?php

declare(strict_types=1);

namespace Webburza\SyliusWishlistPlugin\Factory\View;

class WishlistView
{

  /** @var string */
  public $title;

  /** @var string */
  public $slug;

  /** @var string */
  public $description;

  /** @var int */
  public $id;

  /** @var array */
  public $items = [];

  /** @var boolean */
  public $public;

}
