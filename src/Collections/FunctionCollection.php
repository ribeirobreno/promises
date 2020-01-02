<?php


namespace RibeiroBreno\Promises\Collections;

/**
 * @mixin callable
 */
class FunctionCollection
{

    /**
     * @var callable[]|\Closure[]
     */
    private $callables = [];

    /**
     * @param mixed ...$args
     * @return array
     */
    public function __invoke(...$args)
    {
        $results = [];
        foreach ($this->callables as $k => $fn) {
            $results[$k] = call_user_func_array($fn, $args);
        }

        return $results;
    }

    /**
     * @param mixed ...$args
     * @return array
     */
    public function callAll(...$args): array
    {
        return call_user_func_array($this, $args);
    }

    /**
     * @return callable[]|\Closure[]
     */
    public function getCallables()
    {
        return $this->callables;
    }

    /**
     * @param callable|\Closure $callable
     */
    public function addCallable(callable $callable)
    {
        $this->callables[] = $callable;
    }

}
