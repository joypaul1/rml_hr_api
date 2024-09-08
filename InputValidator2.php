<?php
class InputValidator {
    private $data;
    private $missingFields = [];

    public function __construct($data) {
        $this->data = $data;
    }

    public function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }

    public function validateRequired($fields) {
        $this->missingFields = []; // Reset missing fields before each validation
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || empty($this->data[$field])) {
                $this->missingFields[] = $field; // Add missing field to the list
            }
        }
        return empty($this->missingFields); // Return true if no fields are missing, false otherwise
    }

    public function getMissingFields() {
        return json_encode($this->missingFields); // Return the missing fields as a JSON string
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
