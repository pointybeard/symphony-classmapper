<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper;

use pointybeard\Helpers\Functions\Flags;
use PDO;

abstract class AbstractFilter implements Interfaces\FilterInterface
{
    protected $field;
    protected $type;
    protected $value;
    protected $operator;

    public const FLAG_PATTERN_EXCLUDE_OPERATOR = 0x0001;

    abstract protected function pattern(): string;

    public function __construct(string $field, $value, int $type = PDO::PARAM_STR, string $operator = Interfaces\FilterInterface::OPERATOR_AND)
    {
        $this->field($field);
        $this->type($type);
        $this->value($value);
        $this->operator($operator);
    }

    public function __get(string $name)
    {
        return $this->$name;
    }

    public function __call(string $name, array $args)
    {
        if (empty($args)) {
            return $this->$name;
        }

        if (method_exists($this, "__{$name}")) {
            call_user_func([$this, "__{$name}"], $args[0]);
        } else {
            $this->$name = $args[0];
        }

        return $this;
    }

    private function __operator(string $operator): void
    {
        if (Interfaces\FilterInterface::OPERATOR_AND != $operator && Interfaces\FilterInterface::OPERATOR_OR != $operator) {
            throw new \Exception("Invalid filter operator '{$operator}' specified.");
        }
        $this->operator = $operator;
    }

    private function __type(int $type): void
    {
        if (!Flags\is_flag_set(PDO::PARAM_BOOL | PDO::PARAM_NULL | PDO::PARAM_INT | PDO::PARAM_STR | PDO::PARAM_LOB | PDO::PARAM_STMT | PDO::PARAM_INPUT_OUTPUT, $type)) {
            throw new \Exception('Invalid filter value type specified. Acceptable types are: PDO::PARAM_BOOL, PDO::PARAM_NULL, PDO::PARAM_INT, PDO::PARAM_STR, PDO::PARAM_LOB, PDO::PARAM_STMT, and PDO::PARAM_INPUT_OUTPUT');
        }
        $this->type = $type;
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field(),
            'type' => $this->type(),
            'value' => $this->value(),
            'operator' => $this->operator(),
            'pattern' => static::pattern(),
        ];
    }

    public function __toString(): string
    {
        return json_encode(static::toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getPattern(?int $flags = null): string
    {
        return trim(
            (
                true == Flags\is_flag_set($flags, self::FLAG_PATTERN_EXCLUDE_OPERATOR)
                ? ''
                : $this->operator()
            ).' '.static::pattern()
        );
    }
}
