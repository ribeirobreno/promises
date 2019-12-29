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
     * @param PromiseInterface[] $promises
     */
    public function __construct(array $promises)
    {
        $this->promises = $promises;

        $this->settledPromise = new Promise(function ($resolve, $reject) {
            foreach ($this->promises as $k => $promise) {
                $promise
                    ->then(
                        function ($value) use ($k) {
                            $this->outcomes[$k] = new PromiseOutcome(PromiseOutcome::FULFILLED, $value);
                        },
                        function ($reason) use ($k) {
                            if (is_null($this->firstRejected)) {
                                $this->firstRejected = $k;
                            }
                            $this->outcomes[$k] = new PromiseOutcome(PromiseOutcome::REJECTED, $reason);
                        }
                    )
                    ->finally(function () use ($k, $resolve) {
                        if (is_null($this->first)) {
                            $this->first = $k;
                        }

                        if ($this->allOutcomesCollected()) {
                            call_user_func($resolve, $this->outcomes);
                        }
                    });
            }
        });
    }

    public function getAllPromise(): PromiseInterface
    {
        if ($this->isAnyRejected()) {
            return Promise::reject($this->getFirstRejectedOutcome()->reason);
        } else if ($this->allOutcomesCollected()) {
            return Promise::resolve($this->getValues());
        }

        return new Promise(function ($resolve, $reject) {
            $values = [];
            $toSettle = count($this->promises);
            foreach ($this->promises as $k => $promise) {
                $values[$k] = null;
                $promise
                    ->then(
                        function ($value) use ($resolve, $k, &$toSettle, &$values) {
                            --$toSettle;
                            $values[$k] = $value;
                            if ($toSettle === 0) {
                                call_user_func($resolve, $values);
                            }
                        },
                        function ($reason) use ($reject, &$toSettle) {
                            if ($toSettle > 0) {
                                $toSettle = 0;
                                call_user_func($reject, $reason);
                            }
                        }
                    );
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
                ;
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
