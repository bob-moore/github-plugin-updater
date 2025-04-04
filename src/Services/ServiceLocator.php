<?php
/**
 * Service Locator
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace MarkedEffect\GHPluginUpdater\Services;

use MarkedEffect\GHPluginUpdater\Core\Interfaces;

use DI\Container,
	DI\ContainerBuilder,
	DI\DependencyException,
	DI\NotFoundException,
	DI\Definition\Reference,
	DI\Definition\StringDefinition,
	DI\Definition\ValueDefinition,
	DI\Definition\Helper;

/**
 * Builder for Service Containers
 *
 * @subpackage DI
 */
class ServiceLocator
{
	/**
	 * Cached PHP\DI Service Container.
	 *
	 * @var Container
	 * @see https://php-di.org/doc/container.html
	 */
	private static ?Container $cached_container = null;
	/**
	 * PHP\DI Service Container.
	 *
	 * @var Container
	 * @see https://php-di.org/doc/container.html
	 */
	private Container $container;
	/**
	 * PHP\DI Container Builder.
	 *
	 * @var ContainerBuilder
	 * @see https://php-di.org/doc/container-configuration.html
	 */
	private ContainerBuilder $container_builder;
	/**
	 * Array of service definitions.
	 *
	 * @var array<string, mixed>
	 */
	private array $service_definitions = [];

