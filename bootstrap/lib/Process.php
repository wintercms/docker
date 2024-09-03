<?php

namespace Bootstrap;

class Process
{
    protected string $command;
    protected ?string $cwd = null;
    protected ?array $env = null;
    protected $process = null;
    protected ?Promise $promise = null;
    protected array $pipes = [];
    protected ?bool $ok = null;
    protected bool $executed = false;
    protected ?int $pid = null;
    protected ?int $exitCode = null;
    protected int $timeout = 60;
    protected ?float $startTime = null;

    public function __construct(Promise $promise, string $command, ?string $cwd = null, ?array $env = null, int $timeout = 60)
    {
        $this->promise = $promise;
        $this->command = $command;
        $this->cwd = $cwd;
        $this->env = $env;
        $this->timeout = $timeout;

        $this->createProcess();
    }

    public static function exec(string $command, ?string $cwd = null, ?array $env = null, int $timeout = 60): Promise
    {
        $promise = new Promise();
        $promise->attach(function () use ($promise, $command, $cwd, $env, $timeout) {
            new static($promise, $command, $cwd, $env, $timeout);
        });
        return $promise;
    }

    public function ok(): ?bool
    {
        return $this->ok;
    }

    public function executed(): ?bool
    {
        return $this->executed;
    }

    public function pid(): ?int
    {
        return $this->pid;
    }

    public function stdout(): string
    {
        return $this->pipes[1] ?? '';
    }

    public function stderr(): string
    {
        return $this->pipes[2] ?? '';
    }

    protected function createProcess()
    {
        $this->process = proc_open($this->command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $this->pipes, $this->cwd, $this->env);

        if (!is_resource($this->process)) {
            $this->ok = false;
            $this->executed = true;
            throw new \Exception('Could not create process');
        }

        $this->startTime = microtime(true);

        $this->beginStatusLoop();
    }

    protected function beginStatusLoop()
    {
        $this->executed = true;

        while (true) {
            $status = proc_get_status($this->process);

            $this->pid = $status['pid'];

            if (!$status['running']) {
                $this->exitCode = $status['exitcode'];

                if ($this->exitCode === 0) {
                    $this->ok = true;
                } else {
                    $this->ok = false;
                }

                $this->closeProcess();

                if ($this->ok) {
                    $this->promise->resolve($this);
                } else {
                    $this->promise->reject($this);
                }
                break;
            }

            if (microtime(true) > ($this->startTime + ($this->timeout * 1000000))) {
                $this->terminateProcess();
                $this->closeProcess();

                $this->ok = false;
                throw new \Exception('Process timed out');

                break;
            }

            usleep(100000);
        }
    }

    protected function closeProcess()
    {
        foreach ($this->pipes as $key => $pipe) {
            $meta = stream_get_meta_data($pipe);
            if (is_resource($pipe) && $meta['mode'] === 'r') {
                $this->pipes[$key] = trim(stream_get_contents($pipe) ?: '');
            } else {
                $this->pipes[$key] = null;
            }

            fclose($pipe);
        }

        proc_close($this->process);
    }

    protected function terminateProcess()
    {
        proc_terminate($this->process, SIGTERM);
    }
}
