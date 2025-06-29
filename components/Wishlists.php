<?php

declare(strict_types=1);

namespace WebBook\Mall\Components;

use Cms\Classes\Theme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use October\Rain\Exception\ValidationException;
use October\Rain\Support\Facades\Flash;
use WebBook\Mall\Classes\Traits\HashIds;
use WebBook\Mall\Classes\User\Auth;
use WebBook\Mall\Models\Cart;
use WebBook\Mall\Models\GeneralSettings;
use WebBook\Mall\Models\ShippingMethod;
use WebBook\Mall\Models\Wishlist;
use WebBook\Mall\Models\WishlistItem;

class Wishlists extends MallComponent
{
    use HashIds;

    /**
     * All wishlists of this user.
     *
     * @var Collection<Wishlist>
     */
    public $items;

    /**
     * Default minimum quantity.
     *
     * @var int
     */
    public $defaultMinQuantity = 1;

    /**
     * Default maximum quantity.
     *
     * @var int
     */
    public $defaultMaxQuantity = 100;

    /**
     * Display the DiscountApplier component.
     *
     * @var bool
     */
    public $showDiscountApplier = true;

    /**
     * Display a tax summary at the end of the cart.
     *
     * @var bool
     */
    public $showTaxes = true;

    /**
     * The currently displayed wishlist.
     *
     * @var Wishlist
     */
    public $currentItem;

    /**
     * True if at least one wishlist has at least one item.
     *
     * @var bool
     */
    public $hasItems = false;

    /**
     * PDF download is available.
     *
     * @var bool
     */
    public $allowPDFDownload = false;

    /**
     * Show shipping method selector.
     *
     * @var bool
     */
    public $showShipping = false;

    /**
     * All available shipping methods.
     *
     * @var Collection<ShippingMethod>|ShippingMethod[]
     */
    public $shippingMethods;

    public function componentDetails()
    {
        return [
            'name'        => 'webbook.mall::lang.components.wishlists.details.name',
            'description' => 'webbook.mall::lang.components.wishlists.details.description',
        ];
    }

    public function defineProperties()
    {
        return [
            'showShipping' => [
                'title'       => 'webbook.mall::lang.components.wishlists.properties.showShipping.title',
                'description' => 'webbook.mall::lang.components.wishlists.properties.showShipping.description',
                'type'        => 'checkbox',
            ],
        ];
    }

    public function init()
    {
        $this->allowPDFDownload = $this->pdfPartialExists();
    }

    public function onRun()
    {
        if ($this->allowPDFDownload && $download = input('download')) {
            return $this->handlePDFDownload($download);
        }

        /** @var Collection<Wishlist>|Wishlist[] items */
        /** @var Wishlist currentItem */
        $this->items       = $this->getWishlists();
        $this->currentItem = $this->items->where('id', $this->decode($this->param('id') ?? ''))->first() ?: $this->items->first();

        $this->handleShipping();

        $this->hasItems = $this->items->contains(fn ($item) => $item->items->count() > 0);
    }

    public function onSelect()
    {
        $this->setCurrentItem();

        return $this->refreshContent();
    }

    public function onRename()
    {
        $this->setCurrentItem();

        $this->currentItem->name = post('name');
        $this->currentItem->save();

        Flash::success(trans('webbook.mall::frontend.wishlist.renamed'));

        return $this->refreshList();
    }

    public function onRemove()
    {
        WishlistItem::where('wishlist_id', $this->decode(post('id')))
            ->where('id', $this->decode(post('item_id')))
            ->delete();

        $this->setCurrentItem();

        return $this->refreshListAndContent();
    }

    public function onUpdateQuantity()
    {
        $this->setCurrentItem();

        $quantity = post('quantity', 1);

        if ($quantity < 1) {
            $quantity = 1;
        }

        if ($quantity > 1000) {
            $quantity = 1000;
        }

        WishlistItem::where('wishlist_id', $this->decode(post('id')))
            ->where('id', $this->decode(post('item_id')))
            ->update(['quantity' => $quantity]);

        $this->setCurrentItem();

        return $this->refreshListAndContent();
    }

