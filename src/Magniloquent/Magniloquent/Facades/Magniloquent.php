<?php namespace Magniloquent\Magniloquent\Facades;

use Illuminate\Support\Facades\Facade;

class Magniloquent extends Facade {

  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor() { return 'magniloquent'; }

}