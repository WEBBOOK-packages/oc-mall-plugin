<?php

namespace WebBook\Mall\Classes\Observers;

use WebBook\Mall\Classes\Index\Index;
use WebBook\Mall\Classes\Index\VariantEntry;
use WebBook\Mall\Models\Variant;

class VariantObserver
{
    protected $index;

    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function created(Variant $variant)
    {
        // Make sure deferred bindings are persisted
        if (post('_session_key')) {
            $variant->save(null, post('_session_key'));
        }

        (new ProductObserver($this->index))->updated($variant->product);
    }

    public function updated(Variant $variant)
    {
        // Skip the re-index for the backend relation updates. The re-index will
        // be triggered manually for performance reasons.
        if (!$variant->product || $this->isBackendRelationUpdate()) {
            return;
        }
        (new ProductObserver($this->index))->updated($variant->product);
    }

    public function deleted(Variant $variant)
    {
        (new ProductObserver($this->index))->updated($variant->product);
        $this->index->delete(VariantEntry::INDEX, $variant->id);
    }

    /**
     * @return bool
     */
    protected function isBackendRelationUpdate(): bool
    {
        return app()->runningInBackend()
            && post('_relation_field') === 'variants'
            && post('_relation_mode') === 'form';
    }
}
