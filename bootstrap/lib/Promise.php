<?php

namespace Bootstrap;

class Promise
{
    protected $attached = null;
    protected bool $executed = false;
    protected $fulfill = null;
    protected $reject = null;
    protected $value = null;

    public function attach(callable $process): static
    {
        $this->attached = $process;
        return $this;
    }

    public function resolve($value): void
    {
        if (is_callable($this->fulfill)) {
            $callable = $this->fulfill;
            $return = $callable($value);

            if (!is_null($return)) {
                $this->value = $return;
            }
        }
    }

    public function reject($value): void
    {
        if (is_callable($this->reject)) {
            $callable = $this->reject;
            $return = $callable($value);

            if (!is_null($return)) {
                $this->value = $return;
            }
        }
    }

    public function then(callable $onFulfilled, callable $onRejected = null)
    {
        $this->fulfill = $onFulfilled;
        if (!is_null($onRejected)) {
            $this->reject = $onRejected;
        }

        $this->runIfReady();

        if ($this->executed) {
            return $this->value;
        }

        return $this;
    }

    public function catch(callable $onRejected)
    {
        $this->reject = $onRejected;

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

        if (is_null($this->fulfill) && is_null($this->reject) && !$runAnyway) {
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