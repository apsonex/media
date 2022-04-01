<?php

namespace Apsonex\Media\Concerns;

use Laravel\SerializableClosure\SerializableClosure;

trait HasSerializedCallback
{

    /**
     * Trigger callback with data
     */
    protected function triggerCallback($callback, $data = null)
    {
        if (!$callback || (!$trigger = $this->unSerializeCallback($callback))) return;

        if ($trigger instanceof \Laravel\SerializableClosure\SerializableClosure) {
            $trigger->getClosure()($data);
            return;
        }

        if (is_object($trigger) && method_exists($trigger, 'handle')) {
            $trigger->handle($data);
        }
    }

    /**
     * Unserialize Trigger
     */
    protected function unSerializeCallback(string $callback): mixed
    {
        return rescue(fn() => unserialize($callback));
    }

    /**
     * Serialize onFinishCallback
     */
    protected function serializeCallback(mixed $callback): string|null
    {
        return rescue(function () use ($callback) {
            if ($callback instanceof \Closure) {
                return serialize(new SerializableClosure($callback));
            }

            if (is_object($callback)) return serialize($callback);

            return $this->isValidCallbackClass($callback) ?
                serialize(new $callback) :
                null;
        });
    }

    /**
     * Check trigger provided is valid
     */
    protected function isValidCallbackClass($callback = null): bool
    {
        return $callback &&
            class_exists($callback) &&
            is_callable([$callback, 'handle']);
    }

}