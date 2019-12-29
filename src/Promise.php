<?php


namespace RibeiroBreno\Promises;


use RibeiroBreno\Promises\Collections\FunctionCollection;
use RibeiroBreno\Promises\Collections\PromiseCollection;
use RibeiroBreno\Promises\Util\Functions;

class Promise implements PromiseInterface
{

    /**
     * @var FunctionCollection
     */
    protected $onFulfilled = null;

    /**
     * @var FunctionCollection
     */
    protected $onRejected = null;

    /**
     * @var FunctionCollection
     */
    protected $onFinally = null;

    /**
     * @var bool
     */
    protected $settled = false;

    /**
     * @var bool
     */
    protected $rejected = false;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var mixed
     */
    protected $rejectionReason;

    /**
     * @inheritDoc
     */
    public function __construct(callable $executor)
    {
        $this->onFulfilled = new FunctionCollection();
        $this->onRejected = new FunctionCollection();
        $this->onFinally = new FunctionCollection();

        call_user_func($executor, $this->getResolveHandler(), $this->getRejectHandler());
    }

    /**
     * @return bool
     */
    public function isSettled(): bool
    {
        return $this->settled;
    }

    /**
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->rejected;
    }

    /**
     * @return \Closure
     */
    protected function getResolveHandler()
    {
        return function ($value) {
            if (!$this->settled) {
                $this->value = $value;
                $this->settled = true;
                $this->rejected = false;
                call_user_func($this->onFulfilled, $this->value);
                call_user_func($this->onFinally);
            }
        };
    }

    /**
     * @return \Closure
     */
    protected function getRejectHandler()
    {
        return function ($reason) {
            if (!$this->settled) {
                $this->rejectionReason = $reason;
                $this->settled = true;
                $this->rejected = true;
                call_user_func($this->onRejected, $this->rejectionReason);
                call_user_func($this->onFinally);
            }
        };
    }

    /**
     * @inheritDoc
     */
    public static function all(array $promises): PromiseInterface
    {
        return (new PromiseCollection($promises))->getAllPromise();
    }

    /**
     * @inheritDoc
     */
    public static function allSettled(array $promises): PromiseInterface
    {
        return (new PromiseCollection($promises))->getAllSettledPromise();
    }

    /**
     * @inheritDoc
     */
    public static function race(array $promises): PromiseInterface
    {
        return (new PromiseCollection($promises))->getRacePromise();
    }

    /**
     * @inheritDoc
     */
    public static function reject($reason): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($reason) {
            call_user_func($reject, $reason);
        });
    }

    /**
     * @inheritDoc
     */
    public static function resolve($value): PromiseInterface
    {
        return new Promise(function (callable $resolve) use ($value) {
            call_user_func($resolve, $value);
        });
    }

    /**
     * @inheritDoc
     */
    public function catch(callable $callback): PromiseInterface
    {
        return $this->then(null, $callback);
    }

    /**
     * @inheritDoc
     * @todo Callback returns a promise.
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null): PromiseInterface
    {
        $result = null;
        if ($this->settled) {
            if ($this->rejected) {
                try {
                    $return = Functions::callOrIdentity($onRejected, $this->rejectionReason);
                } catch (\Throwable $t) {
                    $return = $t;
                }
                $result = Promise::reject($return);
            } else {
                try {
                    $return = Functions::callOrIdentity($onFulfilled, $this->value);
                    $result = Promise::resolve($return);
                } catch (\Throwable $t) {
                    $result = Promise::reject($t);
                }
            }
        } else {
            $result = new Promise(function ($resolve, $reject) use ($onFulfilled, $onRejected) {
                if (is_callable($onFulfilled)) {
                    $this->onFulfilled->addCallable(function ($value) use ($resolve, $reject, $onFulfilled) {
                        try {
                            $return = call_user_func($onFulfilled, $value) ?? $value;
                            call_user_func($resolve, $return);
                        } catch (\Throwable $t) {
                            call_user_func($reject, $t);
                        }
                    });
                }

                if (is_callable($onRejected)) {
                    $this->onRejected->addCallable(function ($reason) use ($reject, $onRejected) {
                        try {
                            $return = call_user_func($onRejected, $reason) ?? $reason;
                        } catch (\Throwable $t) {
                            $return = $t;
                        }
                        call_user_func($reject, $return);
                    });
                }
            });
        }

        return $result;
    }

    /**
     * @inheritDoc
     * @todo Callback returns a promise.
     */
    public function finally(callable $callback): PromiseInterface
    {
        if ($this->settled) {
            try {
                $param = call_user_func($callback);
                $thrown = false;
            } catch (\Throwable $t) {
                $param = $t;
                $thrown = true;
            }

            if ($this->rejected || $thrown) {
                $result = Promise::reject($param);
            } else {
                $result = Promise::resolve($param);
            }
        } else {
            $result = new Promise(function ($resolve, $reject) use ($callback) {
                $this->onFinally->addCallable(function () use ($resolve, $reject, $callback) {
                    try {
                        $return = call_user_func($callback);
                        call_user_func($this->rejected ? $reject : $resolve, $return);
                    } catch (\Throwable $t) {
                        call_user_func($reject, $t);
                    }
                });
            });
        }

        return $result;
    }
}
