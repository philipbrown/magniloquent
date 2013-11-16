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

        // Purge unnecessary fields
        $this->attributes = $this->purgeUnneeded($this->attributes);

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

    /**
     * Performs validation on the model and return whether it
     * passed or failed
     *
     * @param array $attributes The attributes to be validated
     *
     * @return bool
     */
    public function validate($attributes)
    {
        // Merge the rules arrays into one array
        $this->rules = $this->mergeRules();

        $validation = Validator::make($attributes, $this->rules, $this->customMessages);

        if ($validation->passes()) {
            $this->valid = true;
            return true;
        }

        $this->validationErrors = $validation->messages();

        return false;
    }

    /**
     * Returns validationErrors MessageBag
     *
     * @return MessageBag
     */
    public function errors()
    {
        return $this->validationErrors;
    }

    /**
     * Returns if model has been saved to the database
     *
     * @return bool
     */
    public function isSaved()
    {
        return $this->saved;
    }

    /**
     * Returns if the model has passed validation
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Purges unneeded fields by getting rid of all attributes
     * ending in '_confirmation' or starting with '_'
     *
     * @param $attributes
     *
     * @return array
     */
    private function purgeUnneeded($attributes)
    {
        $clean = array();
        foreach ($attributes as $key => $value) {
            if (!Str::endsWith($key, '_confirmation') && !Str::startsWith($key, '_')) {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    /**
     * Auto-hashes the password parameter if it exists
     *
     * @return array
     */
    private function autoHash()
    {
        if (isset($this->attributes['password'])) {
            if ($this->attributes['password'] != $this->getOriginal('password')) {
                $this->attributes['password'] = Hash::make($this->attributes['password']);
            }
        }
        return $this->attributes;
    }

}
