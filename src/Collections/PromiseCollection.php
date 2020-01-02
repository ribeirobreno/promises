<?php

namespace RibeiroBreno\Promises\Collections;

use RibeiroBreno\Promises\Promise;
use RibeiroBreno\Promises\PromiseInterface;
use RibeiroBreno\Promises\PromiseOutcome;

class PromiseCollection
{

    /**
     * @var PromiseInterface[]
     */
    private $promises = [];

    /**
     * @var PromiseOutcome[]
     */
    private $outcomes = [];

    /**
     * @var int|string|null
     */
    private $first = null;

    /**
     * @var int|string|null
     */
    private $firstRejected = null;

    /**
     * @var PromiseInterface
     */
    private $settledPromise;

    /**
     * @var FunctionCollection
     */
    private $onFirst;

    /**
     * @var FunctionCollection
     */
    private $onAllSettled;

    /**
     * @param PromiseInterface[] $promises
     */
    public function __construct(array $promises)
    {
        $this->promises = $promises;

        $this->onFirst = new FunctionCollection();
        $this->onAllSettled = new FunctionCollection();

        $this->settledPromise = new Promise(function ($resolve, $reject) {
            $this->onAllSettled->addCallable($resolve);
            foreach ($this->promises as $k => $promise) {
                $promise
                    ->then($this->getPromiseOutcomeFulfilledCallback($k), $this->getPromiseOutcomeRejectedCallback($k))
                    ->finally($this->getPromiseFinishedCallback($k));
            }
        });
    }

    /**
     * @param string|int $k
     * @return \Closure
     */
    private function getPromiseOutcomeFulfilledCallback($k)
    {
        return function ($value) use ($k) {
            $this->outcomes[$k] = new PromiseOutcome(PromiseOutcome::FULFILLED, $value);
        };
    }

    /**
     * @param string|int $k
     * @return \Closure
     */
    private function getPromiseOutcomeRejectedCallback($k)
    {
        return function ($reason) use ($k) {
            if (is_null($this->firstRejected)) {
                $this->firstRejected = $k;
            }
            $this->outcomes[$k] = new PromiseOutcome(PromiseOutcome::REJECTED, $reason);
        };
    }

    /**
     * @param string|int $k
     * @return \Closure
     */
    private function getPromiseFinishedCallback($k)
    {
        return function () use ($k) {
            if (is_null($this->first)) {
                $this->first = $k;
                $this->onFirst->callAll();
            }

            if ($this->allOutcomesCollected()) {
                $this->onAllSettled->callAll($this->outcomes);
            }
        };
    }

    public function getAllPromise(): PromiseInterface
    {
        if ($this->isAnyRejected()) {
            return Promise::reject($this->getFirstRejectedOutcome()->reason);
        } else if ($this->allOutcomesCollected()) {
            return Promise::resolve($this->getValues());
        }

        return new Promise(function ($resolve, $reject) {
            foreach ($this->promises as $k => $promise) {
                $promise->finally(function () use ($resolve, $reject) {
                    if ($this->isAnyRejected()) {
                        call_user_func($reject, $this->getFirstRejectedOutcome()->reason);
                    } else if ($this->allOutcomesCollected()) {
                        call_user_func($resolve, $this->getValues());
                    }
                });
            }
        });
    }

    public function getAllSettledPromise(): PromiseInterface
    {
        if ($this->allOutcomesCollected()) {
            return Promise::resolve($this->outcomes);
        }

        return $this->settledPromise->finally(function () {
            return $this->outcomes;
        });
    }

    public function getRacePromise(): PromiseInterface
    {
        $first = $this->getFirstOutcome();
        if (is_null($first)) {
            return new Promise(function ($resolve, $reject) {
                $this->onFirst->addCallable(function () use ($resolve, $reject) {
                    $first = $this->getFirstOutcome();
                    if ($first->status === PromiseOutcome::FULFILLED) {
                        call_user_func($resolve, $first->value);
                    } else {
                        call_user_func($reject, $first->reason);
                    }
                });
            });
        }

        if ($first->status === PromiseOutcome::FULFILLED) {
            return Promise::resolve($first->value);
        }

        return Promise::reject($first->reason);
    }

    /**
     * @return bool
     */
    public function allOutcomesCollected(): bool
    {
        return count($this->promises) === count($this->outcomes);
    }

    /**
     * @return bool
     */
    public function isAnyRejected(): bool
    {
        return !is_null($this->firstRejected);
    }

    /**
     * @return PromiseOutcome[]
     */
    public function getOutcomes(): array
    {
        return $this->outcomes;
    }

    /**
     * @return PromiseOutcome|null
     */
    public function getFirstOutcome(): ?PromiseOutcome
    {
        return $this->outcomes[$this->first] ?? null;
    }

    /**
     * @return PromiseOutcome[]
     */
    public function getRejectedOutcomes(): array
    {
        return array_filter($this->outcomes, function (PromiseOutcome $outcome) {
            return $outcome->status === PromiseOutcome::REJECTED;
        });
    }

    /**
     * @return PromiseOutcome|null
     */
    public function getFirstRejectedOutcome(): ?PromiseOutcome
    {
        return $this->outcomes[$this->firstRejected] ?? null;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return array_map(function (PromiseOutcome $outcome) {
            return $outcome->value;
        }, $this->outcomes);
    }

}
