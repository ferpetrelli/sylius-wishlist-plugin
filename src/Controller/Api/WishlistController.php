<?php

declare(strict_types=1);

namespace Webburza\SyliusWishlistPlugin\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Bundle\OrderBundle\Factory\AddToCartCommandFactoryInterface;
use Sylius\Bundle\CoreBundle\Form\Type\Order\AddToCartType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webburza\SyliusWishlistPlugin\Factory\View\WishlistListView;
use Webburza\SyliusWishlistPlugin\Factory\View\WishlistViewFactory;
use Webburza\SyliusWishlistPlugin\Form\Type\WishlistType;
use Webburza\SyliusWishlistPlugin\Model\WishlistInterface;
use Webburza\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Webburza\SyliusWishlistPlugin\Model\WishlistItemInterface;
use Webburza\SyliusWishlistPlugin\Provider\LoggedInUserProviderInterface;
use Webburza\SyliusWishlistPlugin\Resolver\ProductVariantFromRequestResolverInterface;
use Webburza\SyliusWishlistPlugin\Resolver\WishlistFromRequestResolverInterface;
use Sylius\ShopApiPlugin\Provider\SupportedLocaleProviderInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;

class WishlistController extends AbstractController
{
  /**
   * @var WishlistRepositoryInterface
   */
  protected $wishlistRepository;

  /**
   * @var LoggedInUserProviderInterface
   */
  protected $loggedInUserProvider;

  /**
   * @var TranslatorInterface
   */
  protected $translator;

  /**
   * @var FormFactoryInterface
   */
  protected $formFactory;

  /**
   * @var bool
   */
  protected $multipleWishlistMode;

  /**
   * @var RepositoryInterface
   */
  protected $wishlistItemRepository;


  /**
   * @var CartContextInterface
   */
  protected $cartContext;

  /**
   * @var ProductVariantRepositoryInterface
   */
  protected $productVariantRepository;

  /**
   * @var CartItemFactoryInterface
   */
  protected $cartItemFactory;

  /**
   * @var AddToCartCommandFactoryInterface
   */
  protected $addToCartCommandFactory;

  /**
   * @var WishlistFromRequestResolverInterface
   */
  protected $wishlistFromRequestResolver;

  /**
   * @var ProductVariantFromRequestResolverInterface
   */
  protected $productVariantFromRequestResolver;

  /**
   * @var FactoryInterface
   */
  protected $wishlistItemFactory;

  /**
   * @var FactoryInterface
   */
  protected $supportedLocaleProvider;

  protected $wishlistViewFactory;
  protected $channelContext;

