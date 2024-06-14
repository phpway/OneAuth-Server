<?php

namespace OneAuth\Data;

abstract class AbstractValidatedData extends AbstractData
{
    protected $validationErrors = [];

    /**
     * Get any validation errors found during parsing and validation.
     *
     * @return array  List of validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Validate the data. Should populate $validationErrors with any errors found.
     */
    abstract protected function validate(): void;
}
