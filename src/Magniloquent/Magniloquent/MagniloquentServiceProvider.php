<?php namespace Magniloquent\Magniloquent;

use Illuminate\Support\ServiceProvider;

class MagniloquentServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

/**
   * Bootstrap the application events.
   *
   * @return void
   */
  public function boot()
  {
    $this->package('magniloquent/magniloquent');
  }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
  public function register()
  {

  }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('magniloquent');
	}

}