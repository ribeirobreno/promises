<?php


namespace RibeiroBreno\Promises\Tests\Util;

use RibeiroBreno\Promises\Util\Functions;

class InspectableCallback
{

    /**
     * @var array
     */
    private $lastArgs = [];

    /**
     * @var mixed
     */
    private $lastReturn = null;

    /**
     * @var mixed
     */
    private $shouldReturn = null;

    /**
     * @var int
     */
    private $totalCalls = 0;

    /**
     * @var float
     */
    private $lastCallTimestamp = -INF;

    /**
     * @var \Throwable|null
     */
    private $throw = null;

    /**
     * @var \Throwable|null
     */
    private $caught = null;

    /**
     * @param mixed $shouldReturn
     */
    public function __construct($shouldReturn = null)
    {
        $this->reset();
        $this->setShouldReturn($shouldReturn);
    }

    /**
     * @return InspectableCallback
     */
    public static function noReturn(): InspectableCallback
    {
        $cb = new self();
        $cb->shouldNotReturn();

        return $cb;
    }

    /**
     * @return InspectableCallback
     */
    public static function identity(): InspectableCallback
    {
        return new self();
    }

    /**
     * @return array
     */
    public function getLastArgs(): array
    {
        return $this->lastArgs;
    }

    /**
     * @return mixed
     */
    public function getLastReturn()
    {
        return $this->lastReturn;
    }

    /**
     * @return int
     */
    public function getTotalCalls(): int
    {
        return $this->totalCalls;
    }

    /**
     * @return float
     */
    public function getLastCallTimestamp(): float
    {
        return $this->lastCallTimestamp;
    }

    /**
     * @return \Throwable|null
     */
    public function getCaught(): ?\Throwable
    {
        return $this->caught;
    }

    /**
     * @param mixed $return
     */
    public function setShouldReturn($return)
    {
        $this->shouldReturn = Functions::toClosure($return);
    }

    public function shouldNotReturn()
    {
        $this->shouldReturn = null;
    }

    /**
     * @param \Throwable|null $throw
     */
    public function setThrow(?\Throwable $throw): void
    {
        $this->throw = $throw;
    }

    public function reset()
    {
        $this->lastArgs = [];
        $this->lastReturn = null;
        $this->setShouldReturn(null);
        $this->totalCalls = 0;
        $this->throw = null;
        $this->caught = null;
    }

    /**
     * @param mixed ...$args
     * @return mixed
     * @throws \Throwable
     */
    public function __invoke(...$args)
    {
        $this->lastCallTimestamp = microtime(true);
        $this->lastArgs = $args;
        ++$this->totalCalls;

        if ($this->throw instanceof \Throwable) {
            throw $this->throw;
        }

        $this->caught = null;
        $this->lastReturn = null;
        if (is_callable($this->shouldReturn)) {
            try {
                $this->lastReturn = call_user_func_array($this->shouldReturn, $args);
            } catch (\Throwable $caught) {
                $this->caught = $caught;
            }
        }

        return $this->lastReturn;
    }

}
