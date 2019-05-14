<?php declare(strict_types=1);

namespace Symphony\ClassMapper\ClassMapper;

abstract class AbstractFilter implements Interfaces\FilterInterface
{
    const OPERATOR_OR = "OR";
    const OPERATOR_AND = "AND";

    public $operator;

    public function __construct(int $operator=self::OPERATOR_AND)
    {
        $this->operator = $operator;

        if ($this->operator != self::OPERATOR_AND && $this->operator != self::OPERATOR_OR) {
            throw new \Exception("Invalid filter operator '{$this->operator}' specified.");
        }
    }

    public function toArray() : array
    {
        return [
            "operator" => $this->operator
        ];
    }

    public function operator() : string
    {
        return $this->operator;
    }
}
