<?php namespace Magniloquent\Magniloquent;

use Closure;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Magniloquent extends Model {

  private $rules = array();

  private $validationErrors;

  private $saved = false;

  protected $customMessages = array();

  public function __construct($attributes = array())
  {
    parent::__construct($attributes);
    $this->validationErrors = new MessageBag;
  }

  /**
   * Save
   *
   * Prepare before the Model is actually saved
   */
  public function save(array $options = array())
  {
    if(!empty($options)) $this->hydrate($options);

    // If the validation failed, return false
    if(!$this->validate($this->attributes)) return false;

    // Purge Redundant fields
    $this->attributes = $this->purgeRedundant($this->attributes);

    // Auto hash passwords
    $this->attributes = $this->autoHash();

    $this->saved = true;

    return $this->performSave($options);
  }

  private function hydrate($attributes)
  {
    $this->fill($attributes);
  }

  private function performSave(array $options = array())
  {
    return parent::save($options);
  }

  /**
   * Merge Rules
   *
   * Merge the rules arrays to form one set of rules
   */
  private function mergeRules()
  {
    $rules = static::$rules;
    $output = array();

    if($this->exists){
      $merged = array_merge_recursive($rules['save'], $rules['update']);
    }else{
      $merged = array_merge_recursive($rules['save'], $rules['create']);
    }
    foreach($merged as $field => $rules){
      if(is_array($rules)){
        $output[$field] = implode("|", $rules);
      }else{
        $output[$field] = $rules;
      }
    }
    return $output;
  }

  /**
   * Validate
   *
   * Validate input against merged rules
   */
  public function validate($attributes)
  {
    // Merge the rules arrays into one array
    $this->rules = $this->mergeRules();

    $validation = Validator::make($attributes, $this->rules, $this->customMessages);

    if($validation->passes()) return true;

    $this->validationErrors = $validation->messages();

    return false;
  }

  public function errors() {
    return $this->validationErrors;
  }

  public function isSaved()
  {
    return $this->saved;
  }

  /**
   * Purge Redundant fields
   *
   * Get rid of '_confirmation' fields
   */
  private function purgeRedundant($attributes)
  {
    $clean = array();
    foreach($attributes as $key => $value){
      if(!Str::endsWith( $key, '_confirmation')){
        $clean[$key] = $value;
      }
    }
    return $clean;
  }

  /**
   * Auto hash
   *
   * Auto hash passwords
   */
  private function autoHash()
  {
    if(isset($this->attributes['password']))
    {
      if($this->attributes['password'] != $this->getOriginal('password')){
        $this->attributes['password'] = Hash::make($this->attributes['password']);
      }
    }
    return $this->attributes;
  }

}