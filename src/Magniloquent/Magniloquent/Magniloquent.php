<?php namespace Magniloquent\Magniloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class Magniloquent extends Model {

    /**
     * @var array The rules used to validate the model
     */
    private $rules = array();

    /**
     * @var \Illuminate\Support\MessageBag The errors generated if validation fails
     */
    private $validationErrors;

    /**
     * @var bool Designates if the model has been saved
     */
    private $saved = false;

    /**
     * @var bool Designates if the model is valid after validation
     */
    private $valid = false;

    /**
     * @var array Custom messages when model doesn't pass validation
     */
    protected $customMessages = array();

    /**
     * The constructor of the model. Takes optional array of attributes.
     * Also, it sets validationErrors to be an empty MessageBag instance.
     *
     * @param array $attributes The attributes of the model to set at instantiation
     */
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
        $this->validationErrors = new MessageBag;
    }

    /**
     * Save
     *
     * Prepare before the Model is actually saved
     * @param bool $touch The option to touch timestamps with all parent models
     */
    public function save(array $new_attributes = array(), $touch = true)
    {
        if(!empty($new_attributes)) $this->hydrate($new_attributes);

        // If the validation failed, return false
        if (!$this->validate($this->attributes)) {
            return false;
        }

        // Purge Redundant fields
        $this->attributes = $this->purgeRedundant($this->attributes);

        // Auto hash passwords
        $this->attributes = $this->autoHash();

        $this->saved = true;

        return $this->performSave(array('touch' => $touch));
    }

    /**
     * Adds attributes to the model
     *
     * @param array $attributes The attributes to add to the model
     *
     * TODO: Remove since not necessary?
     */
    private function hydrate($attributes)
    {
        $this->fill($attributes);
    }

    /**
     * Save the model using Eloquent's save method
     *
     * @param array $options
     *
     * @return bool
     */
    private function performSave(array $options = array())
    {
        return parent::save($options);
    }

    /**
     * Merges saving validation rules in with create and update rules
     * to allow rules to differ on create and update.
     *
     * @return array
     */
    private function mergeRules()
    {
        $rules = static::$rules;
        $output = array();

        if ($this->exists) {
            $merged = array_merge_recursive($rules['save'], $rules['update']);
        } else {
            $merged = array_merge_recursive($rules['save'], $rules['create']);
        }
        foreach ($merged as $field => $rules) {
            if (is_array($rules)) {
                $output[$field] = implode("|", $rules);
            } else {
                $output[$field] = $rules;
            }
        }
        return $output;
    }
}
