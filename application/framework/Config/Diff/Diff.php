<?php

namespace App\Config\Diff;

class Diff
{
    /**
     * @var mixed
     */
    private $variable1;

    /**
     * @var mixed
     */
    private $variable2;

    /**
     * @var string
     */
    private $delimiter;

    public function __construct($variable1, $variable2, string $delimiter = ' / ')
    {
        $this->variable1 = $variable1;
        $this->variable2 = $variable2;
        $this->delimiter = $delimiter;
    }

    /**
     * @return Operation[]
     */
    public function get(): array
    {
        $ret = [];
        $changes = $this->findChanges($this->variable1, $this->variable2);
        foreach ([Type::ADD, Type::UPDATE, Type::REMOVE] as $type) {
            if (empty($changes[$type])) {
                continue;
            }

            foreach ($changes[$type] as $path => $change) {
                $ret[] = new Operation($type, $change['oldValue'], $change['newValue'], $path);
            }
        }

        return $ret;
    }

    /**
     * @param mixed  $oldValue
     * @param mixed  $newValue
     * @param string $prefix
     *
     * @return array
     */
    private function findChanges($oldValue, $newValue, $prefix = ''): array
    {
        $changes = [
            Type::ADD => [],
            Type::UPDATE => [],
            Type::REMOVE => [],
        ];

        $keys = [];
        if (is_array($oldValue)) {
            $keys = array_keys($oldValue);
        }

        if (is_array($newValue)) {
            $keys = array_merge($keys, array_keys($newValue));
        }

        if (!empty($keys)) {
            $keys = array_unique($keys);
        }

        if ($prefix === '' && $oldValue !== $newValue) {
            if ($oldValue === null && $newValue !== null && (!is_array($newValue) || (is_array($newValue) && empty($newValue)))) {
                $changes[Type::ADD][''] = [
                    'oldValue' => $oldValue,
                    'newValue' => $newValue,
                ];

                return $changes;
            } elseif (($newValue === null && is_array($oldValue)) || is_scalar($oldValue) || is_scalar($newValue)) {
                $changes[($newValue !== null ? Type::UPDATE : Type::REMOVE)][''] = [
                    'oldValue' => $oldValue,
                    'newValue' => $newValue,
                ];

                return $changes;
            }
        }

        foreach ($keys as $key) {
            $issetOldValue = isset($oldValue[$key]);
            $issetNewValue = isset($newValue[$key]);
            $oldValueKeyExists = is_array($oldValue) && array_key_exists($key, $oldValue);
            $newValueKeyExists = is_array($newValue) && array_key_exists($key, $newValue);
            if (($issetOldValue && $issetNewValue || $oldValueKeyExists && $newValueKeyExists) && $oldValue[$key] === $newValue[$key]) {
                continue;
            }

            if ($issetNewValue || $newValueKeyExists) {
                if (is_array($newValue[$key])) {
                    $tmpOldValue = $oldValue[$key] ?? null;
                    $tmp = $this->findChanges($tmpOldValue, $newValue[$key], $prefix . $key . '/');

                    if (count($tmp[Type::ADD]) + count($tmp[Type::UPDATE]) + count($tmp[Type::REMOVE]) === 0) {
                        $tmp[!$issetOldValue && !$oldValueKeyExists ? Type::ADD : Type::UPDATE][$prefix . $key] = ['oldValue' => $tmpOldValue, 'newValue' => []];
                    }

                    foreach ([Type::ADD, Type::UPDATE, Type::REMOVE] as $type) {
                        $changes[$type] = array_merge($changes[$type], $tmp[$type]);
                    }
                    unset($tmp);
                } elseif (is_scalar($newValue[$key]) || $newValueKeyExists) {
                    if (!$newValueKeyExists || (!$issetOldValue && !$oldValueKeyExists)) {
                        $changes[Type::ADD][$prefix . $key] = [
                            'oldValue' => null,
                            'newValue' => $newValue[$key],
                        ];
                    } else {
                        $changes[Type::UPDATE][$prefix . $key] = [
                            'oldValue' => $oldValue[$key] ?? null,
                            'newValue' => $newValue[$key],
                        ];
                    }
                }
            } elseif ($issetOldValue || $oldValueKeyExists) {
                if (is_scalar($oldValue[$key])) {
                    $changes[Type::REMOVE][$prefix . $key] = [
                        'oldValue' => $oldValue[$key],
                        'newValue' => null,
                    ];

                } elseif (is_array($oldValue[$key]) || $oldValueKeyExists) {
                    $tmp = $this->findChanges($oldValue[$key], null, $prefix . $key . '/');
                    if (count($tmp[Type::ADD]) + count($tmp[Type::UPDATE]) + count($tmp[Type::REMOVE]) === 0 || count($tmp[Type::REMOVE]) > 0) {
                        $tmp[Type::REMOVE][$prefix . $key] = ['oldValue' => $oldValue[$key], 'newValue' => null];
                    }

                    $changes[Type::REMOVE] = array_merge($changes[Type::REMOVE], $tmp[Type::REMOVE]);
                }
            }
        }

        return $changes;
    }
}
