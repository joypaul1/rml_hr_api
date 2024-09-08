<?php
class InputValidator {
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }

    public function validateRequired($fields) {
        $missingFields = [];
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || empty($this->data[$field])) {
                $missingFields[] = $field; // Add missing field to the array
            }
        }
        return $missingFields; // Return the array of missing fields
    }

    public function sanitizeInputs() {
        foreach ($this->data as $key => $value) {
            $this->data[$key] = $this->sanitize($value);
        }
    }

    public function get($field) {
        return $this->data[$field] ?? '';
    }
}
?>
