<?php

namespace Bootstrap;

use Closure;

/**
 * Simple PHP Promise implementation.
 *
 * Like JavaScript promises, this class allows you to attach a process to be executed and, when either resolved
 * or rejected, will execute additional functionality based on the result.
 *
 * We mainly use this class to handle processes run inside the Docker image.
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

    public function __construct(callable $attach = null)
    {
        if (!is_null($attach)) {
            $this->attach($attach);
        }
    }

    public function attach(callable $process): static
    {
        $this->attached = Closure::fromCallable($process);

        $this->runIfReady();

        if ($this->executed) {
            return $this->value;
        }

        return $this;
    }

    public function resolve($value): void
    {
        if (is_null($this->fulfill)) {
            return;
        }

        $callable = $this->fulfill;
        $return = $callable($value);

        if (!is_null($return)) {
            $this->value = $return;
        }
    }

    public function reject($value): void
    {
        if (is_null($this->fulfill)) {
            return;
        }

        $callable = $this->reject;
        $return = $callable($value);

        if (!is_null($return)) {
            $this->value = $return;
        }
    }

    public function then(callable $onFulfilled, callable $onRejected = null)
    {
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

    public function catch(callable $onRejected)
    {
        $this->reject = Closure::fromCallable($onRejected);

        $this->runIfReady();

        if ($this->executed) {
            return $this->value;
        }

        return $this;
    }

    public function discard(): static
    {
        $this->runIfReady(true);

        if ($this->executed) {
            return $this->value;
        }

        return $this;
    }

    protected function runIfReady(bool $runAnyway = false): void
    {
        if ($this->executed) {
            return;
        }

        if ((is_null($this->fulfill) || is_null($this->reject)) && !$runAnyway) {
            return;
        }

        try {
            $callable = $this->attached;
            $callable();
        } catch (\Throwable $e) {
            $this->reject($e);
        } finally {
            $this->executed = true;
        }
    }
}
