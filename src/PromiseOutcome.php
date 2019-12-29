<?php


namespace RibeiroBreno\Promises;

/**
 * @property-read string $status
 * @property-read mixed $value
 * @property-read mixed $reason
 */
class PromiseOutcome
{

    public const FULFILLED = 'fulfilled';
    public const REJECTED = 'rejected';

    /**
     * @var string Either of the constants above.
     */
    private $status = '';

    /**
     * @var mixed
     */
    private $value = null;

    /**
     * @var mixed
     */
    private $reason = null;

    /**
     * @param string $status
     * @param mixed $valueOrReason
     * @throws \Exception
     */
    public function __construct(string $status, $valueOrReason)
    {
        $this->status = $status;

        switch ($status) {
            case self::FULFILLED:
                $this->value = $valueOrReason;
                break;
            case self::REJECTED:
                $this->reason = $valueOrReason;
                break;
            default:
                throw new \Exception("Invalid promise outcome status.");
        }
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->{$name} ?? null;
    }

}
