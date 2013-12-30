<?php namespace Magniloquent\Magniloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class Magniloquent extends Model {

    /**
     * @var array The rules used to validate the model
     */
    protected static $rules = array(
        'save'   => array(),
        'create' => array(),
        'update' => array()
    );

    /**
     * @var array The attributes to purge before saving
     */
    protected static $purgeable = array();

    /**
     * @var array The relationships this model has to other models
     */
    protected static $relationships = array();

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
     * Handle dynamic method calls into the method.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (array_key_exists($method, static::$relationships))
            return $this->callRelationships($method);

        return parent::__call($method, $parameters);
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, static::$relationships))
        {
            // If the relation is already loaded, just return it, 
            // otherwise it will query the relation twice.
            if (array_key_exists($key, $this->relations))
            {
                return $this->relations[$key];
            }
            
            $relation = $this->callRelationships($key);

            return $relation->getResults();
        }

        return parent::__get($key);
    }

    /**
     * Prepare before the Model is actually saved
     *
     * @param array $new_attributes New attributes to fill onto the model before saving
     * @param bool  $forceSave      Whether to validate or not. Defaults to validating before saving
     *
     * @return bool
     */
    public function save(array $new_attributes = array(), $forceSave = false)
    {
        $options = array();

        if (array_key_exists('touch', $new_attributes))
        {
            $options['touch'] = $new_attributes['touch'];
            $new_attributes = array_except($new_attributes, array('touch'));
        }

        $this->fill($new_attributes);

        if (! $forceSave)
        {
            // If the validation failed, return false
            if (! $this->valid && ! $this->validate())
                return false;
        }

        // Purge unnecessary fields
        $this->purgeUnneeded();

        // Auto hash passwords
        $this->autoHash();

        $this->saved = true;

        return parent::save($options);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param  string $related
     * @param  string $foreignKey
     * @param  string $otherKey
     * @param  string $relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relatinoships.
        if (is_null($relation))
        {
            list(, $caller, $backtrace) = debug_backtrace(false);

            if ($backtrace['function'] == 'callRelationships')
            {
                $relation = $backtrace['args'][0];
            }
            else
            {
                $relation = $caller['function'];
            }
        }

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey))
        {
            $foreignKey = snake_case($relation) . '_id';
        }

        $instance = new $related;

        // Once we have the foreign key names, we'll just create a new Eloquent query
        // for the related models and returns the relationship instance which will
        // actually be responsible for retrieving and hydrating every relations.
        $query = $instance->newQuery();

        $otherKey = $otherKey ? : $instance->getKeyName();

        return new BelongsTo($query, $this, $foreignKey, $otherKey, $relation);
    }

    /**
     * Define an polymorphic, inverse one-to-one or many relationship.
     *
     * @param  string $name
     * @param  string $type
     * @param  string $id
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function morphTo($name = null, $type = null, $id = null)
    {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
        if (is_null($name))
        {
            list(, $caller, $backtrace) = debug_backtrace(false);

            if ($backtrace['function'] == 'callRelationships')
            {
                // If called using Magniloquent $relationships, we need to go back
                // one extra callback in the backtrace
                $name = snake_case($backtrace['args'][0]);
            }
            else
            {
                $name = snake_case($caller['function']);
            }
        }

        // Next we will guess the type and ID if necessary. The type and IDs may also
        // be passed into the function so that the developers may manually specify
        // them on the relations. Otherwise, we will just make a great estimate.
        list($type, $id) = $this->getMorphs($name, $type, $id);

        $class = $this->$type;

        return $this->belongsTo($class, $id);
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if ($this->valid && $key != $this->getKeyName() && ! in_array($key, $this->getDates()))
            $this->valid = false;

        return parent::setAttribute($key, $value);
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

        if ($validation->passes())
        {
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
     * Will call a relationship method as defined by the $relationships variable
     * on the class.  $relationships is an array of arrays in the following form:
     * array('hasOne', 'model', 'foreignKey')
     * array('hasMany', 'model', 'foreignKey')
     * array('belongsTo', 'model', 'foreignKey')
     * array('belongsToMany', 'model', 'table_name', 'this_id', 'other_id')
     * array('morphTo')
     * array('morphMany', 'model', 'relation_name')
     *
     * @param $method
     *
     * @return mixed
     */
    protected function callRelationships($method)
    {
        $relation_params = static::$relationships[$method];

        $relation_type = array_shift($relation_params);

        return call_user_func_array(array($this, $relation_type), $relation_params);
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

        if ($this->exists)
            $merged = array_merge_recursive($rules['save'], $rules['update']);
        else
            $merged = array_merge_recursive($rules['save'], $rules['create']);

        foreach ($merged as $field => $rules)
        {
            if (is_array($rules))
                $output[$field] = implode("|", $rules);
            else
                $output[$field] = $rules;
        }

        $this->mergedRules = $output;
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
        foreach ($this->attributes as $key => $value)
        {
            if (! Str::endsWith($key, '_confirmation') && ! Str::startsWith($key, '_') && ! in_array($key, static::$purgeable))
                $clean[$key] = $value;
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
        if (isset($this->attributes['password']))
        {
            if ($this->attributes['password'] != $this->getOriginal('password'))
                $this->attributes['password'] = Hash::make($this->attributes['password']);
        }
    }

}