    public function onChangeShippingMethod()
    {
        $this->setCurrentItem();

        $method = post('shipping_method_id');

        if (! $method || ! $this->shippingMethods->contains($method)) {
            return $this->controller->run('404');
        }

        $this->currentItem->setShippingMethod(ShippingMethod::where('id', $method)->first());

        $this->setCurrentItem();

        return $this->refreshListAndContent();
    }

    public function onClear()
    {
        WishlistItem::where('wishlist_id', $this->decode(post('id')))
            ->delete();

        $this->setCurrentItem();

        return $this->refreshListAndContent();
    }

    public function onDelete()
    {
        $this->setCurrentItem();

        $this->currentItem->delete();

        Flash::success(trans('webbook.mall::frontend.wishlist.deleted'));

        // Set the current item to the next available record.
        $this->items       = $this->getWishlists();
        $this->currentItem = $this->items->first();

        return $this->refreshListAndContent();
    }

    public function onAddToCart()
    {
        $this->setCurrentItem();

        $allInStock = $this->currentItem->addToCart(Cart::byUser(Auth::user()));

        if (! $allInStock) {
            Flash::warning(trans('webbook.mall::frontend.wishlists.stockmissing'));
        } else {
            Flash::success(trans('webbook.mall::frontend.wishlists.addedtocart'));
        }

        // redirect to the cart page.
        $cartPage = GeneralSettings::get('cart_page');

        return Redirect::to($this->controller->pageUrl($cartPage));
    }

    /**
     * Fetches all wishlists of the currently logged in user
     * or the cart session.
     */
    public function getWishlists()
    {
        return Wishlist::byUser(Auth::user());
    }

    /**
     * Return the wishlist as a PDF.
     *
     * @param string $download
     *
     * @return \Illuminate\Http\Response|string
     */
    protected function handlePDFDownload(string $download)
    {
        $id        = $this->decode($download);
        $wishlists = Wishlist::byUser(Auth::user());

        /** @var Wishlist $wishlist */
        $wishlist = $wishlists->where('id', $id)->first();

        if (! $wishlist) {
            return $this->controller->run('404');
        }

        return $wishlist->getPDF()->stream(sprintf('wishlist-%s.pdf', $download));
    }

    /**
     * Handle the display of shipping methods.
     */
    protected function handleShipping()
    {
        $this->setVar('showShipping', (bool)$this->property('showShipping'));

        if (! $this->showShipping || !$this->currentItem) {
            return;
        }

        $this->shippingMethods = ShippingMethod::getAvailableByWishlist($this->currentItem);

        if ($this->currentItem->shipping_method_id === null) {
            $this->currentItem->setShippingMethod(ShippingMethod::getDefault());
            $this->currentItem = $this->currentItem->fresh('shipping_method');
        }

        return $this->currentItem->validateShippingMethod();
    }

    /**
     * Set the currently active item.
     *
     * @throws ValidationException
     */
    protected function setCurrentItem(): void
    {
        $this->items       = $this->getWishlists();
        $this->currentItem = $this->items->where('id', $this->decode(post('id')))->first();

        if (! $this->currentItem) {
            throw new ValidationException(['id' => 'Invalid wishlist ID specified']);
        }

        $this->handleShipping();
    }

    protected function refreshListAndContent(): array
    {
        return array_merge($this->refreshList(), $this->refreshContent());
    }

    protected function refreshContent(): array
    {
        return [
            '.mall-wishlist-content' => $this->renderPartial(
                $this->alias . '::contents',
                ['item' => $this->currentItem]
            ),
        ];
    }

    protected function refreshList(): array
    {
        return [
            '.mall-wishlists' => $this->renderPartial(
                $this->alias . '::list',
                ['items' => $this->items]
            ),
        ];
    }

    /**
     * Check if the required PDF partial exists.
     * @return bool
     */
    private function pdfPartialExists()
    {
        return file_exists(
            themes_path(
                sprintf('%s/partials/mallPDF/wishlist/default.htm', Theme::getActiveThemeCode())
            )
        );
    }
}
