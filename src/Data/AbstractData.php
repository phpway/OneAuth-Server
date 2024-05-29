<?php

namespace OAuth2\Data;

abstract class AbstractData
{
    // data keys to be defined by implementing class
    protected $fields = [];

    // stored data with keys matching $fields
    protected $data = [];

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