	/**
	 * Init a new service locator when not serving a cached container.
	 *
	 * @return void
	 */
	public function init(): void
	{
		$this->container_builder = new ContainerBuilder();
		$this->container_builder->useAutowiring( true );
		$this->container_builder->useAttributes( true );
	}
	public function hasContainer(): bool
	{
		return isset( self::$cached_container );
	}
	/**
	 * Cache the current container
	 *
	 * @return void
	 */
	public function save(): void
	{
		self::$cached_container = $this->container;
	}
	/**
	 * Restore the cached container
	 *
	 * @return bool
	 */
	public function restoreContainer(): bool
	{
		if ( null === self::$cached_container ) {
			return false;
		}

		$this->container = self::$cached_container;

		return true;
	}
	/**
	 * Clear the container
	 *
	 * @return void
	 */
	public function clearContainer(): void
	{
		self::$cached_container = null;
	}
	/**
	 * Add an array of service definitions to the container.
	 *
	 * @param array<string, mixed> $definitions : array of service definitions.
	 *
	 * @return void
	 */
	public function addDefinitions( array $definitions ): void
	{
		foreach ( $definitions as $key => $definition ) {
			$this->addDefinition( $key, $definition );
		}
	}
	/**
	 * Build the container
	 *
	 * Adds collected service definitions to the container builder, and compiles
	 * the container, setting it to the private container property.
	 *
	 * @return void
	 */
	public function build(): void
	{
		foreach ( $this->service_definitions as $definition ) {
			$this->container_builder->addDefinitions( $definition );
		}
		$this->container = $this->container_builder->build();
	}
	/**
	 * Add a service definition to the collection of definitions.
	 *
	 * If the definition is an instance of the AutowireDefinitionHelper class, it
	 * attempts to set the package name and call the onMount method if the class implements it.
	 *
	 * @param string $service : name of the service.
	 * @param mixed  $definition : service definition.
	 *
	 * @return void
	 */
	public function addDefinition( string $service, mixed $definition ): void
	{
		$extended_definitions = [];
		if ( is_object( $definition ) && is_a( $definition, Helper\AutowireDefinitionHelper::class ) ) {
			$class_name = $this->getAutoWiredClassName( $definition, $service );

			if ( is_a( $class_name, Interfaces\Mountable::class, true ) ) {
				$definition->method( 'onMount' );
			}

			if ( is_a( $class_name, Interfaces\Controller::class, true )
				&& empty( array_column( $this->service_definitions, $service ) ) ) {
				$extended_definitions = $class_name::getServiceDefinitions();
			}
		}
		$this->service_definitions[] = [ $service => $definition ];

		if ( ! empty( $extended_definitions ) ) {
			$this->addDefinitions( $extended_definitions );
		}
	}
	/**
	 * Get the class name of an auto wired definition
	 *
	 * @param Helper\AutowireDefinitionHelper $definition : service definition to check.
	 * @param string                          $service : the service name.
	 *
	 * @return string
	 */
	protected function getAutoWiredClassName( Helper\AutowireDefinitionHelper $definition, string $service ): string
	{
		$definition_object = $definition->getDefinition( $service );

		$class_name = $definition_object->getClassName();

		return $class_name;
	}
	/**
	 * Locate a specific service
	 *
	 * Use primarily by 3rd party interactions to remove actions/filters
	 *
	 * @param string $service : name of service to locate.
	 *
	 * @return mixed
	 */
	public function getService( string $service ): mixed
	{
		if ( ! isset( $this->container ) ) {
			return new \WP_Error( 'no_container_found', 'No container found' );
		}
		try {
			return $this->container->get( $service );
		} catch ( DependencyException | NotFoundException $e ) {
			return new \WP_Error( $e->getMessage() );
		}
	}
	/**
	 * Mount a service
	 *
	 * Wrapper for getService method. Used to mount a service from the container
	 * without returning it.
	 *
	 * @param string $service : name of service to mount.
	 *
	 * @return void
	 */
	public function mountService( string $service ): void
	{
		$this->getService( $service );
	}
	/**
	 * Resolve a new instance of a service
	 *
	 * @param string       $service : name of the service to make.
	 * @param array<mixed> $args : array of arguments to pass into the service
	 *                             constructor.
	 *
	 * @return \WP_Error|null
	 */
	public function makeService( string $service, array $args = [] ): \WP_Error|null
	{
		if ( ! isset( $this->container ) ) {
			return new \WP_Error( 'no_container_found', 'No container found' );
		}

		return $this->container->make( $service, $args );
	}
	/**
	 * Set a service in the container.
	 *
	 * @param string $service : service name.
	 * @param mixed  $value : service value.
	 *
	 * @return void
	 */
	public function setService( string $service, $value ): void
	{
		$this->container->set( $service, $value );
	}
	/**
	 * Locate a specific service
	 *
	 * @param string $service : name of service to locate.
	 *
	 * @return mixed
	 */
	public static function locateService( string $service ): mixed
	{
		try {
			$instance = new self();

			$instance->restoreContainer();

			$resolved = $instance->getService( $service );

			if ( is_wp_error( $resolved ) ) {
				$resolved = $instance->getService( str_replace( '\\Services', '', __NAMESPACE__ ) . '\\' . $service );
			}

			return $resolved;
		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getMessage() );
		}
	}
	/**
	 * Wrapper for parent auto wire function. Only used for simplicity
	 *
	 * @param string $class_name : name of service to auto wire.
	 *
	 * @return Helper\AutowireDefinitionHelper
	 */
	public static function autowire( string $class_name = null ): Helper\AutowireDefinitionHelper
	{
		return \DI\autowire( $class_name );
	}
	/**
	 * Helper for defining an object.
	 *
	 * @param string|null $class_name Class name of the object.
	 *                               If null, the name of the entry (in the container) will be used as class name.
	 */
	public static function create( string $class_name = null ): Helper\DefinitionHelper
	{
		return \DI\create( $class_name );
	}
	/**
	 * Wrapper for parent get function. Only used for simplicity
	 *
	 * @param string $class_name : name of service to retrieve.
	 *
	 * @return Reference;
	 */
	public static function get( string $class_name ): Reference
	{
		return \DI\get( $class_name );
	}

	/**
	 * Helper for defining a container entry using a factory function/callable.
	 *
	 * @param callable|array<mixed>|string $factory : The factory is a callable that takes the container as parameter
	 *                                                and returns the value to register in the container.
	 */
	public static function factory( $factory ): Helper\DefinitionHelper
	{
		return \DI\factory( $factory );
	}
	/**
	 * Decorate the previous definition using a callable.
	 *
	 * Example:
	 *
	 *     'foo' => decorate(function ($foo, $container) {
	 *         return new CachedFoo($foo, $container->get('cache'));
	 *     })
	 *
	 * @param callable|array<mixed>|string $decorator : The callable takes the decorated object as first parameter and
	 *                                                  the container as second.
	 */
	public static function decorate( $decorator ): Helper\DefinitionHelper
	{
		return \DI\decorate( $decorator );
	}
	/**
	 * Undocumented function
	 *
	 * @param string $expression : A string expression. Use the `{}` placeholders to reference other container entries.
	 *
	 * @return StringDefinition
	 */
	public static function string( string $expression ): StringDefinition
	{
		return \DI\string( $expression );
	}
	/**
	 * Helper for defining a value.
	 *
	 * @param mixed $value : value definition.
	 *
	 * @return ValueDefinition
	 */
	public static function value( mixed $value ): ValueDefinition
	{
		return \DI\value( $value );
	}
}
