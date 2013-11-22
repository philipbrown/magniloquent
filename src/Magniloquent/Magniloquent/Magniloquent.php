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
    protected static $rules = array(
        'save' => array(),
        'create' => array(),
        'update' => array()
    );

    /**
     * @var array The merged rules created when validating
     */
    protected $mergedRules = array();

    /**
     * @var \Illuminate\Support\MessageBag The errors generated if validation fails
     */
    protected $validationErrors;

    /**
     * @var bool Designates if the model has been saved
     */
    protected $saved = false;

    /**
     * @var bool Designates if the model is valid after validation
     */
    protected $valid = false;

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
     * Prepare before the Model is actually saved
     *
     * @param array $new_attributes New attributes to fill onto the model before saving
     * @param bool $forceSave Whether to validate or not. Defaults to validating before saving
     * @param bool  $touch The option to touch timestamps with all parent models
     *
     * @return bool
     */
    public function save(array $new_attributes = array(), $forceSave = false, $touch = true)
    {
        if(!empty($new_attributes))
            $this->fill($new_attributes);

        if (!$forceSave) {
            // If the validation failed, return false
            if (!$this->valid && !$this->validate())
                return false;
        }

        // Purge unnecessary fields
        $this->purgeUnneeded();

        // Auto hash passwords
        $this->autoHash();

        $this->saved = true;

        return parent::save(array('touch' => $touch));
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
        $this->mergedRules = $output;
    }

    /**
     * Performs validation on the model and return whether it
     * passed or failed
     *
     * @return bool
     */
    public function validate()
    {
        // Merge the rules arrays into one array
        $this->mergeRules();

        $validation = Validator::make($this->attributes, $this->mergedRules, $this->customMessages);

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
     * @return array
     */
    private function purgeUnneeded()
    {
        $clean = array();
        foreach ($this->attributes as $key => $value) {
            if (!Str::endsWith($key, '_confirmation') && !Str::startsWith($key, '_')) {
                $clean[$key] = $value;
            }
        }
        $this->attributes = $clean;
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
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if ($this->valid) $this->valid = false;
        return parent::setAttribute($key, $value);
    }

}
