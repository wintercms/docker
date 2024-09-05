<?php

namespace Bootstrap;

use Closure;

/**
 * Simple PHP Promise implementation.
 *
 * Like JavaScript promises, this class allows you to attach a process to be executed when ready and when resolved
 * or rejected, will asynchronously execute additional functionality based on the result.
 *
 * We mainly use this class to handle processes run inside the Docker image.
 *
 * A promise is ready when:
 *
 * - A process is attached.
 * - One of the following is done:
 *   - A `then()` closure is provided to handle both a resolution or rejection.
 *   - A `then()` closure is provided to handle a resolution, and a `catch()` closure is provided to handle a
 *     rejection.
 *   - A `then()` closure is provided to handle a resolution, and `discard()` is called to discard a rejection.
 *   - A `catch()` closure is provided to handle a rejection, and `discard()` is called to discard a resolution.
 *
 * @author Ben Thomson <git@alfreido.com>
 * @copyright 2024 Winter CMS Maintainers
 */
class Promise
{
    protected ?Closure $attached = null;
    protected bool $executed = false;
    protected ?Closure $fulfill = null;
    protected ?Closure $reject = null;
    protected $value = null;

    /**
     * Constructor.
     *
     * An attached callable may be provided which will be executed once the promise is ready.
     */
    public function __construct(callable $attach = null)
    {
        if (!is_null($attach)) {
            $this->attach($attach);
        }
    }

    /**
     * Attaches the process that will resolve or reject the promise.
     */
    public function attach(callable $process): static
    {
        if ($this->executed) {
            return $this;
        }

        $this->attached = Closure::fromCallable($process);

        // If attach is called after a resolution or rejection handler is defined, assume the other is discarded.
        $this->runIfReady(!is_null($this->fulfill) || !is_null($this->reject));

        if ($this->executed) {
            return $this->value;
        }

        return $this;
    }

    /**
     * Resolves the promise, optionally with a value.
     */
    public function resolve($value = null): void
    {
        if ($this->executed) {
            return;
        }

        if (is_null($this->fulfill)) {
            return;
        }

        $callable = $this->fulfill;
        $return = $callable($value);

        if (!is_null($return)) {
            $this->value = $return;
        }
    }

    /**
     * Rejects the promise, optionally with a value.
     *
     * @param mixed $value
     * @return void
     */
    public function reject($value = null): void
    {
        if ($this->executed) {
            return;
        }

        if (is_null($this->fulfill)) {
            return;
        }

        $callable = $this->reject;
        $return = $callable($value);

        if (!is_null($return)) {
            $this->value = $return;
        }
    }

    /**
     * Defines a resolution and optionally a rejection handler.
     *
     * @return static|mixed Returns the promise if not ready, otherwise, returns the value.
     */
    public function then(callable $onFulfilled, callable $onRejected = null)
    {
        if ($this->executed) {
            return $this;
        }

        $this->fulfill = Closure::fromCallable($onFulfilled);
        if (!is_null($onRejected)) {
            $this->reject = Closure::fromCallable($onRejected);
        }

        $this->runIfReady();

        if ($this->executed) {
            return $this->value;
        }

        return $this;
    }

    /**
     * Defines a rejection handler.
     *
     * @return static|mixed Returns the promise if not ready, otherwise, returns the value.
     */
    public function catch(callable $onRejected)
    {
        if ($this->executed) {
            return $this;
        }

        $this->reject = Closure::fromCallable($onRejected);

        $this->runIfReady();

        if ($this->executed) {
            return $this->value;
        }

        return $this;
    }

    /**
     * Discards any further handling of the promise and executes the process.
     *
     * @return static|mixed Returns the promise if not ready, otherwise, returns the value.
     */
    public function discard(): static
    {
        if ($this->executed) {
            return $this;
        }

        $this->runIfReady(true);

        if ($this->executed) {
            return $this->value;
        }

        return $this;
    }

    /**
     * Execute the attached process when the promise is ready.
     */
    protected function runIfReady(bool $runAnyway = false): void
    {
        if ($this->executed || is_null($this->attached)) {
            return;
        }

        if ((is_null($this->fulfill) || is_null($this->reject)) && !$runAnyway) {
            return;
        }

        try {
            $callable = $this->attached;
            $callable(
                Closure::fromCallable([$this, 'resolve']),
                Closure::fromCallable([$this, 'reject'])
            );
        } catch (\Throwable $e) {
            $this->reject($e);
        } finally {
            $this->executed = true;
        }
    }
}