  /**
   * @param WishlistRepositoryInterface $wishlistRepository
   * @param LoggedInUserProviderInterface $loggedInUserProvider
   * @param TranslatorInterface $translator
   * @param FormFactoryInterface $formFactory
   * @param bool $multipleWishlistMode
   */
  public function __construct(
    WishlistRepositoryInterface $wishlistRepository,
    LoggedInUserProviderInterface $loggedInUserProvider,
    RepositoryInterface $wishlistItemRepository,
    TranslatorInterface $translator,
    FormFactoryInterface $formFactory,
    CartContextInterface $cartContext,
    ProductVariantRepositoryInterface $productVariantRepository,
    CartItemFactoryInterface $cartItemFactory,
    AddToCartCommandFactoryInterface $addToCartCommandFactory,
    WishlistFromRequestResolverInterface $wishlistFromRequestResolver,
    ProductVariantFromRequestResolverInterface $productVariantFromRequestResolver,
    FactoryInterface $wishlistItemFactory,
    WishlistViewFactory $wishlistViewFactory,
    SupportedLocaleProviderInterface $supportedLocaleProvider,
    ChannelContextInterface $channelContext,
    ViewHandlerInterface $viewHandler,
    bool $multipleWishlistMode
  ) {

    $this->wishlistRepository = $wishlistRepository;
    $this->loggedInUserProvider = $loggedInUserProvider;
    $this->formFactory = $formFactory;
    $this->wishlistItemRepository = $wishlistItemRepository;
    $this->translator = $translator;
    $this->cartContext = $cartContext;
    $this->productVariantRepository = $productVariantRepository;
    $this->cartItemFactory = $cartItemFactory;
    $this->addToCartCommandFactory = $addToCartCommandFactory;
    $this->wishlistFromRequestResolver = $wishlistFromRequestResolver;
    $this->productVariantFromRequestResolver = $productVariantFromRequestResolver;
    $this->wishlistItemFactory = $wishlistItemFactory;
    $this->multipleWishlistMode = $multipleWishlistMode;
    $this->wishlistViewFactory = $wishlistViewFactory;
    $this->supportedLocaleProvider = $supportedLocaleProvider;
    $this->viewHandler = $viewHandler;
    $this->channelContext = $channelContext;
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function indexAction(Request $request): Response
  {
    // Throw 404 if not in multiple wishlist mode
    if (!$this->multipleWishlistMode) {
      return $this->redirectToRoute('sylius_shop_account_dashboard');
    }

    $channel = $this->channelContext->getChannel();
    $localeParam = $request->query->get('locale');
    $localeCode = $this->supportedLocaleProvider->provide($localeParam, $channel);

    // Get all wishlists for the current user
    $wishlists = $this->wishlistRepository->findBy([
      'user' => $this->loggedInUserProvider->getUser()
    ], [
      'createdAt' => 'asc'
    ]);


    $wishlistListView = new WishlistListView();
    foreach ($wishlists as $wishlist) {
      $wishlistListView->items[] = $this->wishlistViewFactory->create($wishlist, $channel, $localeCode);
    }

    return $this->viewHandler->handle(
      View::create(
        $wishlistListView,
        Response::HTTP_OK
      )
    );
    // return new JsonResponse($wishlists, Response::HTTP_CREATED);

  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function createAction(Request $request): Response
  {
    // Throw 404 if not in multiple wishlist mode
    if (!$this->multipleWishlistMode) {
      throw $this->createNotFoundException();
    }

    // Get wishlist form and handle request
    $form = $this->formFactory->create(WishlistType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      /** @var WishlistInterface $wishlist */
      $wishlist = $form->getData();

      // Set wishlist user
      $wishlist->setUser($this->loggedInUserProvider->getUser());

      // Persist changes
      $this->wishlistRepository->add($wishlist);

      // If this was an AJAX request, return appropriate response
      if ($request->getRequestFormat() != 'html') {
        return new JsonResponse(null, Response::HTTP_CREATED);
      }

      // Set success message
      // $this->addFlash(
      //   'success',
      //   $this->translator->trans('webburza_sylius_wishlist.flash.updated')
      // );

      // return $this->redirectToRoute('webburza_sylius_wishlist_account_wishlist_edit', [
      //   'id' => $wishlist->getId()
      // ]);
    } else {
      return new JsonResponse($this->getFormErrors($form), Response::HTTP_BAD_REQUEST);
    }

    // return $this->render('@WebburzaSyliusWishlistPlugin/Account/create.html.twig', [
    //   'form' => $form->createView()
    // ]);
  }


  /**
   * @param Request $request
   *
   * @return Response
   */
  public function removeAction(Request $request): Response
  {
    /** @var WishlistInterface $wishlist */
    $wishlist = $this->wishlistRepository->findOneBy([
      'id'   => $request->get('id'),
      'user' => $this->loggedInUserProvider->getUser()
    ]);

    // Throw exception if not found
    if (!$wishlist) {
      throw $this->createNotFoundException();
    }

    // Remove the wishlist
    $this->wishlistRepository->remove($wishlist);

    return new JsonResponse(null, Response::HTTP_NO_CONTENT);
  }


  /**
   * @param Request $request
   *
   * @return Response
   */
  public function updateAction(Request $request): Response
  {
    // Get the wishlist
    $wishlist = $this->wishlistRepository->findOneBy([
      'id'   => $request->get('id'),
      'user' => $this->loggedInUserProvider->getUser()
    ]);

    if (!$wishlist) {
      throw $this->createNotFoundException();
    }

    // Get wishlist form
    $form = $this->formFactory->create(WishlistType::class, $wishlist);

    if (in_array($request->getMethod(), ['PUT', 'POST'])) {
      if ($form->handleRequest($request)->isValid()) {
        /** @var WishlistInterface $wishlist */
        $wishlist = $form->getData();

        // Set wishlist user
        $wishlist->setUser($this->loggedInUserProvider->getUser());

        // Persist changes
        $this->wishlistRepository->add($wishlist);

        return new JsonResponse(null, Response::HTTP_OK);
      }
    }

  }

  /* ITEMS ACTIONS */


  /**
   * @param Request $request
   *
   * @return Response
   */
  public function addItemAction(Request $request) : Response
  {

      // Get (or create) the wishlist to which the item should be added
      $wishlist = $this->wishlistFromRequestResolver->resolve($request);

      // Get the product variant to be added to wishlist
      $productVariant = $this->productVariantFromRequestResolver->resolve($request);

      // Prevent duplicates
      if ($wishlist->containsVariant($productVariant)) {
        return new JsonResponse(null, Response::HTTP_CONFLICT);
      }

      /** @var WishlistItemInterface $wishlistItem */
      $wishlistItem = $this->wishlistItemFactory->createNew();
      $wishlistItem->setProductVariant($productVariant);

      $wishlist->addItem($wishlistItem);

      // Persist the wishlist item
      $this->wishlistItemRepository->add($wishlistItem);

      return new JsonResponse(null, Response::HTTP_CREATED);
  }

  /**
   * @param Request $request
   *
   * @return Response
   */
  public function removeItemAction(Request $request): Response
  {
    /** @var WishlistItemInterface $wishlistItem */
    $wishlistItem = $this->wishlistItemRepository->find($request->get('id'));

    // Check if wishlist item found
    if (!$wishlistItem) {
      throw $this->createNotFoundException();
    }

    // Check if this item belongs to the current customer trying to remove it
    if ($wishlistItem->getWishlist()->getUser() != $this->getUser()) {
      throw $this->createAccessDeniedException();
    }

    // Remove the item from the repository
    $this->wishlistItemRepository->remove($wishlistItem);

    return new JsonResponse(null, Response::HTTP_NO_CONTENT);

  }

  /**
   * @param Request $request
   *
   * @return Response $response
   */
  public function clearAction(Request $request): Response
  {
    /** @var WishlistInterface $wishlist */
    $wishlist = $this->wishlistRepository->findOneBy([
      'id' => $request->get('id'),
      'user' => $this->getUser()
    ]);

    // Check if wishlist found
    if (!$wishlist) {
      throw $this->createNotFoundException();
    }

    // Clear items from wishlist
    $wishlist->clearItems();

    // Persist changes
    $this->wishlistRepository->add($wishlist);

    return new JsonResponse(null, 200);
  }


  // protected function getFormErrors($form)
  // {
  //   $errors = array();

  //   foreach ($form->getErrors() as $key => $error) {
  //     if ($form->isRoot()) {
  //       $errors['#'][] = $error->getMessage();
  //     } else {
  //       $errors[] = $error->getMessage();
  //     }
  //   }

  //   foreach ($form->all() as $child) {
  //     if (!$child->isValid()) {
  //       $errors[$child->getName()] = $this->getErrorMessages($child);
  //     }
  //   }

  //   return $errors;
  // }


}
