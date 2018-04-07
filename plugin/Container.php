<?php

namespace GeminiLabs\FlarumBridge;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionParameter;

abstract class Container
{
	/**
	 * The current globally available container (if any).
	 * @var static
	 */
	protected static $instance;

	/**
	 * The container's bound services
	 * @var array
	 */
	protected $services = [];

	/**
	 * Load a globally available instance of the container.
	 * @return static
	 */
	public static function load()
	{
		if( empty( static::$instance )) {
			static::$instance = new static;
		}
		return static::$instance;
	}

	/**
	 * The Application entry point
	 * @return void
	 */
	abstract public function init();

	/**
	 * Bind a service to the container.
	 * @param string $alias
	 * @param mixed $concrete
	 * @return mixed
	 */
	public function bind( $alias, $concrete )
	{
		$this->services[$alias] = $concrete;
	}

	/**
	 * Resolve the given type from the container and allow unbound aliases that omit the
	 * root namespace. i.e. 'Controller' translates to 'GeminiLabs\Package\Controller'
	 * @param mixed $abstract
	 * @return mixed
	 */
	public function make( $abstract )
	{
		if( !isset( $this->services[$abstract] )) {
			$abstract = $this->addNamespace( $abstract );
		}
		if( isset( $this->services[$abstract] )) {
			$abstract = $this->services[$abstract];
		}
		if( is_callable( $abstract )) {
			return call_user_func_array( $abstract, [$this] );
		}
		if( is_object( $abstract )) {
			return $abstract;
		}
		return $this->resolve( $abstract );
	}

	/**
	 * Register a shared binding in the container.
	 * @param string $abstract
	 * @param callable|string|null $concrete
	 * @return void
	 */
	public function singleton( $abstract, $concrete )
	{
		$this->bind( $abstract, $this->make( $concrete ));
	}

	/**
	 * Prefix the current namespace to the abstract if absent
	 * @param string $abstract
	 * @return string
	 */
	protected function addNamespace( $abstract )
	{
		if( strpos( $abstract, __NAMESPACE__ ) === false && !class_exists( $abstract )) {
			$abstract = __NAMESPACE__.'\\'.$abstract;
		}
		return $abstract;
	}

	/**
	 * Throw an exception that the concrete is not instantiable.
	 * @param string $concrete
	 * @throws Exception
	 */
	protected function notInstantiable( $concrete )
	{
		throw new Exception( 'Target ['.$concrete.'] is not instantiable.' );
	}

	/**
	 * Resolve a class based dependency from the container.
	 * @param mixed $concrete
	 * @return mixed
	 * @throws Exception
	 */
	protected function resolve( $concrete )
	{
		if( $concrete instanceof Closure ) {
			return $concrete( $this );
		}
		$reflector = new ReflectionClass( $concrete );
		if( !$reflector->isInstantiable() ) {
			return $this->notInstantiable( $concrete );
		}
		$constructor = $reflector->getConstructor();
		if( empty( $constructor )) {
			return new $concrete;
		}
		return $reflector->newInstanceArgs(
			$this->resolveDependencies( $constructor->getParameters() )
		);
	}

	/**
	 * Resolve a class based dependency from the container.
	 * @return mixed
	 * @throws Exception
	 */
	protected function resolveClass( ReflectionParameter $parameter )
	{
		try {
			return $this->make( $parameter->getClass()->name );
		}
		catch( Exception $error ) {
			if( $parameter->isOptional() ) {
				return $parameter->getDefaultValue();
			}
			throw $error;
		}
	}

	/**
	 * Resolve all of the dependencies from the ReflectionParameters.
	 * @return array
	 */
	protected function resolveDependencies( array $dependencies )
	{
		$results = [];
		foreach( $dependencies as $dependency ) {
			$results[] = !is_null( $class = $dependency->getClass() )
				? $this->resolveClass( $dependency )
				: $this->resolveDependency( $dependency );
		}
		return $results;
	}

	/**
	 * Resolve a single ReflectionParameter dependency.
	 * @return array|null
	 */
	protected function resolveDependency( ReflectionParameter $parameter )
	{
		if( $parameter->isArray() && $parameter->isDefaultValueAvailable() ) {
			return $parameter->getDefaultValue();
		}
		return null;
	}
}
