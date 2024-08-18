<?php

namespace OneAuth\Data;

abstract class AbstractData
{
    // data keys to be defined by implementing class
    protected $fields = [];

    // subset of $fields that are optional
    protected $optionalFields = [];

    // stored data with keys matching $fields
    protected $data = [];

    protected function getRequiredFields(): array
    {
        return array_diff($this->fields, $this->optionalFields);
    }

    public function get(string $field)
    {
        return $this->data[$field] ?? null;
    }

    public function set(string $field, $value): AbstractData
    {
        if (in_array($field, $this->fields)) {
            $this->data[$field] = $value;
        }
        return $this;
    }

    public function createFromArray(array $array): AbstractData
    {
        foreach ($this->fields as $field) {
            $this->set($field, $array[$field] ?? null);
        }
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
